<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Admin\Feedback\FeedbackAdminData;
use App\Enums\FeedbackStatus;
use App\Http\Requests\Admin\Feedback\EditRequest;
use App\Models\Feedback;
use App\Repositories\FeedbackRepository;
use App\Service\FeedbackNotificationService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    /**
     * Connects the feedback requests repository and notification service.
     */
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly FeedbackNotificationService $notifications,
    ) {
        parent::__construct();
    }

    /**
     * Shows list feedback requests.
     */
    public function index(): View
    {
        return view('admin.feedback.index', [
            'title' => 'Feedback',
        ]);
    }

    /**
     * Shows feedback request feedback.
     */
    public function show(int $id): View
    {
        $row = $this->feedbackRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.feedback.show', [
            'row' => $row,
            'title' => 'View feedback request',
        ]);
    }

    /**
     * Shows form editing status and reply.
     */
    public function edit(int $id): View
    {
        $row = $this->feedbackRepository->find($id);

        abort_if($row === null, 404);

        return view('admin.feedback.create_edit', [
            'row' => $row,
            'statusOptions' => $this->feedbackRepository->statusOptions(),
            'title' => 'Edit feedback request',
        ]);
    }

    /**
     * Updates status and response to the request, sending a notification when status changes.
     */
    public function update(EditRequest $request): RedirectResponse
    {
        /** @var Feedback|null $feedback */
        $feedback = $this->feedbackRepository->find((int) $request->input('id'));

        abort_if($feedback === null, 404);

        $previousStatus = FeedbackStatus::tryFrom((int) $feedback->status) ?? FeedbackStatus::New;

        try {
            $this->feedbackRepository->updateFromAdminData(FeedbackAdminData::fromArray($request->validated()));
            $feedback->refresh();

            if ($previousStatus !== $feedback->statusEnum()) {
                $this->notifications->sendStatusChangedNotification($feedback, $previousStatus);
            }
        } catch (Exception $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.feedback.index')
            ->with('success', 'Data updated successfully');
    }
}
