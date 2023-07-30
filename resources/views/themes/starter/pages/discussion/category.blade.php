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
@php var_dump($settings) @endphp
@if (!count($discussions))
    <div>No discussion</div>
@else
    <table class="table table-striped">
        <thead>
          <tr>
            <th scope="col">Discussion</th>
            <th scope="col">Time</th>
            <th scope="col">Organizer</th>
            <th scope="col">Attendees</th>
          </tr>
        </thead>
        <tbody>
        @foreach ($discussions as $discussion)
            @include ('themes.starter.partials.discussion')
        @endforeach
        </tbody>
    </table>
@endif

<x-pagination :items=$discussions />

@push ('scripts')
    <script type="text/javascript" src="{{ $public }}/js/discussion/category.js"></script>
@endpush
