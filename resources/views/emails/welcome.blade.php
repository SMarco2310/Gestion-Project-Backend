<!-- resources/views/emails/welcome.blade.php -->

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bienvenue</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family:Arial, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7; padding:32px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; max-width:600px; width:100%;">

          <tr>
            <td style="background-color:#1d1d1d; padding:24px 32px;">
              <span style="font-size:18px; font-weight:bold; color:#ffffff; font-family:Arial, Helvetica, sans-serif;">
                [App Name]
              </span>
            </td>
          </tr>

          <tr>
            <td style="padding:40px 32px 0 32px;" align="center">
              <div style="width:64px; height:64px; background-color:#e6f4ea; border-radius:50%; text-align:center; line-height:64px; font-size:28px;">
                ✓
              </div>
            </td>
          </tr>

          <tr>
            <td style="padding:24px 32px 8px 32px;" align="center">
              <h1 style="margin:0; font-size:22px; color:#1d1d1d; font-family:Arial, Helvetica, sans-serif;">
                Bienvenue, {{ $user->name }} !
              </h1>
            </td>
          </tr>
          <tr>
            <td style="padding:8px 32px 0 32px;" align="center">
              <p style="margin:0; font-size:15px; line-height:24px; color:#5f5e5a; font-family:Arial, Helvetica, sans-serif;">
                Votre compte a été créé avec succès. Vous pouvez dès à présent organiser vos projets,
                suivre l'avancement de vos tâches et collaborer avec votre équipe.
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:32px 32px 8px 32px;" align="center">
              <a href="{{ $loginUrl }}" target="_blank" style="display:inline-block; padding:14px 32px; font-size:15px; font-weight:bold; color:#ffffff; text-decoration:none; background-color:#378ADD; border-radius:6px; font-family:Arial, Helvetica, sans-serif;">
                Accéder à mon espace
              </a>
            </td>
          </tr>

          <tr>
            <td style="padding:40px 32px 32px 32px;" align="center">
              <p style="margin:0; font-size:12px; color:#888780; font-family:Arial, Helvetica, sans-serif;">
                Vous recevez cet email car un compte a été créé avec cette adresse sur [App Name].
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
