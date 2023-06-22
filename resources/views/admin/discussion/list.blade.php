@extends ('admin.layouts.default')

@section ('header')
    <h3>@php echo __('labels.title.discussions'); @endphp</h3>
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
            No item has been found.
        </div>
    @endif

    <x-pagination :items=$items />

    <input type="hidden" id="createItem" value="{{ route('admin.discussions.create', $query) }}">
    <input type="hidden" id="destroyItems" value="{{ route('admin.discussions.index', $query) }}">
    <input type="hidden" id="publishItems" value="{{ route('admin.discussions.massPublish', $query) }}">
    <input type="hidden" id="unpublishItems" value="{{ route('admin.discussions.massUnpublish', $query) }}">

    <form id="selectedItems" action="{{ route('admin.discussions.index', $query) }}" method="post">
        @method('delete')
        @csrf
    </form>
@endsection

@push ('scripts')
    <script src="{{ asset('/js/admin/list.js') }}"></script>
@endpush
