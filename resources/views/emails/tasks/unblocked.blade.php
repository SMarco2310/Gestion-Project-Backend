@extends('emails.layouts.master', ['title' => 'Tâche principale débloquée'])

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <span style="display: inline-block; width: 48px; height: 48px; background-color: #ECFDF5; color: #10B981; border-radius: 50%; font-size: 24px; line-height: 48px; text-align: center;">
            <svg style="width: 24px; height: 24px; vertical-align: middle; margin-top: -4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
        </span>
    </div>

    <h1 style="margin: 0 0 16px 0; font-size: 22px; font-weight: 700; color: #0F172A; text-align: center;">
        Tâche prête pour clôture !
    </h1>

    <p style="margin: 0 0 16px 0;">
        Bonjour <strong>{{ $user->name }}</strong>,
    </p>

    <p style="margin: 0 0 24px 0;">
        Excellente nouvelle ! L'ensemble des sous-tâches rattachées à votre activité principale <strong>[{{ $parentTask->reference_code }}] {{ $parentTask->title }}</strong> ont été validées et terminées par l'équipe.
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 12px; margin-bottom: 32px;">
        <tr>
            <td style="padding: 16px; font-size: 14px; color: #065F46; text-align: center;">
                <svg style="width: 16px; height: 16px; vertical-align: text-bottom; display: inline-block; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> <strong>Blocage levé :</strong> Vous pouvez dès à présent faire glisser cette tâche principale dans la colonne <strong>Terminé</strong> sur votre tableau Kanban !
            </td>
        </tr>
    </table>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 16px;">
        <tr>
            <td align="center">
                <a href="{{ url('/projects/' . $project->id . '/kanban') }}" target="_blank" style="display: inline-block; background-color: #10B981; color: #FFFFFF; font-size: 15px; font-weight: 600; text-decoration: none; padding: 12px 28px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);">
                    Ouvrir le tableau Kanban
                </a>
            </td>
        </tr>
    </table>
@endsection
