<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\ProjectConfiguration;
use App\Models\SubActivity;
use Illuminate\Support\Collection;

class PlanCalculationService
{
    /**
     * Build plan calculations grouped by sub-activity.
     */
    public function build(Plan $plan, array $rows): array
    {
        $grouped = collect($rows)
            ->filter(function ($row) {
                // Do not calculate unassigned apartment rows.
                return !empty($row['apartment_id']);
            })
            ->groupBy('sub_activity_id');

        $out = [];

        foreach ($grouped as $subId => $items) {
            $subActivity = SubActivity::query()
                ->with('activity')
                ->find($subId);

            if (!$subActivity) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Project Configuration
            |--------------------------------------------------------------------------
            */

            $configs = ProjectConfiguration::query()
                ->where('project_id', $plan->project_id)
                ->where('sub_activity_id', $subId)
                ->whereNotNull('apartment_id')
                ->whereHas('apartment', function ($query) {
                    $query->where('code', '!=', 'A---');
                })
                ->get();

            /*
            |--------------------------------------------------------------------------
            | Total Required Quantity
            |--------------------------------------------------------------------------
            */

            $total = (float) $configs->sum('quantity');

            /*
            |--------------------------------------------------------------------------
            | Productivity
            |--------------------------------------------------------------------------
            */

            $productivity = (float) (
                $subActivity->productivity ?? 0
            );

            /*
            |--------------------------------------------------------------------------
            | Working Days
            |--------------------------------------------------------------------------
            |
            | Priority:
            | 1. Activity planned working days
            | 2. Calculate from Activity start/finish
            | 3. Sub-activity planned working days
            | 4. Calculate from Sub-activity start/finish
            | 5. Submitted working_days
            | 6. Default 1
            |
            */

            $workingDays = $this->resolveWorkingDays(
                $subActivity,
                $items
            );

            /*
            |--------------------------------------------------------------------------
            | Target Quantity
            |--------------------------------------------------------------------------
            */

            $target = $workingDays > 0
                ? $total / $workingDays
                : 0;

            /*
            |--------------------------------------------------------------------------
            | Planned Quantity
            |--------------------------------------------------------------------------
            */

            $planned = collect($items)->sum(
                fn ($row) => (float) ($row['planned_quantity'] ?? 0)
            );

            /*
            |--------------------------------------------------------------------------
            | Shortfall
            |--------------------------------------------------------------------------
            */

            $shortfall = $planned - $target;

            /*
            |--------------------------------------------------------------------------
            | Previous Backlog
            |--------------------------------------------------------------------------
            */

            $previous = $this->previousBacklog(
                $plan,
                $subId
            );

            /*
            |--------------------------------------------------------------------------
            | Current Backlog
            |--------------------------------------------------------------------------
            */

            $backlog = $previous + $shortfall;

            /*
            |--------------------------------------------------------------------------
            | Planned Manpower
            |--------------------------------------------------------------------------
            */

            $submittedManpower = collect($items)->sum(
                fn ($row) => (int) ($row['planned_manpower'] ?? 0)
            );

            $calculatedManpower = $productivity > 0
                ? (int) ceil($planned / $productivity)
                : 0;

            /*
            | Use submitted manpower when supplied.
            | Otherwise calculate it automatically.
            */
            $manpower = $submittedManpower > 0
                ? $submittedManpower
                : $calculatedManpower;

            /*
            |--------------------------------------------------------------------------
            | Output
            |--------------------------------------------------------------------------
            */

            $out[] = [
                'activity_id' => $subActivity->activity_id,

                'sub_activity_id' => (int) $subId,

                'target_quantity' => round(
                    $target,
                    3
                ),

                'planned_quantity' => round(
                    $planned,
                    3
                ),

                'shortfall' => round(
                    $shortfall,
                    3
                ),

                'backlog' => round(
                    $backlog,
                    3
                ),

                'productivity' => $productivity,

                'planned_manpower' => $manpower,

                'previous_backlog' => round(
                    $previous,
                    3
                ),

                'total_quantity' => round(
                    $total,
                    3
                ),

                'working_days' => $workingDays,

                'activity_start_date' =>
                    $subActivity->activity?->start_date,

                'activity_finish_date' =>
                    $subActivity->activity?->finish_date,

                'sub_activity_start_date' =>
                    $subActivity->start_date,

                'sub_activity_finish_date' =>
                    $subActivity->finish_date,
            ];
        }

        return $out;
    }

    /**
     * Resolve working days from master data.
     */
    private function resolveWorkingDays(
        SubActivity $subActivity,
        Collection $items
    ): int {
        /*
        |--------------------------------------------------------------------------
        | 1. Activity planned working days
        |--------------------------------------------------------------------------
        */

        if (
            !empty($subActivity->activity?->planned_working_days)
            && (int) $subActivity->activity->planned_working_days > 0
        ) {
            return (int) $subActivity->activity->planned_working_days;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Activity start / finish
        |--------------------------------------------------------------------------
        */

        if (
            !empty($subActivity->activity?->start_date)
            && !empty($subActivity->activity?->finish_date)
        ) {
            $start = \Carbon\Carbon::parse(
                $subActivity->activity->start_date
            );

            $finish = \Carbon\Carbon::parse(
                $subActivity->activity->finish_date
            );

            $days = $start->diffInDays($finish) + 1;

            if ($days > 0) {
                return $days;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Sub-activity planned working days
        |--------------------------------------------------------------------------
        */

        if (
            !empty($subActivity->planned_working_days)
            && (int) $subActivity->planned_working_days > 0
        ) {
            return (int) $subActivity->planned_working_days;
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Sub-activity start / finish
        |--------------------------------------------------------------------------
        */

        if (
            !empty($subActivity->start_date)
            && !empty($subActivity->finish_date)
        ) {
            $start = \Carbon\Carbon::parse(
                $subActivity->start_date
            );

            $finish = \Carbon\Carbon::parse(
                $subActivity->finish_date
            );

            $days = $start->diffInDays($finish) + 1;

            if ($days > 0) {
                return $days;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Frontend supplied working days
        |--------------------------------------------------------------------------
        */

        $submittedDays = (int) (
            $items->first()['working_days'] ?? 0
        );

        if ($submittedDays > 0) {
            return $submittedDays;
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Safe fallback
        |--------------------------------------------------------------------------
        */

        return 1;
    }

    /**
     * Get backlog from the most recent submitted plan.
     */
    private function previousBacklog(
        Plan $plan,
        int|string $subId
    ): float {
        $previousPlan = Plan::query()
            ->where('project_id', $plan->project_id)
            ->where('plan_date', '<', $plan->plan_date)
            ->where('status', 'submitted')
            ->orderByDesc('plan_date')
            ->first();

        if (!$previousPlan) {
            return 0;
        }

        return (float) (
            $previousPlan
                ->details()
                ->where('sub_activity_id', $subId)
                ->value('backlog') ?? 0
        );
    }
}