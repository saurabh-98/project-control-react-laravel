<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Division;
use App\Models\SubDivision;
use App\Models\Tower;
use App\Models\Level;
use App\Models\Activity;
use App\Models\SubActivity;
use App\Models\Apartment;
use App\Models\ApartmentTypology;
use App\Models\Uom;
use App\Models\Reason;
use App\Models\Priority;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Lookup APIs - Read Only
    |--------------------------------------------------------------------------
    */

    public function projects(): JsonResponse
    {
        return response()->json(
            Project::query()
                ->orderBy('name')
                ->get()
        );
    }

    public function allDivisions(): JsonResponse
    {
        return response()->json(
            Division::query()
                ->orderBy('name')
                ->get()
        );
    }

    public function allSubDivisions(): JsonResponse
    {
        return response()->json(
            SubDivision::query()
                ->orderBy('name')
                ->get()
        );
    }

    public function allTowers(): JsonResponse
    {
        return response()->json(
            Tower::query()
                ->orderBy('name')
                ->get()
        );
    }

    public function allLevels(): JsonResponse
    {
        return response()->json(
            Level::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
        );
    }

    public function activities(): JsonResponse
    {
        return response()->json(
            Activity::query()
                ->orderBy('name')
                ->get()
        );
    }

    public function subActivities(): JsonResponse
    {
        return response()->json(
            SubActivity::query()
                ->orderBy('name')
                ->get()
        );
    }

    public function apartments(): JsonResponse
    {
        return response()->json(
            Apartment::query()
                ->orderBy('code')
                ->get()
        );
    }

    public function typologies(): JsonResponse
    {
        return response()->json(
            ApartmentTypology::query()
                ->orderBy('name')
                ->get()
        );
    }

    public function uoms(): JsonResponse
    {
        return response()->json(
            Uom::query()
                ->orderBy('name')
                ->get()
        );
    }

    public function reasons(): JsonResponse
    {
        return response()->json(
            Reason::query()
                ->orderBy('name')
                ->get()
        );
    }

    public function priorities(): JsonResponse
    {
        return response()->json([
            'data' => Priority::query()
                ->where('is_active', true)
                ->orderBy('value')
                ->get(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Hierarchy APIs
    |--------------------------------------------------------------------------
    */

    public function divisions($project): JsonResponse
    {
        return response()->json(
            Division::query()
                ->where('project_id', $project)
                ->orderBy('name')
                ->get()
        );
    }

    public function subDivisions($division): JsonResponse
    {
        return response()->json(
            SubDivision::query()
                ->where('division_id', $division)
                ->orderBy('name')
                ->get()
        );
    }

    public function towers($subDivision): JsonResponse
    {
        return response()->json(
            Tower::query()
                ->where('sub_division_id', $subDivision)
                ->orderBy('name')
                ->get()
        );
    }

    public function levels($tower): JsonResponse
    {
        return response()->json(
            Level::query()
                ->where('tower_id', $tower)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Generic Admin Master Data CRUD
    |--------------------------------------------------------------------------
    */

    private function modelClass(string $resource): string
    {
        return match ($resource) {
            'projects' => Project::class,
            'divisions' => Division::class,
            'sub-divisions' => SubDivision::class,
            'towers' => Tower::class,
            'levels' => Level::class,
            'activities' => Activity::class,
            'sub-activities' => SubActivity::class,
            'apartments' => Apartment::class,
            'typologies' => ApartmentTypology::class,
            'uoms' => Uom::class,
            'reasons' => Reason::class,

            default => abort(
                404,
                'Invalid master resource.'
            ),
        };
    }


    /**
     * Admin master data listing.
     */
    public function index(string $resource): JsonResponse
    {
        $model = $this->modelClass($resource);

        $query = $model::query();

        if ($resource === 'projects') {
            $query
                ->orderBy('name');

        } elseif ($resource === 'levels') {
            $query
                ->orderBy('sort_order')
                ->orderBy('name');

        } elseif ($resource === 'apartments') {
            $query
                ->orderBy('code');

        } else {
            $query
                ->orderBy('name');
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }


    /**
     * Create master data.
     */
    public function store(
        Request $request,
        string $resource
    ): JsonResponse {
        $model = $this->modelClass($resource);

        $validated = $request->validate(
            $this->validationRules($resource)
        );

        $record = $model::create($validated);

        return response()->json([
            'message' => 'Master data created successfully.',
            'data' => $record,
        ], 201);
    }


    /**
     * Update master data.
     */
    public function update(
        Request $request,
        string $resource,
        int $id
    ): JsonResponse {
        $model = $this->modelClass($resource);

        $record = $model::findOrFail($id);

        $validated = $request->validate(
            $this->validationRules(
                $resource,
                true
            )
        );

        $record->update($validated);

        return response()->json([
            'message' => 'Master data updated successfully.',
            'data' => $record->fresh(),
        ]);
    }


    /**
     * Delete master data.
     */
    public function destroy(
        string $resource,
        int $id
    ): JsonResponse {
        $model = $this->modelClass($resource);

        $record = $model::findOrFail($id);

        $record->delete();

        return response()->json([
            'message' => 'Master data deleted successfully.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Priority CRUD
    |--------------------------------------------------------------------------
    */

    public function storePriority(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'value' => [
                'required',
                'integer',
                'min:1',
            ],

            'label' => [
                'required',
                'string',
                'max:255',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $priority = Priority::create($validated);

        return response()->json([
            'message' => 'Priority created successfully.',
            'data' => $priority,
        ], 201);
    }


    public function updatePriority(
        Request $request,
        int $id
    ): JsonResponse {
        $priority = Priority::findOrFail($id);

        $validated = $request->validate([
            'value' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'label' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $priority->update($validated);

        return response()->json([
            'message' => 'Priority updated successfully.',
            'data' => $priority->fresh(),
        ]);
    }


    public function destroyPriority(
        int $id
    ): JsonResponse {
        $priority = Priority::findOrFail($id);

        $priority->delete();

        return response()->json([
            'message' => 'Priority deleted successfully.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    private function validationRules(
        string $resource,
        bool $update = false
    ): array {
        $required = $update
            ? 'sometimes'
            : 'required';

        return match ($resource) {

            /*
            |--------------------------------------------------------------------------
            | Projects
            |--------------------------------------------------------------------------
            */
            'projects' => [
                'code' => [
                    $required,
                    'string',
                    'max:100',
                ],

                'name' => [
                    $required,
                    'string',
                    'max:255',
                ],

                'start_date' => [
                    'nullable',
                    'date',
                ],

                'end_date' => [
                    'nullable',
                    'date',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | Divisions
            |--------------------------------------------------------------------------
            */
            'divisions' => [
                'project_id' => [
                    $required,
                    'integer',
                    'exists:projects,id',
                ],

                'code' => [
                    $required,
                    'string',
                    'max:100',
                ],

                'name' => [
                    $required,
                    'string',
                    'max:255',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | Sub Divisions
            |--------------------------------------------------------------------------
            */
            'sub-divisions' => [
                'division_id' => [
                    $required,
                    'integer',
                    'exists:divisions,id',
                ],

                'code' => [
                    $required,
                    'string',
                    'max:100',
                ],

                'name' => [
                    $required,
                    'string',
                    'max:255',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | Towers
            |--------------------------------------------------------------------------
            */
            'towers' => [
                'sub_division_id' => [
                    $required,
                    'integer',
                    'exists:sub_divisions,id',
                ],

                'code' => [
                    $required,
                    'string',
                    'max:100',
                ],

                'name' => [
                    $required,
                    'string',
                    'max:255',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | Levels
            |--------------------------------------------------------------------------
            */
            'levels' => [
                'tower_id' => [
                    $required,
                    'integer',
                    'exists:towers,id',
                ],

                'code' => [
                    $required,
                    'string',
                    'max:100',
                ],

                'name' => [
                    $required,
                    'string',
                    'max:255',
                ],

                'sort_order' => [
                    'nullable',
                    'integer',
                    'min:0',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | Activities
            |--------------------------------------------------------------------------
            */
            'activities' => [
                'project_id' => [
                    $required,
                    'integer',
                    'exists:projects,id',
                ],

                'code' => [
                    $required,
                    'string',
                    'max:100',
                ],

                'name' => [
                    $required,
                    'string',
                    'max:255',
                ],

                'uom_id' => [
                    'nullable',
                    'integer',
                    'exists:uoms,id',
                ],

                'start_date' => [
                    'nullable',
                    'date',
                ],

                'finish_date' => [
                    'nullable',
                    'date',
                ],

                'planned_working_days' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | Sub Activities
            |--------------------------------------------------------------------------
            */
            'sub-activities' => [
                'activity_id' => [
                    $required,
                    'integer',
                    'exists:activities,id',
                ],

                'code' => [
                    $required,
                    'string',
                    'max:100',
                ],

                'name' => [
                    $required,
                    'string',
                    'max:255',
                ],

                'productivity' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'start_date' => [
                    'nullable',
                    'date',
                ],

                'finish_date' => [
                    'nullable',
                    'date',
                ],

                'planned_working_days' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | Apartments
            |--------------------------------------------------------------------------
            */
            'apartments' => [
                'project_id' => [
                    $required,
                    'integer',
                    'exists:projects,id',
                ],

                'tower_id' => [
                    $required,
                    'integer',
                    'exists:towers,id',
                ],

                'level_id' => [
                    $required,
                    'integer',
                    'exists:levels,id',
                ],

                'code' => [
                    $required,
                    'string',
                    'max:100',
                ],

                'name' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'typology_id' => [
                    'nullable',
                    'integer',
                    'exists:apartment_typologies,id',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | Apartment Typologies
            |--------------------------------------------------------------------------
            */
            'typologies' => [
                'code' => [
                    $required,
                    'string',
                    'max:100',
                ],

                'name' => [
                    $required,
                    'string',
                    'max:255',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | UOM
            |--------------------------------------------------------------------------
            */
            'uoms' => [
                'code' => [
                    $required,
                    'string',
                    'max:50',
                ],

                'name' => [
                    $required,
                    'string',
                    'max:255',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | Reasons
            |--------------------------------------------------------------------------
            */
            'reasons' => [
                'code' => [
                    $required,
                    'string',
                    'max:100',
                ],

                'name' => [
                    $required,
                    'string',
                    'max:255',
                ],

                'active' => [
                    'nullable',
                    'boolean',
                ],
            ],


            default => abort(
                404,
                'Invalid master resource.'
            ),
        };
    }
}