<?php
// database/seeders/ModulesSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModulesSeeder extends Seeder
{
    public function run(): void
    {
        // Structure : filiere_code => [ semestre_code => [ modules ] ]
        $data = [

            // ==========================================
            // LIC-FIN — Licence Finance
            // ==========================================
            'LIC-FIN' => [
                'S1' => [
                    ['code' => 'FIN-S1-01', 'name' => 'Mathématiques Financières',    'coef' => 3, 'credits' => 4],
                    ['code' => 'FIN-S1-02', 'name' => 'Comptabilité Générale I',      'coef' => 3, 'credits' => 4],
                    ['code' => 'FIN-S1-03', 'name' => 'Introduction à l\'Économie',   'coef' => 2, 'credits' => 3],
                    ['code' => 'FIN-S1-04', 'name' => 'Droit des Affaires I',         'coef' => 2, 'credits' => 3],
                    ['code' => 'FIN-S1-05', 'name' => 'Informatique de Gestion I',    'coef' => 1, 'credits' => 2],
                    ['code' => 'FIN-S1-06', 'name' => 'Langue Française I',           'coef' => 1, 'credits' => 2],
                ],
                'S2' => [
                    ['code' => 'FIN-S2-01', 'name' => 'Analyse Financière I',         'coef' => 3, 'credits' => 4],
                    ['code' => 'FIN-S2-02', 'name' => 'Comptabilité Générale II',     'coef' => 3, 'credits' => 4],
                    ['code' => 'FIN-S2-03', 'name' => 'Statistiques Appliquées',      'coef' => 2, 'credits' => 3],
                    ['code' => 'FIN-S2-04', 'name' => 'Microéconomie',                'coef' => 2, 'credits' => 3],
                    ['code' => 'FIN-S2-05', 'name' => 'Informatique de Gestion II',   'coef' => 1, 'credits' => 2],
                    ['code' => 'FIN-S2-06', 'name' => 'Langue Anglaise I',            'coef' => 1, 'credits' => 2],
                ],
                'S3' => [
                    ['code' => 'FIN-S3-01', 'name' => 'Analyse Financière II',        'coef' => 3, 'credits' => 4],
                    ['code' => 'FIN-S3-02', 'name' => 'Comptabilité Analytique',      'coef' => 3, 'credits' => 4],
                    ['code' => 'FIN-S3-03', 'name' => 'Macroéconomie',                'coef' => 2, 'credits' => 3],
                    ['code' => 'FIN-S3-04', 'name' => 'Fiscalité des Entreprises',    'coef' => 2, 'credits' => 3],
                    ['code' => 'FIN-S3-05', 'name' => 'Mathématiques pour Finances',  'coef' => 2, 'credits' => 3],
                    ['code' => 'FIN-S3-06', 'name' => 'Langue Anglaise II',           'coef' => 1, 'credits' => 1],
                ],
                'S4' => [
                    ['code' => 'FIN-S4-01', 'name' => 'Finance d\'Entreprise',        'coef' => 3, 'credits' => 4],
                    ['code' => 'FIN-S4-02', 'name' => 'Marchés Financiers',           'coef' => 3, 'credits' => 4],
                    ['code' => 'FIN-S4-03', 'name' => 'Contrôle de Gestion',          'coef' => 2, 'credits' => 3],
                    ['code' => 'FIN-S4-04', 'name' => 'Droit Fiscal',                 'coef' => 2, 'credits' => 3],
                    ['code' => 'FIN-S4-05', 'name' => 'Gestion de Trésorerie',        'coef' => 2, 'credits' => 3],
                    ['code' => 'FIN-S4-06', 'name' => 'Langue Française II',          'coef' => 1, 'credits' => 1],
                ],
                'S5' => [
                    ['code' => 'FIN-S5-01', 'name' => 'Finance Internationale',       'coef' => 3, 'credits' => 4],
                    ['code' => 'FIN-S5-02', 'name' => 'Audit Financier',              'coef' => 3, 'credits' => 4],
                    ['code' => 'FIN-S5-03', 'name' => 'Gestion de Portefeuille',      'coef' => 2, 'credits' => 3],
                    ['code' => 'FIN-S5-04', 'name' => 'Évaluation d\'Entreprises',    'coef' => 2, 'credits' => 3],
                    ['code' => 'FIN-S5-05', 'name' => 'Économétrie Financière',       'coef' => 2, 'credits' => 3],
                    ['code' => 'FIN-S5-06', 'name' => 'Éthique et Gouvernance',       'coef' => 1, 'credits' => 1],
                ],
                'S6' => [
                    ['code' => 'FIN-S6-01', 'name' => 'Ingénierie Financière',        'coef' => 3, 'credits' => 4],
                    ['code' => 'FIN-S6-02', 'name' => 'Banque et Crédit',             'coef' => 3, 'credits' => 4],
                    ['code' => 'FIN-S6-03', 'name' => 'Finance Islamique',            'coef' => 2, 'credits' => 3],
                    ['code' => 'FIN-S6-04', 'name' => 'Mémoire de Fin d\'Études',     'coef' => 3, 'credits' => 5],
                    ['code' => 'FIN-S6-05', 'name' => 'Stage Professionnel',          'coef' => 2, 'credits' => 3],
                    ['code' => 'FIN-S6-06', 'name' => 'Séminaire de Recherche',       'coef' => 1, 'credits' => 1],
                ],
            ],

            // ==========================================
            // LIC-CPT — Licence Comptabilité
            // ==========================================
            'LIC-CPT' => [
                'S1' => [
                    ['code' => 'CPT-S1-01', 'name' => 'Comptabilité Générale I',      'coef' => 3, 'credits' => 4],
                    ['code' => 'CPT-S1-02', 'name' => 'Mathématiques Appliquées',     'coef' => 3, 'credits' => 4],
                    ['code' => 'CPT-S1-03', 'name' => 'Droit des Affaires I',         'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S1-04', 'name' => 'Économie Générale I',          'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S1-05', 'name' => 'Informatique Bureautique',     'coef' => 1, 'credits' => 2],
                    ['code' => 'CPT-S1-06', 'name' => 'Langue Française I',           'coef' => 1, 'credits' => 2],
                ],
                'S2' => [
                    ['code' => 'CPT-S2-01', 'name' => 'Comptabilité Générale II',     'coef' => 3, 'credits' => 4],
                    ['code' => 'CPT-S2-02', 'name' => 'Statistiques I',               'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S2-03', 'name' => 'Droit des Sociétés',           'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S2-04', 'name' => 'Fiscalité I',                  'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S2-05', 'name' => 'Informatique Comptable',       'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S2-06', 'name' => 'Langue Anglaise I',            'coef' => 1, 'credits' => 2],
                ],
                'S3' => [
                    ['code' => 'CPT-S3-01', 'name' => 'Comptabilité Analytique',      'coef' => 3, 'credits' => 4],
                    ['code' => 'CPT-S3-02', 'name' => 'Comptabilité des Sociétés',    'coef' => 3, 'credits' => 4],
                    ['code' => 'CPT-S3-03', 'name' => 'Fiscalité II',                 'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S3-04', 'name' => 'Statistiques II',              'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S3-05', 'name' => 'Droit du Travail',             'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S3-06', 'name' => 'Langue Anglaise II',           'coef' => 1, 'credits' => 1],
                ],
                'S4' => [
                    ['code' => 'CPT-S4-01', 'name' => 'Contrôle de Gestion',          'coef' => 3, 'credits' => 4],
                    ['code' => 'CPT-S4-02', 'name' => 'Comptabilité Publique',        'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S4-03', 'name' => 'Audit Interne',                'coef' => 3, 'credits' => 4],
                    ['code' => 'CPT-S4-04', 'name' => 'Gestion Financière',           'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S4-05', 'name' => 'Normes IFRS',                  'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S4-06', 'name' => 'Communication d\'Entreprise',  'coef' => 1, 'credits' => 1],
                ],
                'S5' => [
                    ['code' => 'CPT-S5-01', 'name' => 'Audit et Commissariat',        'coef' => 3, 'credits' => 4],
                    ['code' => 'CPT-S5-02', 'name' => 'Consolidation des Comptes',    'coef' => 3, 'credits' => 4],
                    ['code' => 'CPT-S5-03', 'name' => 'Fiscalité Avancée',            'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S5-04', 'name' => 'Finance d\'Entreprise',        'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S5-05', 'name' => 'Logiciels Comptables',         'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S5-06', 'name' => 'Éthique Professionnelle',      'coef' => 1, 'credits' => 1],
                ],
                'S6' => [
                    ['code' => 'CPT-S6-01', 'name' => 'Expertise Comptable',          'coef' => 3, 'credits' => 4],
                    ['code' => 'CPT-S6-02', 'name' => 'Contrôle Fiscal',              'coef' => 3, 'credits' => 4],
                    ['code' => 'CPT-S6-03', 'name' => 'Gestion des Risques',          'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S6-04', 'name' => 'Mémoire de Fin d\'Études',     'coef' => 3, 'credits' => 5],
                    ['code' => 'CPT-S6-05', 'name' => 'Stage Professionnel',          'coef' => 2, 'credits' => 3],
                    ['code' => 'CPT-S6-06', 'name' => 'Séminaire Professionnel',      'coef' => 1, 'credits' => 1],
                ],
            ],

            // ==========================================
            // LIC-MKT — Licence Marketing
            // ==========================================
            'LIC-MKT' => [
                'S1' => [
                    ['code' => 'MKT-S1-01', 'name' => 'Fondements du Marketing',      'coef' => 3, 'credits' => 4],
                    ['code' => 'MKT-S1-02', 'name' => 'Comptabilité Générale I',      'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S1-03', 'name' => 'Économie Générale',            'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S1-04', 'name' => 'Droit Commercial',             'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S1-05', 'name' => 'Informatique Appliquée',       'coef' => 1, 'credits' => 2],
                    ['code' => 'MKT-S1-06', 'name' => 'Langue Française I',           'coef' => 2, 'credits' => 3],
                ],
                'S2' => [
                    ['code' => 'MKT-S2-01', 'name' => 'Marketing Mix',                'coef' => 3, 'credits' => 4],
                    ['code' => 'MKT-S2-02', 'name' => 'Comportement du Consommateur', 'coef' => 3, 'credits' => 4],
                    ['code' => 'MKT-S2-03', 'name' => 'Statistiques Marketing',       'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S2-04', 'name' => 'Gestion Commerciale',          'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S2-05', 'name' => 'Techniques de Vente',          'coef' => 1, 'credits' => 2],
                    ['code' => 'MKT-S2-06', 'name' => 'Langue Anglaise I',            'coef' => 1, 'credits' => 2],
                ],
                'S3' => [
                    ['code' => 'MKT-S3-01', 'name' => 'Étude de Marché',              'coef' => 3, 'credits' => 4],
                    ['code' => 'MKT-S3-02', 'name' => 'Marketing Digital',            'coef' => 3, 'credits' => 4],
                    ['code' => 'MKT-S3-03', 'name' => 'Communication Marketing',      'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S3-04', 'name' => 'Gestion de la Distribution',   'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S3-05', 'name' => 'Méthodes Quantitatives',       'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S3-06', 'name' => 'Langue Anglaise II',           'coef' => 1, 'credits' => 1],
                ],
                'S4' => [
                    ['code' => 'MKT-S4-01', 'name' => 'Marketing Stratégique',        'coef' => 3, 'credits' => 4],
                    ['code' => 'MKT-S4-02', 'name' => 'E-Commerce',                   'coef' => 3, 'credits' => 4],
                    ['code' => 'MKT-S4-03', 'name' => 'Publicité et Médias',          'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S4-04', 'name' => 'Gestion de Marque',            'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S4-05', 'name' => 'Marketing International',      'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S4-06', 'name' => 'Langue Française II',          'coef' => 1, 'credits' => 1],
                ],
                'S5' => [
                    ['code' => 'MKT-S5-01', 'name' => 'Marketing des Services',       'coef' => 3, 'credits' => 4],
                    ['code' => 'MKT-S5-02', 'name' => 'CRM et Fidélisation',          'coef' => 3, 'credits' => 4],
                    ['code' => 'MKT-S5-03', 'name' => 'Marketing B2B',                'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S5-04', 'name' => 'Neuromarketing',               'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S5-05', 'name' => 'Veille Concurrentielle',       'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S5-06', 'name' => 'Éthique des Affaires',         'coef' => 1, 'credits' => 1],
                ],
                'S6' => [
                    ['code' => 'MKT-S6-01', 'name' => 'Stratégie Commerciale',        'coef' => 3, 'credits' => 4],
                    ['code' => 'MKT-S6-02', 'name' => 'Marketing des Réseaux Sociaux','coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S6-03', 'name' => 'Analyse de Données Marketing', 'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S6-04', 'name' => 'Mémoire de Fin d\'Études',     'coef' => 3, 'credits' => 5],
                    ['code' => 'MKT-S6-05', 'name' => 'Stage Professionnel',          'coef' => 2, 'credits' => 3],
                    ['code' => 'MKT-S6-06', 'name' => 'Séminaire Entrepreneuriat',    'coef' => 1, 'credits' => 1],
                ],
            ],

            // ==========================================
            // LIC-MGT — Licence Management
            // ==========================================
            'LIC-MGT' => [
                'S1' => [
                    ['code' => 'MGT-S1-01', 'name' => 'Principes de Management',      'coef' => 3, 'credits' => 4],
                    ['code' => 'MGT-S1-02', 'name' => 'Économie Générale I',          'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S1-03', 'name' => 'Comptabilité Générale',        'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S1-04', 'name' => 'Droit des Organisations',      'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S1-05', 'name' => 'Mathématiques de Gestion',     'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S1-06', 'name' => 'Langue Française I',           'coef' => 1, 'credits' => 2],
                ],
                'S2' => [
                    ['code' => 'MGT-S2-01', 'name' => 'Management des Organisations', 'coef' => 3, 'credits' => 4],
                    ['code' => 'MGT-S2-02', 'name' => 'Gestion des Ressources Hum.',  'coef' => 3, 'credits' => 4],
                    ['code' => 'MGT-S2-03', 'name' => 'Statistiques de Gestion',      'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S2-04', 'name' => 'Marketing Fondamental',        'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S2-05', 'name' => 'Informatique de Bureau',       'coef' => 1, 'credits' => 2],
                    ['code' => 'MGT-S2-06', 'name' => 'Langue Anglaise I',            'coef' => 1, 'credits' => 2],
                ],
                'S3' => [
                    ['code' => 'MGT-S3-01', 'name' => 'Leadership et Motivation',     'coef' => 3, 'credits' => 4],
                    ['code' => 'MGT-S3-02', 'name' => 'Gestion de Projet',            'coef' => 3, 'credits' => 4],
                    ['code' => 'MGT-S3-03', 'name' => 'Comportement Organisationnel', 'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S3-04', 'name' => 'Finance pour Managers',        'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S3-05', 'name' => 'Gestion de la Qualité',        'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S3-06', 'name' => 'Langue Anglaise II',           'coef' => 1, 'credits' => 1],
                ],
                'S4' => [
                    ['code' => 'MGT-S4-01', 'name' => 'Management Stratégique',       'coef' => 3, 'credits' => 4],
                    ['code' => 'MGT-S4-02', 'name' => 'Gestion du Changement',        'coef' => 3, 'credits' => 4],
                    ['code' => 'MGT-S4-03', 'name' => 'Gestion des Opérations',       'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S4-04', 'name' => 'Entrepreneuriat',              'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S4-05', 'name' => 'Développement Durable',        'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S4-06', 'name' => 'Langue Française II',          'coef' => 1, 'credits' => 1],
                ],
                'S5' => [
                    ['code' => 'MGT-S5-01', 'name' => 'Management International',     'coef' => 3, 'credits' => 4],
                    ['code' => 'MGT-S5-02', 'name' => 'Innovation et Créativité',     'coef' => 3, 'credits' => 4],
                    ['code' => 'MGT-S5-03', 'name' => 'Gouvernance d\'Entreprise',    'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S5-04', 'name' => 'Négociation Professionnelle',  'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S5-05', 'name' => 'Management des Connaissances', 'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S5-06', 'name' => 'Éthique des Affaires',         'coef' => 1, 'credits' => 1],
                ],
                'S6' => [
                    ['code' => 'MGT-S6-01', 'name' => 'Stratégie des Entreprises',    'coef' => 3, 'credits' => 4],
                    ['code' => 'MGT-S6-02', 'name' => 'Management des PME',           'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S6-03', 'name' => 'Gestion des Crises',           'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S6-04', 'name' => 'Mémoire de Fin d\'Études',     'coef' => 3, 'credits' => 5],
                    ['code' => 'MGT-S6-05', 'name' => 'Stage Professionnel',          'coef' => 2, 'credits' => 3],
                    ['code' => 'MGT-S6-06', 'name' => 'Atelier de Recherche',         'coef' => 1, 'credits' => 1],
                ],
            ],

            // ==========================================
            // LIC-IG — Informatique de Gestion
            // ==========================================
            'LIC-IG' => [
                'S1' => [
                    ['code' => 'IG-S1-01', 'name' => 'Algorithmique et Prog. I',      'coef' => 3, 'credits' => 4],
                    ['code' => 'IG-S1-02', 'name' => 'Architecture des Ordinateurs',  'coef' => 2, 'credits' => 3],
                    ['code' => 'IG-S1-03', 'name' => 'Mathématiques Discrètes',       'coef' => 3, 'credits' => 4],
                    ['code' => 'IG-S1-04', 'name' => 'Gestion des Entreprises',       'coef' => 2, 'credits' => 3],
                    ['code' => 'IG-S1-05', 'name' => 'Bureautique Avancée',           'coef' => 1, 'credits' => 2],
                    ['code' => 'IG-S1-06', 'name' => 'Langue Française I',            'coef' => 1, 'credits' => 2],
                ],
                'S2' => [
                    ['code' => 'IG-S2-01', 'name' => 'Algorithmique et Prog. II',     'coef' => 3, 'credits' => 4],
                    ['code' => 'IG-S2-02', 'name' => 'Bases de Données I',            'coef' => 3, 'credits' => 4],
                    ['code' => 'IG-S2-03', 'name' => 'Réseaux Informatiques I',       'coef' => 2, 'credits' => 3],
                    ['code' => 'IG-S2-04', 'name' => 'Comptabilité Générale',         'coef' => 2, 'credits' => 3],
                    ['code' => 'IG-S2-05', 'name' => 'Systèmes d\'Exploitation',      'coef' => 2, 'credits' => 3],
                    ['code' => 'IG-S2-06', 'name' => 'Langue Anglaise I',             'coef' => 1, 'credits' => 1],
                ],
                'S3' => [
                    ['code' => 'IG-S3-01', 'name' => 'Programmation Orientée Objet',  'coef' => 3, 'credits' => 4],
                    ['code' => 'IG-S3-02', 'name' => 'Bases de Données II',           'coef' => 3, 'credits' => 4],
                    ['code' => 'IG-S3-03', 'name' => 'Développement Web',             'coef' => 3, 'credits' => 4],
                    ['code' => 'IG-S3-04', 'name' => 'Analyse et Conception (UML)',   'coef' => 2, 'credits' => 3],
                    ['code' => 'IG-S3-05', 'name' => 'Statistiques Informatiques',    'coef' => 1, 'credits' => 2],
                    ['code' => 'IG-S3-06', 'name' => 'Langue Anglaise II',            'coef' => 1, 'credits' => 1],
                ],
                'S4' => [
                    ['code' => 'IG-S4-01', 'name' => 'Développement Web Avancé',      'coef' => 3, 'credits' => 4],
                    ['code' => 'IG-S4-02', 'name' => 'Sécurité Informatique',         'coef' => 3, 'credits' => 4],
                    ['code' => 'IG-S4-03', 'name' => 'Systèmes d\'Information',       'coef' => 2, 'credits' => 3],
                    ['code' => 'IG-S4-04', 'name' => 'Réseaux Avancés',               'coef' => 2, 'credits' => 3],
                    ['code' => 'IG-S4-05', 'name' => 'Gestion de Projet SI',          'coef' => 2, 'credits' => 3],
                    ['code' => 'IG-S4-06', 'name' => 'Communication Professionnelle', 'coef' => 1, 'credits' => 1],
                ],
                'S5' => [
                    ['code' => 'IG-S5-01', 'name' => 'Applications Mobiles',          'coef' => 3, 'credits' => 4],
                    ['code' => 'IG-S5-02', 'name' => 'Big Data et Analytics',         'coef' => 3, 'credits' => 4],
                    ['code' => 'IG-S5-03', 'name' => 'Cloud Computing',               'coef' => 2, 'credits' => 3],
                    ['code' => 'IG-S5-04', 'name' => 'Intelligence Artificielle',     'coef' => 2, 'credits' => 3],
                    ['code' => 'IG-S5-05', 'name' => 'ERP et Progiciels de Gestion',  'coef' => 2, 'credits' => 3],
                    ['code' => 'IG-S5-06', 'name' => 'Éthique Numérique',             'coef' => 1, 'credits' => 1],
                ],
                'S6' => [
                    ['code' => 'IG-S6-01', 'name' => 'Projet de Développement',       'coef' => 3, 'credits' => 4],
                    ['code' => 'IG-S6-02', 'name' => 'DevOps et CI/CD',               'coef' => 3, 'credits' => 4],
                    ['code' => 'IG-S6-03', 'name' => 'Architecture Logicielle',       'coef' => 2, 'credits' => 3],
                    ['code' => 'IG-S6-04', 'name' => 'Mémoire de Fin d\'Études',      'coef' => 3, 'credits' => 5],
                    ['code' => 'IG-S6-05', 'name' => 'Stage Professionnel',           'coef' => 2, 'credits' => 3],
                    ['code' => 'IG-S6-06', 'name' => 'Veille Technologique',          'coef' => 1, 'credits' => 1],
                ],
            ],

            // ==========================================
            // LIC-SI — Systèmes d'Information
            // ==========================================
            'LIC-SI' => [
                'S1' => [
                    ['code' => 'SI-S1-01', 'name' => 'Introduction aux SI',           'coef' => 3, 'credits' => 4],
                    ['code' => 'SI-S1-02', 'name' => 'Algorithmique de Base',         'coef' => 3, 'credits' => 4],
                    ['code' => 'SI-S1-03', 'name' => 'Réseaux et Télécoms I',         'coef' => 2, 'credits' => 3],
                    ['code' => 'SI-S1-04', 'name' => 'Mathématiques pour l\'Info',    'coef' => 2, 'credits' => 3],
                    ['code' => 'SI-S1-05', 'name' => 'Gestion des Entreprises',       'coef' => 1, 'credits' => 2],
                    ['code' => 'SI-S1-06', 'name' => 'Langue Française I',            'coef' => 1, 'credits' => 2],
                ],
                'S2' => [
                    ['code' => 'SI-S2-01', 'name' => 'Programmation Python',          'coef' => 3, 'credits' => 4],
                    ['code' => 'SI-S2-02', 'name' => 'Bases de Données Relationnelles','coef' => 3, 'credits' => 4],
                    ['code' => 'SI-S2-03', 'name' => 'Réseaux et Télécoms II',        'coef' => 2, 'credits' => 3],
                    ['code' => 'SI-S2-04', 'name' => 'Systèmes d\'Exploitation Linux','coef' => 2, 'credits' => 3],
                    ['code' => 'SI-S2-05', 'name' => 'Comptabilité pour Ingénieurs',  'coef' => 1, 'credits' => 2],
                    ['code' => 'SI-S2-06', 'name' => 'Langue Anglaise I',             'coef' => 1, 'credits' => 2],
                ],
                'S3' => [
                    ['code' => 'SI-S3-01', 'name' => 'Sécurité des Systèmes',         'coef' => 3, 'credits' => 4],
                    ['code' => 'SI-S3-02', 'name' => 'Développement Web Full Stack',  'coef' => 3, 'credits' => 4],
                    ['code' => 'SI-S3-03', 'name' => 'Administration Système',        'coef' => 2, 'credits' => 3],
                    ['code' => 'SI-S3-04', 'name' => 'Méthodes Agiles',               'coef' => 2, 'credits' => 3],
                    ['code' => 'SI-S3-05', 'name' => 'Virtualisation',                'coef' => 2, 'credits' => 3],
                    ['code' => 'SI-S3-06', 'name' => 'Langue Anglaise II',            'coef' => 1, 'credits' => 1],
                ],
                'S4' => [
                    ['code' => 'SI-S4-01', 'name' => 'Cybersécurité',                 'coef' => 3, 'credits' => 4],
                    ['code' => 'SI-S4-02', 'name' => 'Architecture des SI',           'coef' => 3, 'credits' => 4],
                    ['code' => 'SI-S4-03', 'name' => 'Cloud et Virtualisation',       'coef' => 2, 'credits' => 3],
                    ['code' => 'SI-S4-04', 'name' => 'Bases de Données NoSQL',        'coef' => 2, 'credits' => 3],
                    ['code' => 'SI-S4-05', 'name' => 'API et Web Services',           'coef' => 2, 'credits' => 3],
                    ['code' => 'SI-S4-06', 'name' => 'Communication Technique',       'coef' => 1, 'credits' => 1],
                ],
                'S5' => [
                    ['code' => 'SI-S5-01', 'name' => 'Infrastructure Cloud',          'coef' => 3, 'credits' => 4],
                    ['code' => 'SI-S5-02', 'name' => 'DevSecOps',                     'coef' => 3, 'credits' => 4],
                    ['code' => 'SI-S5-03', 'name' => 'Analyse des Données',           'coef' => 2, 'credits' => 3],
                    ['code' => 'SI-S5-04', 'name' => 'Internet of Things',            'coef' => 2, 'credits' => 3],
                    ['code' => 'SI-S5-05', 'name' => 'Audit des SI',                  'coef' => 2, 'credits' => 3],
                    ['code' => 'SI-S5-06', 'name' => 'Éthique et RGPD',               'coef' => 1, 'credits' => 1],
                ],
                'S6' => [
                    ['code' => 'SI-S6-01', 'name' => 'Projet Intégré SI',             'coef' => 3, 'credits' => 4],
                    ['code' => 'SI-S6-02', 'name' => 'Gouvernance des SI',            'coef' => 3, 'credits' => 4],
                    ['code' => 'SI-S6-03', 'name' => 'Management des SI',             'coef' => 2, 'credits' => 3],
                    ['code' => 'SI-S6-04', 'name' => 'Mémoire de Fin d\'Études',      'coef' => 3, 'credits' => 5],
                    ['code' => 'SI-S6-05', 'name' => 'Stage Professionnel',           'coef' => 2, 'credits' => 3],
                    ['code' => 'SI-S6-06', 'name' => 'Conférence Métiers',            'coef' => 1, 'credits' => 1],
                ],
            ],
        ];

        $totalInserted = 0;

        foreach ($data as $filiereCode => $semestresData) {
            $filiere = DB::table('filieres')->where('code', $filiereCode)->first();
            if (!$filiere) {
                $this->command->error("❌ Filière {$filiereCode} introuvable !");
                continue;
            }

            foreach ($semestresData as $semestreCode => $modules) {
                $semestre = DB::table('semestres')
                    ->where('code', $semestreCode)
                    ->where('academic_year', '2024-2025')
                    ->first();

                if (!$semestre) {
                    $this->command->error("❌ Semestre {$semestreCode} introuvable !");
                    continue;
                }

                foreach ($modules as $module) {
                    DB::table('modules')->insertOrIgnore([
                        'filiere_id'  => $filiere->id,
                        'semestre_id' => $semestre->id,
                        'code'        => $module['code'],
                        'name'        => $module['name'],
                        'coefficient' => $module['coef'],
                        'credits'     => $module['credits'],
                        'is_active'   => true,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                    $totalInserted++;
                }
            }
        }

        $this->command->info("✅ Modules insérés : {$totalInserted} modules pour 6 filières × 6 semestres");
    }
}
