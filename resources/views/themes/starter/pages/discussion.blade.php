    <h1 class="h2">{{ $discussion->subject}}</h1>

    <div class="content">
        {!! $discussion->description !!}
    </div>

    <div class="mb-2">
        <img src="{{ $discussion->getMediaThumbnail() }}">
    </div>

    <div class="p-2 bg-light border mb-2">
        <span class="fw-bold">Date: </span>@date ($discussion->discussion_date->tz($page['timezone']), 'd M Y H:i')
    </div>

    @include('themes.starter.partials.discussion.time')

    <div class="p-2 bg-light border mb-2">
        <span class="fw-bold">Platform: </span>{{ __('labels.discussion.'.$discussion->platform) }}
    </div>

    @if ($discussion->isUserRegistered() && $discussion->canShowDiscussionLink())
        <div class="p-2 bg-light border mb-2">
            <span class="fw-bold">Meeting link: </span>{{ $discussion->discussion_link }}
        </div>
    @endif

    @if ($daypicker)
        <div class="p-2 bg-light border mb-2">
            <span class="fw-bold">Day: </span><a href="{{ url('/'.$segments['discussions']) }}{{ '?_day_picker='.$daypicker }}" class="btn btn-success btn-sm active" role="button" aria-pressed="true">{{ \Carbon\Carbon::parse($daypicker, $page['timezone'])->isoFormat('MMM Do YY') }}</a>
        </div>
    @endif

    <div class="p-2 bg-light border mb-2">
        <span class="fw-bold">Category: </span><a href="{{ url('/'.$segments['discussions'].$discussion->category->getUrl()) }}" class="btn btn-primary btn-sm active" role="button" aria-pressed="true">{{ $discussion->category->name }}</a>
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
