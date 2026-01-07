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
    <script src="https://cdn.jsdelivr.net/npm/tsparticles-slim@2.0.6/tsparticles.slim.bundle.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+Antique&display=swap');

        .fuente {
            font-family: 'Zen Kaku Gothic Antique';
        }

        .glass-card {
            background: rgba(250, 250, 250, 1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
        }

        .borderinput {
            border-color: rgba(189, 189, 189, 1);
        }
    </style>
</head>

<body>
    <div class="relative z-10 min-h-screen overflow-hidden"
        style="background-image: url('img/fondotech.jpg'); background-size: cover; background-position: center; filter:grayscale(60%)">
        <div class="flex justify-center px-4 sm:px-6 py-4 sm:py-8 md:pt-12 fuente">
            <div id="tsparticles"></div>
            <div class="w-full max-w-7xl grid lg:grid-cols-2 grid-cols-1 gap-4 lg:gap-0 content-start pt-4 sm:pt-6 md:pt-10 mt-4 sm:mt-6 md:mt-10">

                <div class="hidden lg:block min-h-screen">
                    <div class="w-full">
                        @yield('content')
                    </div>
                </div>

                <div class="glass-card rounded-lg p-4 sm:p-6 md:p-8 w-full max-w-md mx-auto">
                    <div class="mb-6 sm:mb-8">
                        <p class="text-black text-xs sm:text-sm font-medium mb-2">BIENVENIDO DE NUEVO</p>
                        <h3 class="text-2xl sm:text-3xl md:text-4xl font-semibold text-gray-800">Iniciar sesión</h3>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="space-y-4 sm:space-y-6 text-black">
                        @csrf

                        <div class="relative">
                            <select name="database" id="database"
                                class="peer text-black w-full px-3 sm:px-4 py-2.5 sm:py-3 rounded-lg border borderinput focus:outline-none focus:border-black cursor-pointer text-sm sm:text-base"
                                onchange="updateEnvDatabase(this.value)" required>
                                <option value="">Selecciona</option>
                                <option value="unidplay_controlinventarioti">Control Inventario TI</option>
                                <option value="unidplay_presupuestoscontrol">Presupuestos</option>
                                <option value="unidplay_presupuestoscontrol2026">Presupuestos 2026</option>
                            </select>
                            <label for="database" class="absolute text-xs sm:text-sm text-gray-500 duration-300 transform -translate-y-3 scale-75 top-2 left-3 sm:left-4 z-10 origin-[0] bg-white px-1 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-2.5 peer-focus:scale-75 peer-focus:-translate-y-3">
                                Base de datos
                            </label>
                        </div>

                        <div class="relative w-full">
                            <input type="username" id="username" name="username"
                                value="{{ Cookie::get('username') ?? old('username') }}"
                                required
                                class="peer w-full px-3 sm:px-4 py-2.5 sm:py-3 rounded-lg border borderinput bg-white text-black focus:outline-none focus:border-black text-sm sm:text-base">
                            <label for="username"
                                class="absolute text-xs sm:text-sm text-gray-500 duration-300 transform -translate-y-3 scale-75 top-2 left-3 sm:left-4 z-10 origin-[0] bg-white px-1 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-2.5 peer-focus:scale-75 peer-focus:-translate-y-3">
                                Nombre de Usuario
                            </label>
                        </div>

                        <div class="relative">
                            <input type="password" name="password" id="password"
                                value="{{ Cookie::get('password') ?? '' }}" required
                                class="peer w-full px-3 sm:px-4 py-2.5 sm:py-3 rounded-lg border borderinput bg-white text-black focus:outline-none focus:border-black text-sm sm:text-base">
                            <label for="password"
                                class="absolute text-xs sm:text-sm text-gray-500 duration-300 transform -translate-y-3 scale-75 top-2 left-3 sm:left-4 z-10 origin-[0] bg-white px-1 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-2.5 peer-focus:scale-75 peer-focus:-translate-y-3">
                                Contraseña
                            </label>
                        </div>

                        <div class="flex flex-col gap-2 pt-4 sm:pt-6 md:pt-8">
                            <div class="flex items-center">
                                <input type="checkbox" id="remember" name="remember"
                                    {{ Cookie::get('remember') !== null ? 'checked' : '' }}
                                    class="w-4 h-4 text-blue-600 bg-gray-100 borderinput rounded-lg focus:outline-none cursor-pointer align-middle">
                                <label for="remember" class="ml-2 text-xs sm:text-sm text-gray-600 cursor-pointer">Recuérdame</label>
                            </div>

                            <button type="submit"
                                class="w-full bg-black text-white font-medium py-2.5 sm:py-3 px-4 rounded-lg transition hover:scale-105 text-sm sm:text-base">
                                Ingresar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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

        tsParticles.load("tsparticles", {
            preset: "links",
            fullScreen: {
                enable: true,
                zIndex: 0
            },
            particles: {
                number: {
                    value: 80,
                    density: {
                        enable: true,
                        area: 800
                    }
                },
                color: {
                    value: "#ffffff"
                },
                links: {
                    enable: true,
                    distance: 140,
                    color: "#ffffff",
                    opacity: 0.4,
                    width: 2
                },
                move: {
                    enable: true,
                    speed: 1
                },
                size: {
                    value: 3
                },
                opacity: {
                    value: 0.7
                }
            },
            interactivity: {
                events: {
                    onHover: {
                        enable: true,
                    },
                    resize: true
                }
            },
            detectRetina: true
        });
    </script>
</body>
</html>