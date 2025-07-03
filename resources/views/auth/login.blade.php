@extends('layouts.auth_app')
@section('title')
    Admin Login
@endsection
@section('content')
<!-- Section: Design Block -->
<section class="text-center text-lg-start">
  <style>
    .cascading-right {
      margin-right: -50px;
    }

    @media (max-width: 991.98px) {
      .cascading-right {
        margin-right: 0;
      }
    }
  </style>

  <!-- Jumbotron -->
  <div class="container py-4">
    <div class="row g-0 align-items-center">
      <div class="col-12 col-md-12 col-lg-6 mb-5 mb-lg-0">
        <div class="card cascading-right bg-body-tertiary" style="
            backdrop-filter: blur(30px);
            ">
          <div class="card-body p-5 shadow-5 text-center">
          <img class="navbar-brand-full app-header-logo" src="{{ asset('img/logo.png') }}" width="50%"
          alt="Infyom Logo">
            <h4 class="fw-bold mb-5">Inicio de sesion</h4>
            <form method="POST" action="{{ route('login') }}">
            @csrf
                @if ($errors->any())
                    <div class="alert alert-danger p-0">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                  <!-- Database Select -->
                  <div class="form-group">
                    <label for="database">Base de Datos</label>
                    <select name="database" id="database" class="form-control" onchange="updateEnvDatabase(this.value)" required>
                        <option value="">Seleccione Base de Datos</option>
                        <option value="unidplay_controlinventarioti">Control Inventario TI</option>
                        <option value="unidplay_presupuestoscontrol">Presupuestos</option>
                    </select>
                </div>


              <!-- username input -->
              <div class="form-group">
                   
                    <input aria-describedby="usernameHelpBlock" id="username" type="username"
                           class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" name="username"
                           placeholder="Ingrese username" tabindex="1"
                           value="{{ (Cookie::get('username') !== null) ? Cookie::get('username') : old('username') }}" autofocus
                           required>
                    <div class="invalid-feedback">
                        {{ $errors->first('username') }}
                    </div>
                </div>

              <!-- Password input -->
              <div class="form-group">
                    <div class="d-block">
                       
                        <div class="float-right">
                           
                        </div>
                    </div>
                    <input aria-describedby="passwordHelpBlock" id="password" type="password"
                           value="{{ (Cookie::get('password') !== null) ? Cookie::get('password') : null }}"
                           placeholder="Ingrese password"
                           class="form-control{{ $errors->has('password') ? ' is-invalid': '' }}" name="password"
                           tabindex="2" required>
                    <div class="invalid-feedback">
                        {{ $errors->first('password') }}
                    </div>
                </div>

          
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script type="text/javascript">
                  

                    function updateEnvDatabase(value) {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                        fetch('/update-database', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({database: value}),
                            credentials: 'same-origin',
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                             
                                const Toast = Swal.mixin({
                                  toast: true,
                                  position: "top-end",
                                  showConfirmButton: false,
                                  timer:  1500,
                                  timerProgressBar: true,
                                  didOpen: (toast) => {
                                    toast.onmouseenter = Swal.stopTimer;
                                    toast.onmouseleave = Swal.resumeTimer;
                                  }
                                });
                                Toast.fire({
                                  icon: "success",
                                  title: "Base de datos actualizada correctamente"
                                }); 
                               
                            } else {
                              
                                const Toast = Swal.mixin({
                                  toast: true,
                                  position: "top-end",
                                  showConfirmButton: false,
                                  timer: 3000,
                                  timerProgressBar: true,
                                  didOpen: (toast) => {
                                    toast.onmouseenter = Swal.stopTimer;
                                    toast.onmouseleave = Swal.resumeTimer;
                                  }
                                });
                                Toast.fire({
                                  icon: "warning",
                                  title: 'Error al actualizar: ' + (data.error || 'Error desconocido')
                                }); 

                            }
                        })
                        .catch(error => {
                          
                            const Toast = Swal.mixin({
                                  toast: true,
                                  position: "top-end",
                                  showConfirmButton: false,
                                  timer: 3000,
                                  timerProgressBar: true,
                                  didOpen: (toast) => {
                                    toast.onmouseenter = Swal.stopTimer;
                                    toast.onmouseleave = Swal.resumeTimer;
                                  }
                                });
                                Toast.fire({
                                  icon: "error",
                                  title: "Error al actualizar la base de datos"
                                }); 

                        });
                    }
                </script>

               

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="remember" class="custom-control-input" tabindex="3"
                               id="remember"{{ (Cookie::get('remember') !== null) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="remember">Recordar</label>
                    </div>
                </div>
     
                

              

              <!-- Submit button -->
              <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                        Ingresar
                    </button>
                </div>

            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-5 mb-lg-0">
        <img  src="{{ asset('img/corporativo-web-2.jpg') }}" class="w-100 rounded-4 shadow-4"
          alt="" />
      </div>
    </div>
  </div>
  <!-- Jumbotron -->
</section>
<!-- Section: Design Block -->
@endsection