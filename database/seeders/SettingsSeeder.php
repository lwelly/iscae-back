<?php
// database/seeders/SettingsSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // OTP
            ['key'=>'otp_expiry_minutes',        'value'=>'10',          'type'=>'integer','group'=>'security',  'is_public'=>false,'description'=>'Durée de validité OTP en minutes'],
            ['key'=>'otp_max_attempts',           'value'=>'5',           'type'=>'integer','group'=>'security',  'is_public'=>false,'description'=>'Nombre max de tentatives OTP'],

            // Login
            ['key'=>'login_max_attempts',         'value'=>'5',           'type'=>'integer','group'=>'security',  'is_public'=>false,'description'=>'Nombre max de tentatives de connexion'],
            ['key'=>'login_lockout_minutes',       'value'=>'30',          'type'=>'integer','group'=>'security',  'is_public'=>false,'description'=>'Durée de blocage après échecs (minutes)'],

            // 2FA Admin
            ['key'=>'two_fa_reask_days',          'value'=>'30',          'type'=>'integer','group'=>'security',  'is_public'=>false,'description'=>'Jours avant re-demande 2FA'],

            // Session
            ['key'=>'session_lifetime_minutes',   'value'=>'120',         'type'=>'integer','group'=>'session',   'is_public'=>false,'description'=>'Durée de vie de la session (minutes)'],
            ['key'=>'device_trust_days',          'value'=>'30',          'type'=>'integer','group'=>'security',  'is_public'=>false,'description'=>'Jours de confiance d\'un appareil'],

            // Upload
            ['key'=>'max_upload_size_mb',         'value'=>'10',          'type'=>'integer','group'=>'upload',    'is_public'=>true, 'description'=>'Taille max des fichiers uploadés (MB)'],
            ['key'=>'allowed_file_types',         'value'=>'pdf,jpg,jpeg,png,doc,docx','type'=>'string','group'=>'upload','is_public'=>true,'description'=>'Types de fichiers autorisés'],

            // Réclamations
            ['key'=>'reclamation_max_active',     'value'=>'3',           'type'=>'integer','group'=>'reclamation','is_public'=>true,'description'=>'Nombre max de réclamations actives par étudiant'],

            // Application
            ['key'=>'app_name',                   'value'=>'ISCAE Reclamation System','type'=>'string','group'=>'general','is_public'=>true,'description'=>'Nom de l\'application'],
            ['key'=>'current_academic_year',      'value'=>'2024-2025',   'type'=>'string', 'group'=>'academic',  'is_public'=>true, 'description'=>'Année académique en cours'],
            ['key'=>'maintenance_mode',           'value'=>'false',       'type'=>'boolean','group'=>'general',   'is_public'=>false,'description'=>'Mode maintenance actif'],
            ['key'=>'contact_email',              'value'=>'contact@iscae.mr','type'=>'string','group'=>'general','is_public'=>true,'description'=>'Email de contact'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insertOrIgnore([
                ...$setting,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✅ Paramètres système insérés : ' . count($settings) . ' entrées');
    }
}
