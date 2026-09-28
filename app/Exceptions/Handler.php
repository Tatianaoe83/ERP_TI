<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof TokenMismatchException) {
            $mensaje = 'La sesión del formulario expiró. Recarga la página e intenta de nuevo.';

            if ($request->is('login') || $request->routeIs('login')) {
                return redirect()
                    ->route('login')
                    ->withInput($request->only('username', 'remember'))
                    ->with('error', $mensaje);
            }

            return redirect()
                ->back()
                ->withInput($request->except($this->dontFlash))
                ->with('error', $mensaje);
        }

        return parent::render($request, $exception);
    }
}
