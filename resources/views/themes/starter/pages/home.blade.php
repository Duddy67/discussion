
@if (isset($category) && $category)
    <ul class="post-list">
	@if (count($discussions))
	    @foreach ($discussions as $discussion)
		@include ('themes.starter.partials.discussion')
	    @endforeach
	@else
	    <div>No discussion</div>
	@endif
    </ul>
@endif

@push ('scripts')
    <script type="text/javascript" src="{{ $public }}/js/post/category.js"></script>
@endpush
