@extends ('admin.layouts.default')

@section ('main')
    <h3>@php echo (isset($discussion)) ? __('labels.discussion.edit_discussion') : __('labels.discussion.create_discussion'); @endphp</h3>

    @include('admin.partials.x-toolbar')

    @php $action = (isset($discussion)) ? route('admin.discussions.update', $query) : route('admin.discussions.store', $query) @endphp
    <form method="post" action="{{ $action }}" id="itemForm">
        @csrf

        @if (isset($discussion))
            @method('put')
        @endif

        @php
                $dataTab = null;
                $dateFormats = [];
        @endphp

        @foreach ($fields as $field)
            @php $value = (isset($discussion)) ? old($field->name, $field->value) : old($field->name); @endphp
            <x-input :field="$field" :value="$value" />
        @endforeach

        @if ($field->type == 'date' && isset($field->format))
             @php $dateFormats[$field->name] = $field->format; @endphp
        @endif

        <input type="hidden" id="cancelEdit" value="{{ route('admin.discussions.cancel', $query) }}">
        <input type="hidden" id="close" name="_close" value="0">

        @foreach ($dateFormats as $key => $value)
            <input type="hidden" name="_date_formats[{{ $key }}]" value="{{ $value }}">
        @endforeach
    </form>

    @if (isset($discussion))
        <form id="deleteItem" action="{{ route('admin.discussions.destroy', $query) }}" method="post">
            @method('delete')
            @csrf
        </form>
    @endif
@endsection

@push ('style')
    <link rel="stylesheet" href="{{ asset('/vendor/adminlte/plugins/daterangepicker/daterangepicker.css') }}">
@endpush

@push ('scripts')
    <script type="text/javascript" src="{{ asset('/vendor/adminlte/plugins/moment/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/vendor/adminlte/plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/vendor/tinymce/tinymce.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/vendor/codalia/c.ajax.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/admin/daterangepicker.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/admin/form.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/admin/set.private.groups.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/admin/disable.toolbars.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/tinymce/filemanager.js') }}"></script>
@endpush
