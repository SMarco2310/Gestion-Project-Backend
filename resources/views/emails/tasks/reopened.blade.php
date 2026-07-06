@extends('emails.layouts.master', ['title' => 'Révision requise'])

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <span style="display: inline-block; width: 48px; height: 48px; background-color: #FEF2F2; color: #EF4444; border-radius: 50%; font-size: 24px; line-height: 48px; text-align: center;">
            <svg style="width: 24px; height: 24px; vertical-align: middle; margin-top: -4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </span>
    </div>

    <h1 style="margin: 0 0 16px 0; font-size: 22px; font-weight: 700; color: #0F172A; text-align: center;">
        Une tâche a été réouverte
    </h1>

    <p style="margin: 0 0 16px 0;">
        Bonjour <strong>{{ $user->name }}</strong>,
    </p>

    <p style="margin: 0 0 24px 0;">
        La tâche <strong>{{ $task->reference_code }} : {{ $task->title }}</strong>, qui était marquée comme terminée, a été réouverte par <strong>{{ $actor->name }}</strong> et remise dans la colonne <span style="font-weight: 600; color: #D97706;">{{ $newStatus }}</span>.
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #FFFBEB; border: 1px solid #FDE68A; border-radius: 12px; margin-bottom: 32px;">
        <tr>
            <td style="padding: 16px; font-size: 14px; color: #92400E;">
                <svg style="width: 16px; height: 16px; vertical-align: text-bottom; display: inline-block; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v8l9-11h-7z"></path></svg> <strong>Note :</strong> Des ajustements ou corrections sont nécessaires. Veuillez consulter l'historique des commentaires de la tâche pour connaître les détails précis des retours.
            </td>
        </tr>
    </table>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 16px;">
        <tr>
            <td align="center">
                <a href="{{ url('/projects/' . $project->id . '/tasks/' . $task->id) }}" target="_blank" style="display: inline-block; background-color: #0F172A; color: #FFFFFF; font-size: 15px; font-weight: 600; text-decoration: none; padding: 12px 28px; border-radius: 8px;">
                    Consulter les retours
                </a>
            </td>
        </tr>
    </table>
@endsection
