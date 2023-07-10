<div class="position-relative">
@push ('style')
    <link rel="stylesheet" href="{{ asset('/vendor/adminlte/plugins/daterangepicker/daterangepicker.css') }}">
@endpush

    @include('themes.starter.layouts.flash-message')

    @php $action = (isset($discussion)) ? route('discussions.update', $query) : route('discussions.store', $query) @endphp
    <form method="post" action="{{ $action }}" id="itemForm">
        @csrf

        @if (isset($discussion))
            @method('put')
        @endif

        @foreach ($fields as $field)
            @php $value = (isset($discussion)) ? old($field->name, $field->value) : old($field->name); @endphp
            <x-input :field="$field" :value="$value" />
        @endforeach

        <input type="hidden" id="cancelEdit" value="{{ route('discussions.cancel', $query) }}">
        <input type="hidden" id="close" name="_close" value="0">
    </form>

    @if (isset($discussion))
        <form id="deleteItem" action="{{ route('discussions.destroy', $query) }}" method="post">
            @method('delete')
            @csrf
        </form>
    @endif
</div>

@push ('scripts')
    <script type="text/javascript" src="{{ asset('/vendor/adminlte/plugins/moment/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/vendor/adminlte/plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/vendor/tinymce/tinymce.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/vendor/codalia/c.ajax.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/admin/daterangepicker.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/admin/form.js') }}"></script>
  <script type="text/javascript" src="{{ asset('/js/admin/set.private.groups.js') }}"></script>
@endpush
