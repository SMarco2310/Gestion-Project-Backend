@extends('emails.layouts.master', ['title' => 'Rappel : échéance demain'])

@section('content')
    <h1 style="margin: 0 0 20px 0; font-size: 22px; font-weight: 700; color: #0F172A;">
        Échéance dans 24 heures <svg style="width: 20px; height: 20px; vertical-align: sub; display: inline-block; margin-left: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    </h1>

    <p style="margin: 0 0 20px 0;">
        Bonjour <strong>{{ $user->name }}</strong>,
    </p>

    <p style="margin: 0 0 24px 0;">
        Ceci est un rappel automatique pour vous informer que la tâche suivante arrive à échéance <strong>demain ({{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }})</strong> :
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 32px;">
        <tr>
            <td style="padding: 20px;">
                <p style="margin: 0 0 4px 0; font-size: 13px; color: #64748B; font-weight: 700;">{{ $task->reference_code }} • Projet : {{ $project->name }}</p>
                <p style="margin: 0 0 12px 0; font-size: 18px; font-weight: 700; color: #0F172A;">{{ $task->title }}</p>
                <p style="margin: 0; font-size: 14px; color: #475569;">
                    Statut actuel : <strong style="color: #3B82F6;">{{ $task->status }}</strong>
                </p>
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 24px 0; font-size: 14px; color: #64748B; text-align: center;">
        Si votre travail est terminé, pensez à déplacer la carte vers "Terminé" sur votre tableau !
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 16px;">
        <tr>
            <td align="center">
                <a href="{{ url('/projects/' . $project->id . '/tasks/' . $task->id) }}" target="_blank" style="display: inline-block; background-color: #0F172A; color: #FFFFFF; font-size: 15px; font-weight: 600; text-decoration: none; padding: 12px 28px; border-radius: 8px;">
                    Mettre à jour l'avancement
                </a>
            </td>
        </tr>
    </table>
@endsection
