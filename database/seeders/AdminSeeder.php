<?php
// database/seeders/AdminSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================
        // Super Admin principal
        // ============================================
        $adminUserId = DB::table('users')->insertGetId([
            'role'              => 'admin',
            'login_identifier'  => 'admin@iscae.mr',  // garde le login
            'email'             => 'lwellysaleck33@gmail.com',  // vrai email pour recevoir OTP
            'password'          => Hash::make('i@s@c@a@e@'),
            'is_active'         => true,
            'is_verified'       => true,
            'email_verified_at' => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        DB::table('admins')->insert([
            'user_id'           => $adminUserId,
            'department_id'     => null,
            'nom'               => 'Administrateur',
            'prenom'            => 'Principal',
            'role_label'        => 'super_admin',
            'two_fa_enabled'    => true,
            'two_fa_reask_days' => 30,
            'last_two_fa_at'    => null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $this->command->info('✅ Admin créé : admin@iscae.mr / i@s@c@a@e@');

        // ============================================
        // Chef département Finance
        // ============================================
        $finDept = DB::table('departments')->where('code', 'FIN')->first();

        if ($finDept) {
            $chefFinId = DB::table('users')->insertGetId([
                'role'              => 'admin',
                'login_identifier'  => 'chef.fin@iscae.mr',
                'email'=> 'lwellysaleck33@gmail.com',
                'password'          => Hash::make('i@s@c@a@e@'),
                'is_active'         => true,
                'is_verified'       => true,
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            DB::table('admins')->insert([
                'user_id'           => $chefFinId,
                'department_id'     => $finDept->id,
                'nom'               => 'Ould Mohamed',
                'prenom'            => 'Ahmed',
                'role_label'        => 'department_head',
                'two_fa_enabled'    => true,
                'two_fa_reask_days' => 30,
                'last_two_fa_at'    => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            $this->command->info('✅ Chef département FIN créé : chef.fin@iscae.mr');
        }

        // ============================================
        // Chef département INFO
        // ============================================
        $infoDept = DB::table('departments')->where('code', 'INFO')->first();

        if ($infoDept) {
            $chefInfoId = DB::table('users')->insertGetId([
                'role'              => 'admin',
                'login_identifier'  => 'chef.info@iscae.mr',
                'email'=> 'lwellysaleck22@gmail.com',
                'password'          => Hash::make('i@s@c@a@e@'),
                'is_active'         => true,
                'is_verified'       => true,
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            DB::table('admins')->insert([
                'user_id'           => $chefInfoId,
                'department_id'     => $infoDept->id,
                'nom'               => 'Ould Bouh',
                'prenom'            => 'Aissa',
                'role_label'        => 'department_head',
                'two_fa_enabled'    => true,
                'two_fa_reask_days' => 30,
                'last_two_fa_at'    => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            $this->command->info('✅ Chef département INFO créé : chef.info@iscae.mr');
        }
    }
}
