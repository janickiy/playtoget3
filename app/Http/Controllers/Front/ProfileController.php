<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\FriendRepository;
use App\Repositories\ProfileRepository;
use Illuminate\Contracts\View\View;
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

    public function edit(): View
    {
        return view('front.pages.placeholder', ['title' => 'Редактирование профиля']);
    }
}
