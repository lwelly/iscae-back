<?php
// database/seeders/FilieresSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FilieresSeeder extends Seeder
{
    public function run(): void
    {
        $filieres = [
            // Finance
            [
                'dept_code'   => 'FIN',
                'name'        => 'Licence Finance',
                'code'        => 'LIC-FIN',
                'description' => 'Licence en Finance et Gestion Financière',
            ],
            [
                'dept_code'   => 'FIN',
                'name'        => 'Licence Comptabilité',
                'code'        => 'LIC-CPT',
                'description' => 'Licence en Comptabilité et Audit',
            ],
            // Marketing
            [
                'dept_code'   => 'MKT',
                'name'        => 'Licence Marketing',
                'code'        => 'LIC-MKT',
                'description' => 'Licence en Marketing et Commerce',
            ],
            // Management
            [
                'dept_code'   => 'MGT',
                'name'        => 'Licence Management',
                'code'        => 'LIC-MGT',
                'description' => 'Licence en Management des Organisations',
            ],
            // Informatique
            [
                'dept_code'   => 'INFO',
                'name'        => 'Licence Informatique de Gestion',
                'code'        => 'LIC-IG',
                'description' => 'Licence en Informatique appliquée à la Gestion',
            ],
            [
                'dept_code'   => 'INFO',
                'name'        => 'Licence Systèmes d\'Information',
                'code'        => 'LIC-SI',
                'description' => 'Licence en Systèmes d\'Information et Réseaux',
            ],
        ];

        foreach ($filieres as $f) {
            $dept = DB::table('departments')->where('code', $f['dept_code'])->first();

            if (!$dept) {
                $this->command->error("❌ Département {$f['dept_code']} introuvable !");
                continue;
            }

            DB::table('filieres')->insertOrIgnore([
                'department_id' => $dept->id,
                'name'          => $f['name'],
                'code'          => $f['code'],
                'description'   => $f['description'],
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        $this->command->info('✅ Filières insérées : LIC-FIN, LIC-CPT, LIC-MKT, LIC-MGT, LIC-IG, LIC-SI');
    }
}
