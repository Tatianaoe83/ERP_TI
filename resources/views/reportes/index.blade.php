@extends('layouts.app')

@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2"></div>
    </div>
</section>

<div class="content px-3">


    <div class="clearfix"></div>

    <div class="card">
        <div class="card-body p-0">
            @include('reportes.table')

            <div class="card-footer clearfix">
                <div class="float-right">

                </div>
            </div>
        </div>

    </div>
</div>
@endsection