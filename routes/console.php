<?php

use App\Models\ChatbotEvent;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Bitácora del chatbot: resumen de solo lectura (salvo la limpieza de eventos vencidos).
Artisan::command('chatbot:report {--days=30 : Días hacia atrás que se resumen}', function () {
    $purged = ChatbotEvent::purgeExpired();
    $days = max(1, (int) $this->option('days'));
    $since = today()->subDays($days - 1);
    $events = fn () => ChatbotEvent::query()->where('occurred_on', '>=', $since);

    $total = $events()->count();
    $recognized = $events()->where('resolution', 'INTENT_RESOLVED')->count();
    $this->info("Bitácora del chatbot: últimos {$days} días (desde {$since->toDateString()}).");
    $this->line("Limpieza: {$purged['deleted']} eventos borrados, {$purged['cleared']} formas vaciadas.");
    $this->line("Eventos: {$total}. Solicitudes reconocidas: {$recognized}"
        .($total ? ' ('.round(100 * $recognized / $total, 1).' %).' : '.'));
    if ($total === 0) {
        return;
    }

    $group = fn (array $columns) => $events()->select($columns)->selectRaw('count(*) as total')
        ->groupBy($columns)->orderByDesc('total')->get()
        ->map(fn ($row) => array_map(fn ($value) => $value ?? '—', $row->only([...$columns, 'total'])))->all();
    $this->table(['Rol', 'Eventos'], $group(['role']));
    $this->table(['Estado', 'Código', 'Eventos'], $group(['status', 'code']));
    $this->table(['Resolución', 'Regla que bloqueó', 'Eventos'], $group(['resolution', 'guard']));
    $this->table(['Intención', 'Eventos'], $group(['intent']));

    $shapes = $events()->where('resolution', 'NO_MATCH')->whereNotNull('message_shape')
        ->select('message_shape')->selectRaw('count(*) as total')->groupBy('message_shape')
        ->orderByDesc('total')->limit(20)->get()->map(fn ($row) => [$row->message_shape, $row->total])->all();
    $this->table(['Forma del mensaje no reconocido (top 20)', 'Eventos'], $shapes);
})->purpose('Resume la bitácora del chatbot y borra los eventos vencidos');

Schedule::call(fn () => ChatbotEvent::purgeExpired())->daily()->name('chatbot-events-purge');
