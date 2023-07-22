
@if (isset($category) && $category)
    <table class="table">
	@if (count($discussions))
	    @foreach ($discussions as $discussion)
		@include ('themes.starter.partials.discussion')
	    @endforeach
	@else
	    <div>No discussion</div>
	@endif
    </table>
@endif

@push ('scripts')
    <script type="text/javascript" src="{{ $public }}/js/post/category.js"></script>
@endpush
