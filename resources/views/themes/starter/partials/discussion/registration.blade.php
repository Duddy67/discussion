@if (auth()->check() && $discussion->getTimeBeforeDiscussion())
    @if (!$discussion->isUserRegistered())
        <div class="text-center"><button class="btn btn-success" id="register" data-url="{{ route('discussions.register', $discussion->id) }}" type="button">Register</button></div>
    @else
        <div class="text-center"><button class="btn btn-danger" id="unregister" data-url="{{ route('discussions.unregister', $discussion->id) }}" type="button">Unregister</button></div>
    @endif
@endif
