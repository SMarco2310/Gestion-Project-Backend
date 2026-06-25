<!-- resources/views/emails/added-to-project.blade.php -->
<!-- Converted from the 02-added-to-project.html template built earlier -->

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ajouté à un projet</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family:Arial, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7; padding:32px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; max-width:600px; width:100%;">

          <!-- Header -->
          <tr>
            <td style="background-color:#1d1d1d; padding:24px 32px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="font-size:18px; font-weight:bold; color:#ffffff; font-family:Arial, Helvetica, sans-serif;">
                    [App Name]
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Main content -->
          <tr>
            <td style="padding:40px 32px 0 32px;">
              <p style="margin:0; font-size:14px; color:#888780; font-family:Arial, Helvetica, sans-serif;">
                Nouvelle invitation
              </p>
              <h1 style="margin:8px 0 0 0; font-size:22px; color:#1d1d1d; font-family:Arial, Helvetica, sans-serif;">
                {{ $inviterName }} vous a ajouté à un projet
              </h1>
            </td>
          </tr>

          <!-- Project card -->
          <tr>
            <td style="padding:24px 32px 0 32px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f7f5; border-radius:6px; border-left:4px solid #378ADD;">
                <tr>
                  <td style="padding:20px 24px;">
                    <p style="margin:0 0 4px 0; font-size:12px; font-weight:bold; color:#185FA5; text-transform:uppercase; font-family:Arial, Helvetica, sans-serif;">
                      Projet
                    </p>
                    <p style="margin:0 0 8px 0; font-size:17px; font-weight:bold; color:#1d1d1d; font-family:Arial, Helvetica, sans-serif;">
                      {{ $projectName }}
                    </p>
                    <p style="margin:0; font-size:14px; line-height:21px; color:#5f5e5a; font-family:Arial, Helvetica, sans-serif;">
                      {{ $projectDescription }}
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Role / detail row -->
          <tr>
            <td style="padding:16px 32px 0 32px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="font-size:14px; color:#5f5e5a; font-family:Arial, Helvetica, sans-serif; padding:6px 0;">
                    Ajouté par
                  </td>
                  <td align="right" style="font-size:14px; color:#1d1d1d; font-family:Arial, Helvetica, sans-serif; padding:6px 0;">
                    {{ $inviterName }}
                  </td>
                </tr>
                <tr>
                  <td style="font-size:14px; color:#5f5e5a; font-family:Arial, Helvetica, sans-serif; padding:6px 0; border-top:1px solid #eeeeee;">
                    Nombre de tâches
                  </td>
                  <td align="right" style="font-size:14px; color:#1d1d1d; font-family:Arial, Helvetica, sans-serif; padding:6px 0; border-top:1px solid #eeeeee;">
                    {{ $taskCount }}
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- CTA -->
          <tr>
            <td style="padding:32px 32px 8px 32px;" align="center">
              <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="background-color:#378ADD; border-radius:6px;">
                    <a href="{{ $projectUrl }}" target="_blank" style="display:inline-block; padding:14px 32px; font-size:15px; font-weight:bold; color:#ffffff; text-decoration:none; font-family:Arial, Helvetica, sans-serif;">
                      Voir le projet
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:40px 32px 32px 32px;" align="center">
              <p style="margin:0; font-size:12px; color:#888780; font-family:Arial, Helvetica, sans-serif;">
                Vous recevez cet email car vous avez été ajouté à un projet sur [App Name].<br>
                Gérez vos préférences de notification depuis votre profil.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
