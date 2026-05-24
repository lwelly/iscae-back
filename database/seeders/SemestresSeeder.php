<?php
// database/seeders/SemestresSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SemestresSeeder extends Seeder
{
    public function run(): void
    {
        $semestres = [
            // L1 → S1, S2
            [
                'niveau_code'  => 'L1',
                'code'         => 'S1',
                'label'        => 'Semestre 1',
                'order_index'  => 1,
                'academic_year'=> '2024-2025',
                'is_open'      => false,
                'open_at'      => '2024-09-15 08:00:00',
                'close_at'     => '2025-02-01 23:59:59',
            ],
            [
                'niveau_code'  => 'L1',
                'code'         => 'S2',
                'label'        => 'Semestre 2',
                'order_index'  => 2,
                'academic_year'=> '2024-2025',
                'is_open'      => true,
                'open_at'      => '2025-02-10 08:00:00',
                'close_at'     => '2025-07-01 23:59:59',
            ],
            // L2 → S3, S4
            [
                'niveau_code'  => 'L2',
                'code'         => 'S3',
                'label'        => 'Semestre 3',
                'order_index'  => 3,
                'academic_year'=> '2024-2025',
                'is_open'      => false,
                'open_at'      => '2024-09-15 08:00:00',
                'close_at'     => '2025-02-01 23:59:59',
            ],
            [
                'niveau_code'  => 'L2',
                'code'         => 'S4',
                'label'        => 'Semestre 4',
                'order_index'  => 4,
                'academic_year'=> '2024-2025',
                'is_open'      => true,
                'open_at'      => '2025-02-10 08:00:00',
                'close_at'     => '2025-07-01 23:59:59',
            ],
            // L3 → S5, S6
            [
                'niveau_code'  => 'L3',
                'code'         => 'S5',
                'label'        => 'Semestre 5',
                'order_index'  => 5,
                'academic_year'=> '2024-2025',
                'is_open'      => false,
                'open_at'      => '2024-09-15 08:00:00',
                'close_at'     => '2025-02-01 23:59:59',
            ],
            [
                'niveau_code'  => 'L3',
                'code'         => 'S6',
                'label'        => 'Semestre 6',
                'order_index'  => 6,
                'academic_year'=> '2024-2025',
                'is_open'      => true,
                'open_at'      => '2025-02-10 08:00:00',
                'close_at'     => '2025-07-01 23:59:59',
            ],
        ];

        foreach ($semestres as $s) {
            $niveau = DB::table('niveaux')->where('code', $s['niveau_code'])->first();

            if (!$niveau) {
                $this->command->error("❌ Niveau {$s['niveau_code']} introuvable !");
                continue;
            }

            DB::table('semestres')->insertOrIgnore([
                'niveau_id'    => $niveau->id,
                'code'         => $s['code'],
                'label'        => $s['label'],
                'order_index'  => $s['order_index'],
                'academic_year'=> $s['academic_year'],
                'is_open'      => $s['is_open'],
                'open_at'      => $s['open_at'],
                'close_at'     => $s['close_at'],
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $this->command->info('✅ Semestres insérés : S1 → S6 (2024-2025)');
    }
}
