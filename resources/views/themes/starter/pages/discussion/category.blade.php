@if ($settings['show_name'])
    <h3 class="pb-2">{{ $category->name }}</h3>
@endif

@if ($settings['show_description'])
    <div>{!! $category->description !!}</div>
@endif

@if ($settings['show_image'] && $category->image)
    <img class="discussion-image" src="{{ url('/').$category->image->getThumbnailUrl() }}" >
@endif

@if ($settings['show_search'])
    <div class="card">
	<div class="card-body">
	    @include('themes.starter.partials.filters')
	</div>
    </div>
@endif

<ul class="discussion-list pt-4">
    @if (count($discussions))
	@foreach ($discussions as $discussion)
	    @include ('themes.starter.partials.discussion')
	@endforeach
    @else
	<div>No discussion</div>
    @endif
</ul>

<x-pagination :items=$discussions />

@if ($settings['show_subcategories'])
    @include ('themes.starter.partials.discussion.subcategories')
@endif

@push ('scripts')
    <script type="text/javascript" src="{{ $public }}/js/discussion/category.js"></script>
@endpush
