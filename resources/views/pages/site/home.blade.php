
@if ($category)
    <ul class="post-list">
	@if (count($posts))
	    @foreach ($posts as $post)
		@include ('partials.blog.post')
	    @endforeach
	@else
	    <div>No post</div>
	@endif
    </ul>
@endif

@push ('scripts')
    <script type="text/javascript" src="{{ $public }}/js/blog/category.js"></script>
@endpush
