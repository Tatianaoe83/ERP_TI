<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Registra qué pasó realmente con un correo: con qué config salió y qué respondió el servidor SMTP.
 * Uso: $diag = SmtpDiagnostico::para($mailable); Mail::to(...)->send($mailable); Log::info($diag->resumen());
 */
class SmtpDiagnostico
{
    /** Logger enganchado al SwiftMailer; se registra una sola vez por proceso. */
    private static $logger = null;

    private $messageId = null;

    public static function para(Mailable $mailable): self
    {
        $diag = new self();

        $mailable->withSwiftMessage(function ($message) use ($diag) {
            $diag->messageId = $message->getId();
        });

        if (config('mail.default') === 'smtp') {
            if (self::$logger === null) {
                self::$logger = new \Swift_Plugins_Loggers_ArrayLogger(100);
                Mail::getSwiftMailer()->registerPlugin(new \Swift_Plugins_LoggerPlugin(self::$logger));
            }
            self::$logger->clear();
        }

        return $diag;
    }

    public static function config(): string
    {
        return 'mailer=' . config('mail.default')
            . ' host=' . config('mail.mailers.smtp.host')
            . ' user=' . config('mail.mailers.smtp.username')
            . ' from=' . config('mail.from.address');
    }

    public function resumen(): string
    {
        return 'message-id=' . ($this->messageId ?? 'n/a')
            . ' | failures=' . json_encode(Mail::failures())
            . ' | smtp=' . $this->ultimaRespuestaSmtp();
    }

    /**
     * Última respuesta 250 del servidor: la del fin de DATA trae el id de cola (ej. "250 OK id=...").
     * Si hubo error, devuelve la última respuesta que dio el servidor.
     */
    private function ultimaRespuestaSmtp(): string
    {
        if (config('mail.default') !== 'smtp' || self::$logger === null) {
            return 'n/a (mailer no es smtp)';
        }

        $respuestas = array_values(array_filter(explode(PHP_EOL, self::$logger->dump()), function ($linea) {
            return strpos($linea, '<< ') !== false;
        }));

        $aceptadas = array_values(array_filter($respuestas, function ($linea) {
            return strpos($linea, '<< 250') !== false;
        }));

        $linea = end($aceptadas) ?: end($respuestas);

        return $linea ? trim($linea) : 'sin respuesta';
    }
}
