<?php

use App\Models\Community;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The required callback is used to authenticate if
| an incoming user can listen on the given channel.
|
*/

/**
 * Channel for community chat.
 * Users can only subscribe if they are members of the community.
 */
Broadcast::channel('community.{communityId}', function ($user, $communityId) {
    // Get the community
    $community = Community::find($communityId);

    // Return true if user is a member of the community
    if ($community && $community->isMember($user)) {
        return [
            'id' => $user->id,
            'email' => $user->email,
        ];
    }

    return false;
});
