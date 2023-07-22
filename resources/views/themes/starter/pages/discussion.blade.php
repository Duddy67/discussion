    <h1 class="h2"><a href="{{ url($discussion->getUrl()) }}">{{ $discussion->subject}}</a></h1>

    <div>
        @date ($discussion->discussion_date->tz($timezone))
    </div>

    @include('themes.starter.partials.discussion.time')

    <div class="content">
        {!! $discussion->description !!}
    </div>

    <div>
        Platform: {{ __('labels.discussion.'.$discussion->platform) }}
    </div>

    @if ($discussion->getTimeBeforeDiscussionInMinutes() < $discussion::DELAY_BEFORE_SHOWING_LINK)
        <div>
            {{ $discussion->discussion_link }}
        </div>
    @endif

    <div>
        <a href="{{ url('/'.$segments['discussions'].$discussion->category->getUrl()) }}" class="btn btn-primary btn-sm active" role="button" aria-pressed="true">{{ $discussion->category->name }}</a>
    </div>

    <div>
        <img src="{{ $discussion->getMediaThumbnail() }}">
    </div>

    @include('themes.starter.partials.discussion.registration')

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
