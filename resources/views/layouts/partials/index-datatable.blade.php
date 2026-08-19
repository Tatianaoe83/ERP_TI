@push('third_party_stylesheets')
    @include('layouts.datatables_css')
@endpush

<div class="index-page__table-wrap table-responsive">
    {!! $dataTable->table(['width' => '100%', 'class' => 'table index-table w-full']) !!}
</div>

@push('third_party_scripts')
    @include('layouts.datatables_js')
    @include('layouts.partials.index-page-js')
    {!! $dataTable->scripts() !!}
@endpush
