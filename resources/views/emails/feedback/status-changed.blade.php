<p>Здравствуйте, {{ $feedback->name ?: 'пользователь' }}!</p>

<p>Статус вашего обращения изменен.</p>

<p><strong>Предыдущий статус:</strong> {{ $previousStatus->label() }}</p>
<p><strong>Новый статус:</strong> {{ $feedback->statusLabel() }}</p>

@if ($feedback->subject)
    <p><strong>Тема:</strong> {{ $feedback->subject }}</p>
@endif

@if ($feedback->statusEnum() === \App\Enums\FeedbackStatus::Closed && $feedback->answer)
    <p><strong>Ответ:</strong></p>
    <p>{!! nl2br(e($feedback->answer)) !!}</p>
@endif

<p>Спасибо за обращение.</p>
