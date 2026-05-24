<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            NiveauxSeeder::class,           // 1. Niveaux L1, L2, L3
            SemestresSeeder::class,          // 2. Semestres S1→S6
            DepartmentsSeeder::class,        // 3. Départements
            FilieresSeeder::class,           // 4. Filières
            ModulesSeeder::class,            // 5. Modules (216 modules)
            AdminSeeder::class,              // 6. Admins (hash auto)
            StudentsPreloadedSeeder::class,  // 7. Étudiants pré-chargés
            SettingsSeeder::class,           // 8. Paramètres système
        ]);
    }
}
