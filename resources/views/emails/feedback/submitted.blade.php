<p>Здравствуйте, {{ $feedback->name() }}!</p>

<p>Ваше сообщение отправлено. С вами свяжется сотрудник PlayToGet.</p>

@if ($feedback->subject())
    <p><strong>Тема:</strong> {{ $feedback->subject() }}</p>
@endif

<p><strong>Текст сообщения:</strong></p>
<p>{!! nl2br(e($feedback->message())) !!}</p>

<p>Спасибо за обращение.</p>
