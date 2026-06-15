<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Profile\ProfileSettingsRequest;
use App\Repositories\FriendRepository;
use App\Repositories\MessageRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\UserRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{

    /**
     * Показывает профиль пользователя, его данные и ленту комментариев.
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
            'title' => 'Стена',
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
     * Показывает форму редактирования профиля текущего пользователя.
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

    /**
     * Показывает список диалогов текущего пользователя.
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
            'title' => 'Диалоги',
            'hideTopProfile' => true,
            'viewer' => $viewer,
            'friends' => $friends->friendsFor($viewer->id, 100, 0),
            'dialogues' => $messages->dialogues($viewer),
        ]);
    }

    /**
     * Показывает переписку текущего пользователя с выбранным собеседником.
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
            'title' => 'Диалог',
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
     * Валидирует и сохраняет настройки профиля текущего пользователя.
     *
     * @param ProfileSettingsRequest $request
     * @param ProfileRepository $profiles
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function update(ProfileSettingsRequest $request, ProfileRepository $profiles): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        $profiles->updateProfileSettings($viewer, $request->toDto());

        return redirect()
            ->route('front.profile.edit')
            ->with('status', 'Изменения сохранены');
    }
}
