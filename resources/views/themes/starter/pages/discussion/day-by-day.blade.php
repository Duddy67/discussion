<div>DAY BY DAY</div>
<table class="table">
    @if (count($discussions))
        @foreach ($discussions as $discussion)
            @include ('themes.starter.partials.discussion')
        @endforeach
    @else
        <div>No discussion</div>
    @endif
</table>
