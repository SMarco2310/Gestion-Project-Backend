# Backend Features & Implementation Guide

This document lists all the major features implemented in the backend application, including their associated logic, architecture, and exactly where to find the source code.

## Getting Started (Setup & Run)

To set up and run the backend locally, follow these steps from the `Backend/server` directory:

1. **Install Dependencies:**
   ```bash
   composer install
   ```
2. **Environment Configuration:**
   Copy the example environment file and configure your database settings.
   ```bash
   cp .env.example .env
   ```
3. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```
4. **Database Migrations & Seeding:**
   ```bash
   php artisan migrate --seed
   ```
5. **Start the Development Server:**
   ```bash
   php artisan serve
   ```
   The API will now be available at `http://localhost:8000`.

## 1. Authentication & User Management
Handles user sign-up, sign-in, profile management, and password recovery.
- **Registration & Login**: Issues API tokens for secured routes. Implements initial integration for auto-accepting invitations during sign-up.
  - **Location**: `app/Http/Controllers/AuthController.php`
- **Profile Management**: Profile picture uploads, bio updates, and account termination.
  - **Location**: `app/Http/Controllers/UserController.php`
- **Password Resets**: Sends recovery emails and handles token verification for password resets.
  - **Location**: `app/Http/Controllers/PasswordResetController.php`

## 2. Organization & Team Hierarchy (Multi-Tenancy)
Supports a structured approach where users belong to Organizations, and within Organizations, they belong to specific Teams. Projects can be assigned to multiple Teams simultaneously.
- **Organizations**: Creating and managing high-level organizations, logo uploads, and their global settings (like dynamic reminder thresholds and specific reminder times).
  - **Location**: `app/Http/Controllers/OrganizationController.php`
- **Teams**: Sub-groups within an organization. Projects are linked to teams via the `projet_team` pivot table.
  - **Location**: `app/Http/Controllers/TeamController.php`

## 3. Role-Based Access Control (RBAC) & Member Management
Users within an organization have assigned roles (`proprietaire`, `admin`, `membre`) which dictate their permissions.
- **Role Assignment & Verification**: Admins and Owners can change the roles of other members, or remove them entirely. It prevents Admins from modifying Owners.
  - **Location**: `app/Http/Controllers/OrganizationMemberController.php`
- **Database Schema**: Pivot table `organization_user` holds the role enum.
  - **Location**: `database/migrations/2026_07_01_132324_create_organization_user_table.php`

## 4. The Invitation System
Allows `proprietaire` or `admin` users to invite people to join their organization/team via email. 
- **Core Logic**: Validates permissions, generates a unique secure token (valid for 2 days), and sends an email.
  - **Location**: `app/Http/Controllers/InvitationController.php`
- **Email Notifications**: Formats and sends the invitation email containing the frontend link with the token.
  - **Location**: `app/Notifications/OrganizationInvitationNotification.php`
- **Registration Hook**: Automatically attaches a new user to the invited organization/team if they provide the `invite_token` when registering.
  - **Location**: `app/Http/Controllers/AuthController.php@register`

## 5. Project & Task Management (Kanban Features)
The core of the application logic for managing workflows.
- **Projects**: Creating projects, assigning them to multiple teams and specific users, setting project colors, and auto-calculating project status based on task completion. Supports **Project Archiving** to hide completed work without permanent deletion.
- **Tasks (Taches)**: CRUD operations for tasks, including priority levels, statuses, due dates, multiple tags (`multi-tag support`), and banner image uploads. Automatically syncs parent project statuses.
- **Incremental Referencing**: Projects and Tasks automatically generate human-readable incremental IDs (e.g., `PRJ-001`, `TSK-001`) via a centralized boot logic to standardize identification across the platform.
- **Kanban Customization**: Organizations can customize their Kanban board via `kanban_colors` to apply distinct visual styling to different column statuses.
  - **Location**: `app/Http/Controllers/ProjetController.php`, `app/Http/Controllers/TacheController.php`

## 6. Tags & Custom Labels
Dynamic labeling system to categorize tasks. Tags are scoped to specific organizations (`organization_id`) to ensure data isolation. Users can create custom colored tags alongside default system tags.
- **Location**: `app/Http/Controllers/TagController.php`

## 7. Comments & Mentions
Users can leave comments on specific tasks. Features user-association and authorization checks to ensure only the author can edit/delete their comment.
- **Mentions**: Users can tag other team members using `@username` in comments. This triggers an automated system notification specifically alerting the mentioned user.
- **Location**: `app/Http/Controllers/CommentairesController.php`

## 8. Notification Tracking & Management
In-app notification system that integrates with Laravel's core polymorphic notifications.
- **Listing & Read/Unread Status**: Fetching paginated lists, marking as read, and getting unread counts for the dashboard sidebar.
- **Human-Readable Deadlines**: Notification messaging translates raw dates into intuitive phrases (e.g., "dans X jours", "aujourd'hui", "en retard de X jours").
  - **Location**: `app/Http/Controllers/NotificationsController.php`

## 9. Automated Daily Reminders (CRON Jobs)
A daily scheduled task that checks organization settings and active projects.
- **Logic**: Evaluates `reminder_days_before_start` and `reminder_days_before_end` organization settings. If a project matches, it dispatches notifications to the entire assigned team.
  - **Worker Location**: `app/Console/Commands/SendProjectReminders.php`
- **Scheduler Location**: `app/Console/Kernel.php` (Runs daily at 08:00 and 22:00 UTC)

## 10. Standardized API Responses
All API endpoints have been refactored to wrap their execution in `try/catch` blocks.
- **Success Responses**: Return HTTP 200/201, `success => true`, a descriptive `message`, and the resulting data.
- **Validation Errors**: Return HTTP 422, `success => false`, and an `errors` object.
- **Not Found Errors**: Return HTTP 404, `success => false`, preventing Laravel's default HTML exception pages from breaking frontend JSON parsers.
- **System Failures**: Return HTTP 500, log the error securely, and provide an `error` trace.
  - **Locations**: Applied globally across `*Controller.php` files.

## 11. Automated Testing (PHPUnit)
The backend includes a comprehensive PHPUnit testing suite that runs in an isolated, in-memory SQLite database for maximum speed and reliability.
- **Model Factories**: Defines robust states for generating mock `Organization`, `Team`, `Projet`, `Tache`, and `User` records.
- **Feature Tests**: Covers Authentication, Profile modifications, RBAC restrictions on Organizations/Teams, and Project/Task management workflows.
- **Usage**: Run `./vendor/bin/phpunit` or `php artisan test` from the server directory.
  - **Location**: `tests/Feature/` and `database/factories/`
