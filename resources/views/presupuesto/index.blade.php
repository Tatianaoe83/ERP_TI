@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Presupuestos</h3>
        </div>
        <div class="section-body">
            <div class="row">

                <div class="col-12 col-md-12 col-lg-12">
                
                <div class="card card-info">
                  <div class="card-header">
                    <h4>Generar reportes</h4>
                  </div>
                  <form enctype="multipart/form-data" action="presupuesto/descargar" method="POST" target="_blank">
                  {{ csrf_field() }}
                  <div class="card-body">

                        {!! Form::label('tipo', 'Tipo:') !!}
                        <select name="tipo" id="semestre" class="form-control" required>
                        <option value="mens">Mensual</option>
                        <option value="anual">Anual</option>
                        
                      </select>

                    
                        {!! Form::label('GerenciaID', 'Gerencia:') !!}

                        {!!Form::select('GerenciaID',App\Models\Gerencia::all()->
                            pluck('NombreGerencia','GerenciaID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control','required'])!!}

                  </div>
                  <div class="card-footer">
                     <button  type="submit" class="btn btn-success" name="submitbutton" value="pdf">Generar PDF</button>
                     <button type="submit" class="btn btn-primary" name="submitbutton" value="excel">Generar Excel</button>
                 </div>
                 </form>
              </div>

                    
                </div>
            </div>
        </div>
    </section>
@endsection
