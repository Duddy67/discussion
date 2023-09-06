<div class="position-relative">
    @include('themes.starter.partials.flash-message')
@php var_dump(auth()->user()->canAccessAdmin()) @endphp
    @php $action = (isset($discussion)) ? route('discussions.update', $query) : route('discussions.store', $query) @endphp
    <form method="post" action="{{ $action }}" id="form" role="form">
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

        <div class="text-center">
            <button class="btn btn-success" id="submit" type="button">Save</button>
        </div>

        @if (isset($discussion))
            <div class="text-center">
                <button class="btn btn-danger" id="delete" type="button">
                    Delete
                </button>
            </div>

            <div class="text-center">
                <button class="btn btn-info" id="cancel" data-url="{{ route('discussions.cancel', $discussion->id) }}" type="button">Cancel</button>
            </div>
        @else
            <div class="text-center">
                <button class="btn btn-info" id="cancel" data-url="{{ route('discussions.cancel', $query) }}" type="button">Cancel</button>
            </div>
        @endif
    </form>

    @if (isset($discussion))
        <form id="deleteItem" action="{{ route('discussions.destroy', $query) }}" method="post">
            @method('delete')
            @csrf
        </form>
    @endif

    <div class="ajax-progress d-none" id="ajax-progress">
        <img src="{{ asset('/images/progress-icon.gif') }}" class="progress-icon" style="top:20%;" />
    </div>
</div>

@push ('scripts')
    <script type="text/javascript" src="{{ asset('/vendor/adminlte/plugins/select2/js/select2.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/vendor/tinymce/tinymce.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/vendor/codalia/c.ajax.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/admin/form.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/admin/set.private.groups.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/tinymce/filemanager.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/discussion.js') }}"></script>
@endpush
