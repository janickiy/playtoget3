<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FeedbackStatus;
use App\Enums\UserStatus;
use App\Models\Announcement;
use App\Models\Community;
use App\Models\Content;
use App\Models\Event;
use App\Models\Feedback;
use App\Models\Log;
use App\Models\SportBlock;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Shows dashboard with core admin metrics and recent activity.
     */
    public function index(): View
    {
        $stats = [
            [
                'label' => 'Users',
                'value' => User::query()->count(),
                'description' => 'Registered profiles',
                'icon' => 'fas fa-users',
                'class' => 'bg-info',
                'url' => route('admin.users.index'),
            ],
            [
                'label' => 'Communities',
                'value' => Community::query()->count(),
                'description' => 'Teams and groups',
                'icon' => 'fas fa-users-cog',
                'class' => 'bg-success',
                'url' => route('admin.communities.index'),
            ],
            [
                'label' => 'Events',
                'value' => Event::query()->count(),
                'description' => 'Created events',
                'icon' => 'fas fa-calendar-alt',
                'class' => 'bg-warning',
                'url' => route('admin.events.index'),
            ],
            [
                'label' => 'Feedback',
                'value' => Feedback::query()->where('status', FeedbackStatus::New->value)->count(),
                'description' => 'New requests',
                'icon' => 'fas fa-envelope',
                'class' => 'bg-danger',
                'url' => route('admin.feedback.index'),
            ],
            [
                'label' => 'Sports blocks',
                'value' => SportBlock::query()->count(),
                'description' => 'Venues, shops and fitness',
                'icon' => 'fas fa-running',
                'class' => 'bg-primary',
                'url' => route('admin.sport-blocks.index'),
            ],
            [
                'label' => 'Pages',
                'value' => Content::query()->count(),
                'description' => 'Content sections',
                'icon' => 'fas fa-file-alt',
                'class' => 'bg-secondary',
                'url' => route('admin.content.index'),
            ],
            [
                'label' => 'Announcements',
                'value' => Announcement::query()->count(),
                'description' => 'Published and drafts',
                'icon' => 'fas fa-bullhorn',
                'class' => 'bg-purple',
                'url' => route('admin.announcements.index'),
            ],
            [
                'label' => 'Logs',
                'value' => Log::query()->count(),
                'description' => 'Sign-in records',
                'icon' => 'fas fa-clipboard-list',
                'class' => 'bg-dark',
                'url' => route('admin.logs.index'),
            ],
        ];

        $latestUsers = User::query()
            ->latest('id')
            ->limit(5)
            ->get(['id', 'email', 'firstname', 'lastname', 'status', 'created_at']);

        $latestFeedback = Feedback::query()
            ->latest('id')
            ->limit(5)
            ->get(['id', 'subject', 'name', 'email', 'status', 'time']);

        $latestLogs = Log::query()
            ->with('user:id,email,firstname,lastname')
            ->latest('last_sign_in_at')
            ->limit(5)
            ->get(['id', 'user_id', 'ip', 'last_sign_in_at']);

        return view('admin.dashboard.index', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'latestUsers' => $latestUsers,
            'latestFeedback' => $latestFeedback,
            'latestLogs' => $latestLogs,
            'newUsersCount' => User::query()->where('status', UserStatus::New->value)->count(),
        ]);
    }
}
