@extends('emails.layouts.master', ['title' => 'Priorité élevée assignée'])

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <span style="display: inline-block; width: 48px; height: 48px; background-color: #FEF2F2; color: #EF4444; border-radius: 50%; font-size: 24px; line-height: 48px; text-align: center;">
            <svg style="width: 24px; height: 24px; vertical-align: middle; margin-top: -4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 7 9a8.001 8.001 0 0010.586 10.586z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14a2 2 0 100-4 2 2 0 000 4z"></path></svg>
        </span>
    </div>

    <h1 style="margin: 0 0 16px 0; font-size: 22px; font-weight: 700; color: #DC2626; text-align: center;">
        Escalade de priorité : ÉLEVÉE
    </h1>

    <p style="margin: 0 0 16px 0;">
        Bonjour <strong>{{ $user->name }}</strong>,
    </p>

    <p style="margin: 0 0 24px 0;">
        Veuillez noter que la priorité de votre activité <strong>[{{ $task->reference_code }}] {{ $task->title }}</strong> a été reclassée comme <strong style="color: #DC2626;">ÉLEVÉE</strong> par <strong>{{ $actor->name }}</strong>.
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px; margin-bottom: 32px;">
        <tr>
            <td style="padding: 16px; font-size: 14px; color: #991B1B; text-align: center;">
                <svg style="width: 16px; height: 16px; vertical-align: text-bottom; display: inline-block; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v8l9-11h-7z"></path></svg> <strong>Action requise :</strong> Nous vous invitons à traiter cette tâche en priorité absolue dans votre planning d'aujourd'hui.
            </td>
        </tr>
    </table>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 16px;">
        <tr>
            <td align="center">
                <a href="{{ url('/projects/' . $project->id . '/tasks/' . $task->id) }}" target="_blank" style="display: inline-block; background-color: #DC2626; color: #FFFFFF; font-size: 15px; font-weight: 600; text-decoration: none; padding: 12px 28px; border-radius: 8px;">
                    Accéder à la tâche urgente
                </a>
            </td>
        </tr>
    </table>
@endsection
