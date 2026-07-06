@extends('emails.layouts.master', ['title' => 'Nouvelle tâche assignée'])

@section('content')
    <h1 style="margin: 0 0 20px 0; font-size: 22px; font-weight: 700; color: #0F172A;">
        Nouvelle tâche assignée <svg style="width: 20px; height: 20px; vertical-align: sub; display: inline-block; margin-left: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
    </h1>

    <p style="margin: 0 0 20px 0;">
        Bonjour <strong>{{ $user->name }}</strong>,
    </p>

    <p style="margin: 0 0 24px 0;">
        <strong>{{ $assigner->name }}</strong> vous a confié une nouvelle activité sur le projet <strong>{{ $project->name }}</strong> :
    </p>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 32px; overflow: hidden;">
        <tr>
            <td style="padding: 20px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="padding-bottom: 12px; font-size: 13px; color: #64748B; font-weight: 700; letter-spacing: 0.5px;">
                            {{ $task->reference_code }}
                        </td>
                        <td align="right" style="padding-bottom: 12px;">
                            <span style="background-color: #E0F2FE; color: #0284C7; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase;">
                                {{ $task->priority }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-size: 18px; font-weight: 700; color: #0F172A; padding-bottom: 12px;">
                            {{ $task->title }}
                        </td>
                    </tr>
                    @if($task->due_date)
                    <tr>
                        <td colspan="2" style="font-size: 14px; color: #475569; border-top: 1px solid #E2E8F0; padding-top: 12px;">
                            <svg style="width: 16px; height: 16px; vertical-align: text-bottom; display: inline-block; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> <strong>Échéance :</strong> {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }}
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 16px;">
        <tr>
            <td align="center">
                <a href="{{ url('/projects/' . $project->id . '/tasks/' . $task->id) }}" target="_blank" style="display: inline-block; background-color: #3B82F6; color: #FFFFFF; font-size: 15px; font-weight: 600; text-decoration: none; padding: 12px 28px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);">
                    Consulter la tâche
                </a>
            </td>
        </tr>
    </table>
@endsection
