<?php

namespace Database\Seeders;

use App\Models\Activity;

use App\Models\Apartment;

use App\Models\ApartmentTypology;

use App\Models\Division;

use App\Models\Level;

use App\Models\Permission;

use App\Models\Priority;

use App\Models\Project;

use App\Models\ProjectConfiguration;

use App\Models\Reason;

use App\Models\RolePermission;

use App\Models\SubActivity;

use App\Models\SubDivision;

use App\Models\Tower;

use App\Models\Uom;

use App\Models\User;

use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder

{

    public function run(): void

    {

        DB::transaction(function () {

            $this->seedPermissions();

            $this->seedUsers();

            $this->seedMasterData();

        });

    }

    private function seedPermissions(): void

    {

        $permissions = [

            ['name' => 'master.view', 'label' => 'View master data', 'group' => 'master'],

            ['name' => 'master.manage', 'label' => 'Manage master data', 'group' => 'master'],

            ['name' => 'configuration.view', 'label' => 'View project configuration', 'group' => 'configuration'],

            ['name' => 'configuration.edit', 'label' => 'Edit project configuration', 'group' => 'configuration'],

            ['name' => 'configuration.import', 'label' => 'Import configuration', 'group' => 'configuration'],

            ['name' => 'configuration.export', 'label' => 'Export configuration', 'group' => 'configuration'],

            ['name' => 'plan.view', 'label' => 'View daily plans', 'group' => 'plan'],

            ['name' => 'plan.create', 'label' => 'Create and save daily plans', 'group' => 'plan'],

            ['name' => 'plan.submit', 'label' => 'Submit daily plans', 'group' => 'plan'],

            ['name' => 'user.manage', 'label' => 'Manage users', 'group' => 'administration'],

            ['name' => 'role.manage', 'label' => 'Manage roles and permissions', 'group' => 'administration'],

        ];

        foreach ($permissions as $permission) {

            Permission::updateOrCreate(

                ['name' => $permission['name']],

                [

                    'label' => $permission['label'],

                    'group' => $permission['group'],

                ]

            );

        }

        $ids = Permission::whereIn(

            'name',

            collect($permissions)->pluck('name')->all()

        )->pluck('id', 'name');

        $roles = [
            'admin' => collect($permissions)->pluck('name')->all(),

            'project_manager' => [
                'master.view',
                'configuration.view',
                'configuration.edit',
                'configuration.import',
                'configuration.export',
                'plan.view',
                'plan.create',
                'plan.submit',
            ],

            'engineer' => [
                'master.view',
                'plan.view',
                'plan.create',
                'plan.submit',
            ],

            'qs' => [
                'master.view',
                'configuration.view',
                'configuration.edit',
                'configuration.import',
                'configuration.export',
            ],

            'viewer' => [
                'master.view',
                'configuration.view',
                'configuration.export',
                'plan.view',
            ],
        ];

        foreach (array_keys($roles) as $role) {

            RolePermission::where('role', $role)->delete();

        }

        foreach ($roles as $role => $permissionsForRole) {

            foreach ($permissionsForRole as $permissionName) {

                if (!isset($ids[$permissionName])) {

                    throw new \RuntimeException(

                        "Permission '{$permissionName}' does not exist."

                    );

                }

                RolePermission::create([

                    'role' => $role,

                    'permission_id' => $ids[$permissionName],

                ]);

            }

        }

    }

    private function seedUsers(): void

    {

        $users = [

            ['name' => 'Administrator', 'email' => 'admin@example.com', 'role' => 'admin'],

            ['name' => 'Project Manager', 'email' => 'manager@example.com', 'role' => 'project_manager'],

            ['name' => 'Site Engineer', 'email' => 'engineer@example.com', 'role' => 'engineer'],

            ['name' => 'QS Team', 'email' => 'qs@example.com', 'role' => 'qs'],

            ['name' => 'Read Only User', 'email' => 'viewer@example.com', 'role' => 'viewer'],

        ];

        foreach ($users as $user) {

            User::updateOrCreate(

                ['email' => $user['email']],

                [

                    'name' => $user['name'],

                    'password' => Hash::make('password'),

                    'role' => $user['role'],

                ]

            );

        }

    }

    private function seedMasterData(): void

    {

        /*

        |--------------------------------------------------------------------------

        | PROJECT HIERARCHY

        |--------------------------------------------------------------------------

        */

        $project = Project::updateOrCreate(

            ['code' => 'SOBHA'],

            [

                'name' => 'Sobha Project',

                'status' => 'active',

            ]

        );

        $division = Division::updateOrCreate(

            [

                'project_id' => $project->id,

                'code' => 'TA',

            ],

            [

                'name' => 'Tower A',

            ]

        );

        $subDivision = SubDivision::updateOrCreate(

            [

                'division_id' => $division->id,

                'code' => 'CIVIL',

            ],

            [

                'name' => 'Civil',

            ]

        );

        $tower = Tower::updateOrCreate(

            [

                'sub_division_id' => $subDivision->id,

                'code' => 'W01',

            ],

            [

                'name' => 'W01',

            ]

        );

        $levels = [];

        foreach (range(1, 10) as $number) {

            $levels[$number] = Level::updateOrCreate(

                [

                    'tower_id' => $tower->id,

                    'code' => 'L' . $number,

                ],

                [

                    'name' => 'Level ' . $number,

                    'sort_order' => $number,

                ]

            );

        }

        /*

        |--------------------------------------------------------------------------

        | UOM

        |--------------------------------------------------------------------------

        */

        $uoms = [];

        foreach ([

            ['code' => 'M2', 'name' => 'm²'],

            ['code' => 'M3', 'name' => 'm³'],

            ['code' => 'KG', 'name' => 'Kg'],

            ['code' => 'NOS', 'name' => 'Nos'],

        ] as $uom) {

            $uoms[$uom['code']] = Uom::updateOrCreate(

                ['code' => $uom['code']],

                ['name' => $uom['name']]

            );

        }

        /*

        |--------------------------------------------------------------------------

        | PRIORITY

        |--------------------------------------------------------------------------

        */

        if (class_exists(Priority::class)) {

            foreach (range(1, 10) as $value) {

                Priority::updateOrCreate(

                    ['value' => $value],

                    [

                        'label' => 'Priority ' . $value,

                        'is_active' => true,

                    ]

                );

            }

        }

        /*

        |--------------------------------------------------------------------------

        | APARTMENT TYPOLOGIES

        | These are the typologies visible in the reference table.

        |--------------------------------------------------------------------------

        */

        $typologies = [];

        foreach ([

            ['code' => '1BHK-A', 'name' => '1 BHK Type A'],

            ['code' => '2BHK-A', 'name' => '2 BHK Type A'],

            ['code' => '2BHK-B', 'name' => '2 BHK Type B'],

            ['code' => '2BHK-C', 'name' => '2 BHK Type C'],

            ['code' => '3BHK-A', 'name' => '3 BHK Type A'],

            ['code' => '3BHK-B', 'name' => '3 BHK Type B'],

        ] as $typology) {

            $typologies[$typology['code']] = ApartmentTypology::updateOrCreate(

                ['code' => $typology['code']],

                [

                    'name' => $typology['name'],

                ]

            );

        }

        /*

        |--------------------------------------------------------------------------

        | APARTMENTS

        |--------------------------------------------------------------------------

        |

        | The reference contains A601-A610 and one blank/placeholder row.

        | A--- is therefore seeded as an explicit placeholder apartment.

        |

        */

        $apartmentDefinitions = [

            ['code' => 'A601', 'name' => 'Apartment A601', 'typology' => '1BHK-A'],

            ['code' => 'A602', 'name' => 'Apartment A602', 'typology' => '2BHK-B'],

            ['code' => 'A603', 'name' => 'Apartment A603', 'typology' => '2BHK-B'],

            ['code' => 'A604', 'name' => 'Apartment A604', 'typology' => '2BHK-B'],

            ['code' => 'A605', 'name' => 'Apartment A605', 'typology' => '3BHK-A'],

            ['code' => 'A606', 'name' => 'Apartment A606', 'typology' => '3BHK-B'],

            ['code' => 'A607', 'name' => 'Apartment A607', 'typology' => '1BHK-A'],

            ['code' => 'A608', 'name' => 'Apartment A608', 'typology' => '2BHK-C'],

            ['code' => 'A609', 'name' => 'Apartment A609', 'typology' => '2BHK-C'],

            ['code' => 'A610', 'name' => 'Apartment A610', 'typology' => '3BHK-B'],

            ['code' => 'A---', 'name' => 'Unassigned Apartment', 'typology' => '1BHK-A'],

        ];

        $apartments = [];

        foreach ($apartmentDefinitions as $definition) {

            $apartments[$definition['code']] = Apartment::updateOrCreate(

                ['code' => $definition['code']],

                [

                    'name' => $definition['name'],

                    'typology_id' => $typologies[$definition['typology']]->id,

                ]

            );

        }

        /*

        |--------------------------------------------------------------------------

        | ACTIVITIES

        |--------------------------------------------------------------------------

        */

        $gypsumCeiling = Activity::updateOrCreate(

            ['code' => 'GC'],

            [

                'name' => 'Gypsum Ceiling',

                'start_date' => '2025-04-16',

                'finish_date' => '2025-04-21',

                'planned_working_days' => 550,

            ]

        );

        $floorTiling = Activity::updateOrCreate(

            ['code' => 'FT'],

            [

                'name' => 'Floor Tiling',

                'start_date' => '2025-04-18',

                'finish_date' => '2025-04-20',

                'planned_working_days' => 250,

            ]

        );

        /*

        |--------------------------------------------------------------------------

        | SUB-ACTIVITIES

        |--------------------------------------------------------------------------

        */

        $subActivities = [];

        $subActivityDefinitions = [

            [

                'activity' => $gypsumCeiling,

                'code' => 'WO-TA-L6-GC-001',

                'name' => 'Shadow Angle Fixing - Dry Area',

                'productivity' => 4,

                'start_date' => '2025-04-18',

                'finish_date' => '2025-04-20',

                'planned_working_days' => null,

            ],

            [

                'activity' => $gypsumCeiling,

                'code' => 'WO-TA-L6-GC-002',

                'name' => 'Gypsum Ceiling Boarding - Dry Area',

                'productivity' => 4,

                'start_date' => '2025-04-16',

                'finish_date' => '2025-04-21',

                'planned_working_days' => null,

            ],

            [

                'activity' => $gypsumCeiling,

                'code' => 'WO-TA-L6-GC-003',

                'name' => 'Joint Tapino And Majim-Dry Area',

                'productivity' => 4,

                'start_date' => '2025-04-16',

                'finish_date' => '2025-04-21',

                'planned_working_days' => null,

            ],

            [

                'activity' => $gypsumCeiling,

                'code' => 'WO-TA-L6-GC-004',

                'name' => 'Bulk Head Boarding - Framing',

                'productivity' => 4,

                'start_date' => '2025-04-16',

                'finish_date' => '2025-04-21',

                'planned_working_days' => null,

            ],

            [

                'activity' => $gypsumCeiling,

                'code' => 'WO-TA-L6-GC-005',

                'name' => 'Gypsum Ceiling Bldik ig',

                'productivity' => 4,

                'start_date' => '2025-04-16',

                'finish_date' => '2025-04-21',

                'planned_working_days' => null,

            ],

            [

                'activity' => $gypsumCeiling,

                'code' => 'WO-TA-L6-GC-006',

                'name' => 'Framiag-Gituco Wali Area',

                'productivity' => 4,

                'start_date' => '2025-04-16',

                'finish_date' => '2025-04-21',

                'planned_working_days' => null,

            ],

            [

                'activity' => $gypsumCeiling,

                'code' => 'WO-TA-L6-GC-007',

                'name' => 'Shadow Flundt Filling',

                'productivity' => 4,

                'start_date' => '2025-04-16',

                'finish_date' => '2025-04-21',

                'planned_working_days' => null,

            ],

            [

                'activity' => $floorTiling,

                'code' => 'FT-WALL',

                'name' => 'Wall Tiling',

                'productivity' => 4,

                'start_date' => '2025-04-18',

                'finish_date' => '2025-04-20',

                'planned_working_days' => null,

            ],

            [

                'activity' => $floorTiling,

                'code' => 'FT-DOOR',

                'name' => 'Door Side Tiling',

                'productivity' => 5,

                'start_date' => '2025-04-16',

                'finish_date' => '2025-04-19',

                'planned_working_days' => null,

            ],

        ];

        foreach ($subActivityDefinitions as $definition) {

            $subActivities[$definition['code']] = SubActivity::updateOrCreate(

                [

                    'activity_id' => $definition['activity']->id,

                    'code' => $definition['code'],

                ],

                [

                    'name' => $definition['name'],

                    'productivity' => $definition['productivity'],

                    'start_date' => $definition['start_date'] ?? null,

                    'finish_date' => $definition['finish_date'] ?? null,

                    'planned_working_days' => $definition['planned_working_days'] ?? null,

                ]

            );

        }

        /*

        |--------------------------------------------------------------------------

        | GAP REASONS

        |--------------------------------------------------------------------------

        */

        foreach ([

            ['code' => 'MATERIAL', 'name' => 'Material Not available'],

            ['code' => 'RAIN', 'name' => 'Due to Rain'],

            ['code' => 'LABOUR', 'name' => 'Unskilled Mason'],

            ['code' => 'EQUIPMENT', 'name' => 'Equipment Repairs/Breakdown'],

            ['code' => 'DRAWING', 'name' => 'Drawing not available'],

        ] as $reason) {

            Reason::updateOrCreate(

                ['code' => $reason['code']],

                [

                    'name' => $reason['name'],

                    'active' => true,

                ]

            );

        }

        /*

        |--------------------------------------------------------------------------

        | PROJECT CONFIGURATION

        |--------------------------------------------------------------------------

        |

        | These rows map the visible reference screenshot:

        |

        | A601  -> 400 Kg -> Priority 3

        | A602  -> 200 Kg -> Priority 2

        | A603  ->  50 Kg -> Priority 4

        | A604  -> 2000 m² -> Priority 5

        | A605  -> 500 Kg -> Priority 6

        | A606  -> 100 Kg -> Priority 7

        | A607  -> 300 Kg -> Priority 8

        | A608  -> 1800 m³ -> Priority 1

        | A609  -> 1500 m² -> Priority 10

        | A610  -> 300 Kg -> Priority 9

        | A---  -> blank   -> no priority

        |

        */

        $configurationRows = [

            [

                'sub_activity' => 'WO-TA-L6-GC-001',

                'apartment' => 'A601',

                'quantity' => 400,

                'uom' => 'KG',

                'priority' => 3,

            ],

            [

                'sub_activity' => 'WO-TA-L6-GC-002',

                'apartment' => 'A602',

                'quantity' => 200,

                'uom' => 'KG',

                'priority' => 2,

            ],

            [

                'sub_activity' => 'WO-TA-L6-GC-002',

                'apartment' => 'A603',

                'quantity' => 50,

                'uom' => 'KG',

                'priority' => 4,

            ],

            [

                'sub_activity' => 'WO-TA-L6-GC-003',

                'apartment' => 'A604',

                'quantity' => 2000,

                'uom' => 'M2',

                'priority' => 5,

            ],

            [

                'sub_activity' => 'WO-TA-L6-GC-004',

                'apartment' => 'A605',

                'quantity' => 500,

                'uom' => 'KG',

                'priority' => 6,

            ],

            [

                'sub_activity' => 'WO-TA-L6-GC-002',

                'apartment' => 'A606',

                'quantity' => 100,

                'uom' => 'KG',

                'priority' => 7,

            ],

            [

                'sub_activity' => 'WO-TA-L6-GC-005',

                'apartment' => 'A607',

                'quantity' => 300,

                'uom' => 'KG',

                'priority' => 8,

            ],

            [

                'sub_activity' => 'WO-TA-L6-GC-002',

                'apartment' => 'A608',

                'quantity' => 1800,

                'uom' => 'M3',

                'priority' => 1,

            ],

            [

                'sub_activity' => 'WO-TA-L6-GC-006',

                'apartment' => 'A609',

                'quantity' => 1500,

                'uom' => 'M2',

                'priority' => 10,

            ],

            [

                'sub_activity' => 'WO-TA-L6-GC-007',

                'apartment' => 'A610',

                'quantity' => 300,

                'uom' => 'KG',

                'priority' => 9,

            ],

            [

                'sub_activity' => 'WO-TA-L6-GC-001',

                // project_configurations.quantity and priority are NOT NULL; 0 is the DB placeholder for the visually blank row.

                'apartment' => 'A---',

                'quantity' => 0,

                'uom' => 'KG',

                'priority' => 0,

            ],

        ];

        $level6 = $levels[6];

        foreach ($configurationRows as $row) {

            $subActivity = $subActivities[$row['sub_activity']];

            $apartment = $apartments[$row['apartment']];

            ProjectConfiguration::updateOrCreate(

                [

                    'project_id' => $project->id,

                    'division_id' => $division->id,

                    'sub_division_id' => $subDivision->id,

                    'tower_id' => $tower->id,

                    'level_id' => $level6->id,

                    'activity_id' => $subActivity->activity_id,

                    'sub_activity_id' => $subActivity->id,

                    'apartment_id' => $apartment->id,

                ],

                [

                    'typology_id' => $apartment->typology_id,

                    'quantity' => $row['quantity'],

                    'uom_id' => $uoms[$row['uom']]->id,

                    'priority' => $row['priority'],

                ]

            );

        }

    }

}
