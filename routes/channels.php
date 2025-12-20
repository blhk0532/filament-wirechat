<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use AdultDate\FilamentWirechat\Helpers\MorphClassResolver;
use AdultDate\FilamentWirechat\Models\Conversation;

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

// Get all Filament panels
$panels = Filament::getPanels();

foreach ($panels as $panel) {
    $panelId = $panel->getId();
    $guard = $panel->getAuthGuard();

    // Conversation channel
    Broadcast::channel("{$panelId}.conversation.{conversationId}", function ($user, $conversationId) use ($guard) {
        // If $user is already authenticated by the application's broadcast auth, use it
        if (! $user) {
            // Fallback to checking the guard defined in the panel
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
            } else {
                return false;
            }
        }

        $conversation = Conversation::find($conversationId);

        return $conversation && $user->belongsToConversation($conversation);
    }, [
        'guards' => [$guard],
    ]);

    // Participant channel
    Broadcast::channel("{$panelId}.participant.{encodedType}.{id}", function ($user, $encodedType, $id) use ($guard) {
        // If $user is already authenticated by the application's broadcast auth, use it
        if (! $user) {
            // Fallback to checking the guard defined in the panel
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
            } else {
                return false;
            }
        }

        $morphType = MorphClassResolver::decode($encodedType);

        return $user->id == $id && $user->getMorphClass() == $morphType;
    }, [
        'guards' => [$guard],
    ]);
}
