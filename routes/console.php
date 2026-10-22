<?php

use App\Models\ChatbotEvent;
use App\Services\Chatbot\Contracts\IntentClassifier;
use App\Services\Chatbot\Intent\HybridIntentResolver;
use App\Services\Chatbot\Intent\IntentDescriptions;
use App\Services\Chatbot\Intent\NullIntentClassifier;
use App\Services\Chatbot\Intent\RuleBasedIntentResolver;
use App\Services\Chatbot\Intent\ShadowClassificationTracker;
use App\Services\Chatbot\Intent\WriteRequestFilter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

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

    // Clasificador (modo sombra o assist). Sin la migración de sus columnas, esta parte no se muestra.
    if (! Schema::hasColumn('chatbot_events', 'classifier_outcome')) {
        return;
    }
    $classified = $events()->whereNotNull('classifier_outcome')
        ->get(['classifier_outcome', 'classifier_intent', 'classifier_latency_ms', 'prompt_version', 'message_shape']);
    if ($classified->isEmpty()) {
        return;
    }
    $count = fn (string $outcome) => $classified->where('classifier_outcome', $outcome)->count();
    $this->line("Clasificador: {$classified->count()} eventos con modelo (prompt: "
        .$classified->pluck('prompt_version')->filter()->unique()->sort()->implode(', ').').');

    $used = $count('USED');
    $fallbacks = $classified->filter(fn ($event) => str_starts_with($event->classifier_outcome, 'FALLBACK_'))->count();
    if ($used + $fallbacks > 0) {
        $this->line("Modo assist: respuesta del modelo en {$used} de ".($used + $fallbacks).' consultas ('
            .round(100 * $used / ($used + $fallbacks), 1)." %); {$fallbacks} vueltas a las reglas.");
    }
    $shadowFailures = $classified->whereIn('classifier_outcome', ['EMPTY', 'INVALID_JSON', 'OUT_OF_LIST', 'TIMEOUT', 'HTTP_ERROR', 'DISABLED'])->count();
    if ($count('AGREE') + $count('DISAGREE') + $count('RESCUE') + $shadowFailures > 0) {
        $this->line("Modo sombra: {$count('AGREE')} acuerdos, {$count('DISAGREE')} desacuerdos, {$count('RESCUE')} rescates, {$shadowFailures} fallos.");
    }
    $latencies = $classified->pluck('classifier_latency_ms')->filter(fn ($value) => $value !== null)->map(fn ($value) => (int) $value)->sort()->values();
    if ($latencies->isNotEmpty()) {
        $this->line('Latencia del modelo: media '.round($latencies->avg()).' ms, p95 '
            .$latencies[(int) ceil(0.95 * $latencies->count()) - 1].' ms, máxima '.$latencies->max().' ms.');
    }
    $this->table(['Resultado del clasificador', 'Intención del modelo', 'Eventos'], $classified
        ->groupBy(fn ($event) => $event->classifier_outcome.'|'.($event->classifier_intent ?? '—'))
        ->map(fn ($rows, $key) => [...explode('|', $key), $rows->count()])->sortByDesc(2)->values()->all());
    $this->table(['Forma en desacuerdos, rescates y respuestas del modelo (top 20)', 'Eventos'], $classified
        ->whereIn('classifier_outcome', ['DISAGREE', 'RESCUE', 'USED'])->whereNotNull('message_shape')
        ->countBy('message_shape')->sortDesc()->take(20)->map(fn ($total, $shape) => [$shape, $total])->values()->all());
})->purpose('Resume la bitácora del chatbot y borra los eventos vencidos');

// Evaluación del clasificador con frases escritas para ello. Consulta al proveedor real: no forma parte de la suite.
Artisan::command('chatbot:evaluate-classifier {--file= : Conjunto de frases (por defecto tests/Fixtures/chatbot_frases.php)} {--intent= : Solo las frases con esta intención esperada}', function () {
    $classifier = app(IntentClassifier::class);
    if ($classifier instanceof NullIntentClassifier) {
        $this->error('No hay proveedor configurado: define CHATBOT_CLASSIFIER_DRIVER=ollama (y CHATBOT_OLLAMA_URL y CHATBOT_OLLAMA_MODEL) en .env y ejecuta php artisan config:clear.');

        return 1;
    }
    $file = $this->option('file') ?: base_path('tests/Fixtures/chatbot_frases.php');
    if (! is_file($file)) {
        $this->error("No existe el conjunto de frases: {$file}");

        return 1;
    }
    $phrases = array_values(array_filter(require $file, fn (array $phrase) => ! $this->option('intent') || $phrase['esperada'] === $this->option('intent')));
    if ($phrases === []) {
        $this->error('No hay frases que evaluar.');

        return 1;
    }

    $descriptions = new IntentDescriptions;
    $writes = new WriteRequestFilter;
    $rules = new RuleBasedIntentResolver;
    $assist = new HybridIntentResolver($rules, 'assist', new ShadowClassificationTracker, classifier: $classifier);
    $this->info('Clasificador: '.config('chatbot.classifier.driver').' · modelo '.config('chatbot.classifier.ollama.model')
        .' · prompt '.IntentDescriptions::VERSION.' · '.count($phrases).' frases.');
    $this->line('El modo assist vuelve a consultar al modelo en las frases que las reglas no resuelven.');

    $perIntent = [];
    $errors = [];
    $failures = [];
    $latencies = [];
    $hits = ['model' => 0, 'assist' => 0];
    foreach ($phrases as $phrase) {
        $result = $classifier->classify($phrase['frase'], $descriptions->offered());
        $latencies[] = $result->latencyMs;
        if ($result->outcome !== 'OK') {
            $failures[$result->outcome] = ($failures[$result->outcome] ?? 0) + 1;
        }
        $model = $result->outcome === 'OK' && $result->intent ? $writes->filter($result->intent, $phrase['frase'])->value : '('.$result->outcome.')';
        $assisted = $assist->resolve($phrase['frase'])->intent->value;
        $ok = ['model' => $model === $phrase['esperada'], 'assist' => $assisted === $phrase['esperada']];

        $row = $perIntent[$phrase['esperada']] ?? ['total' => 0, 'model' => 0, 'assist' => 0];
        $row['total']++;
        foreach (['model', 'assist'] as $kind) {
            $hits[$kind] += (int) $ok[$kind];
            $row[$kind] += (int) $ok[$kind];
        }
        $perIntent[$phrase['esperada']] = $row;
        if (! $ok['model'] || ! $ok['assist']) {
            $errors[] = [$phrase['frase'], $phrase['esperada'], $model, $rules->resolve($phrase['frase'])->intent->value, $assisted];
        }
    }

    $total = count($phrases);
    $pct = fn (int $hit, int $of) => round(100 * $hit / $of, 1);
    $this->line("Aciertos del modelo: {$hits['model']}/{$total} ({$pct($hits['model'], $total)} %).");
    $this->line("Aciertos en modo assist (reglas + modelo): {$hits['assist']}/{$total} ({$pct($hits['assist'], $total)} %).");
    ksort($perIntent);
    $this->table(['Intención esperada', 'Frases', 'Modelo', '%', 'Assist', '%'], collect($perIntent)->map(fn ($row, $intent) => [
        $intent, $row['total'], $row['model'], $pct($row['model'], $row['total']), $row['assist'], $pct($row['assist'], $row['total']),
    ])->values()->all());
    $this->line('Fallos del modelo: '.($failures ? collect($failures)->map(fn ($n, $outcome) => "{$outcome} {$n}")->implode(', ') : 'ninguno').'.');
    sort($latencies);
    $this->line('Latencia del modelo: media '.round(array_sum($latencies) / $total).' ms, p95 '
        .$latencies[(int) ceil(0.95 * $total) - 1].' ms, máxima '.max($latencies).' ms.');
    $this->table(['Frase', 'Esperada', 'Modelo', 'Reglas', 'Assist'], $errors);
})->purpose('Mide el clasificador de intenciones con el conjunto de frases (necesita el proveedor encendido)');

Schedule::call(fn () => ChatbotEvent::purgeExpired())->daily()->name('chatbot-events-purge');
