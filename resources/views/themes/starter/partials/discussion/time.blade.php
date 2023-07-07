@php $time = $discussion->getTimeBeforeDiscussion(); @endphp

@if ($time)
    <div>
        Starts in: 
        @if ($time->days) 
            {{ $time->days }} days 
        @endif

        @if ($time->hours) 
            {{ $time->hours }} hours 
        @endif

        {{ $time->minutes }} minutes 
    </div>
@endif
