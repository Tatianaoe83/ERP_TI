<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tickets;
use App\Models\TicketChat;

class DebugTicketInfo extends Command
{
    protected $signature = 'debug:ticket {id}';
    protected $description = 'Debug: muestra información de un ticket y sus chats';

    public function handle()
    {
        $id = $this->argument('id');

        $ticket = Tickets::find($id);
        if (!$ticket) {
            $this->error("Ticket {$id} no encontrado");
            return 1;
        }

        $chats = TicketChat::where('ticket_id', $id)->orderBy('created_at', 'desc')->limit(50)->get();

        $out = [
            'ticket' => $ticket->toArray(),
            'chats' => $chats->toArray(),
        ];

        $this->line(json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return 0;
    }
}
