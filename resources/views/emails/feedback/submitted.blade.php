<p>Hello, {{ $feedback->name() }}!</p>

<p>Your message has been sent. A PlayToGet team member will contact you.</p>

@if ($feedback->subject())
    <p><strong>Subject:</strong> {{ $feedback->subject() }}</p>
@endif

<p><strong>Message text:</strong></p>
<p>{!! nl2br(e($feedback->message())) !!}</p>

<p>Thank you for contacting us.</p>
