    <h1 class="h2">{{ $discussion->subject}}</h1>

    <div>
        @date ($discussion->discussion_date->tz($page['timezone']))
    </div>

    @include('themes.starter.partials.discussion.time')

    <div class="content">
        {!! $discussion->description !!}
    </div>

    <div>
        Platform: {{ __('labels.discussion.'.$discussion->platform) }}
    </div>

    @if ($discussion->isUserRegistered() && $discussion->getTimeBeforeDiscussionInMinutes() < $discussion::DELAY_BEFORE_SHOWING_LINK)
        <div>
            {{ $discussion->discussion_link }}
        </div>
    @endif

    @if ($daypicker)
        <div>
            <a href="{{ url('/'.$segments['discussions']) }}{{ '?_day_picker='.$daypicker }}" class="btn btn-success btn-sm active" role="button" aria-pressed="true">{{ \Carbon\Carbon::parse($daypicker, $page['timezone'])->isoFormat('MMM Do YY') }}</a>
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

    @include('themes.starter.partials.discussion.comments')

@push ('scripts')
    <!--<script src="{{ asset('/vendor/codalia/c.ajax.js') }}"></script>-->
    <!--<script src="{{ asset('/js/discussion.js') }}"></script>-->
@endpush
