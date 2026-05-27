<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SimpleWebklexImapService;

class DebugImap extends Command
{
    protected $signature = 'debug:imap';
    protected $description = 'Debug IMAP: diagnosticar conexión y contar mensajes';

    public function handle()
    {
        $svc = new SimpleWebklexImapService();
        $diag = $svc->diagnosticar();
        $this->line(json_encode($diag, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return 0;
    }
}
