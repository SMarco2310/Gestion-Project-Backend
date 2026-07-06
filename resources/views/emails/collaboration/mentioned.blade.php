@extends('emails.layouts.master', ['title' => 'Mention dans un commentaire'])

@section('content')
    <h1 style="margin: 0 0 20px 0; font-size: 22px; font-weight: 700; color: #0F172A;">
        Nouveau message <svg style="width: 20px; height: 20px; vertical-align: sub; display: inline-block; margin-left: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
    </h1>

    <p style="margin: 0 0 20px 0;">
        Bonjour <strong>{{ $user->name }}</strong>,
    </p>

    <p style="margin: 0 0 24px 0;">
        <strong>{{ $commenter->name }}</strong> vous a mentionné(e) dans une discussion sur la tâche <strong>[{{ $task->reference_code }}] {{ $task->title }}</strong> :
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; border-left: 4px solid #3B82F6; border-radius: 0 12px 12px 0; margin-bottom: 32px;">
        <tr>
            <td style="padding: 20px; font-style: italic; font-size: 15px; color: #334155; line-height: 24px;">
                " {{ $commentContent }} "
            </td>
        </tr>
    </table>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 16px;">
        <tr>
            <td align="center">
                <a href="{{ url('/projects/' . $project->id . '/tasks/' . $task->id . '#comments') }}" target="_blank" style="display: inline-block; background-color: #3B82F6; color: #FFFFFF; font-size: 15px; font-weight: 600; text-decoration: none; padding: 12px 28px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);">
                    Répondre au commentaire
                </a>
            </td>
        </tr>
    </table>
@endsection
