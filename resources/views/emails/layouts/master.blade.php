{{-- resources/views/emails/layouts/master.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title ?? 'Notification' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F4F5F7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #F4F5F7; padding: 32px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

          {{-- Header --}}
          <tr>
            <td style="background-color: #0F172A; padding: 20px 32px; border-radius: 12px 12px 0 0;">
              <span style="font-size: 18px; font-weight: 700; color: #FFFFFF; letter-spacing: -0.3px;">
                {{ config('app.name', 'Gestion Project') }}
              </span>
            </td>
          </tr>

          {{-- Content Card --}}
          <tr>
            <td style="background-color: #FFFFFF; padding: 40px 32px 32px 32px; font-size: 15px; line-height: 24px; color: #475569;">
              @yield('content')
            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td style="background-color: #FFFFFF; padding: 0 32px 32px 32px; border-radius: 0 0 12px 12px; border-top: 1px solid #F1F5F9;">
              <p style="margin: 16px 0 0 0; font-size: 12px; color: #94A3B8; text-align: center; line-height: 18px;">
                Cet email a été envoyé automatiquement par <strong>{{ config('app.name', 'Gestion Project') }}</strong>.<br>
                Si vous pensez avoir reçu ce message par erreur, vous pouvez l'ignorer en toute sécurité.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
