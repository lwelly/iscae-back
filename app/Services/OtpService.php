<?php
namespace App\Services;

use App\Models\OtpCode;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, Log, Mail};
use Illuminate\Support\Str;

class OtpService
{
    // ─────────────────────────────────────────────
    //  GÉNÉRER ET ENVOYER OTP
    // ─────────────────────────────────────────────

    public function generate(User $user, string $type, Request $request): OtpCode
    {
        // Invalider les anciens OTP du même type
        OtpCode::where('user_id', $user->id)
            ->where('type', $type)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        // Générer code à 6 chiffres
        $plainCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = OtpCode::create([
            'user_id'    => $user->id,
            'code'       => Hash::make($plainCode),
            'type'       => $type,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_used'    => false,
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Envoyer par email
        $this->sendEmail($user, $plainCode, $type);

        Log::info('[OtpService] OTP généré', [
            'user_id' => $user->id,
            'type'    => $type,
            'ip'      => $request->ip(),
        ]);

        return $otp;
    }

    // ─────────────────────────────────────────────
    //  VÉRIFIER OTP
    // ─────────────────────────────────────────────

    public function verify(User $user, string $code, string $type): bool
    {
        $otp = OtpCode::where('user_id', $user->id)
            ->where('type', $type)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otp) {
            Log::warning('[OtpService] OTP introuvable ou expiré', [
                'user_id' => $user->id,
                'type'    => $type,
            ]);
            return false;
        }

        // Incrémenter tentatives
        $otp->increment('attempts');

        // Trop de tentatives
        if ($otp->attempts > 5) {
            $otp->update(['is_used' => true]);
            Log::warning('[OtpService] Trop de tentatives', ['user_id' => $user->id]);
            return false;
        }

        // Vérifier le code
        if (!Hash::check($code, $otp->code)) {
            Log::warning('[OtpService] Code incorrect', [
                'user_id'  => $user->id,
                'attempts' => $otp->attempts,
            ]);
            return false;
        }

        // Marquer utilisé
        $otp->update(['is_used' => true]);
        Log::info('[OtpService] OTP vérifié avec succès', ['user_id' => $user->id]);
        return true;
    }

    // ─────────────────────────────────────────────
    //  VÉRIFIER APPAREIL DE CONFIANCE
    // ─────────────────────────────────────────────

    public function isTrustedDevice(User $user, Request $request): bool
    {
        $deviceToken = $request->header('X-Device-Token')
                    ?? $request->input('device_token');

        if (!$deviceToken) return false;

        $device = TrustedDevice::where('user_id', $user->id)
            ->where('device_token', $deviceToken)
            ->where('expires_at', '>', now())
            ->first();

        if (!$device) return false;

        $device->update(['last_used_at' => now()]);

        Log::info('[OtpService] Appareil de confiance reconnu', [
            'user_id' => $user->id,
            'device'  => $device->device_name,
        ]);

        return true;
    }

    // ─────────────────────────────────────────────
    //  ENREGISTRER APPAREIL DE CONFIANCE
    // ─────────────────────────────────────────────

    public function trustDevice(User $user, Request $request): string
    {
        $deviceToken = Str::random(64);
        $ua          = $request->userAgent() ?? '';

        TrustedDevice::create([
            'user_id'      => $user->id,
            'device_token' => $deviceToken,
            'ip_address'   => $request->ip(),
            'user_agent'   => $ua,
            'device_name'  => $this->parseDeviceName($ua),
            'last_used_at' => now(),
            'expires_at'   => now()->addDays(30),
        ]);

        Log::info('[OtpService] Appareil enregistré', [
            'user_id' => $user->id,
            'device'  => $this->parseDeviceName($ua),
        ]);

        return $deviceToken;
    }

    // ─────────────────────────────────────────────
    //  ENVOI EMAIL
    // ─────────────────────────────────────────────

    private function sendEmail(User $user, string $code, string $type): void
    {
        try {
            $subject = $type === 'login'
                ? '🔐 Code de connexion ISCAE'
                : '🔑 Réinitialisation de mot de passe ISCAE';

            $html = $this->buildEmailHtml($user, $code, $type);

            Mail::html($html, function ($message) use ($user, $subject) {
                $message->to($user->email)->subject($subject);
            });

            Log::info('[OtpService] Email envoyé', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'type'    => $type,
            ]);
        } catch (\Throwable $e) {
            Log::error('[OtpService] Erreur envoi email', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // ─────────────────────────────────────────────
    //  TEMPLATE EMAIL
    // ─────────────────────────────────────────────

    private function buildEmailHtml(User $user, string $code, string $type): string
    {
        $name       = $user->login_identifier ?? $user->email;
        $actionText = $type === 'login'
            ? 'Vous tentez de vous connecter à votre espace étudiant ISCAE.'
            : 'Vous avez demandé la réinitialisation de votre mot de passe ISCAE.';

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;">
    <tr><td align="center">
      <table width="500" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:16px;overflow:hidden;
                    box-shadow:0 8px 32px rgba(0,0,0,0.10);">

        <!-- En-tête -->
        <tr><td style="background:linear-gradient(135deg,#0F2D5E 0%,#1565C0 100%);
                        padding:32px 36px;text-align:center;">
          <div style="font-size:26px;font-weight:800;color:#fff;letter-spacing:4px;">ISCAE</div>
          <div style="font-size:11px;color:rgba(255,255,255,0.65);
                      margin-top:4px;letter-spacing:2px;text-transform:uppercase;">
            Espace Étudiant
          </div>
        </td></tr>

        <!-- Corps -->
        <tr><td style="padding:36px;">
          <p style="font-size:15px;color:#1e293b;margin:0 0 6px;">
            Bonjour <strong>{$name}</strong>,
          </p>
          <p style="font-size:13px;color:#64748b;margin:0 0 28px;">{$actionText}</p>

          <!-- Code OTP -->
          <div style="background:#f8fafc;border:2px dashed #1565C0;
                      border-radius:14px;padding:28px 20px;text-align:center;
                      margin-bottom:28px;">
            <div style="font-size:11px;color:#64748b;text-transform:uppercase;
                        letter-spacing:3px;margin-bottom:12px;">Votre code de vérification</div>
            <div style="font-size:42px;font-weight:900;letter-spacing:14px;
                        color:#0F2D5E;font-variant-numeric:tabular-nums;">{$code}</div>
            <div style="font-size:12px;color:#ef4444;margin-top:12px;font-weight:500;">
              ⏱ Valable 10 minutes uniquement
            </div>
          </div>

          <!-- Avertissement -->
          <div style="background:#fefce8;border:1px solid #fde68a;border-radius:10px;
                      padding:12px 16px;margin-bottom:20px;">
            <p style="font-size:12px;color:#92400e;margin:0;">
              ⚠️ <strong>Ne partagez jamais ce code.</strong>
              L'équipe ISCAE ne vous demandera jamais votre code OTP.
            </p>
          </div>

          <p style="font-size:12px;color:#94a3b8;text-align:center;margin:0;">
            Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.
          </p>
        </td></tr>

        <!-- Pied de page -->
        <tr><td style="background:#f8fafc;padding:16px;text-align:center;
                        border-top:1px solid #e2e8f0;">
          <div style="font-size:11px;color:#94a3b8;">
            © 2026 ISCAE — Institut Supérieur de Comptabilité et d'Administration des Entreprises
          </div>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }

    // ─────────────────────────────────────────────
    //  UTILITAIRES
    // ─────────────────────────────────────────────

    private function parseDeviceName(string $ua): string
    {
        $browser = match(true) {
            str_contains($ua, 'Edg')     => 'Edge',
            str_contains($ua, 'Chrome')  => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari')  => 'Safari',
            default                      => 'Navigateur inconnu',
        };

        $os = match(true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac')     => 'macOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone')  => 'iPhone',
            str_contains($ua, 'Linux')   => 'Linux',
            default                      => 'OS inconnu',
        };

        return "{$browser} / {$os}";
    }
}
