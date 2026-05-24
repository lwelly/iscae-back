<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code OTP ISCAE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #1565C0, #0D47A1); padding: 35px 30px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 28px; margin-bottom: 5px; }
        .header p { color: #90CAF9; font-size: 14px; }
        .body { padding: 40px 30px; }
        .greeting { font-size: 18px; color: #333; margin-bottom: 15px; font-weight: bold; }
        .message { font-size: 15px; color: #555; line-height: 1.7; margin-bottom: 25px; }
        .otp-box { background: linear-gradient(135deg, #E3F2FD, #BBDEFB); border: 2px dashed #1565C0; border-radius: 12px; padding: 30px 20px; text-align: center; margin: 25px 0; }
        .otp-label { font-size: 13px; color: #1565C0; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 15px; }
        .otp-code { font-size: 52px; font-weight: bold; color: #0D47A1; letter-spacing: 16px; font-family: 'Courier New', monospace; }
        .otp-expiry { font-size: 13px; color: #666; margin-top: 12px; }
        .otp-expiry strong { color: #E53935; }
        .warning { background: #FFF8E1; border-left: 4px solid #FFB300; padding: 15px 20px; border-radius: 4px; margin: 20px 0; }
        .warning p { font-size: 14px; color: #795548; line-height: 1.6; }
        .footer { background: #F5F5F5; padding: 25px 30px; text-align: center; border-top: 1px solid #E0E0E0; }
        .footer p { font-size: 12px; color: #9E9E9E; margin: 4px 0; line-height: 1.6; }
        .footer strong { color: #1565C0; }
    </style>
</head>
<body>
    <div class="container">

        <!-- Header -->
        <div class="header">
            <h1>🎓 ISCAE</h1>
            <p>Institut Supérieur de Commerce et d'Administration des Entreprises</p>
        </div>

        <!-- Body -->
        <div class="body">

            <p class="greeting">Bonjour {{ $studentName }},</p>

            @if($type === 'registration')
                <p class="message">
                    Vous avez initié votre inscription sur la plateforme de gestion des réclamations de l'ISCAE.
                    Veuillez utiliser le code ci-dessous pour vérifier votre identité et finaliser votre compte.
                </p>
            @else
                <p class="message">
                    Une tentative de connexion a été détectée sur votre compte administrateur ISCAE.
                    Veuillez saisir ce code pour confirmer votre identité.
                </p>
            @endif

            <!-- Code OTP -->
            <div class="otp-box">
                <div class="otp-label">🔐 Votre code de vérification</div>
                <div class="otp-code">{{ $otpCode }}</div>
                <div class="otp-expiry">
                    Ce code expire dans <strong>{{ $expiresIn }} minutes</strong>
                </div>
            </div>

            <!-- Avertissement -->
            <div class="warning">
                <p>
                    ⚠️ <strong>Important :</strong> Ne partagez jamais ce code avec qui que ce soit.
                    L'ISCAE ne vous demandera jamais votre code par téléphone, SMS ou email.
                    Si vous n'avez pas demandé ce code, ignorez cet email — votre compte reste sécurisé.
                </p>
            </div>

        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>ISCAE — Système de Gestion des Réclamations</strong></p>
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
            <p>© {{ date('Y') }} ISCAE. Tous droits réservés.</p>
        </div>

    </div>
</body>
</html>
