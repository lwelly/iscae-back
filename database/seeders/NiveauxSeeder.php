<?php
// database/seeders/NiveauxSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NiveauxSeeder extends Seeder
{
    public function run(): void
    {
        $niveaux = [
            [
                'code'        => 'L1',
                'label'       => 'Licence 1ère année',
                'order_index' => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'code'        => 'L2',
                'label'       => 'Licence 2ème année',
                'order_index' => 2,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'code'        => 'L3',
                'label'       => 'Licence 3ème année',
                'order_index' => 3,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        DB::table('niveaux')->insertOrIgnore($niveaux);

        $this->command->info('✅ Niveaux insérés : L1, L2, L3');
    }
}
