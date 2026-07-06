<!-- resources/views/emails/reset-password.blade.php -->

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Réinitialisation du mot de passe</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family:Arial, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7; padding:32px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; max-width:600px; width:100%;">

          <!-- Header -->
          <tr>
            <td style="background-color:#1d1d1d; padding:24px 32px;">
              <span style="font-size:18px; font-weight:bold; color:#ffffff; font-family:Arial, Helvetica, sans-serif;">
                {{ $app }}
              </span>
            </td>
          </tr>

          <!-- Lock icon -->
          <tr>
            <td style="padding:40px 32px 0 32px;" align="center">
              <div style="width:64px; height:64px; background-color:#fff3e0; border-radius:50%; text-align:center; line-height:64px; font-size:28px;">
                <svg style="width: 24px; height: 24px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
              </div>
            </td>
          </tr>

          <!-- Heading -->
          <tr>
            <td style="padding:24px 32px 8px 32px;" align="center">
              <h1 style="margin:0; font-size:22px; color:#1d1d1d; font-family:Arial, Helvetica, sans-serif;">
                Réinitialisation de mot de passe
              </h1>
            </td>
          </tr>

          <!-- Body text -->
          <tr>
            <td style="padding:8px 32px 0 32px;" align="center">
              <p style="margin:0; font-size:15px; line-height:24px; color:#5f5e5a; font-family:Arial, Helvetica, sans-serif;">
                Bonjour {{ $userName }},<br><br>
                Nous avons reçu une demande de réinitialisation de votre mot de passe.
                Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe.
              </p>
            </td>
          </tr>

          <!-- CTA Button -->
          <tr>
            <td style="padding:32px 32px 8px 32px;" align="center">
              <a href="{{ $resetUrl }}" target="_blank" style="display:inline-block; padding:14px 32px; font-size:15px; font-weight:bold; color:#ffffff; text-decoration:none; background-color:#378ADD; border-radius:6px; font-family:Arial, Helvetica, sans-serif;">
                Réinitialiser mon mot de passe
              </a>
            </td>
          </tr>

          <!-- Expiry notice -->
          <tr>
            <td style="padding:16px 32px 0 32px;" align="center">
              <p style="margin:0; font-size:13px; line-height:20px; color:#888780; font-family:Arial, Helvetica, sans-serif;">
                Ce lien expirera dans <strong>{{ $expireMin }} minutes</strong>.<br>
                Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet e-mail.
              </p>
            </td>
          </tr>

          <!-- Fallback URL -->
          <tr>
            <td style="padding:24px 32px 0 32px;" align="center">
              <p style="margin:0; font-size:12px; line-height:18px; color:#aaa; font-family:Arial, Helvetica, sans-serif; word-break:break-all;">
                Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>
                <a href="{{ $resetUrl }}" style="color:#378ADD; text-decoration:underline;">{{ $resetUrl }}</a>
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:40px 32px 32px 32px;" align="center">
              <p style="margin:0; font-size:12px; color:#888780; font-family:Arial, Helvetica, sans-serif;">
                Vous recevez cet email car une demande de réinitialisation de mot de passe a été effectuée sur {{ $app }}.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
