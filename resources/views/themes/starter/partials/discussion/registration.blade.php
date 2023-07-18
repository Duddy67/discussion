@if (auth()->check() && auth()->user()->id != $discussion->owned_by && $discussion->getTimeBeforeDiscussion())

    @foreach ($discussion->registrations as $registration)
        <div>{{ $registration->nickname }} {{ $registration->user_id }}</div>
    @endforeach

    @if (!$discussion->isUserRegistered() && $discussion->registrations->count() > $discussion->max_attendees)
        <div class="text-center"><button class="btn btn-success" id="register" data-url="{{ route('discussions.register', $discussion->id) }}" type="button">Register</button></div>
    @else
        <div class="text-center"><button class="btn btn-danger" id="unregister" data-url="{{ route('discussions.unregister', $discussion->id) }}" type="button">Unregister</button></div>
    @endif
@endif
