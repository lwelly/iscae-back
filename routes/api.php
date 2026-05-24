<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\Auth\AuthController;

// ── Student controllers ────────────────────────────────────────────────
use App\Http\Controllers\API\Student\DashboardController    as StudentDashboard;
use App\Http\Controllers\API\Student\NoteController         as StudentNote;
use App\Http\Controllers\API\Student\ReclamationController  as StudentReclamation;
use App\Http\Controllers\API\Student\NotificationController as StudentNotification;
use App\Http\Controllers\API\Student\ProfileController      as StudentProfile;
use App\Http\Controllers\API\Student\ModuleController       as StudentModule;
use App\Http\Controllers\API\Student\DocumentController     as StudentDocument;

// ── Admin controllers ──────────────────────────────────────────────────
use App\Http\Controllers\API\Admin\DashboardController    as AdminDashboard;
use App\Http\Controllers\API\Admin\StudentController      as AdminStudent;
use App\Http\Controllers\API\Admin\NoteController         as AdminNote;
use App\Http\Controllers\API\Admin\ReclamationController  as AdminReclamation;
use App\Http\Controllers\API\Admin\SemestreController     as AdminSemestre;
use App\Http\Controllers\API\Admin\DocumentController     as AdminDocument;
use App\Http\Controllers\API\Admin\NotificationController as AdminNotification;
use App\Http\Controllers\API\Admin\SettingController      as AdminSetting;
use App\Http\Controllers\API\Admin\ProfileController      as AdminProfile;

Route::prefix('v1')->group(function () {

    // ══════════════════════════════════════════════════════════════════
    // AUTH — Routes publiques
    // ══════════════════════════════════════════════════════════════════
    Route::prefix('auth')->group(function () {

        // ── Inscription (3 étapes) ─────────────────────────────────
        Route::post('verify-identity', [AuthController::class, 'verifyPreloaded']); // Étape 1
        Route::post('send-otp',        [AuthController::class, 'sendOtp']);         // Envoi OTP
        Route::post('verify-otp',      [AuthController::class, 'verifyOtp']);       // Étape 2
        Route::post('register',        [AuthController::class, 'register']);        // Étape 3

        // ── Connexion ──────────────────────────────────────────────
        Route::post('login', [AuthController::class, 'login']);

        // ── Reconnaissance d'appareil (Device OTP) ────────────────
        Route::post('verify-device-otp', [AuthController::class, 'verifyDeviceOtp']);
        Route::post('resend-device-otp', [AuthController::class, 'resendDeviceOtp']);

        // ── 2FA ────────────────────────────────────────────────────
        Route::prefix('2fa')->group(function () {
            Route::post('verify', [AuthController::class, 'verify2FA']);
            Route::post('resend', [AuthController::class, 'resendOtp']);
        });

        // ── Mot de passe oublié (3 étapes) ────────────────────────
        Route::post('forgot-password',            [AuthController::class, 'forgotPassword']);
        Route::post('forgot-password/verify-otp', [AuthController::class, 'forgotVerifyOtp']);
        Route::post('reset-password',             [AuthController::class, 'resetPassword']);

        // ── Routes protégées ───────────────────────────────────────
        Route::middleware('auth:sanctum')->group(function () {
            Route::get ('me',     [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    // ══════════════════════════════════════════════════════════════════
    // ADMIN
    // ══════════════════════════════════════════════════════════════════
    Route::prefix('admin')
        ->middleware(['auth:sanctum', 'role.check:admin'])
        ->name('api.admin.')
        ->group(function () {

        // ── Dashboard ──────────────────────────────────────────────
        Route::get('dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

        // ── Dashboard stats (KPI + charts) ────────────────────────
        Route::get('dashboard/stats', function () {

            $byStatus = DB::table('reclamations')
                ->whereNull('deleted_at')
                ->selectRaw('status, COUNT(*) as cnt')
                ->groupBy('status')
                ->get()
                ->mapWithKeys(fn($r) => [$r->status => (int) $r->cnt]);

            $byType = DB::table('reclamations')
                ->whereNull('deleted_at')
                ->selectRaw('type, COUNT(*) as cnt')
                ->groupBy('type')
                ->get()
                ->mapWithKeys(fn($r) => [$r->type => (int) $r->cnt]);

            $monthly = DB::table('reclamations')
                ->whereNull('deleted_at')
                ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt")
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(fn($r) => [
                    'month' => $r->month,
                    'count' => (int) $r->cnt,
                ]);

            $thisMonth = DB::table('reclamations')
                ->whereNull('deleted_at')
                ->whereYear('created_at',  now()->year)
                ->whereMonth('created_at', now()->month)
                ->count();

            $avgResolution = DB::table('reclamations')
                ->whereNull('deleted_at')
                ->whereNotNull('resolved_at')
                ->selectRaw('AVG(DATEDIFF(resolved_at, created_at)) as avg_days')
                ->value('avg_days');

            $total = (int) $byStatus->sum();

            return response()->json([
                'success' => true,
                'data'    => [
                    'total'          => $total,
                    'pending'        => (int)(($byStatus['submitted'] ?? 0) + ($byStatus['received']  ?? 0)),
                    'in_progress'    => (int)(($byStatus['in_review']  ?? 0) + ($byStatus['escalated'] ?? 0)),
                    'resolved'       => (int)($byStatus['resolved'] ?? 0),
                    'rejected'       => (int)($byStatus['rejected']  ?? 0),
                    'this_month'     => (int) $thisMonth,
                    'avg_resolution' => $avgResolution ? round((float) $avgResolution, 1) : null,
                    'by_status'      => $byStatus,
                    'by_type'        => $byType,
                    'monthly'        => $monthly->values(),
                ],
            ]);

        })->name('dashboard.stats');

        // ── Réclamations ───────────────────────────────────────────
        Route::get ('reclamations',               [AdminReclamation::class, 'index']          )->name('reclamations.index');
        Route::get ('reclamations/{id}',          [AdminReclamation::class, 'show']           )->name('reclamations.show');
        Route::put ('reclamations/{id}/status',   [AdminReclamation::class, 'updateStatus']   )->name('reclamations.update-status');
        Route::post('reclamations/{id}/escalate', [AdminReclamation::class, 'escalate']       )->name('reclamations.escalate');
        Route::post('reclamations/{id}/meeting',  [AdminReclamation::class, 'scheduleMeeting'])->name('reclamations.meeting');

        // ── Semestres ──────────────────────────────────────────────
        Route::get   ('semestres',                        [AdminSemestre::class, 'index']           )->name('semestres.index');
        Route::post  ('semestres',                        [AdminSemestre::class, 'store']           )->name('semestres.store');
        Route::put   ('semestres/{id}',                   [AdminSemestre::class, 'update']          )->name('semestres.update');
        Route::put   ('semestres/{id}/toggle',            [AdminSemestre::class, 'toggle']          )->name('semestres.toggle');
        Route::put   ('semestres/{id}/toggle-exam',       [AdminSemestre::class, 'toggleExam']      )->name('semestres.toggle-exam');
        Route::put   ('semestres/{id}/toggle-rattrapage', [AdminSemestre::class, 'toggleRattrapage'])->name('semestres.toggle-rattrapage');
        Route::delete('semestres/{id}',                   [AdminSemestre::class, 'destroy']         )->name('semestres.destroy');

        // ── Modules ────────────────────────────────────────────────
        Route::get   ('modules',      [AdminSemestre::class, 'modulesIndex'] )->name('modules.index');
        Route::post  ('modules',      [AdminSemestre::class, 'moduleStore']  )->name('modules.store');
        Route::put   ('modules/{id}', [AdminSemestre::class, 'moduleUpdate'] )->name('modules.update');
        Route::delete('modules/{id}', [AdminSemestre::class, 'moduleDestroy'])->name('modules.destroy');

        // ── Étudiants ──────────────────────────────────────────────
        Route::get   ('students',             [AdminStudent::class, 'index']       )->name('students.index');
        Route::post  ('students',             [AdminStudent::class, 'store']       )->name('students.store');
        Route::get   ('students/{id}',        [AdminStudent::class, 'show']        )->name('students.show');
        Route::put   ('students/{id}',        [AdminStudent::class, 'update']      )->name('students.update');
        Route::put   ('students/{id}/status', [AdminStudent::class, 'updateStatus'])->name('students.update-status');
        Route::delete('students/{id}',        [AdminStudent::class, 'destroy']     )->name('students.destroy');

        // ── Notes ──────────────────────────────────────────────────
        Route::get('notes',      [AdminNote::class, 'index'])->name('notes.index');
        Route::get('notes/{id}', [AdminNote::class, 'show'] )->name('notes.show');

        // ── Documents ──────────────────────────────────────────────
        Route::get   ('documents',      [AdminDocument::class, 'index']  )->name('documents.index');
        Route::get   ('documents/{id}', [AdminDocument::class, 'show']   )->name('documents.show');
        Route::post  ('documents',      [AdminDocument::class, 'store']  )->name('documents.store');
        Route::delete('documents/{id}', [AdminDocument::class, 'destroy'])->name('documents.destroy');

        // ── Notifications ──────────────────────────────────────────
        Route::get ('notifications',           [AdminNotification::class, 'index']     )->name('notifications.index');
        Route::post('notifications',           [AdminNotification::class, 'store']     )->name('notifications.store');
        Route::put ('notifications/{id}/read', [AdminNotification::class, 'markAsRead'])->name('notifications.read');

        // ── Paramètres ─────────────────────────────────────────────
        Route::get('settings', [AdminSetting::class, 'index'] )->name('settings.index');
        Route::put('settings', [AdminSetting::class, 'update'])->name('settings.update');

        // ── Profil admin ───────────────────────────────────────────
        Route::get ('profile',          [AdminProfile::class, 'show']          )->name('profile.show');
        Route::put ('profile',          [AdminProfile::class, 'update']        )->name('profile.update');
        Route::post('profile/photo',    [AdminProfile::class, 'updatePhoto']   )->name('profile.photo');
        Route::put ('profile/password', [AdminProfile::class, 'updatePassword'])->name('profile.password');

        // ── Niveaux ────────────────────────────────────────────────
        Route::get('niveaux', function () {
            $niveaux = DB::table('niveaux')->orderBy('order_index')->get();
            return response()->json(['success' => true, 'data' => $niveaux]);
        })->name('niveaux.index');

        // ── Filieres ───────────────────────────────────────────────
        Route::get('filieres', function () {
            $filieres = DB::table('filieres')->orderBy('nom')->get();
            return response()->json(['success' => true, 'data' => $filieres]);
        })->name('filieres.index');

    });

    // ══════════════════════════════════════════════════════════════════
    // STUDENT
    // ══════════════════════════════════════════════════════════════════
    Route::prefix('student')
        ->middleware(['auth:sanctum', 'role.check:student'])
        ->name('api.student.')
        ->group(function () {

        // ── Dashboard ──────────────────────────────────────────────
        Route::get('dashboard', [StudentDashboard::class, 'index'])->name('dashboard');

        // ── Semestres filtrés par niveau étudiant ──────────────────
        // L1 → S1, S2 | L2 → S1, S2, S3, S4 | L3 → S3, S4, S5, S6
        Route::get('semestres', function (\Illuminate\Http\Request $request) {

            // Mapping niveau → codes semestres autorisés
            $niveauSemestres = [
                'L1' => ['S1', 'S2'],
                'L2' => ['S1', 'S2', 'S3', 'S4'],
                'L3' => ['S3', 'S4', 'S5', 'S6'],
            ];

            $user = $request->user();

            // Récupérer l'étudiant connecté
            $student = DB::table('students')->where('user_id', $user->id)->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil étudiant introuvable.',
                ], 404);
            }

            // Récupérer le niveau de l'étudiant
            $niveau = DB::table('niveaux')->where('id', $student->niveau_id)->first();

            if (!$niveau) {
                return response()->json([
                    'success' => false,
                    'message' => 'Niveau introuvable.',
                ], 404);
            }

            $niveauCode   = strtoupper(trim($niveau->code)); // "L1", "L2", "L3"
            $allowedCodes = $niveauSemestres[$niveauCode] ?? [];

            // Aucune configuration pour ce niveau
            if (empty($allowedCodes)) {
                return response()->json([
                    'success' => true,
                    'data'    => [],
                    'niveau'  => $niveauCode,
                    'message' => "Aucun semestre configuré pour le niveau {$niveauCode}.",
                ]);
            }

            // Récupérer uniquement les semestres ouverts ET autorisés pour ce niveau
            $semestres = DB::table('semestres')
                ->whereIn('code', $allowedCodes)
                ->where(function ($q) {
                    $q->where('is_open',             true)
                      ->orWhere('is_exam_open',       true)
                      ->orWhere('is_rattrapage_open', true);
                })
                ->select(
                    'id', 'code', 'label', 'academic_year', 'order_index',
                    'is_open', 'is_exam_open', 'is_rattrapage_open',
                    'open_at', 'close_at',
                    'exam_open_at', 'exam_close_at',
                    'rattrapage_open_at', 'rattrapage_close_at',
                    'created_at'
                )
                ->orderBy('order_index')
                ->get()
                ->map(function ($s) {
                    $types = [];
                    if ($s->is_open)            $types[] = 'cc';
                    if ($s->is_exam_open)       $types[] = 'examen';
                    if ($s->is_rattrapage_open) $types[] = 'rattrapage';

                    return [
                        'id'                 => $s->id,
                        'code'               => $s->code,
                        'label'              => $s->label,
                        'academic_year'      => $s->academic_year,
                        'is_open'            => (bool) $s->is_open,
                        'is_exam_open'       => (bool) $s->is_exam_open,
                        'is_rattrapage_open' => (bool) $s->is_rattrapage_open,
                        'available_types'    => $types,
                        'open_at'            => $s->open_at,
                        'close_at'           => $s->close_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data'    => $semestres,
                'niveau'  => $niveauCode,
            ]);

        })->name('semestres.index');

        // ── Notes ──────────────────────────────────────────────────
        Route::get('notes',      [StudentNote::class, 'index'])->name('notes.index');
        Route::get('notes/{id}', [StudentNote::class, 'show'] )->name('notes.show');

        // ── Réclamations ───────────────────────────────────────────
        Route::get ('reclamations',      [StudentReclamation::class, 'index'] )->name('reclamations.index');
        Route::post('reclamations',      [StudentReclamation::class, 'store'] )->name('reclamations.store');
        Route::get ('reclamations/{id}', [StudentReclamation::class, 'show']  )->name('reclamations.show');
        Route::put ('reclamations/{id}', [StudentReclamation::class, 'update'])->name('reclamations.update');

        // ── Notifications (ordre critique : statiques avant {id}) ──
        Route::get   ('notifications',           [StudentNotification::class, 'index']      )->name('notifications.index');
        Route::get   ('notifications/counts',    [StudentNotification::class, 'counts']     )->name('notifications.counts');
        Route::put   ('notifications/read-all',  [StudentNotification::class, 'markAllRead'])->name('notifications.read-all');
        Route::put   ('notifications/{id}/read', [StudentNotification::class, 'markAsRead'] )->name('notifications.read');
        Route::delete('notifications/{id}',      [StudentNotification::class, 'destroy']    )->name('notifications.destroy');

        // ── Profil étudiant ────────────────────────────────────────
        Route::get ('profile',          [StudentProfile::class, 'show']          )->name('profile.show');
        Route::put ('profile',          [StudentProfile::class, 'update']        )->name('profile.update');
        Route::post('profile/photo',    [StudentProfile::class, 'updatePhoto']   )->name('profile.photo');
        Route::put ('profile/password', [StudentProfile::class, 'updatePassword'])->name('profile.password');

        // ── Modules ────────────────────────────────────────────────
        Route::get('modules', [StudentModule::class, 'index'])->name('modules.index');

        // ── Documents ──────────────────────────────────────────────
        Route::get('documents',      [StudentDocument::class, 'index'])->name('documents.index');
        Route::get('documents/{id}', [StudentDocument::class, 'show'] )->name('documents.show');

    });

});
