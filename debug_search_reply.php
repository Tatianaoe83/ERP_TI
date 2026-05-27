<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TicketChat;

$email = 'becario.prog@proser.com.mx';
$ticketId = 92;

$chats = TicketChat::where('ticket_id', $ticketId)
    ->whereRaw('LOWER(correo_remitente) = ?', [strtolower($email)])
    ->orderBy('created_at', 'desc')
    ->get();

echo json_encode(['count' => $chats->count(), 'chats' => $chats->toArray()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
