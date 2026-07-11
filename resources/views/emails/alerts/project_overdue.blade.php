@extends('emails.layouts.master', ['title' => 'Alerte : Projet en retard'])

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <span style="display: inline-block; width: 48px; height: 48px; background-color: #FEF2F2; color: #DC2626; border-radius: 50%; font-size: 24px; line-height: 48px; text-align: center;">
            <svg style="width: 24px; height: 24px; vertical-align: middle; margin-top: -4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
        </span>
    </div>

    <h1 style="margin: 0 0 16px 0; font-size: 22px; font-weight: 700; color: #DC2626; text-align: center;">
        Projet en dépassement de délai
    </h1>

    <p style="margin: 0 0 16px 0;">
        Bonjour <strong>{{ $user->name }}</strong>,
    </p>

    <p style="margin: 0 0 24px 0;">
        Le système a détecté que le projet suivant n'a pas encore été clôturé, alors que sa date limite était fixée au <strong style="color: #DC2626;">{{ \Carbon\Carbon::parse($project->end_date)->format('d/m/Y') }}</strong> :
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px; margin-bottom: 32px;">
        <tr>
            <td style="padding: 20px;">
                <p style="margin: 0 0 4px 0; font-size: 13px; color: #991B1B; font-weight: 700;">{{ $project->reference_code }}</p>
                <p style="margin: 0 0 12px 0; font-size: 18px; font-weight: 700; color: #7F1D1D;">{{ $project->name }}</p>
                <p style="margin: 0; font-size: 14px; color: #991B1B;">
                    Retard constaté : <strong>{{ \Carbon\Carbon::parse($project->end_date)->diffForHumans() }}</strong>
                </p>
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 24px 0; font-size: 14px; color: #64748B; text-align: center;">
        Merci de vérifier l'avancement du projet et de mettre à jour son statut si nécessaire.
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 16px;">
        <tr>
            <td align="center">
                <a href="{{ url('/organization/' . $project->organization_id . '/projects/' . $project->id) }}" target="_blank" style="display: inline-block; background-color: #DC2626; color: #FFFFFF; font-size: 15px; font-weight: 600; text-decoration: none; padding: 12px 28px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.3);">
                    Voir le projet en retard
                </a>
            </td>
        </tr>
    </table>
@endsection
