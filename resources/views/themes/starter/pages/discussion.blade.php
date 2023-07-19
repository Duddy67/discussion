    <h1 class="h2"><a href="{{ url($discussion->getUrl()) }}">{{ $discussion->subject}}</a></h1>

    <div>@date ($discussion->discussion_date->tz($timezone))</div>

    @include('themes.starter.partials.discussion.time')

    <div>@lang ('labels.discussion.organiser'): {{ $discussion->nickname}}</div>

    <div class="content">
        {!! $discussion->description !!}
    </div>
    <div>Platform: {{ __('labels.discussion.'.$discussion->platform) }}</div>
    <div><img src="{{ $discussion->getMediaThumbnail() }}"></div>

    <div>Attendees: {{ $discussion->getAttendees()->count() }}/{{ $discussion->max_attendees }}</div>

    @include('themes.starter.partials.discussion.registration')

    @if ($discussion->getAttendeesOnWaitingList()->count())
        <div>Waiting list: {{ $discussion->getAttendeesOnWaitingList()->count() }}</div>
    @endif

    @if ($discussion->canEdit() && $discussion->getTimeBeforeDiscussionInMinutes())
        <a href="{{ route('discussions.edit', $discussion->id) }}" class="btn btn-success">Edit</a>
    @endif

    @if (!auth()->check())
        <div>@lang ('messages.discussion.registration_required')</div>
    @endif

@push ('scripts')
    <script src="{{ asset('/vendor/codalia/c.ajax.js') }}"></script>
    <script src="{{ asset('/js/discussion.js') }}"></script>
@endpush
