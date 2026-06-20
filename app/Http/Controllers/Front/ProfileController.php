<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Profile\ProfileSettingsRequest;
use App\Repositories\FriendRepository;
use App\Repositories\MessageRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\UserRepository;
use App\Service\ProfileDeletionService;
use App\Service\ProfileUpdateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{

    /**
     * Shows the user's profile, his data and comment feed.
     *
     * @param int $user
     * @param ProfileRepository $profiles
     * @param FriendRepository $friends
     * @return View
     */
    public function show(int $user, ProfileRepository $profiles, FriendRepository $friends): View
    {
        $profile = $profiles->profile($user);

        abort_if(! $profile, 404);

        $viewer = Auth::guard('web')->user();
        $friendshipStatus = $friends->friendshipStatus($viewer?->id, $profile->id);
        $permissions = $profiles->permissions($profile, $viewer, $friendshipStatus);

        return view('front.profile.show', [
            'title' => 'Wall',
            'hideTopProfile' => true,
            'profileUser' => $profile,
            'viewer' => $viewer,
            'profileData' => $profiles->profileData($profile),
            'permissions' => $permissions,
            'friendshipStatus' => $friendshipStatus,
            'comments' => $permissions['profile'] && $permissions['wall']
                ? $profiles->wallComments($profile->id, 10, 0, $viewer)
                : collect(),
            'commentsPageSize' => 10,
            'hasMoreComments' => $permissions['profile'] && $permissions['wall']
                ? $profiles->hasMoreWallComments($profile->id, 10, 0)
                : false,
        ]);
    }

    /**
     * Shows the current user profile edit form.
     *
     * @param ProfileRepository $profiles
     * @return View|RedirectResponse
     */
    public function edit(ProfileRepository $profiles): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        return view('front.profile.edit', [
            'title' => 'Settings',
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

    /**
     * Shows list dialogues current user.
     *
     * @param int $user
     * @param FriendRepository $friends
     * @param MessageRepository $messages
     * @return View|RedirectResponse
     */
    public function dialogues(
        int $user,
        FriendRepository $friends,
        MessageRepository $messages,
    ): View|RedirectResponse {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        if ((int) $viewer->id !== $user) {
            return redirect()->route('front.profile.messages.index', ['user' => $viewer->id]);
        }

        return view('front.profile.dialogues', [
            'title' => 'Dialogs',
            'hideTopProfile' => true,
            'viewer' => $viewer,
            'friends' => $friends->friendsFor($viewer->id, 100, 0),
            'dialogues' => $messages->dialogues($viewer),
        ]);
    }

    /**
     * Shows the current user's correspondence with the selected interlocutor.
     *
     * @param int $user
     * @param int $recipient
     * @param UserRepository $users
     * @param MessageRepository $messages
     * @return View|RedirectResponse
     */
    public function messages(
        int $user,
        int $recipient,
        UserRepository $users,
        MessageRepository $messages,
    ): View|RedirectResponse {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        if ((int) $viewer->id !== $user) {
            return redirect()->route('front.profile.messages.show', [
                'user' => $viewer->id,
                'recipient' => $recipient,
            ]);
        }

        $receiver = $users->findActive($recipient);
        abort_if(! $receiver, 404);

        $messages->markConversationRead($viewer, $receiver);

        return view('front.profile.messages', [
            'title' => 'Dialogue',
            'hideTopProfile' => true,
            'viewer' => $viewer,
            'receiver' => $receiver,
            'canSendMessage' => $messages->canSendMessage($viewer, $receiver),
            'messages' => $messages->conversation($viewer, $receiver, 10, 0),
            'messagesPageSize' => 10,
            'hasMoreMessages' => $messages->hasMoreConversation($viewer, $receiver, 10, 0),
        ]);
    }

    /**
     * Validates and saves the current user's profile settings.
     *
     * @param ProfileSettingsRequest $request
     * @param ProfileUpdateService $profileUpdates
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function update(ProfileSettingsRequest $request, ProfileUpdateService $profileUpdates): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        $profileUpdates->update($viewer, $request->toDto());

        return redirect()
            ->to(route('front.profile.edit') . '#' . $request->activeTab())
            ->with('status', __('profile.messages.updated'));
    }

    /**
     * Sends the current user an account deletion confirmation link.
     *
     * @param ProfileDeletionService $profileDeletions
     * @return RedirectResponse
     */
    public function requestAccountDeletion(ProfileDeletionService $profileDeletions): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        $profileDeletions->sendConfirmation($viewer);

        return redirect()
            ->to(route('front.profile.edit') . '#profile')
            ->with('status', __('profile.messages.deletion_requested'));
    }

    /**
     * Confirms account deletion by token and logs the user out.
     *
     * @param string $token
     * @param Request $request
     * @param ProfileDeletionService $profileDeletions
     * @return RedirectResponse
     */
    public function confirmAccountDeletion(
        string $token,
        Request $request,
        ProfileDeletionService $profileDeletions,
    ): RedirectResponse {
        $deletedUser = $profileDeletions->confirm($token);

        if ((int) Auth::guard('web')->id() === (int) $deletedUser->id) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()
            ->route('front.home')
            ->with('status', __('profile.messages.account_closed'));
    }
}
