    <h1 class="h2"><a href="{{ url($discussion->getUrl()) }}">{{ $discussion->subject}}</a></h1>

    @if ($settings['show_created_at'])
        <div>@date ($discussion->created_at->tz($timezone))</div>
    @endif

    @if ($settings['show_owner'])
        <div>{{ $discussion->owner_name }}</div>
    @endif

    @if ($settings['show_excerpt'])
        <div class="excerpt">
            {!! $discussion->excerpt !!}
        </div>
    @endif

    <div class="content">
        @if ($settings['show_image'] && $discussion->image)
            <img class="discussion-image img-fluid" src="{{ url('/').$discussion->image->getThumbnailUrl() }}" >
        @endif
        {!! $discussion->description !!}
    </div>

    @if ($settings['show_categories'] && count($discussion->categories))
        <p class="categories">
            <h6>Categories</h6>
            @foreach ($discussion->categories as $category)
                <a href="{{ url('/'.$segments['plugin'].$category->getUrl()) }}" class="btn btn-primary btn-sm active" role="button" aria-pressed="true">{{ $category->name }}</a>
            @endforeach
        </p>
    @endif

    @if ($settings['allow_comments'])
        @include('themes.starter.partials.discussion.comments')
    @endif
@endif

