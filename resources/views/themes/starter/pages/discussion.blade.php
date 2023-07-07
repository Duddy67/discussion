    <h1 class="h2"><a href="{{ url($discussion->getUrl()) }}">{{ $discussion->subject}}</a></h1>

    <div>@date ($discussion->discussion_date->tz($timezone))</div>

    @include('themes.starter.partials.discussion.time')

    <div>{{ $discussion->nickname}}</div>

    <div class="content">
        {!! $discussion->description !!}
    </div>
    <div><img src="{{ $discussion->getMediaThumbnail() }}"></div>

    <div>Attendees: {{ $discussion->subscriptions->count() }}/{{ $discussion->max_attendees }}</div>

    @if ($discussion->subscriptionsOnWaitingList->count())
        <div>Waiting list: {{ $discussion->subscriptionsOnWaitingList->count() }}</div>
    @endif

    <div>Platform: {{ __('labels.discussion.'.$discussion->platform) }}</div>

