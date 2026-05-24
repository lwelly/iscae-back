<?php
// database/seeders/DepartmentsSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentsSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name'       => 'Finance et Comptabilité',
                'code'       => 'FIN',
                'head_name'  => 'Dr. Ahmed Ould Mohamed',
                'head_email' => 'ahmed.fin@iscae.mr',
                'is_active'  => true,
            ],
            [
                'name'       => 'Marketing et Commerce',
                'code'       => 'MKT',
                'head_name'  => 'Dr. Fatima Mint Ahmed',
                'head_email' => 'fatima.mkt@iscae.mr',
                'is_active'  => true,
            ],
            [
                'name'       => 'Management et Organisation',
                'code'       => 'MGT',
                'head_name'  => 'Dr. Mohamed Ould Saleck',
                'head_email' => 'med.mgt@iscae.mr',
                'is_active'  => true,
            ],
            [
                'name'       => 'Informatique de Gestion',
                'code'       => 'INFO',
                'head_name'  => 'Dr. Aissa Ould Bouh',
                'head_email' => 'aissa.info@iscae.mr',
                'is_active'  => true,
            ],
        ];

        foreach ($departments as $dept) {
            DB::table('departments')->insertOrIgnore([
                ...$dept,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✅ Départements insérés : FIN, MKT, MGT, INFO');
    }
}
