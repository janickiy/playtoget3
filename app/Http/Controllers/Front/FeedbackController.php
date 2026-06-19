<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Feedback\StoreRequest;
use App\Repositories\FeedbackRepository;
use App\Service\FeedbackNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FeedbackController extends Controller
{
    /**
     * Shows form feedback.
     *
     * @return View
     */
    public function create(): View
    {
        return view('front.feedback.create', [
            'title' => 'Feedback',
            'hideTopProfile' => true,
        ]);
    }

    /**
     * Saves the feedback message and returns the user to the form.
     *
     * @param StoreRequest $request
     * @param FeedbackRepository $feedback
     * @param FeedbackNotificationService $notifications
     * @return RedirectResponse
     */
    public function store(
        StoreRequest $request,
        FeedbackRepository $feedback,
        FeedbackNotificationService $notifications,
    ): RedirectResponse
    {
        $data = $request->toDto();

        $feedback->createFromData($data);
        $notifications->sendSubmittedNotification($data);

        return redirect()
            ->route('front.feedback.create')
            ->with('status', 'Your message has been sent. A notification has been sent to the specified email address.');
    }
}
