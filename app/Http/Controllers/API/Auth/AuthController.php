<?php
// app/Http/Controllers/API/Auth/AuthController.php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // ══════════════════════════════════════════════════════════════════
    // INSCRIPTION — Étape 1 : Vérifier matricule + email
    // POST /api/v1/auth/verify-identity
    // ══════════════════════════════════════════════════════════════════
    public function verifyPreloaded(Request $request): JsonResponse
    {
        $request->validate([
            'matricule' => 'required|string|max:20',
            'email'     => 'required|email|max:255',
        ]);

        $matricule = strtoupper(trim($request->matricule));
        $email     = strtolower(trim($request->email));

        // 1️⃣ Chercher dans students
        $student = Student::whereRaw(
            'UPPER(matricule) = ? AND LOWER(email) = ? AND deleted_at IS NULL',
            [$matricule, $email]
        )->first();

        // 2️⃣ Si pas trouvé, chercher dans students_preloaded
        if (! $student) {
            $preloaded = DB::table('students_preloaded')
                ->whereRaw('UPPER(matricule) = ?', [$matricule])
                ->whereRaw('LOWER(email) = ?',     [$email])
                ->where('is_registered', 0)
                ->first();

            if (! $preloaded) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun étudiant trouvé avec ce matricule et cet email. Contactez l\'administration.',
                ], 404);
            }

            // 3️⃣ Migrer vers students
            $studentId = DB::table('students')->insertGetId([
                'matricule'     => strtoupper($preloaded->matricule),
                'nni'           => $preloaded->nni           ?? null,
                'nom'           => $preloaded->nom           ?? '',
                'prenom'        => $preloaded->prenom        ?? '',
                'email'         => strtolower($preloaded->email),
                'filiere_id'    => $preloaded->filiere_id    ?? null,
                'niveau_id'     => $preloaded->niveau_id     ?? null,
                'academic_year' => $preloaded->academic_year ?? null,
                'preloaded_id'  => $preloaded->id,
                'status'        => 'active',
                'user_id'       => null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $student = Student::find($studentId);

            Log::info('[verifyPreloaded] Migré depuis students_preloaded', [
                'preloaded_id' => $preloaded->id,
                'student_id'   => $studentId,
            ]);
        }

        // Compte déjà créé
        if ($student->user_id && User::find($student->user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Un compte existe déjà. Veuillez vous connecter.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Identité vérifiée avec succès.',
            'data'    => [
                'student_id' => $student->id,
                'matricule'  => $student->matricule,
                'full_name'  => trim(($student->prenom ?? '') . ' ' . ($student->nom ?? '')) ?: 'Étudiant',
                'email'      => $student->email,
                'filiere'    => $student->filiere?->nom ?? null,
                'niveau'     => $student->niveau?->nom  ?? null,
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // ENVOYER OTP (inscription)
    // POST /api/v1/auth/send-otp
    // ══════════════════════════════════════════════════════════════════
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|integer',
            'email'      => 'required|email',
        ]);

        $student = Student::find($request->student_id);
        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable.',
            ], 404);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $student->email],
            [
                'token'      => Hash::make($code),
                'created_at' => now(),
            ]
        );

        try {
            Mail::raw(
                "Bonjour {$student->prenom},\n\n" .
                "Votre code de vérification ISCAE est :\n\n" .
                "  {$code}\n\n" .
                "Ce code expire dans 10 minutes.\n\n" .
                "Si vous n'avez pas demandé ce code, ignorez cet email.\n\n" .
                "— L'équipe ISCAE",
                function ($m) use ($student, $code) {
                    $m->to($student->email)
                      ->subject('🔐 Code de vérification ISCAE : ' . $code);
                }
            );

            Log::info('[Auth] sendOtp envoyé', [
                'student_id' => $student->id,
                'email'      => $student->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Code envoyé par email.',
            ]);

        } catch (\Throwable $e) {
            Log::error('[Auth] sendOtp ERREUR', [
                'error' => $e->getMessage(),
                'email' => $student->email,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur envoi email : ' . $e->getMessage(),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // INSCRIPTION — Étape 2 : Vérifier OTP
    // POST /api/v1/auth/verify-otp
    // ══════════════════════════════════════════════════════════════════
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|integer',
            'otp_code'   => 'required|string|size:6',
        ]);

        $student = Student::find($request->student_id);
        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable.',
            ], 404);
        }

        $otpRecord = DB::table('password_reset_tokens')
                       ->where('email', $student->email)
                       ->first();

        if (! $otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun code envoyé. Recommencez l\'étape 1.',
            ], 422);
        }

        if (now()->diffInMinutes($otpRecord->created_at) > 10) {
            DB::table('password_reset_tokens')->where('email', $student->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Code expiré. Recommencez l\'étape 1.',
            ], 422);
        }

        if (! Hash::check($request->otp_code, $otpRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Code incorrect.',
            ], 422);
        }

        DB::table('password_reset_tokens')->where('email', $student->email)->delete();

        Log::info('[Auth] verifyOtp OK', ['student_id' => $student->id]);

        return response()->json([
            'success' => true,
            'message' => 'Code vérifié. Choisissez votre mot de passe.',
            'data'    => [
                'student_id' => $student->id,
                'email'      => $student->email,
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // INSCRIPTION — Étape 3 : Créer le compte
    // POST /api/v1/auth/register
    // ══════════════════════════════════════════════════════════════════
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'student_id'            => 'required|integer',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $student = Student::find($request->student_id);
        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable.',
            ], 404);
        }

        if ($student->user_id && User::find($student->user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Un compte existe déjà. Connectez-vous.',
            ], 409);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'email'            => $student->email,
                'login_identifier' => $student->matricule,
                'password'         => Hash::make($request->password),
                'role'             => 'student',
                'is_active'        => true,
            ]);

            $student->update(['user_id' => $user->id]);

            DB::commit();

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('[Auth] register OK', [
                'user_id'    => $user->id,
                'student_id' => $student->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Compte créé avec succès.',
                'token'   => $token,
                'user'    => $this->buildUserPayload($user),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Auth] register error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du compte.',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // CONNEXION — avec reconnaissance d'appareil
    // POST /api/v1/auth/login
    // ══════════════════════════════════════════════════════════════════
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login'              => 'required|string|max:255',
            'password'           => 'required|string',
            'device_fingerprint' => 'nullable|string|max:64',
        ]);

        $loginValue = trim($request->input('login'));
        $deviceFp   = trim($request->input('device_fingerprint', ''));
        $ip         = $request->ip();
        $userAgent  = $request->userAgent() ?? '';

        // ── 1. Trouver l'utilisateur ──────────────────────────────────
        $user = User::where('login_identifier', $loginValue)
                    ->orWhere('email', $loginValue)
                    ->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects.',
            ], 401);
        }

        // ── 2. Vérifier le verrouillage avant le mot de passe ─────────
        if (! empty($user->locked_until) && now()->lt($user->locked_until)) {
            $minutes = (int) now()->diffInMinutes($user->locked_until);
            return response()->json([
                'success' => false,
                'message' => "Compte bloqué. Réessayez dans {$minutes} minute(s).",
            ], 423);
        }

        // ── 3. Vérifier le mot de passe ───────────────────────────────
        if (! Hash::check($request->input('password'), $user->password)) {
            $failCount = ($user->failed_login_count ?? 0) + 1;
            $updateFail = ['failed_login_count' => $failCount];

            if ($failCount >= 5) {
                $updateFail['locked_until'] = now()->addMinutes(15);
                $user->update($updateFail);
                return response()->json([
                    'success' => false,
                    'message' => 'Compte bloqué 15 min suite à trop de tentatives échouées.',
                ], 423);
            }

            $user->update($updateFail);
            $remaining = 5 - $failCount;
            return response()->json([
                'success' => false,
                'message' => "Identifiants incorrects. {$remaining} tentative(s) restante(s).",
            ], 401);
        }

        // ── 4. Vérifier si le compte est actif ────────────────────────
        if (isset($user->is_active) && ! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Compte désactivé. Contactez l\'administrateur.',
            ], 403);
        }

        // ── 5. Réinitialiser les tentatives échouées ──────────────────
        $updateData = [
            'failed_login_count' => 0,
            'locked_until'       => null,
        ];

        // ── 6. Vérifier si l'appareil est reconnu ────────────────────
        $isTrusted = false;

        if ($deviceFp) {
            $trusted = TrustedDevice::where('user_id', $user->id)
                ->where('device_fingerprint', $deviceFp)
                ->valid()
                ->first();

            if ($trusted) {
                $trusted->update([
                    'last_used_at' => now(),
                    'ip_address'   => $ip,
                    'expires_at'   => now()->addDays(30),
                ]);
                $isTrusted = true;
            }
        }

        // ── 7. Si appareil non reconnu → envoyer OTP ─────────────────
        if (! $isTrusted) {
            $code   = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $sendTo = $user->notification_email ?? $user->email;
            $deviceInfo = $this->parseUserAgent($userAgent);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($code), 'created_at' => now()]
            );

            try {
                Mail::raw(
                    "Bonjour,\n\n" .
                    "Une tentative de connexion a été détectée depuis un nouvel appareil :\n" .
                    "• Appareil : {$deviceInfo}\n" .
                    "• IP       : {$ip}\n\n" .
                    "Votre code de vérification : {$code}\n\n" .
                    "Ce code expire dans 10 minutes.\n\n" .
                    "Si ce n'est pas vous, ignorez ce message.",
                    fn($m) => $m->to($sendTo)
                                ->subject('🔐 Connexion nouvel appareil – ISCAE')
                );

                Log::info('[login] OTP envoyé (nouvel appareil)', [
                    'user_id' => $user->id,
                    'send_to' => $sendTo,
                    'ip'      => $ip,
                    'device'  => $deviceFp ?: 'inconnu',
                ]);
            } catch (\Exception $e) {
                Log::error('[login] Erreur envoi OTP', [
                    'error'   => $e->getMessage(),
                    'send_to' => $sendTo,
                ]);
            }

            return response()->json([
                'success'             => false,
                'requires_device_otp' => true,
                'message'             => 'Nouvel appareil détecté. Code OTP envoyé par email.',
                'data'                => [
                    'user_id'      => $user->id,
                    'masked_email' => $this->maskEmail($sendTo),
                ],
            ], 200);
        }

        // ── 8. Connexion directe (appareil reconnu) ───────────────────
        $updateData['last_login_at'] = now();
        $user->update($updateData);

        // 2FA activé ?
        if (! empty($user->two_factor_enabled)) {
            return response()->json([
                'success'      => true,
                'requires_2fa' => true,
                'user_id'      => $user->id,
                'login_type'   => $user->role,
            ], 200);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('[login] Connexion réussie (appareil reconnu)', [
            'user_id' => $user->id,
            'role'    => $user->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie.',
            'token'   => $token,
            'user'    => $this->buildUserPayload($user),
        ], 200);
    }

    // ══════════════════════════════════════════════════════════════════
    // VERIFY DEVICE OTP — Valider OTP + enregistrer l'appareil
    // POST /api/v1/auth/verify-device-otp
    // ══════════════════════════════════════════════════════════════════
    public function verifyDeviceOtp(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'            => 'required|integer',
            'otp_code'           => 'required|string|size:6',
            'device_fingerprint' => 'nullable|string|max:64',
            'device_name'        => 'nullable|string|max:100',
        ]);

        $user = User::find($request->user_id);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        // ── Vérifier OTP ──────────────────────────────────────────────
        $record = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => 'Code introuvable. Veuillez refaire la connexion.',
            ], 422);
        }

        if (now()->diffInMinutes($record->created_at) > 10) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Code expiré. Veuillez refaire la connexion.',
            ], 422);
        }

        if (! Hash::check($request->otp_code, $record->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Code incorrect.',
            ], 422);
        }

        // ── Supprimer OTP utilisé ─────────────────────────────────────
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        // ── Enregistrer l'appareil comme fiable ───────────────────────
        $deviceFp = trim($request->input('device_fingerprint', ''));
        if ($deviceFp) {
            TrustedDevice::updateOrCreate(
                [
                    'user_id'            => $user->id,
                    'device_fingerprint' => $deviceFp,
                ],
                [
                    'device_name'  => $request->input('device_name')
                                      ?? $this->parseUserAgent($request->userAgent() ?? ''),
                    'ip_address'   => $request->ip(),
                    'user_agent'   => $request->userAgent(),
                    'last_used_at' => now(),
                    'expires_at'   => now()->addDays(30),
                ]
            );

            Log::info('[verifyDeviceOtp] Appareil enregistré', [
                'user_id' => $user->id,
                'device'  => $deviceFp,
            ]);
        }

        // ── 2FA activé ? ──────────────────────────────────────────────
        if (! empty($user->two_factor_enabled)) {
            return response()->json([
                'success'      => true,
                'requires_2fa' => true,
                'user_id'      => $user->id,
                'login_type'   => $user->role,
            ], 200);
        }

        // ── Générer token de connexion ────────────────────────────────
        $user->update([
            'last_login_at'      => now(),
            'failed_login_count' => 0,
            'locked_until'       => null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('[verifyDeviceOtp] Connexion réussie', ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie. Appareil enregistré pour 30 jours.',
            'token'   => $token,
            'user'    => $this->buildUserPayload($user),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // RESEND DEVICE OTP — Renvoyer le code de vérification appareil
    // POST /api/v1/auth/resend-device-otp
    // ══════════════════════════════════════════════════════════════════
    public function resendDeviceOtp(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
        ]);

        $user = User::find($request->user_id);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        // ── Vérifier le cooldown (1 minute) ──────────────────────────
        $existing = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        if ($existing && now()->diffInSeconds($existing->created_at) < 60) {
            $wait = 60 - (int) now()->diffInSeconds($existing->created_at);
            return response()->json([
                'success' => false,
                'message' => "Attendez {$wait} seconde(s) avant de renvoyer.",
            ], 429);
        }

        // ── Générer et envoyer un nouveau code ────────────────────────
        $code   = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $sendTo = $user->notification_email ?? $user->email;

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($code), 'created_at' => now()]
        );

        try {
            Mail::raw(
                "Bonjour,\n\n" .
                "Votre nouveau code de vérification ISCAE : {$code}\n\n" .
                "Ce code expire dans 10 minutes.",
                fn($m) => $m->to($sendTo)
                            ->subject('🔐 Nouveau code de vérification – ISCAE')
            );

            Log::info('[resendDeviceOtp] Code renvoyé', [
                'user_id' => $user->id,
                'send_to' => $sendTo,
            ]);
        } catch (\Exception $e) {
            Log::error('[resendDeviceOtp] Erreur mail', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nouveau code envoyé par email.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // PROFIL CONNECTÉ
    // GET /api/v1/auth/me
    // ══════════════════════════════════════════════════════════════════
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié.',
            ], 401);
        }
        return response()->json([
            'success' => true,
            'data'    => $this->buildUserPayload($user),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // DÉCONNEXION
    // POST /api/v1/auth/logout
    // ══════════════════════════════════════════════════════════════════
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();
        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // 2FA — Vérifier le code
    // POST /api/v1/auth/2fa/verify
    // ══════════════════════════════════════════════════════════════════
    public function verify2FA(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'    => 'required|integer',
            'otp_code'   => 'required|string',
            'login_type' => 'nullable|string',
        ]);

        $user = User::find($request->user_id);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        $valid = $user->two_factor_secret === $request->otp_code;
        if (! $valid) {
            return response()->json([
                'success' => false,
                'message' => 'Code OTP invalide.',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->update(['last_login_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Authentification 2FA réussie.',
            'token'   => $token,
            'user'    => $this->buildUserPayload($user),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // RESEND OTP — 2FA classique
    // POST /api/v1/auth/2fa/resend
    // ══════════════════════════════════════════════════════════════════
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'    => 'required|integer',
            'login_type' => 'nullable|string',
        ]);

        $user = User::find($request->user_id);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        $code   = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $sendTo = $user->notification_email ?? $user->email;

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($code), 'created_at' => now()]
        );

        try {
            Mail::raw(
                "Votre code 2FA ISCAE : {$code}\n\nCe code expire dans 10 minutes.",
                fn($m) => $m->to($sendTo)->subject('Code 2FA – ISCAE')
            );
            Log::info('[resendOtp] Code 2FA envoyé', [
                'user_id' => $user->id,
                'send_to' => $sendTo,
            ]);
        } catch (\Exception $e) {
            Log::error('[resendOtp] Erreur mail', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Code renvoyé par email.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // MOT DE PASSE OUBLIÉ — Étape 1 : Envoyer OTP
    // POST /api/v1/auth/forgot-password
    // ══════════════════════════════════════════════════════════════════
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim($request->email));

        // ── 1. Chercher via email principal (admin ou étudiant) ───────
        $user = User::whereRaw('LOWER(email) = ?', [$email])
            ->where('is_active', true)
            ->first();

        // ── 2. Chercher via notification_email ────────────────────────
        if (! $user) {
            $user = User::whereRaw('LOWER(notification_email) = ?', [$email])
                ->where('is_active', true)
                ->first();
        }

        // ── 3. Chercher via la table students ─────────────────────────
        if (! $user) {
            $student = Student::whereRaw('LOWER(email) = ?', [$email])
                ->whereNotNull('user_id')
                ->first();
            if ($student) {
                $user = User::find($student->user_id);
            }
        }

        // ── 4. Email introuvable → message personnalisé ───────────────
        if (! $user) {
            return response()->json([
                'success'    => false,
                'message'    => 'Aucun étudiant trouvé avec ce email. Contactez l\'administration.',
                'error_code' => 'EMAIL_NOT_FOUND',
            ], 404);
        }

        // ── 5. Générer et envoyer l'OTP ───────────────────────────────
        $code   = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $sendTo = $user->notification_email ?? $user->email;

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($code), 'created_at' => now()]
        );

        try {
            Mail::raw(
                "Bonjour,\n\n" .
                "Votre code de réinitialisation ISCAE : {$code}\n\n" .
                "Ce code expire dans 10 minutes.\n\n" .
                "Si vous n'avez pas demandé cette réinitialisation, ignorez ce message.",
                fn($m) => $m->to($sendTo)
                            ->subject('🔑 Code de réinitialisation – ISCAE')
            );
            Log::info('[forgotPassword] OTP envoyé', [
                'email'   => $user->email,
                'send_to' => $sendTo,
                'role'    => $user->role,
            ]);
        } catch (\Exception $e) {
            Log::error('[forgotPassword] Erreur mail', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Code envoyé par email.',
            'data'    => [
                'masked_email' => $this->maskEmail($sendTo),
                'user_id'      => $user->id,
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // MOT DE PASSE OUBLIÉ — Étape 2 : Vérifier OTP
    // POST /api/v1/auth/forgot-password/verify-otp
    // ══════════════════════════════════════════════════════════════════
    public function forgotVerifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'  => 'required|integer',
            'otp_code' => 'required|string|size:6',
        ]);

        $user = User::find($request->user_id);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => 'Code introuvable. Veuillez refaire la demande.',
            ], 422);
        }

        if (now()->diffInMinutes($record->created_at) > 10) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Code expiré. Veuillez refaire la demande.',
            ], 422);
        }

        if (! Hash::check($request->otp_code, $record->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Code incorrect.',
            ], 422);
        }

        // ── Générer un reset_token temporaire (valable 15 min) ────────
        $resetToken = Str::random(64);
        DB::table('password_reset_tokens')->where('email', $user->email)->update([
            'token'      => Hash::make($resetToken),
            'created_at' => now(),
        ]);

        Log::info('[forgotVerifyOtp] OTP validé', ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'Code vérifié avec succès.',
            'data'    => ['reset_token' => $resetToken],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // MOT DE PASSE OUBLIÉ — Étape 3 : Nouveau mot de passe
    // POST /api/v1/auth/reset-password
    // ══════════════════════════════════════════════════════════════════
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'reset_token'           => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $record     = null;
        $targetUser = null;

        // ── Parcourir les tokens pour trouver le bon ──────────────────
        $allTokens = DB::table('password_reset_tokens')->get();
        foreach ($allTokens as $row) {
            if (Hash::check($request->reset_token, $row->token)) {
                $record     = $row;
                $targetUser = User::where('email', $row->email)->first();
                break;
            }
        }

        if (! $record || ! $targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré.',
            ], 422);
        }

        if (now()->diffInMinutes($record->created_at) > 15) {
            DB::table('password_reset_tokens')->where('email', $record->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Token expiré. Veuillez recommencer.',
            ], 422);
        }

        // ── Mettre à jour le mot de passe ─────────────────────────────
        $targetUser->update(['password' => Hash::make($request->password)]);

        // ── Révoquer tous les tokens Sanctum ──────────────────────────
        $targetUser->tokens()->delete();

        // ── Supprimer le reset token ──────────────────────────────────
        DB::table('password_reset_tokens')->where('email', $record->email)->delete();

        Log::info('[resetPassword] Mot de passe réinitialisé', [
            'user_id' => $targetUser->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPER PRIVÉ — Construire le payload user
    // ══════════════════════════════════════════════════════════════════
    private function buildUserPayload(User $user): array
    {
        $studentData = null;

        if ($user->role === 'student') {
            $student = Student::with(['filiere', 'niveau'])
                              ->where('user_id', $user->id)
                              ->first();

            if ($student) {
                $photoUrl = null;
                if ($student->photo_path) {
                    $photoUrl = Storage::url($student->photo_path);
                }

                $studentData = [
                    'id'            => $student->id,
                    'matricule'     => $student->matricule,
                    'nom'           => $student->nom,
                    'prenom'        => $student->prenom,
                    'full_name'     => trim(($student->prenom ?? '') . ' ' . ($student->nom ?? '')) ?: null,
                    'filiere_id'    => $student->filiere_id,
                    'filiere'       => $student->filiere?->nom ?? null,
                    'niveau_id'     => $student->niveau_id,
                    'niveau'        => $student->niveau?->nom  ?? null,
                    'academic_year' => $student->academic_year,
                    'photo_path'    => $student->photo_path,
                    'photo_url'     => $photoUrl,
                    'status'        => $student->status ?? 'active',
                ];
            }
        }

        return array_filter([
            'id'                 => $user->id,
            'email'              => $user->email,
            'notification_email' => $user->notification_email ?? null,
            'login_identifier'   => $user->login_identifier,
            'role'               => $user->role,
            'is_active'          => $user->is_active ?? true,
            'last_login_at'      => $user->last_login_at,
            'student'            => $studentData,
        ], fn($v) => $v !== null);
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPER PRIVÉ — Masquer email
    // ══════════════════════════════════════════════════════════════════
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;
        $visible = strlen($parts[0]) > 3
            ? substr($parts[0], 0, 3)
            : substr($parts[0], 0, 1);
        return $visible . '***@' . $parts[1];
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPER PRIVÉ — Parser le User-Agent
    // ══════════════════════════════════════════════════════════════════
    private function parseUserAgent(string $ua): string
    {
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'iOS';
        if (str_contains($ua, 'Android'))   return 'Android';
        if (str_contains($ua, 'Windows'))   return 'Windows';
        if (str_contains($ua, 'Macintosh')) return 'macOS';
        if (str_contains($ua, 'Linux'))     return 'Linux';
        return 'Navigateur inconnu';
    }
}
