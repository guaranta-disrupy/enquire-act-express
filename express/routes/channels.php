<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// User-specific private channel for notifications
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Presence channel for tracking users in a team
// Simple auth: Express to Express only - just verify user is authenticated
Broadcast::channel('team.{teamId}', function (User $user, int $teamId) {
    // Simple check: if authenticated, allow access (Express to Express only)
    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'profile_photo_url' => $user->profile_photo_url,
    ];
});

// Presence channel for tracking users working on a project
// Simple auth: Express to Express only - just verify user is authenticated
Broadcast::channel('project.{projectId}', function (User $user, int $projectId) {
    // Simple check: if authenticated, allow access (Express to Express only)
    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'profile_photo_url' => $user->profile_photo_url,
        'joined_at' => now()->toIso8601String(),
    ];
});

// Presence channel for tracking users editing a questionnaire (by code)
// Simple auth: Express to Express only - just verify user is authenticated
Broadcast::channel('questionnaire.{questionnaireCode}', function (User $user, string $questionnaireCode) {
    // Simple check: if authenticated and valid code, allow access (Express to Express only)
    if ($questionnaireCode === 'null' || $questionnaireCode === 'unknown') {
        return null;
    }
    
    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'profile_photo_url' => $user->profile_photo_url,
        'joined_at' => now()->toIso8601String(),
    ];
});

// Private channel for project-specific events
// Simple auth: Express to Express only - just verify user is authenticated
Broadcast::channel('project.{projectId}.events', function (User $user, int $projectId) {
    // Simple check: if authenticated, allow access (Express to Express only)
    return true;
});

// Private channel for questionnaire updates
// Simple auth: Express to Express only - just verify user is authenticated
Broadcast::channel('questionnaire.{questionnaireId}.updates', function (User $user, int $questionnaireId) {
    // Simple check: if authenticated, allow access (Express to Express only)
    return true;
});
