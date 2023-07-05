<li>
    <h3><a href="{{ url($discussion->getUrl()) }}">{{ $discussion->subject }}</a></h3>

    <div>
        {!! $discussion->description!!}
    </div>
</li>
