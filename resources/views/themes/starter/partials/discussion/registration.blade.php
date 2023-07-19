@if (auth()->check() && $discussion->getTimeBeforeDiscussion())

    @if ($discussion->getAttendees()->count())
        @foreach ($discussion->getAttendees() as $attendee)
            <div>{{ $attendee->nickname }} {{ $attendee->user_id }}</div>
        @endforeach
    @endif

    @if ($discussion->getAttendees()->count() < $discussion->max_attendees && auth()->user()->id != $discussion->owned_by)
        @if (!$discussion->isUserRegistered())
            <div class="text-center">
                <button class="btn btn-success" id="register" data-url="{{ route('discussions.register', $discussion->id) }}" type="button">
                    Register
                </button>
            </div>
        @else
            <div class="text-center">
                <button class="btn btn-danger" id="unregister" data-url="{{ route('discussions.unregister', $discussion->id) }}" type="button">
                    Unregister
                </button>
            </div>
        @endif
    @endif

    @if ($discussion->getAttendees()->count() >= $discussion->max_attendees && auth()->user()->id != $discussion->owned_by)
        @if (!$discussion->isUserOnWaitingList())
            <div class="text-center">
                <button class="btn btn-success" id="registerWaitingList" data-url="{{ route('discussions.register', $discussion->id) }}" type="button">
                    Register waiting list
                </button>
            </div>
        @else
            <div class="text-center">
                <button class="btn btn-danger" id="unregisterWaitingList" data-url="{{ route('discussions.unregister', $discussion->id) }}" type="button">
                    Unregister waiting list
                </button>
            </div>
        @endif
    @endif

        <h5>Waiting list</h5>
    @if ($discussion->getAttendeesOnWaitingList()->count())
        @foreach ($discussion->getAttendeesOnWaitingList() as $attendee)
            <div>{{ $attendee->nickname }} {{ $attendee->user_id }}</div>
        @endforeach
    @endif
@endif
