<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ERP TI Proser</title>
    <link rel="icon" href="{!! asset('img/mantenimiento.ico') !!}" />
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+Antique&display=swap');

        .fuente {
            font-family: 'Zen Kaku Gothic Antique';
        }

        .glass-card {
            background: rgba(250, 250, 250, 1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }

        .borderinput {
            border-color: rgba(189, 189, 189, 1);
        }
    </style>
</head>

<body>
    <div class="relative z-10" style="background-image: url('img/fondotech.jpg'); background-size: cover; background-position: center;">

        {{-- Contenedor principal --}}
        <div class="flex justify-center min-h-screen px-6 pt-12 fuente">
            <div class="w-full max-w-7xl grid lg:grid-cols-2 content-start pt-10 mt-10">

                <div class="hidden lg:block p-5 h-full">
                    <div class="w-full h-full flex flex-col justify-center">
                        @yield('content')
                    </div>
                </div>

                {{-- Login a la derecha --}}
                <div class="glass-card rounded-t-lg p-8 w-full max-w-md mx-auto">
                    <div class="mb-8">
                        <p class="text-black text-sm font-medium mb-2">BIENVENIDO DE NUEVO</p>
                        <h3 class="text-4xl font-semibold text-gray-800">Iniciar sesión</h3>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="space-y-6 text-black">
                        @csrf

                        <div>
                            <select name="database" id="database"
                                class="peer text-black w-full px-4 py-3 rounded-lg border borderinput focus:outline-none focus:border-black cursor-pointer"
                                onchange="updateEnvDatabase(this.value)" required>
                                <option value="">Selecciona</option>
                                <option value="unidplay_controlinventarioti">Control Inventario TI</option>
                                <option value="unidplay_presupuestoscontrol">Presupuestos</option>
                            </select>
                            <label for="database" class="absolute text-sm text-gray-500 duration-300 transform -translate-y-3 scale-75 to-2 left-12 z-10 origin-[0] bg-white px-1 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-3 peer-focus:scale-75 peer-focus:-translate-y-3">
                                Base de datos
                            </label>
                        </div>

                        <div class="relative w-full">
                            <input type="username" id="username" name="username"
                                value="{{ Cookie::get('username') ?? old('username') }}"
                                required
                                class="peer w-full px-4 py-3 rounded-lg border borderinput bg-white text-black focus:outline-none focus:border-black">
                            <label for="username"
                                class="absolute text-sm text-gray-500 duration-300 transform -translate-y-3 scale-75 to-2 left-4 z-10 origin-[0] bg-white px-1 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-3 peer-focus:scale-75 peer-focus:-translate-y-3">
                                Nombre de Usuario
                            </label>
                        </div>

                        <div>
                            <input type="password" name="password" id="password"
                                value="{{ Cookie::get('password') ?? '' }}" required
                                class="peer w-full px-4 py-3 rounded-lg border borderinput bg-white text-black focus:outline-none focus:border-black"">
                            <label for=" password"
                                class="absolute text-sm text-gray-500 duration-300 transform -translate-y-3 scale-75 to-2 left-12 z-10 origin-[0] bg-white px-1 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-3 peer-focus:scale-75 peer-focus:-translate-y-3">
                            Contraseña
                            </label>
                        </div>

                        <div class="flex flex-col gap-2 pt-8">
                            <div class="flex items-center">
                                <input type="checkbox" id="remember" name="remember"
                                    {{ Cookie::get('remember') !== null ? 'checked' : '' }}
                                    class="w-4 h-4 text-blue-600 bg-gray-100 borderinput rounded-lg focus:outline-none cursor-pointer align-middle">
                                <label for="remember" class="ml-2 text-sm text-gray-600 cursor-pointer">Recuérdame</label>
                            </div>

                            <button type="submit"
                                class="w-full bg-black text-white font-medium py-3 px-4 rounded-lg transition hover:scale-105">
                                Ingresar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function updateEnvDatabase(value) {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('/update-database', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        database: value
                    }),
                    credentials: 'same-origin',
                })
                .then(res => res.json())
                .then(data => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: data.success ? 'success' : 'warning',
                        title: data.success ? 'Base de datos actualizada correctamente' : (data.error || 'Error desconocido'),
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                })
                .catch(() => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Error al actualizar la base de datos',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                });
        }
    </script>
</body>