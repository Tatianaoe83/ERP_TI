@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-12">
                <a href="{{ route('reportes.create') }}" class="btn btn-primary float-right">
                    <i class="fas fa-plus"></i> Crear nuevo reporte
                </a>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">

    @include('flash::message')

    <!-- <a href="{{ route('reportes.create') }}" class="btn btn-primary float-right mb-2">
        <i class="fas fa-plus"></i> Crear nuevo reporte
    </a> -->

    <div class="clearfix"></div>

    <div class="card">
        <div class="card-body p-0">
            @include('reportes.table')

            <div class="card-footer clearfix">
                <div class="float-right">
                    {{-- Aquí podrías repetir otro botón si quisieras --}}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection