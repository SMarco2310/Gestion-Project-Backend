# Backend Features & Implementation Guide

This document lists all the major features implemented in the backend application, including their associated logic, architecture, and exactly where to find the source code.

## 1. Authentication & User Management
Handles user sign-up, sign-in, profile management, and password recovery.
- **Registration & Login**: Issues API tokens for secured routes. Implements initial integration for auto-accepting invitations during sign-up.
  - **Location**: `app/Http/Controllers/AuthController.php`
- **Profile Management**: Profile picture uploads, bio updates, and account termination.
  - **Location**: `app/Http/Controllers/UserController.php`
- **Password Resets**: Sends recovery emails and handles token verification for password resets.
  - **Location**: `app/Http/Controllers/PasswordResetController.php`

## 2. Organization & Team Hierarchy (Multi-Tenancy)
Supports a structured approach where users belong to Organizations, and within Organizations, they belong to specific Teams. Projects are assigned to Teams.
- **Organizations**: Creating and managing high-level workspaces and their global settings (like reminder thresholds).
  - **Location**: `app/Http/Controllers/OrganizationController.php`
- **Teams**: Sub-groups within an organization that hold specific projects and members.
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
- **Projects**: Creating projects, assigning them to teams, and auto-calculating project status based on task completion.
  - **Location**: `app/Http/Controllers/ProjetController.php`
- **Tasks (Taches)**: CRUD operations for tasks, including priority levels, statuses, due dates, tags, and banner image uploads. Automatically syncs parent project statuses.
  - **Location**: `app/Http/Controllers/TacheController.php`

## 6. Tags & Custom Labels
Dynamic labeling system to categorize tasks. Users can create custom colored tags alongside default system tags.
- **Location**: `app/Http/Controllers/TagController.php`

## 7. Comments & Communication
Users can leave comments on specific tasks. Features user-association and authorization checks to ensure only the author can edit/delete their comment.
- **Location**: `app/Http/Controllers/CommentairesController.php`

## 8. Notification Tracking & Management
In-app notification system that integrates with Laravel's core polymorphic notifications.
- **Listing & Read/Unread Status**: Fetching paginated lists, marking as read, and getting unread counts for the dashboard sidebar.
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
