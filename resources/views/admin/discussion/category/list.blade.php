@extends ('admin.layouts.default')

@section ('header')
    <p class="h3">{{ __('labels.title.categories') }}</p>
@endsection

@section ('main')
    <div class="card">
        <div class="card-body">
            <x-toolbar :items=$actions />
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <x-filters :filters="$filters" :url="$url" />
        </div>
    </div>

    @if (!empty($rows)) 
        <x-item-list :columns="$columns" :rows="$rows" :url="$url" />
    @else
        <div class="alert alert-info" role="alert">
            {{ __('messages.generic.no_item_found') }}
        </div>
    @endif

    <input type="hidden" id="createItem" value="{{ route('admin.discussions.categories.create', $query) }}">
    <input type="hidden" id="destroyItems" value="{{ route('admin.discussions.categories.index', $query) }}">
    <input type="hidden" id="checkinItems" value="{{ route('admin.discussions.categories.massCheckIn', $query) }}">
    <input type="hidden" id="publishItems" value="{{ route('admin.discussions.categories.massPublish', $query) }}">
    <input type="hidden" id="unpublishItems" value="{{ route('admin.discussions.categories.massUnpublish', $query) }}">

    <form id="selectedItems" action="{{ route('admin.discussions.categories.index', $query) }}" method="post">
        @method('delete')
        @csrf
    </form>
@endsection

@push ('scripts')
    <script src="{{ asset('/js/admin/list.js') }}"></script>
@endpush
