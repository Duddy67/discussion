@php $time = $discussion->getTimeBeforeDiscussion(); @endphp

@if ($time)
    <div class="p-2 bg-light border mb-2">
        <span class="fw-bold">Starts in:</span> 
        @if ($time->days) 
            {{ $time->days }} days 
        @endif

        @if ($time->hours) 
            {{ $time->hours }} hours 
        @endif

        {{ $time->minutes }} minutes 
    </div>
@endif
