<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\FriendRepository;
use App\Repositories\ProfileRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show(int $user, ProfileRepository $profiles, FriendRepository $friends): View
    {
        $profile = $profiles->profile($user);

        abort_if(! $profile, 404);

        $viewer = Auth::guard('web')->user();
        $friendshipStatus = $friends->friendshipStatus($viewer?->id, $profile->id);
        $permissions = $profiles->permissions($profile, $viewer, $friendshipStatus);

        return view('front.profile.show', [
            'title' => 'Стена',
            'hideTopProfile' => true,
            'profileUser' => $profile,
            'viewer' => $viewer,
            'profileData' => $profiles->profileData($profile),
            'permissions' => $permissions,
            'friendshipStatus' => $friendshipStatus,
            'comments' => $permissions['wall']
                ? $profiles->wallComments($profile->id, 10, 0, $viewer)
                : collect(),
            'commentsPageSize' => 10,
            'hasMoreComments' => $permissions['wall']
                ? $profiles->hasMoreWallComments($profile->id, 10, 0)
                : false,
        ]);
    }

    public function edit(ProfileRepository $profiles): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        return view('front.profile.edit', [
            'title' => 'Настройки',
            'editableProfileAssets' => true,
            'profileLayout' => $profiles->topProfileData($viewer),
            'user' => $viewer,
            'settings' => $profiles->profileSettings($viewer),
            'blockedUsers' => $profiles->blockedUsers($viewer),
            'securityLogs' => $profiles->securityLogs($viewer),
            'permissionFields' => $profiles->permissionFields(),
            'notificationFields' => $profiles->notificationFields(),
        ]);
    }

    public function update(Request $request, ProfileRepository $profiles): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        $validated = $request->validate([
            'user.contact_email' => ['nullable', 'email', 'max:100'],
            'user.phone' => ['nullable', 'string', 'max:255'],
            'user.skype' => ['nullable', 'string', 'max:255'],
            'user.website' => ['nullable', 'string', 'max:255'],
            'user.permission_send_message' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_view_profile' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_view_friends' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_view_photo' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_view_video' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_view_wall' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_comment_photo' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_comment_video' => ['nullable', 'integer', 'in:0,1,2'],
            'user.permission_comment_wall' => ['nullable', 'integer', 'in:0,1,2'],
            'user.notification_friends_request' => ['nullable', 'in:yes'],
            'user.notification_private_messages' => ['nullable', 'in:yes'],
            'user.notification_wall_comments' => ['nullable', 'in:yes'],
            'user.notification_picture_comments' => ['nullable', 'in:yes'],
            'user.notification_video_comments' => ['nullable', 'in:yes'],
            'user.notification_answers_in_comments' => ['nullable', 'in:yes'],
            'user.notification_events' => ['nullable', 'in:yes'],
            'user.notification_birthdays' => ['nullable', 'in:yes'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        $profiles->updateProfileSettings(
            $viewer,
            $validated['user'] ?? [],
            $request->file('avatar'),
            $request->file('cover'),
        );

        return redirect()
            ->route('front.profile.edit')
            ->with('status', 'Изменения сохранены');
    }
}
