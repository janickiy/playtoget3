<p>Hello, {{ $feedback->name ?: 'user' }}!</p>

<p>The status of your request has changed.</p>

<p><strong>Previous status:</strong> {{ $previousStatus->label() }}</p>
<p><strong>New status:</strong> {{ $feedback->statusLabel() }}</p>

@if ($feedback->subject)
    <p><strong>Subject:</strong> {{ $feedback->subject }}</p>
@endif

@if ($feedback->statusEnum() === \App\Enums\FeedbackStatus::Closed && $feedback->answer)
    <p><strong>Answer:</strong></p>
    <p>{!! nl2br(e($feedback->answer)) !!}</p>
@endif

<p>Thank you for contacting us.</p>
