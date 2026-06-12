<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Feedback\StoreRequest;
use App\Repositories\FeedbackRepository;
use App\Service\FeedbackCaptchaService;
use App\Service\FeedbackNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class FeedbackController extends Controller
{
    /**
     * Показывает форму обратной связи.
     *
     * @return View
     */
    public function create(): View
    {
        return view('front.feedback.create', [
            'title' => 'Обратная связь',
            'hideTopProfile' => true,
        ]);
    }

    /**
     * Отдает изображение CAPTCHA для защиты формы от спама.
     *
     * @param FeedbackCaptchaService $captcha
     * @return Response
     */
    public function captcha(FeedbackCaptchaService $captcha): Response
    {
        return $captcha->imageResponse();
    }

    /**
     * Сохраняет сообщение обратной связи и возвращает пользователя к форме.
     *
     * @param StoreRequest $request
     * @param FeedbackRepository $feedback
     * @param FeedbackNotificationService $notifications
     * @param FeedbackCaptchaService $captcha
     * @return RedirectResponse
     */
    public function store(
        StoreRequest $request,
        FeedbackRepository $feedback,
        FeedbackNotificationService $notifications,
        FeedbackCaptchaService $captcha,
    ): RedirectResponse
    {
        $data = $request->toDto();

        $feedback->createFromData($data);
        $notifications->sendSubmittedNotification($data);
        $captcha->forget();

        return redirect()
            ->route('front.feedback.create')
            ->with('status', 'Сообщение отправлено. На указанный адрес электронной почты отправлено уведомление.');
    }
}
