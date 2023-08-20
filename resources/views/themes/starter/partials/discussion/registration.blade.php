<div class="p-2 bg-light border mb-2">
<h4>Registration</h4>
<table class="table caption-top">
    <caption>Attendees: {{ $discussion->getAttendees()->count() }}/{{ $discussion->max_attendees }}</caption>
    @foreach ($discussion->getAttendees() as $attendee)
        <tr><td>
            @if ($attendee->user_id == $discussion->owned_by)
                <h6>{{ $attendee->nickname }} <span class="badge bg-primary">Organizer</span></h6>
            @else
                <h6>{{ $attendee->nickname }}</h6>
            @endif
        </td></tr>
    @endforeach
</table>

@if ($discussion->isSoldOut() && !$discussion->isUserRegistered())
    <table class="table caption-top">
        <caption>Waiting list: {{ $discussion->getAttendeesOnWaitingList()->count() }}</caption>
        @foreach ($discussion->getAttendeesOnWaitingList() as $attendee)
            <tr><td>
                <h6>{{ $attendee->nickname }}</h6>
            </tr></td>
        @endforeach
    </table>
@endif

@if (auth()->check() && auth()->user()->id != $discussion->owned_by)
    @if (!$discussion->isSoldOut() && !$discussion->isUserRegistered())
        <div class="text-center">
            <button class="btn btn-success" id="register" data-url="{{ route('discussions.register', $discussion->id) }}" type="button">
                Register
            </button>
        </div>
    @endif

    @if ($discussion->isUserRegistered())
        <div class="text-center">
            <button class="btn btn-danger" id="unregister" data-url="{{ route('discussions.unregister', $discussion->id) }}" type="button">
                Unregister
            </button>
        </div>
    @endif

    @if ($discussion->isSoldOut() && !$discussion->isUserRegistered())
        @if (!$discussion->isUserOnWaitingList())
            <div class="text-center">
                <button class="btn btn-secondary" id="registerWaitingList" data-url="{{ route('discussions.register', $discussion->id) }}" type="button">
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
@endif
</div>
