<?php

namespace App\Http\Controllers;

use App\Models\ProjectConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectConfigurationController extends Controller
{
    /**
     * Display project configurations.
     */
    public function index(Request $request)
    {
        $query = ProjectConfiguration::with([
            'project',
            'division',
            'subDivision',
            'tower',
            'level',
            'activity',
            'subActivity',
            'apartment.typology',
            'typology',
            'uom',
        ]);

        $filters = [
            'project_id',
            'division_id',
            'sub_division_id',
            'tower_id',
            'level_id',
            'activity_id',
            'sub_activity_id',
            'apartment_id',
        ];

        foreach ($filters as $field) {
            if ($request->filled($field)) {
                $query->where(
                    $field,
                    $request->integer($field)
                );
            }
        }

        return response()->json(
            $query
                ->orderBy('priority')
                ->orderBy('id')
                ->paginate(
                    $request->integer('per_page', 100)
                )
        );
    }

    /**
     * Update a single configuration row.
     */
    public function update(
        Request $request,
        ProjectConfiguration $configuration
    ) {
        $data = $request->validate([
            'quantity' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'priority' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'uom_id' => [
                'sometimes',
                'integer',
                'exists:uoms,id',
            ],

            'typology_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:apartment_typologies,id',
            ],
        ]);

        if (!empty($data)) {
            $configuration->update($data);
        }

        return response()->json([
            'message' => 'Configuration updated successfully.',
            'data' => $configuration
                ->fresh()
                ->load([
                    'project',
                    'division',
                    'subDivision',
                    'tower',
                    'level',
                    'activity',
                    'subActivity',
                    'apartment.typology',
                    'typology',
                    'uom',
                ]),
        ]);
    }

    /**
     * Bulk update configuration rows.
     *
     * Supports partial updates.
     */
    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'rows' => [
                'required',
                'array',
                'min:1',
            ],

            'rows.*.id' => [
                'required',
                'integer',
                'exists:project_configurations,id',
            ],

            'rows.*.quantity' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'rows.*.priority' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            /*
             * UOM must be a real record from the uoms table.
             * React sends the actual database primary key.
             */
            'rows.*.uom_id' => [
                'sometimes',
                'integer',
                'exists:uoms,id',
            ],

            'rows.*.typology_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:apartment_typologies,id',
            ],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['rows'] as $row) {
                $configuration = ProjectConfiguration::findOrFail(
                    $row['id']
                );

                $updates = [];

                foreach ([
                    'quantity',
                    'priority',
                    'uom_id',
                    'typology_id',
                ] as $field) {
                    if (array_key_exists($field, $row)) {
                        $updates[$field] = $row[$field];
                    }
                }

                if (!empty($updates)) {
                    $configuration->update($updates);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Configuration saved successfully.',
        ]);
    }

    /**
     * Import configuration from CSV.
     *
     * UOM IDs are validated against the uoms table before updating.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240',
            ],
        ]);

        $handle = fopen(
            $request->file('file')->getRealPath(),
            'r'
        );

        if (!$handle) {
            return response()->json([
                'message' => 'Unable to read uploaded file.',
            ], 422);
        }

        $header = fgetcsv($handle);

        $requiredHeaders = [
            'id',
            'quantity',
            'priority',
            'uom_id',
            'typology_id',
        ];

        if (!$header) {
            fclose($handle);

            return response()->json([
                'message' => 'CSV file is empty.',
            ], 422);
        }

        // Remove UTF-8 BOM from the first header if present.
        if (isset($header[0])) {
            $header[0] = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $header[0]
            );
        }

        $missingHeaders = array_diff(
            $requiredHeaders,
            $header
        );

        if (!empty($missingHeaders)) {
            fclose($handle);

            return response()->json([
                'message' => 'Missing CSV headers.',
                'missing' => array_values($missingHeaders),
                'required' => $requiredHeaders,
            ], 422);
        }

        $count = 0;
        $errors = [];
        $lineNumber = 1;

        DB::transaction(function () use (
            $handle,
            $header,
            &$count,
            &$errors,
            &$lineNumber
        ) {
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;

                if (count($row) !== count($header)) {
                    $errors[] = [
                        'line' => $lineNumber,
                        'message' => 'Column count does not match CSV headers.',
                    ];
                    continue;
                }

                $data = array_combine(
                    $header,
                    $row
                );

                if (empty($data['id'])) {
                    continue;
                }

                $configuration = ProjectConfiguration::find(
                    (int) $data['id']
                );

                if (!$configuration) {
                    $errors[] = [
                        'line' => $lineNumber,
                        'id' => (int) $data['id'],
                        'message' => 'Project configuration was not found.',
                    ];
                    continue;
                }

                $uomId = (int) $data['uom_id'];

                if (
                    $uomId <= 0 ||
                    !DB::table('uoms')->where('id', $uomId)->exists()
                ) {
                    $errors[] = [
                        'line' => $lineNumber,
                        'id' => (int) $data['id'],
                        'uom_id' => $data['uom_id'],
                        'message' => 'The selected UOM does not exist.',
                    ];
                    continue;
                }

                $typologyId = null;

                if ($data['typology_id'] !== '') {
                    $typologyId = (int) $data['typology_id'];

                    if (
                        $typologyId <= 0 ||
                        !DB::table('apartment_typologies')
                            ->where('id', $typologyId)
                            ->exists()
                    ) {
                        $errors[] = [
                            'line' => $lineNumber,
                            'id' => (int) $data['id'],
                            'typology_id' => $data['typology_id'],
                            'message' => 'The selected typology does not exist.',
                        ];
                        continue;
                    }
                }

                $quantity = (float) $data['quantity'];
                $priority = (int) $data['priority'];

                if ($quantity < 0) {
                    $errors[] = [
                        'line' => $lineNumber,
                        'id' => (int) $data['id'],
                        'message' => 'Quantity cannot be negative.',
                    ];
                    continue;
                }

                if ($priority < 1) {
                    $errors[] = [
                        'line' => $lineNumber,
                        'id' => (int) $data['id'],
                        'message' => 'Priority must be at least 1.',
                    ];
                    continue;
                }

                $configuration->update([
                    'quantity' => $quantity,
                    'priority' => $priority,
                    'uom_id' => $uomId,
                    'typology_id' => $typologyId,
                ]);

                $count++;
            }
        });

        fclose($handle);

        return response()->json([
            'success' => empty($errors),
            'message' => empty($errors)
                ? "Imported {$count} rows successfully."
                : "Imported {$count} rows with validation errors.",
            'count' => $count,
            'errors' => $errors,
        ], empty($errors) ? 200 : 422);
    }

    /**
     * Export project configuration.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = ProjectConfiguration::with([
            'activity',
            'subActivity',
            'apartment',
            'uom',
        ]);

        if ($request->filled('project_id')) {
            $query->where(
                'project_id',
                $request->integer('project_id')
            );
        }

        $rows = $query
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        $response = new StreamedResponse(
            function () use ($rows) {
                $output = fopen(
                    'php://output',
                    'w'
                );

                fputcsv($output, [
                    'id',
                    'activity_code',
                    'activity_name',
                    'sub_activity_code',
                    'sub_activity_name',
                    'apartment',
                    'quantity',
                    'uom',
                    'priority',
                    'uom_id',
                    'typology_id',
                ]);

                foreach ($rows as $row) {
                    fputcsv($output, [
                        $row->id,
                        $row->activity?->code,
                        $row->activity?->name,
                        $row->subActivity?->code,
                        $row->subActivity?->name,
                        $row->apartment?->code,
                        $row->quantity,
                        $row->uom?->name,
                        $row->priority,
                        $row->uom_id,
                        $row->typology_id,
                    ]);
                }

                fclose($output);
            }
        );

        $response->headers->set(
            'Content-Type',
            'text/csv; charset=UTF-8'
        );

        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="project-configuration.csv"'
        );

        return $response;
    }
}
