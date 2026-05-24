<?php

namespace App\Services;

use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthService
{
    // ═══════════════════════════════════════════════════════════════════════════
    // LOGIN
    // ═══════════════════════════════════════════════════════════════════════════
    public function login(string $login, string $password, string $ip, string $userAgent, ?string $deviceFingerprint = null): array
{
    $user = User::where('login_identifier', $login)->first();
    if (!$user) {
        $student = DB::table('students')->where('matricule', $login)->first();
        $user = $student ? User::find($student->user_id) : null;
    }
    if (!$user || !Hash::check($password, $user->password)) {
        return ['success' => false, 'message' => 'Identifiants incorrects.'];
    }
    if (!$user->is_active) {
        return ['success' => false, 'message' => 'Compte désactivé.'];
    }

    // ✅ Vérifier si l'appareil est de confiance
    if ($deviceFingerprint) {
        $trustedDevice = DB::table('user_devices')
            ->where('user_id', $user->id)
            ->where('device_fingerprint', $deviceFingerprint)
            ->where('is_trusted', true)
            ->where(function($q) {
                $q->whereNull('trusted_until')
                  ->orWhere('trusted_until', '>', now());
            })
            ->first();

        if ($trustedDevice) {
            // Appareil de confiance → pas d'OTP, connexion directe
            DB::table('user_devices')
                ->where('id', $trustedDevice->id)
                ->update(['last_seen_at' => now(), 'ip_address' => $ip]);

            $tokenName = $user->role === 'admin' ? 'admin-token' : 'student-token';
            $token = $user->createToken($tokenName)->plainTextToken;

            $user->update(['last_login_at' => now(), 'last_login_ip' => $ip]);

            return [
                'success'      => true,
                'message'      => 'Connexion réussie.',
                'data'         => [
                    'requires_2fa' => false,
                    'token'        => $token,
                    'user'         => [
                        'id'   => $user->id,
                        'name' => $user->name ?? $login,
                        'role' => $user->role,
                        'email'=> $user->email,
                    ],
                ],
            ];
        }
    }

    // Nouvel appareil → envoyer OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    Cache::put("2fa_{$user->id}", $otp, now()->addMinutes(10));
    $this->sendOtpEmail($user->email, $otp, 'Connexion');

    return [
        'success' => true,
        'message' => 'Code OTP envoyé.',
        'data'    => [
            'requires_2fa' => true,
            'user_id'      => $user->id,
            'login_type'   => $user->role,
        ],
    ];
}

    // ═══════════════════════════════════════════════════════════════════════════
    // REGISTER – ÉTAPE 1 : Vérifier matricule + email
    // ═══════════════════════════════════════════════════════════════════════════
    public function verifyPreloaded(string $matricule, string $email): array
    {
        $preloaded = DB::table('students_preloaded')
            ->where('matricule', $matricule)
            ->where('email', $email)
            ->first();

        if (!$preloaded) {
            return [
                'success' => false,
                'message' => 'Matricule ou adresse e-mail introuvable. Vérifiez vos informations ou contactez l\'administration.',
            ];
        }

        if ($preloaded->is_registered) {
            return [
                'success' => false,
                'message' => 'Ce compte est déjà enregistré. Connectez-vous directement.',
            ];
        }

        // Récupérer filière et niveau via les codes
        $filiere = DB::table('filieres')->where('id', $preloaded->filiere_id)->first();
        $niveau  = DB::table('niveaux')->where('id', $preloaded->niveau_id)->first();

        return [
            'success' => true,
            'message' => 'Étudiant trouvé.',
            'data'    => [
                'preloaded_id' => $preloaded->id,
                'name'         => $preloaded->prenom . ' ' . $preloaded->nom,
                'masked_email' => $this->maskEmail($email),
                'filiere'      => $filiere?->name,
                'filiere_code' => $filiere?->code ?? 'N/A',
                'niveau'       => $niveau?->label,
                'niveau_code'  => $niveau?->code  ?? 'N/A',
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // REGISTER – ÉTAPE 2 : Envoyer l'OTP
    // ═══════════════════════════════════════════════════════════════════════════
    public function sendRegistrationOtp(int $preloadedId): array
    {
        $preloaded = DB::table('students_preloaded')->where('id', $preloadedId)->first();

        if (!$preloaded) {
            return ['success' => false, 'message' => 'Étudiant préchargé introuvable.'];
        }

        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put("register_otp_{$preloadedId}", $otp, now()->addMinutes(15));
        $this->sendOtpEmail($preloaded->email, $otp, 'Inscription');

        return [
            'success' => true,
            'message' => 'Code OTP envoyé à ' . $this->maskEmail($preloaded->email),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // REGISTER – ÉTAPE 3 : Vérifier l'OTP
    // ═══════════════════════════════════════════════════════════════════════════
    public function verifyRegistrationOtp(int $preloadedId, string $otpCode): array
{
    $cached = Cache::get("register_otp_{$preloadedId}");

    if (!$cached || (string)$cached !== (string)$otpCode) {
        return ['success' => false, 'message' => 'Code OTP invalide ou expiré.'];
    }

    Cache::forget("register_otp_{$preloadedId}");

    $token = Str::random(64);
    Cache::put("register_token_{$token}", $preloadedId, now()->addMinutes(30));

    return [
        'success' => true,
        'message' => 'OTP vérifié.',
        'data'    => ['registration_token' => $token],
    ];
}


    // ═══════════════════════════════════════════════════════════════════════════
    // REGISTER – ÉTAPE 4 : Définir le mot de passe
    // ═══════════════════════════════════════════════════════════════════════════
    public function setPassword(string $token, string $password, string $ip, string $userAgent): array
    {
        $preloadedId = Cache::get("register_token_{$token}");

        if (!$preloadedId) {
            return ['success' => false, 'message' => 'Token d\'inscription invalide ou expiré.'];
        }

        $preloaded = DB::table('students_preloaded')->where('id', $preloadedId)->first();

        if (!$preloaded || $preloaded->is_registered) {
            return ['success' => false, 'message' => 'Inscription impossible.'];
        }

        // Récupérer filière et niveau via les codes
        $filiere = DB::table('filieres')->where('id', $preloaded->filiere_id)->first();
        $niveau  = DB::table('niveaux')->where('id', $preloaded->niveau_id)->first();

        if (!$filiere || !$niveau) {
            return ['success' => false, 'message' => 'Filière ou niveau introuvable. Contactez l\'administration.'];
        }

        DB::beginTransaction();
try {
    // ✅ Créer l'utilisateur avec login_identifier
    $user = User::create([
        'login_identifier' => $preloaded->matricule,
        'email'            => $preloaded->email,
        'password'         => Hash::make($password),
        'role'             => 'student',
        'is_active'        => true,
        'is_verified'      => true,
    ]);

    // ✅ Créer l'étudiant avec toutes les colonnes
    DB::table('students')->insert([
        'user_id'       => $user->id,
        'preloaded_id'  => $preloaded->id,
        'matricule'     => $preloaded->matricule,
        'nni'           => $preloaded->nni,
        'nom'           => $preloaded->nom,
        'prenom'        => $preloaded->prenom,
        'email'         => $preloaded->email,
        'filiere_id'    => $filiere->id,
        'niveau_id'     => $niveau->id,
        'academic_year' => $preloaded->academic_year ?? date('Y') . '-' . (date('Y') + 1),
        'status'        => 'active',
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);


            // Marquer comme inscrit
            DB::table('students_preloaded')
                ->where('id', $preloadedId)
                ->update(['is_registered' => true, 'registered_at' => now()]);

            Cache::forget("register_token_{$token}");

            // Générer le token d'accès
            $accessToken = $user->createToken('student-token')->plainTextToken;

            DB::commit();

            return [
                'success' => true,
                'message' => 'Compte créé avec succès.',
                'data'    => [
                    'token'   => $accessToken,
                    'user'    => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role],
                    'student' => ['matricule' => $preloaded->matricule],
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 2FA
    // ═══════════════════════════════════════════════════════════════════════════
   
public function verify2FA(int $userId, string $otp, string $loginType, string $ip, string $userAgent, ?string $deviceFingerprint = null, bool $trustDevice = false): array
{
    $cached = Cache::get("2fa_{$userId}");
    if (!$cached || (string)$cached !== (string)$otp) {
        return ['success' => false, 'message' => 'Code OTP invalide ou expiré.'];
    }
    Cache::forget("2fa_{$userId}");

    $user = User::find($userId);
    if (!$user) {
        return ['success' => false, 'message' => 'Utilisateur introuvable.'];
    }

    // ✅ Enregistrer l'appareil comme de confiance
    if ($deviceFingerprint) {
        $existingDevice = DB::table('user_devices')
            ->where('user_id', $userId)
            ->where('device_fingerprint', $deviceFingerprint)
            ->first();

        $deviceData = [
            'user_id'            => $userId,
            'device_fingerprint' => $deviceFingerprint,
            'device_name'        => $this->parseDeviceName($userAgent),
            'browser'            => $this->parseBrowser($userAgent),
            'os'                 => $this->parseOS($userAgent),
            'ip_address'         => $ip,
            'is_trusted'         => true,
            'trusted_at'         => now(),
            'trusted_until'      => now()->addDays(30),
            'last_seen_at'       => now(),
            'updated_at'         => now(),
        ];

        if ($existingDevice) {
            DB::table('user_devices')->where('id', $existingDevice->id)->update($deviceData);
        } else {
            DB::table('user_devices')->insert(array_merge($deviceData, ['created_at' => now()]));
        }
    }

    $tokenName = $loginType === 'admin' ? 'admin-token' : 'student-token';
    $token = $user->createToken($tokenName)->plainTextToken;
    $user->update(['last_login_at' => now(), 'last_login_ip' => $ip]);

    $profile = $this->getUserProfile($user);

    return [
        'success' => true,
        'message' => 'Authentification réussie.',
        'data'    => array_merge(['token' => $token], $profile),
    ];
}

private function parseDeviceName(string $userAgent): string
{
    if (str_contains($userAgent, 'Mobile')) return 'Mobile';
    if (str_contains($userAgent, 'Tablet')) return 'Tablette';
    return 'Ordinateur';
}

private function parseBrowser(string $userAgent): string
{
    if (str_contains($userAgent, 'Chrome')) return 'Chrome';
    if (str_contains($userAgent, 'Firefox')) return 'Firefox';
    if (str_contains($userAgent, 'Safari')) return 'Safari';
    if (str_contains($userAgent, 'Edge')) return 'Edge';
    return 'Inconnu';
}

private function parseOS(string $userAgent): string
{
    if (str_contains($userAgent, 'Windows')) return 'Windows';
    if (str_contains($userAgent, 'Mac')) return 'macOS';
    if (str_contains($userAgent, 'Linux')) return 'Linux';
    if (str_contains($userAgent, 'Android')) return 'Android';
    if (str_contains($userAgent, 'iOS')) return 'iOS';
    return 'Inconnu';
}

    // ═══════════════════════════════════════════════════════════════════════════
    // PROFIL UTILISATEUR
    // ═══════════════════════════════════════════════════════════════════════════
    public function getUserProfile($user): array
    {
        $base = [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ];

        if ($user->role === 'student') {
            $student = DB::table('students')->where('user_id', $user->id)->first();
            $filiere = $student ? DB::table('filieres')->where('id', $student->filiere_id)->first() : null;
            $niveau  = $student ? DB::table('niveaux')->where('id', $student->niveau_id)->first() : null;

            $base['student'] = [
                'matricule'     => $student?->matricule,
                'filiere'       => $filiere?->name,
                'filiere_code'  => $filiere?->code,
                'niveau'        => $niveau?->label,
                'niveau_code'   => $niveau?->code,
                'academic_year' => $student?->academic_year,
            ];
        }

        return $base;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════════════════
    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);
        $masked = substr($local, 0, 2) . str_repeat('*', max(0, strlen($local) - 2));
        return $masked . '@' . $domain;
    }

    private function sendOtpEmail(string $email, $otp, string $context): void
    {
        try {
            Mail::raw(
                "Votre code OTP pour {$context} : {$otp}\n\nValide 15 minutes.\n\nISCAE",
                function ($m) use ($email, $context) {
                    $m->to($email)->subject("Code OTP – {$context} ISCAE");
                }
            );
        } catch (\Exception $e) {
            \Log::error("Échec envoi OTP à {$email} : " . $e->getMessage());
        }
    }
}
