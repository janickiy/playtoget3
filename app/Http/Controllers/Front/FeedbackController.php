<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Feedback\StoreRequest;
use App\Repositories\FeedbackRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FeedbackController extends Controller
{
    /**
     * Показывает форму обратной связи.
     *
     * @return View
     */
    public function create(): View
    {
        return view('front.feedback.create', ['title' => 'Обратная связь']);
    }

    /**
     * Сохраняет сообщение обратной связи и возвращает пользователя к форме.
     *
     * @param StoreRequest $request
     * @param FeedbackRepository $feedback
     * @return RedirectResponse
     */
    public function store(StoreRequest $request, FeedbackRepository $feedback): RedirectResponse
    {
        $feedback->createFromData($request->toDto());

        return redirect()->route('front.feedback.create')->with('status', 'Сообщение отправлено.');
    }
}
