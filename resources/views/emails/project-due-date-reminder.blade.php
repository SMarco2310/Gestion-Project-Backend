<!-- resources/views/emails/project-due-date-reminder.blade.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rappel d'échéance de projet</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family:Arial, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7; padding:32px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; max-width:600px; width:100%;">

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

          <tr>
            <td style="background-color:#fdf3e3; padding:14px 32px; border-bottom:1px solid #f5e0b0;">
              <p style="margin:0; font-size:13px; font-weight:bold; color:#854F0B; font-family:Arial, Helvetica, sans-serif;">
                ⏰ Projet à échéance dans {{ $daysRemaining }} jour(s)
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:32px 32px 0 32px;">
              <h1 style="margin:0; font-size:22px; color:#1d1d1d; font-family:Arial, Helvetica, sans-serif;">
                Un projet arrive à échéance
              </h1>
              <p style="margin:8px 0 0 0; font-size:15px; line-height:23px; color:#5f5e5a; font-family:Arial, Helvetica, sans-serif;">
                Voici un rappel concernant un projet que vous gérez.
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:24px 32px 0 32px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f7f5; border-radius:6px; border-left:4px solid #EF9F27;">
                <tr>
                  <td style="padding:20px 24px;">
                    <p style="margin:0 0 12px 0; font-size:17px; font-weight:bold; color:#1d1d1d; font-family:Arial, Helvetica, sans-serif;">
                      {{ $projectName }}
                    </p>
                    <p style="margin:0 0 16px 0; font-size:14px; line-height:21px; color:#5f5e5a; font-family:Arial, Helvetica, sans-serif;">
                      {{ $projectDescription }}
                    </p>

                    <table role="presentation" cellpadding="0" cellspacing="0">
                      <tr>
                        <td>
                          <table role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                              <td style="background-color:#e6e6e6; border-radius:4px; padding:4px 10px; font-size:12px; font-weight:bold; color:#444441; font-family:Arial, Helvetica, sans-serif;">
                                Échéance : {{ $endDate }}
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

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

          <tr>
            <td style="padding:40px 32px 32px 32px;" align="center">
              <p style="margin:0; font-size:12px; color:#888780; font-family:Arial, Helvetica, sans-serif;">
                Vous recevez ce rappel car vous êtes le créateur de ce projet sur [App Name].<br>
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
