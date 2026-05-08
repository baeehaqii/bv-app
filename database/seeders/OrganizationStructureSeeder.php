<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Division;
use App\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrganizationStructureSeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            'Sales' => [
                'description' => 'Divisi penjualan, business development, dan account management',
                'departments' => [
                    'Leadership' => [
                        'description' => 'Kepemimpinan divisi Sales',
                        'positions' => [
                            ['name' => 'Business Director', 'level' => 'director'],
                        ],
                    ],
                    'Business Development' => [
                        'description' => 'Tim pengembangan bisnis baru',
                        'positions' => [
                            ['name' => 'Head of Sales', 'level' => 'manager'],
                            ['name' => 'Business Development Manager', 'level' => 'manager'],
                            ['name' => 'Business Development', 'level' => 'staff'],
                        ],
                    ],
                    'Account Management' => [
                        'description' => 'Tim pengelolaan akun dan retensi klien',
                        'positions' => [
                            ['name' => 'Account Manager', 'level' => 'manager'],
                            ['name' => 'Senior Account Manager', 'level' => 'senior'],
                            ['name' => 'Account Manager Staff', 'level' => 'staff'],
                            ['name' => 'AM GRES', 'level' => 'staff'],
                        ],
                    ],
                ],
            ],
            'Creative' => [
                'description' => 'Divisi kreatif mencakup konten, seni, strategi, KOL, dan produksi',
                'departments' => [
                    'Leadership' => [
                        'description' => 'Kepemimpinan divisi Creative',
                        'positions' => [
                            ['name' => 'Creative Director', 'level' => 'director'],
                        ],
                    ],
                    'Content' => [
                        'description' => 'Tim pembuatan konten tulisan dan copywriting',
                        'positions' => [
                            ['name' => 'Head of Content', 'level' => 'manager'],
                            ['name' => 'Copywriter', 'level' => 'staff'],
                            ['name' => 'Admin', 'level' => 'staff'],
                        ],
                    ],
                    'Art' => [
                        'description' => 'Tim desain visual dan art direction',
                        'positions' => [
                            ['name' => 'Art Director', 'level' => 'manager'],
                            ['name' => 'Art Designer Senior', 'level' => 'senior'],
                            ['name' => 'Art Designer Junior', 'level' => 'junior'],
                        ],
                    ],
                    'Strategy' => [
                        'description' => 'Tim strategi kampanye (50% Recurring / 50% Pitch)',
                        'positions' => [
                            ['name' => 'Strategist Manager', 'level' => 'manager'],
                            ['name' => 'Strategist', 'level' => 'staff'],
                        ],
                    ],
                    'KOL' => [
                        'description' => 'Tim pengelolaan Key Opinion Leader',
                        'positions' => [
                            ['name' => 'KOL Specialist Manager', 'level' => 'manager'],
                            ['name' => 'Senior KOL - Planning', 'level' => 'senior'],
                            ['name' => 'KOL Specialist', 'level' => 'staff'],
                            ['name' => 'Senior KOL - Implementation', 'level' => 'senior'],
                            ['name' => 'KOL', 'level' => 'staff'],
                            ['name' => 'KOL Intern', 'level' => 'intern'],
                        ],
                    ],
                    'Production' => [
                        'description' => 'Tim produksi video dan foto',
                        'positions' => [
                            ['name' => 'Senior Videographer & Photographer', 'level' => 'senior'],
                            ['name' => 'Junior Video & Photo', 'level' => 'junior'],
                            ['name' => 'Editor 3D Animation', 'level' => 'staff'],
                            ['name' => 'Production Intern', 'level' => 'intern'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($structure as $divisionName => $divisionData) {
            $division = Division::firstOrCreate(
                ['slug' => Str::slug($divisionName)],
                [
                    'name' => $divisionName,
                    'description' => $divisionData['description'],
                    'is_active' => true,
                ]
            );

            foreach ($divisionData['departments'] as $deptName => $deptData) {
                $department = Department::firstOrCreate(
                    ['slug' => Str::slug($divisionName . '-' . $deptName)],
                    [
                        'division_id' => $division->id,
                        'name' => $deptName,
                        'description' => $deptData['description'],
                        'is_active' => true,
                    ]
                );

                foreach ($deptData['positions'] as $posData) {
                    Position::firstOrCreate(
                        [
                            'department_id' => $department->id,
                            'name' => $posData['name'],
                        ],
                        [
                            'level' => $posData['level'],
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
