    <h1 class="h2"><a href="{{ url($discussion->getUrl()) }}">{{ $discussion->subject}}</a></h1>

    <div>@date ($discussion->discussion_date->tz($timezone))</div>

    @include('themes.starter.partials.discussion.time')

    <div>{{ $discussion->nickname}}</div>

    <div class="content">
        {!! $discussion->description !!}
    </div>
    <div><img src="{{ $discussion->getMediaThumbnail() }}"></div>

    <div>Attendees: {{ $discussion->registrations->count() }}/{{ $discussion->max_attendees }}</div>

    @include('themes.starter.partials.discussion.registration')

    @if ($discussion->registrationsOnWaitingList->count())
        <div>Waiting list: {{ $discussion->registrationsOnWaitingList->count() }}</div>
    @endif

    <div>Platform: {{ __('labels.discussion.'.$discussion->platform) }}</div>

@push ('scripts')
    <script src="{{ asset('/vendor/codalia/c.ajax.js') }}"></script>
    <script src="{{ asset('/js/discussion.js') }}"></script>
@endpush
