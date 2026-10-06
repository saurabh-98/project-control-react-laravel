<?php



namespace App\Http\Controllers;



use App\Models\{

    Plan,



    ProjectConfiguration,

    SubActivity

};

use App\Services\PlanCalculationService;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;



class PlanController extends Controller

{

    /*

    |--------------------------------------------------------------------------

    | List Plans

    |--------------------------------------------------------------------------

    */



    public function index(Request $request)

    {

        $query = Plan::with('project')

            ->orderByDesc('plan_date');



        if ($request->filled('project_id')) {

            $query->where(

                'project_id',

                $request->integer('project_id')

            );

        }



        return response()->json(

            $query->paginate(30)

        );

    }



    /*

    |--------------------------------------------------------------------------

    | Show Plan

    |--------------------------------------------------------------------------

    */



    public function show(Plan $plan)

    {

        return response()->json(

            $this->payload($plan)

        );

    }



    /*

    |--------------------------------------------------------------------------

    | Build Daily Plan

    |--------------------------------------------------------------------------

    */



    public function build(

        Request $request,

        PlanCalculationService $calc

    ) {

        $data = $request->validate([

            'project_id' => [

                'required',

                'exists:projects,id',

            ],



            'plan_date' => [

                'required',

                'date',

            ],



            'rows' => [

                'array',

            ],

        ]);



        $plan = Plan::where('project_id', $data['project_id'])

            ->where('plan_date', $data['plan_date'])

            ->first();



        if (!$plan) {

            $plan = Plan::create([

                'project_id' => $data['project_id'],

                'plan_date' => $data['plan_date'],

                'status' => 'draft',

                'created_by' => $request->user()->id,

            ]);

        }



        return response()->json(

            $this->buildPayload(

                $plan,

                $request->input('filters', []),

                $calc

            )

        );

    }



    /*

    |--------------------------------------------------------------------------

    | Save Daily Plan

    |--------------------------------------------------------------------------

    */



    public function save(

        Request $request,

        PlanCalculationService $calc

    ) {

        $data = $request->validate([

            'project_id' => [

                'required',

                'exists:projects,id',

            ],



            'plan_date' => [

                'required',

                'date',

            ],



            'rows' => [

                'required',

                'array',

            ],



            'rows.*.sub_activity_id' => [

                'required',

                'exists:sub_activities,id',

            ],



            'rows.*.apartment_id' => [

                'required',

                'exists:apartments,id',

            ],



            'rows.*.planned_quantity' => [

                'required',

                'numeric',

                'min:0',

            ],



            'rows.*.completion_quantity' => [

                'nullable',

                'numeric',

                'min:0',

            ],



            'rows.*.planned_manpower' => [

                'nullable',

                'integer',

                'min:0',

            ],



            'rows.*.reason_id' => [

                'nullable',

                'exists:reasons,id',

            ],



            'rows.*.reason_text' => [

                'nullable',

                'string',

                'max:1000',

            ],



            'rows.*.working_days' => [

                'nullable',

                'integer',

                'min:1',

                'max:365',

            ],

        ]);



        return DB::transaction(function () use (

            $request,

            $data,

            $calc

        ) {

            /*

            |--------------------------------------------------------------------------

            | Find / Create Plan

            |--------------------------------------------------------------------------

            */



            $plan = Plan::firstOrCreate(

                [

                    'project_id' => $data['project_id'],

                    'plan_date' => $data['plan_date'],

                ],

                [

                    'status' => 'draft',

                    'created_by' => $request->user()->id,

                ]

            );



            /*

            |--------------------------------------------------------------------------

            | Submitted Plan Cannot Be Edited

            |--------------------------------------------------------------------------

            */



            if ($plan->status === 'submitted') {

                return response()->json([

                    'message' => 'Submitted plan cannot be edited.',

                ], 422);

            }



            /*

            |--------------------------------------------------------------------------

            | Remove Existing Details

            |--------------------------------------------------------------------------

            */



            $plan->details()

                ->each(function ($detail) {

                    $detail->apartments()->delete();

                    $detail->delete();

                });



            /*

            |--------------------------------------------------------------------------

            | Calculate Plan Groups

            |--------------------------------------------------------------------------

            */



            $groups = $calc->build(

                $plan,

                $data['rows']

            );



            /*

            |--------------------------------------------------------------------------

            | Create Plan Details

            |--------------------------------------------------------------------------

            */



            foreach ($groups as $group) {



                /*

                |--------------------------------------------------------------------------

                | IMPORTANT:

                | Resolve parent Activity from Sub Activity.

                |--------------------------------------------------------------------------

                */



                $subActivity = SubActivity::query()

                    ->with('activity')

                    ->findOrFail(

                        $group['sub_activity_id']

                    );



                /*

                |--------------------------------------------------------------------------

                | Make sure activity_id is always supplied.

                |--------------------------------------------------------------------------

                */



                $group['activity_id'] = $subActivity->activity_id;



                /*

                |--------------------------------------------------------------------------

                | Create Plan Detail

                |--------------------------------------------------------------------------

                */



                $detail = $plan->details()->create([

                    'activity_id' => $group['activity_id'],



                    'sub_activity_id' => $group['sub_activity_id'],



                    'target_quantity' => (float) (

                        $group['target_quantity'] ?? 0

                    ),



                    'planned_quantity' => (float) (

                        $group['planned_quantity'] ?? 0

                    ),



                    'shortfall' => (float) (

                        $group['shortfall'] ?? 0

                    ),



                    'backlog' => (float) (

                        $group['backlog'] ?? 0

                    ),



                    'productivity' => (float) (

                        $group['productivity'] ?? 0

                    ),



                    'planned_manpower' => (int) (

                        $group['planned_manpower'] ?? 0

                    ),

                ]);



                /*

                |--------------------------------------------------------------------------

                | Create Apartment Details

                |--------------------------------------------------------------------------

                */



                foreach (

                    array_filter(

                        $data['rows'],

                        fn ($row) =>

                            (int) $row['sub_activity_id']

                            === (int) $group['sub_activity_id']

                    ) as $row

                ) {



                    $config = ProjectConfiguration::query()

                        ->where(

                            'project_id',

                            $plan->project_id

                        )

                        ->where(

                            'sub_activity_id',

                            $row['sub_activity_id']

                        )

                        ->where(

                            'apartment_id',

                            $row['apartment_id']

                        )

                        ->first();



                    $detail->apartments()->create([

                        'apartment_id' => $row['apartment_id'],



                        'required_quantity' => $config?->quantity ?? 0,



                        'planned_quantity' => (float) ($row['planned_quantity'] ?? 0),



                        'completion_quantity' => (float) ($row['completion_quantity'] ?? 0),



                        'planned_manpower' => (int) ($row['planned_manpower'] ?? 0),



                        'reason_id' => (

                            $row['reason_id'] ?? null

                        ),



                        'reason_text' => (

                            $row['reason_text'] ?? null

                        ),

                    ]);

                }

            }



            /*

            |--------------------------------------------------------------------------

            | Return Saved Plan

            |--------------------------------------------------------------------------

            */



            return response()->json([

                'message' => 'Plan saved successfully.',



                'plan' => $this->payload(

                    $plan->fresh()

                ),

            ]);

        });

    }



    /*

    |--------------------------------------------------------------------------

    | Submit Plan

    |--------------------------------------------------------------------------

    */



    public function submit(

        Request $request,

        Plan $plan

    ) {

        if ($plan->status === 'submitted') {

            return response()->json([

                'message' => 'Plan already submitted.',

            ], 422);

        }



        if ($plan->details()->count() === 0) {

            return response()->json([

                'message' => 'Plan has no details.',

            ], 422);

        }



        $plan->update([

            'status' => 'submitted',

            'submitted_by' => $request->user()->id,

            'submitted_at' => now(),

        ]);



        return response()->json([

            'message' => 'Plan submitted successfully.',



            'plan' => $this->payload(

                $plan->fresh()

            ),

        ]);

    }



    /*

    |--------------------------------------------------------------------------

    | Plan Response Payload

    |--------------------------------------------------------------------------

    */



    private function payload(Plan $plan)

    {

        return $plan->load([

            'project',



            'details.activity',



            'details.subActivity',



            'details.apartments.apartment',



            'details.apartments.reason',

        ]);

    }



    /*

    |--------------------------------------------------------------------------

    | Build Project Control Payload

    |--------------------------------------------------------------------------

    */



    private function buildPayload(

        Plan $plan,

        array $filters,

        PlanCalculationService $calc

    ) {

        $query = ProjectConfiguration::with([

            'activity',

            'subActivity',

            'apartment.typology',

            'uom',

            'typology',

        ]);



        $query->where(

            'project_id',

            $plan->project_id

        );



        /*

        |--------------------------------------------------------------------------

        | Apply Filters

        |--------------------------------------------------------------------------

        */



        foreach (

            [

                'division_id',

                'sub_division_id',

                'tower_id',

                'level_id',

                'activity_id',

                'sub_activity_id',

                'apartment_id',

            ] as $filter

        ) {

            if (!empty($filters[$filter])) {

                $query->where(

                    $filter,

                    $filters[$filter]

                );

            }

        }



        /*

        |--------------------------------------------------------------------------

        | Configuration Ordering

        |--------------------------------------------------------------------------

        */



        $configs = $query

            ->orderBy('priority')

            ->get();



        /*

        |--------------------------------------------------------------------------

        | Working Days

        |--------------------------------------------------------------------------

        */



        $days = 8;



        /*

        |--------------------------------------------------------------------------

        | Group By Sub Activity

        |--------------------------------------------------------------------------

        */



        $groups = $configs->groupBy(

            'sub_activity_id'

        );



        $data = [];



        foreach ($groups as $subActivityId => $items) {



            $first = $items->first();



            if (!$first) {

                continue;

            }



            $total = $items->sum(

                'quantity'

            );



            $productivity = (float) (

                $first->subActivity?->productivity ?? 0

            );



            $target = $days > 0

                ? $total / $days

                : 0;



            /*

            |--------------------------------------------------------------------------

            | Apartment Rows

            |--------------------------------------------------------------------------

            */



            $apartments = $items

                ->map(function ($item) {



                    return [

                        'apartment' => $item->apartment,



                        'typology' =>

                            $item->typology

                            ?? $item->apartment?->typology,



                        'typology_id' =>

                            $item->typology_id,



                        'required_quantity' =>

                            (float) $item->quantity,



                        'priority' =>

                            $item->priority,



                        'uom' =>

                            $item->uom,



                        'planned_quantity' => 0,



                        'completion_quantity' => 0,



                        'planned_manpower' => 0,



                        'reason_id' => null,



                        'reason_text' => null,

                    ];

                })

                ->values();



            /*

            |--------------------------------------------------------------------------

            | Activity / Sub Activity Card

            |--------------------------------------------------------------------------

            */



            $data[] = [



                'activity' =>

                    $first->activity,



                'sub_activity' =>

                    $first->subActivity,



                'total_quantity' =>

                    (float) $total,



                'working_days' =>

                    $days,



                'target_quantity' =>

                    round($target, 3),



                'productivity' =>

                    $productivity,



                'planned_manpower' =>

                    0,



                'target_percentage' =>

                    $total > 0

                        ? round(

                            ($target / $total) * 100,

                            2

                        )

                        : 0,



                'apartments' =>

                    $apartments,

            ];

        }



        return [

            'plan' => $plan,



            'items' => $data,

        ];

    }

}