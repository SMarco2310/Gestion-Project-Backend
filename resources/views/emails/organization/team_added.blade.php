@extends('emails.layouts.master', ['title' => 'Bienvenue dans l\'équipe'])

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <span style="display: inline-block; width: 48px; height: 48px; background-color: #EFF6FF; color: #3B82F6; border-radius: 50%; font-size: 24px; line-height: 48px; text-align: center;">
            <svg style="width: 24px; height: 24px; vertical-align: middle; margin-top: -4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        </span>
    </div>

    <h1 style="margin: 0 0 16px 0; font-size: 22px; font-weight: 700; color: #0F172A; text-align: center;">
        Vous avez rejoint une nouvelle équipe !
    </h1>

    <p style="margin: 0 0 16px 0;">
        Bonjour <strong>{{ $user->name }}</strong>,
    </p>

    <p style="margin: 0 0 24px 0;">
        Vous avez été ajouté(e) à l'équipe <strong>{{ $team->name }}</strong> au sein du organization <span style="color: #0F172A; font-weight: 600;">{{ $organization->name }}</span>. Vous pouvez dès à présent collaborer sur tous les projets rattachés à cette équipe.
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 32px;">
        <tr>
            <td style="padding: 20px; text-align: center;">
                <p style="margin: 0 0 4px 0; font-size: 13px; color: #64748B; text-transform: uppercase;">Équipe</p>
                <p style="margin: 0; font-size: 18px; font-weight: 700; color: #0F172A;">{{ $team->name }}</p>
            </td>
        </tr>
    </table>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 16px;">
        <tr>
            <td align="center">
                <a href="{{ url('/teams/' . $team->id) }}" target="_blank" style="display: inline-block; background-color: #3B82F6; color: #FFFFFF; font-size: 15px; font-weight: 600; text-decoration: none; padding: 12px 28px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);">
                    Voir les projets de l'équipe
                </a>
            </td>
        </tr>
    </table>
@endsection
