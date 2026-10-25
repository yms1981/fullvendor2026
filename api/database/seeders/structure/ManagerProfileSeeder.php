<?php

namespace Database\Seeders\structure;

use App\Models\NavbarRolId;
use App\Models\ManagerProfiles;
use Illuminate\Database\Seeder;

class ManagerProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->generateWarehouseProfile();
        $this->generateCommercialProfile();
        $this->generateAdminProfile();
    }

    /**
     * @return void
     */
    public function generateWarehouseProfile(): void
    {
        $warehouseRol = ManagerProfiles::create([
            'name' => 'DEPOSITO - E-COMMERCE',
            'is_active' => true
        ]);

        $permissions = [
            ['navbar_id' => 4],
            ['navbar_id' => 6],
            ['navbar_id' => 7],
            ['navbar_id' => 9],
            ['navbar_id' => 10],
            ['navbar_id' => 11],
            [
                'navbar_id' => 12,
                'fhater_id' => 699
            ],
            ['navbar_id' => 16],
            [
                'navbar_id' => 17,
                'fhater_id' => 699
            ]
        ];

        $this->createPermissions($permissions, $warehouseRol->rol_id);
    }

    /**
     * @return void
     */
    public function generateCommercialProfile(): void
    {
        $commercialRol = ManagerProfiles::create([
            'name' => 'COMERCIAL',
            'is_active' => true
        ]);

        $permissions = [
            ['navbar_id' => 4],
            ['navbar_id' => 9],
            [
                'navbar_id' => 12,
                'fhater_id' => 500
            ],
            [
                'navbar_id' => 15,
                'fhater_id' => 500
            ]
        ];

        $this->createPermissions($permissions, $commercialRol->rol_id);
    }

    /**
     * @return void
     */
    public function generateAdminProfile(): void
    {
        $adminRol = ManagerProfiles::create([
            'name' => 'ADMINISTRADOR ',
            'is_active' => true
        ]);

        $permissions = [
            ['navbar_id' => 3],
            ['navbar_id' => 4],
            ['navbar_id' => 5],
            ['navbar_id' => 6],
            ['navbar_id' => 7],
            ['navbar_id' => 8],
            ['navbar_id' => 9],
            ['navbar_id' => 10],
            ['navbar_id' => 11],
            [
                'navbar_id' => 12,
                'fhater_id' => 874
            ],
            [
                'navbar_id' => 13,
                'fhater_id' => 880
            ],
            [
                'navbar_id' => 14,
                'fhater_id' => 880
            ],
            ['navbar_id' => 15],
            ['navbar_id' => 16],
            [
                'navbar_id' => 17,
                'fhater_id' => 874
            ],
            [
                'navbar_id' => 19,
                'fhater_id' => 880
            ],
        ];

        $this->createPermissions($permissions, $adminRol->rol_id);
    }

    /**
     * @param array $permissions
     * @return void
     */
    public function createPermissions(array $permissions, int $rolId): void
    {
        foreach ($permissions as $permission) {
            $permission['rol_id'] = $rolId;

            NavbarRolId::create($permission);
        }
    }
}
