<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentsPreloadedSeeder extends Seeder
{
    public function run(): void
    {
        // Supprimer seulement les non-inscrits
        DB::table('students_preloaded')->where('is_registered', false)->delete();

        $filieres = DB::table('filieres')->pluck('id', 'code');
        $niveaux  = DB::table('niveaux')->pluck('id', 'code');

        // ⚠️ EN PRODUCTION : remplacer par les vrais emails des étudiants
        // EN TEST : on utilise des alias Gmail (+matricule) qui arrivent tous dans la même boîte
        $baseEmail = 'lwellysaleck33'; // ton Gmail sans @gmail.com

        $students = [
            // ══════════════════════════════
            // LIC-FIN — L1
            // ══════════════════════════════
            ['matricule'=>'20240001','nni'=>'1000000001','nom'=>'Ould Ahmed',    'prenom'=>'Mohamed',    'email'=>"{$baseEmail}+20240001@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L1'],
            ['matricule'=>'20240002','nni'=>'1000000002','nom'=>'Mint Salem',    'prenom'=>'Mariem',     'email'=>"{$baseEmail}+20240002@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L1'],
            ['matricule'=>'20240003','nni'=>'1000000003','nom'=>'Ould Brahim',   'prenom'=>'Abdallah',   'email'=>"{$baseEmail}+20240003@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L1'],
            ['matricule'=>'20240004','nni'=>'1000000004','nom'=>'Mint Mohamed',  'prenom'=>'Khadija',    'email'=>"{$baseEmail}+20240004@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L1'],
            ['matricule'=>'20240005','nni'=>'1000000005','nom'=>'Ould Sidi',     'prenom'=>'Hamza',      'email'=>"{$baseEmail}+20240005@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L1'],
            ['matricule'=>'20240006','nni'=>'1000000006','nom'=>'Mint Vall',     'prenom'=>'Aminata',    'email'=>"{$baseEmail}+20240006@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L1'],
            ['matricule'=>'20240007','nni'=>'1000000007','nom'=>'Ould Cheikh',   'prenom'=>'Yahya',      'email'=>"{$baseEmail}+20240007@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L1'],
            ['matricule'=>'20240008','nni'=>'1000000008','nom'=>'Mint Diallo',   'prenom'=>'Fatou',      'email'=>"{$baseEmail}+20240008@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L1'],
            ['matricule'=>'20240009','nni'=>'1000000009','nom'=>'Ould Habibi',   'prenom'=>'Sidi',       'email'=>"{$baseEmail}+20240009@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L1'],
            ['matricule'=>'20240010','nni'=>'1000000010','nom'=>'Mint Bouh',     'prenom'=>'Nana',       'email'=>"{$baseEmail}+20240010@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L1'],
            // LIC-FIN — L2
            ['matricule'=>'20230001','nni'=>'2000000001','nom'=>'Ould Isselmou', 'prenom'=>'Idriss',     'email'=>"{$baseEmail}+20230001@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L2'],
            ['matricule'=>'20230002','nni'=>'2000000002','nom'=>'Mint Bilal',    'prenom'=>'Roukia',     'email'=>"{$baseEmail}+20230002@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L2'],
            ['matricule'=>'20230003','nni'=>'2000000003','nom'=>'Ould Deh',      'prenom'=>'Moctar',     'email'=>"{$baseEmail}+20230003@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L2'],
            ['matricule'=>'20230004','nni'=>'2000000004','nom'=>'Mint Ethmane',  'prenom'=>'Salma',      'email'=>"{$baseEmail}+20230004@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L2'],
            ['matricule'=>'20230005','nni'=>'2000000005','nom'=>'Ould Ghaber',   'prenom'=>'Moussa',     'email'=>"{$baseEmail}+20230005@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L2'],
            ['matricule'=>'20230006','nni'=>'2000000006','nom'=>'Mint Abdi',     'prenom'=>'Zeinab',     'email'=>"{$baseEmail}+20230006@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L2'],
            ['matricule'=>'20230007','nni'=>'2000000007','nom'=>'Ould Mohamed',  'prenom'=>'Ahmed',      'email'=>"{$baseEmail}+20230007@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L2'],
            ['matricule'=>'20230008','nni'=>'2000000008','nom'=>'Mint Lekbir',   'prenom'=>'Hawa',       'email'=>"{$baseEmail}+20230008@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L2'],
            // LIC-FIN — L3
            ['matricule'=>'20220001','nni'=>'3000000001','nom'=>'Ould Taleb',    'prenom'=>'Boubacar',   'email'=>"{$baseEmail}+20220001@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L3'],
            ['matricule'=>'20220002','nni'=>'3000000002','nom'=>'Mint Wane',     'prenom'=>'Coumba',     'email'=>"{$baseEmail}+20220002@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L3'],
            ['matricule'=>'20220003','nni'=>'3000000003','nom'=>'Ould Barry',    'prenom'=>'Oumar',      'email'=>"{$baseEmail}+20220003@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L3'],
            ['matricule'=>'20220004','nni'=>'3000000004','nom'=>'Mint Sow',      'prenom'=>'Maimouna',   'email'=>"{$baseEmail}+20220004@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L3'],
            ['matricule'=>'20220005','nni'=>'3000000005','nom'=>'Ould Diop',     'prenom'=>'Cheikh',     'email'=>"{$baseEmail}+20220005@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L3'],
            ['matricule'=>'20220006','nni'=>'3000000006','nom'=>'Mint Kane',     'prenom'=>'Oumou',      'email'=>"{$baseEmail}+20220006@gmail.com",'filiere'=>'LIC-FIN','niveau'=>'L3'],
            // ══════════════════════════════
            // LIC-IG — L1
            // ══════════════════════════════
            ['matricule'=>'20240011','nni'=>'1000000011','nom'=>'Ould Limam',    'prenom'=>'Ismail',     'email'=>"{$baseEmail}+20240011@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L1'],
            ['matricule'=>'20240012','nni'=>'1000000012','nom'=>'Mint Jeddou',   'prenom'=>'Aicha',      'email'=>"{$baseEmail}+20240012@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L1'],
            ['matricule'=>'20240013','nni'=>'1000000013','nom'=>'Ould Moustaph', 'prenom'=>'Ali',        'email'=>"{$baseEmail}+20240013@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L1'],
            ['matricule'=>'20240014','nni'=>'1000000014','nom'=>'Mint Khalil',   'prenom'=>'Marwa',      'email'=>"{$baseEmail}+20240014@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L1'],
            ['matricule'=>'20240015','nni'=>'1000000015','nom'=>'Ould Seydi',    'prenom'=>'Hamidou',    'email'=>"{$baseEmail}+20240015@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L1'],
            ['matricule'=>'20240016','nni'=>'1000000016','nom'=>'Mint Kebe',     'prenom'=>'Fatimetou',  'email'=>"{$baseEmail}+20240016@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L1'],
            ['matricule'=>'20240017','nni'=>'1000000017','nom'=>'Ould Traoré',   'prenom'=>'Ibrahim',    'email'=>"{$baseEmail}+20240017@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L1'],
            ['matricule'=>'20240018','nni'=>'1000000018','nom'=>'Mint Ndiaye',   'prenom'=>'Khady',      'email'=>"{$baseEmail}+20240018@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L1'],
            // LIC-IG — L2
            ['matricule'=>'20230009','nni'=>'2000000009','nom'=>'Ould Bocar',    'prenom'=>'Samba',      'email'=>"{$baseEmail}+20230009@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L2'],
            ['matricule'=>'20230010','nni'=>'2000000010','nom'=>'Mint Fall',     'prenom'=>'Adja',       'email'=>"{$baseEmail}+20230010@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L2'],
            ['matricule'=>'20230011','nni'=>'2000000011','nom'=>'Ould Konaté',   'prenom'=>'Seydou',     'email'=>"{$baseEmail}+20230011@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L2'],
            ['matricule'=>'20230012','nni'=>'2000000012','nom'=>'Mint Touré',    'prenom'=>'Binta',      'email'=>"{$baseEmail}+20230012@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L2'],
            ['matricule'=>'20230013','nni'=>'2000000013','nom'=>'Ould Camara',   'prenom'=>'Mamadou',    'email'=>"{$baseEmail}+20230013@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L2'],
            ['matricule'=>'20230014','nni'=>'2000000014','nom'=>'Mint Gaye',     'prenom'=>'Ndéye',      'email'=>"{$baseEmail}+20230014@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L2'],
            // LIC-IG — L3
            ['matricule'=>'20220007','nni'=>'3000000007','nom'=>'Ould Baldé',    'prenom'=>'Alpha',      'email'=>"{$baseEmail}+20220007@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L3'],
            ['matricule'=>'20220008','nni'=>'3000000008','nom'=>'Mint Diallo',   'prenom'=>'Mariama',    'email'=>"{$baseEmail}+20220008@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L3'],
            ['matricule'=>'20220009','nni'=>'3000000009','nom'=>'Ould Mbaye',    'prenom'=>'Pape',       'email'=>"{$baseEmail}+20220009@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L3'],
            ['matricule'=>'20220010','nni'=>'3000000010','nom'=>'Mint Thiam',    'prenom'=>'Aminata',    'email'=>"{$baseEmail}+20220010@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L3'],
            ['matricule'=>'20220011','nni'=>'3000000011','nom'=>'Ould Sarr',     'prenom'=>'Modou',      'email'=>"{$baseEmail}+20220011@gmail.com",'filiere'=>'LIC-IG','niveau'=>'L3'],
            // ══════════════════════════════
            // LIC-MKT — L1
            // ══════════════════════════════
            ['matricule'=>'20240019','nni'=>'1000000019','nom'=>'Ould Sy',       'prenom'=>'Mamoudou',   'email'=>"{$baseEmail}+20240019@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L1'],
            ['matricule'=>'20240020','nni'=>'1000000020','nom'=>'Mint Tall',     'prenom'=>'Rougui',     'email'=>"{$baseEmail}+20240020@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L1'],
            ['matricule'=>'20240021','nni'=>'1000000021','nom'=>'Ould Bâ',       'prenom'=>'Hamed',      'email'=>"{$baseEmail}+20240021@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L1'],
            ['matricule'=>'20240022','nni'=>'1000000022','nom'=>'Mint Ly',       'prenom'=>'Aissatou',   'email'=>"{$baseEmail}+20240022@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L1'],
            ['matricule'=>'20240023','nni'=>'1000000023','nom'=>'Ould Coulibaly','prenom'=>'Adama',      'email'=>"{$baseEmail}+20240023@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L1'],
            ['matricule'=>'20240024','nni'=>'1000000024','nom'=>'Mint Kouyaté',  'prenom'=>'Kadiatou',   'email'=>"{$baseEmail}+20240024@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L1'],
            ['matricule'=>'20240025','nni'=>'1000000025','nom'=>'Ould Doumbia',  'prenom'=>'Souleymane', 'email'=>"{$baseEmail}+20240025@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L1'],
            ['matricule'=>'20240026','nni'=>'1000000026','nom'=>'Mint Sanogo',   'prenom'=>'Tenin',      'email'=>"{$baseEmail}+20240026@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L1'],
            // LIC-MKT — L2
            ['matricule'=>'20230015','nni'=>'2000000015','nom'=>'Ould Keita',    'prenom'=>'Bouréma',    'email'=>"{$baseEmail}+20230015@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L2'],
            ['matricule'=>'20230016','nni'=>'2000000016','nom'=>'Mint Cissé',    'prenom'=>'Fatoumata',  'email'=>"{$baseEmail}+20230016@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L2'],
            ['matricule'=>'20230017','nni'=>'2000000017','nom'=>'Ould Diabaté',  'prenom'=>'Lassana',    'email'=>"{$baseEmail}+20230017@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L2'],
            ['matricule'=>'20230018','nni'=>'2000000018','nom'=>'Mint Koné',     'prenom'=>'Mariam',     'email'=>"{$baseEmail}+20230018@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L2'],
            ['matricule'=>'20230019','nni'=>'2000000019','nom'=>'Ould Coulibaly','prenom'=>'Tièba',      'email'=>"{$baseEmail}+20230019@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L2'],
            ['matricule'=>'20230020','nni'=>'2000000020','nom'=>'Mint Traoré',   'prenom'=>'Djeneba',    'email'=>"{$baseEmail}+20230020@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L2'],
            // LIC-MKT — L3
            ['matricule'=>'20220012','nni'=>'3000000012','nom'=>'Ould Fofana',   'prenom'=>'Drissa',     'email'=>"{$baseEmail}+20220012@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L3'],
            ['matricule'=>'20220013','nni'=>'3000000013','nom'=>'Mint Bamba',    'prenom'=>'Salimata',   'email'=>"{$baseEmail}+20220013@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L3'],
            ['matricule'=>'20220014','nni'=>'3000000014','nom'=>'Ould Diarra',   'prenom'=>'Youssouf',   'email'=>"{$baseEmail}+20220014@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L3'],
            ['matricule'=>'20220015','nni'=>'3000000015','nom'=>'Mint Dembélé',  'prenom'=>'Awa',        'email'=>"{$baseEmail}+20220015@gmail.com",'filiere'=>'LIC-MKT','niveau'=>'L3'],
            // ══════════════════════════════
            // LIC-MGT — L1
            // ══════════════════════════════
            ['matricule'=>'20240027','nni'=>'1000000027','nom'=>'Ould Sissoko',  'prenom'=>'Bakary',     'email'=>"{$baseEmail}+20240027@gmail.com",'filiere'=>'LIC-MGT','niveau'=>'L1'],
            ['matricule'=>'20240028','nni'=>'1000000028','nom'=>'Mint Coulibaly','prenom'=>'Rokia',      'email'=>"{$baseEmail}+20240028@gmail.com",'filiere'=>'LIC-MGT','niveau'=>'L1'],
            ['matricule'=>'20240029','nni'=>'1000000029','nom'=>'Ould Maïga',    'prenom'=>'Hamidou',    'email'=>"{$baseEmail}+20240029@gmail.com",'filiere'=>'LIC-MGT','niveau'=>'L1'],
            ['matricule'=>'20240030','nni'=>'1000000030','nom'=>'Mint Samaké',   'prenom'=>'Mariam',     'email'=>"{$baseEmail}+20240030@gmail.com",'filiere'=>'LIC-MGT','niveau'=>'L1'],
            ['matricule'=>'20240031','nni'=>'1000000031','nom'=>'Ould Dao',      'prenom'=>'Ibrahim',    'email'=>"{$baseEmail}+20240031@gmail.com",'filiere'=>'LIC-MGT','niveau'=>'L1'],
            ['matricule'=>'20240032','nni'=>'1000000032','nom'=>'Mint Touré',    'prenom'=>'Fatoumata',  'email'=>"{$baseEmail}+20240032@gmail.com",'filiere'=>'LIC-MGT','niveau'=>'L1'],
            // LIC-MGT — L2
            ['matricule'=>'20230021','nni'=>'2000000021','nom'=>'Ould Kanté',    'prenom'=>'Sekou',      'email'=>"{$baseEmail}+20230021@gmail.com",'filiere'=>'LIC-MGT','niveau'=>'L2'],
            ['matricule'=>'20230022','nni'=>'2000000022','nom'=>'Mint Kouyaté',  'prenom'=>'Hawa',       'email'=>"{$baseEmail}+20230022@gmail.com",'filiere'=>'LIC-MGT','niveau'=>'L2'],
            ['matricule'=>'20230023','nni'=>'2000000023','nom'=>'Ould Diallo',   'prenom'=>'Oumar',      'email'=>"{$baseEmail}+20230023@gmail.com",'filiere'=>'LIC-MGT','niveau'=>'L2'],
            ['matricule'=>'20230024','nni'=>'2000000024','nom'=>'Mint Bah',      'prenom'=>'Aissata',    'email'=>"{$baseEmail}+20230024@gmail.com",'filiere'=>'LIC-MGT','niveau'=>'L2'],
            ['matricule'=>'20230025','nni'=>'2000000025','nom'=>'Ould Baldé',    'prenom'=>'Mamadou',    'email'=>"{$baseEmail}+20230025@gmail.com",'filiere'=>'LIC-MGT','niveau'=>'L2'],
            // LIC-MGT — L3
            ['matricule'=>'20220016','nni'=>'3000000016','nom'=>'Ould Sow',      'prenom'=>'Ibrahima',   'email'=>"{$baseEmail}+20220016@gmail.com",'filiere'=>'LIC-MGT','niveau'=>'L3'],
            ['matricule'=>'20220017','nni'=>'3000000017','nom'=>'Mint Diallo',   'prenom'=>'Oumou',      'email'=>"{$baseEmail}+20220017@gmail.com",'filiere'=>'LIC-MGT','niveau'=>'L3'],
            ['matricule'=>'20220018','nni'=>'3000000018','nom'=>'Ould Camara',   'prenom'=>'Thierno',    'email'=>"{$baseEmail}+20220018@gmail.com",'filiere'=>'LIC-MGT','niveau'=>'L3'],
            ['matricule'=>'20220019','nni'=>'3000000019','nom'=>'Mint Balde',    'prenom'=>'Mariama',    'email'=>"{$baseEmail}+20220019@gmail.com",'filiere'=>'LIC-MGT','niveau'=>'L3'],
        ];

        $inserted = 0;
        foreach ($students as $s) {
            $filiereId = $filieres[$s['filiere']] ?? null;
            $niveauId  = $niveaux[$s['niveau']]   ?? null;

            if (!$filiereId || !$niveauId) {
                $this->command->warn("⚠️  Filière/Niveau introuvable pour {$s['matricule']} ({$s['filiere']}/{$s['niveau']})");
                continue;
            }

            DB::table('students_preloaded')->insertOrIgnore([
                'matricule'     => $s['matricule'],
                'nni'           => $s['nni'],
                'nom'           => $s['nom'],
                'prenom'        => $s['prenom'],
                'email'         => $s['email'],
                'filiere_code'  => $s['filiere'],
                'niveau_code'   => $s['niveau'],
                'academic_year' => '2024-2025',
                'is_registered' => false,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            $inserted++;
        }

        $this->command->info("✅ Étudiants pré-chargés : {$inserted} étudiants insérés");
    }
}
