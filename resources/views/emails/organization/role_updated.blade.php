@extends('emails.layouts.master', ['title' => 'Mise à jour de votre rôle'])

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <span style="display: inline-block; width: 48px; height: 48px; background-color: #EFF6FF; color: #3B82F6; border-radius: 50%; font-size: 24px; line-height: 48px; text-align: center;">
            <svg style="width: 24px; height: 24px; vertical-align: middle; margin-top: -4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
        </span>
    </div>

    <h1 style="margin: 0 0 16px 0; font-size: 22px; font-weight: 700; color: #0F172A; text-align: center;">
        Vos permissions ont évolué
    </h1>

    <p style="margin: 0 0 16px 0;">
        Bonjour <strong>{{ $user->name }}</strong>,
    </p>

    <p style="margin: 0 0 24px 0;">
        Nous vous informons que votre rôle au sein du organization <span style="color: #0F172A; font-weight: 600;">{{ $organization->name }}</span> a été mis à jour par un administrateur.
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 32px; text-align: center;">
        <tr>
            <td style="padding: 24px;">
                <span style="font-size: 13px; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Nouveau rôle attribué</span>
                <span style="display: inline-block; background-color: #3B82F6; color: #FFFFFF; font-size: 16px; font-weight: 700; padding: 6px 18px; border-radius: 20px; text-transform: capitalize;">
                    {{ $newRole }}
                </span>
            </td>
        </tr>
    </table>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 16px;">
        <tr>
            <td align="center">
                <a href="{{ url('/dashboard') }}" target="_blank" style="display: inline-block; background-color: #0F172A; color: #FFFFFF; font-size: 15px; font-weight: 600; text-decoration: none; padding: 12px 28px; border-radius: 8px;">
                    Accéder à mon espace de travail
                </a>
            </td>
        </tr>
    </table>
@endsection
