<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Organization;
use App\Models\Team;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\Commentaires;
use App\Notifications\RoleUpdatedNotification;
use App\Notifications\TeamAddedNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskReopenedNotification;
use App\Notifications\TaskUnblockedNotification;
use App\Notifications\PriorityEscalatedNotification;
use App\Notifications\TaskOverdueNotification;
use App\Notifications\CommentMentionNotification;
use App\Notifications\TaskDueSoonNotification;
use Illuminate\Support\Facades\Notification;

class TestEmailsCommand extends Command
{
    protected $signature = 'test:emails {email}';
    protected $description = 'Test all email notifications with dummy data';

    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Sending test emails to: {$email}");

        // 1. Create or find the dummy user
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Test User',
                'password' => bcrypt('tester123#'),
            ]
        );

        // Create dummy actor/assigner
        $actor = User::firstOrCreate(
            ['email' => 'actor@example.com'],
            [
                'name' => 'System Admin',
                'password' => bcrypt('password'),
            ]
        );

        // 2. Create dummy Organization
        $org = Organization::firstOrCreate(
            ['name' => 'Acme Corporation'],
            ['description' => 'A dummy organization for testing']
        );

        // 3. Create dummy Team
        $team = Team::firstOrCreate(
            ['name' => 'Engineering Team'],
            ['organization_id' => $org->id]
        );

        // 4. Create dummy Project
        $projet = Projet::firstOrCreate(
            ['name' => 'Redesign Website'],
            [
                'description' => 'A test project',
                'organization_id' => $org->id,
                'user_id' => $actor->id,
                'reference_code' => 'PRJ-001'
            ]
        );

        // 5. Create dummy Tasks
        $task = Tache::firstOrCreate(
            ['title' => 'Implement new landing page'],
            [
                'description' => 'Test task description',
                'projet_id' => $projet->id,
                'status' => 'en cours',
                'priority' => 'moyen',
                'reference_code' => 'TSK-001',
                'due_date' => now()->addDays(1),
            ]
        );

        $overdueTask = Tache::firstOrCreate(
            ['title' => 'Fix critical bug in production'],
            [
                'description' => 'This is overdue',
                'projet_id' => $projet->id,
                'status' => 'en cours',
                'priority' => 'élevé',
                'reference_code' => 'TSK-002',
                'due_date' => now()->subDays(2),
            ]
        );
        
        $parentTask = Tache::firstOrCreate(
            ['title' => 'Deploy to Production'],
            [
                'description' => 'Parent task',
                'projet_id' => $projet->id,
                'status' => 'en cours',
                'priority' => 'moyen',
                'reference_code' => 'TSK-003',
            ]
        );

        // 6. Create dummy Comment
        $comment = Commentaires::firstOrCreate(
            ['content' => 'Hey @Marc Sossou, can you check this?'],
            [
                'tache_id' => $task->id,
                'user_id' => $actor->id,
            ]
        );

        // --- Send Notifications ---

        $this->info("Sending RoleUpdatedNotification...");
        $user->notify(new RoleUpdatedNotification($org, 'admin'));

        $this->info("Sending TeamAddedNotification...");
        $user->notify(new TeamAddedNotification($team, $org));

        $this->info("Sending TaskAssignedNotification...");
        $user->notify(new TaskAssignedNotification($task, $projet, $actor));

        $this->info("Sending TaskReopenedNotification...");
        $user->notify(new TaskReopenedNotification($task, $projet, $actor, 'en cours'));

        $this->info("Sending TaskUnblockedNotification...");
        $user->notify(new TaskUnblockedNotification($parentTask, $projet));

        $this->info("Sending PriorityEscalatedNotification...");
        $user->notify(new PriorityEscalatedNotification($task, $projet, $actor));

        $this->info("Sending TaskOverdueNotification...");
        $user->notify(new TaskOverdueNotification($overdueTask, $projet));

        $this->info("Sending CommentMentionNotification...");
        $user->notify(new CommentMentionNotification($comment, $actor->name));

        $this->info("Sending TaskDueSoonNotification...");
        $user->notify(new TaskDueSoonNotification($task));

        $this->info('All test emails sent successfully!');
    }
}
