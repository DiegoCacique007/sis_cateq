<?php

// Solo lectura de MySQL. Los archivos privados generados no se sirven desde public/.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = ['periodos', 'comunidades', 'grupos', 'niveles', 'unidades', 'rubros', 'alumnos', 'tutores', 'asigna_grupo', 'inscripciones', 'evaluaciones'];
$folder = storage_path('app/private/audits/boletas-'.date('Ymd-His'));
if (! mkdir($folder, 0700, true)) {
    throw new RuntimeException('No se pudo crear el directorio de respaldo.');
}
$save = function (string $name, mixed $data) use ($folder): void {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    if (file_put_contents($folder.'/'.$name, $json, LOCK_EX) === false) {
        throw new RuntimeException('No se pudo guardar '.$name);
    }
};
$data = $schema = [];
DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
DB::statement('SET TRANSACTION READ ONLY');
DB::beginTransaction();
try {
    foreach ($tables as $table) {
        $data[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
        $schema[$table] = (array) DB::selectOne('SHOW CREATE TABLE `'.$table.'`');
    }
    $data['users_reference'] = DB::table('users')->orderBy('id')->get(['id', 'role', 'status', 'comunidad_id'])->map(fn ($r) => (array) $r)->all();
    DB::commit();
} catch (Throwable $e) {
    DB::rollBack();
    throw $e;
}
$save('snapshot.json', $data);
$save('schema.json', $schema);
$indexed = [];
foreach ($data as $table => $rows) {
    $indexed[$table] = array_column($rows, null, 'id');
}
$live = fn ($row) => $row !== null && empty($row['deleted_at']);
$periods = array_values(array_filter($data['periodos'], $live));
usort($periods, fn ($a, $b) => (($b['estado'] == 1) <=> ($a['estado'] == 1)) ?: strcmp($b['fecha_inicio'], $a['fecha_inicio']));
$period = isset($argv[1]) ? ($indexed['periodos'][(int) $argv[1]] ?? null) : ($periods[0] ?? null);
if (! $live($period)) {
    throw new RuntimeException('Periodo no disponible. El respaldo se conserva.');
}
$validAssignments = [];
$assignmentIssues = [];
foreach ($data['asigna_grupo'] as $a) {
    if (! $live($a)) {
        continue;
    }
    $issues = [];
    foreach (['comunidad_id' => 'comunidades', 'grupo_id' => 'grupos', 'nivel_id' => 'niveles', 'periodo_id' => 'periodos'] as $field => $table) {
        if (! $live($indexed[$table][$a[$field]] ?? null)) {
            $issues[] = 'dependencia_invalida:'.$field;
        }
    }
    $teacher = $indexed['users_reference'][$a['catequista_id']] ?? null;
    if (! $teacher) {
        $issues[] = 'catequista_inexistente';
    }
    if (! $issues) {
        $validAssignments[] = $a;
    }
    if ($teacher && ($teacher['role'] !== 'catequista' || $teacher['status'] !== 'aprobado')) {
        $issues[] = 'cuenta_catequista_no_operativa_revisar_historial';
    }
    $g = $indexed['grupos'][$a['grupo_id']] ?? null;
    if ($g && $g['periodo_id'] !== null && (int) $g['periodo_id'] !== (int) $a['periodo_id']) {
        $issues[] = 'periodo_grupo_distinto';
    }
    if ($g && $g['periodo_id'] === null) {
        $issues[] = 'grupo_sin_periodo_explicito';
    }
    if ($issues && (int) $a['periodo_id'] === (int) $period['id']) {
        $assignmentIssues[] = ['id' => $a['id'], 'issues' => $issues];
    }
}
$evaluations = [];
foreach ($data['evaluaciones'] as $e) {
    if ($live($e)) {
        $evaluations[$e['inscripcion_id']][] = $e;
    }
}
$report = $proposals = [];
foreach ($data['inscripciones'] as $i) {
    if (! $live($i) || ((int) $i['periodo_id'] !== (int) $period['id'] && (int) $i['id'] !== 1)) {
        continue;
    }
    $student = $indexed['alumnos'][$i['alumno_id']] ?? null;
    $ref = $indexed['asigna_grupo'][$i['asigna_grupo_id']] ?? null;
    $issues = [];
    if ($i['asigna_grupo_id'] === null) {
        $issues[] = 'referencia_nula';
    } elseif (! $ref) {
        $issues[] = 'referencia_inexistente';
    } elseif (! $live($ref)) {
        $issues[] = 'referencia_eliminada';
    }
    foreach (['alumno_id' => 'alumnos', 'grupo_id' => 'grupos', 'periodo_id' => 'periodos'] as $field => $table) {
        if (! $live($indexed[$table][$i[$field]] ?? null)) {
            $issues[] = 'dependencia_invalida:'.$field;
        }
    }
    if (! $live($indexed['comunidades'][$student['comunidad_id'] ?? null] ?? null)) {
        $issues[] = 'comunidad_alumno_invalida';
    }
    if ($ref) {
        foreach (['grupo_id', 'periodo_id'] as $field) {
            if ((int) $ref[$field] !== (int) $i[$field]) {
                $issues[] = 'referencia_incompatible:'.$field;
            }
        }
        if ((int) $ref['comunidad_id'] !== (int) ($student['comunidad_id'] ?? 0)) {
            $issues[] = 'referencia_incompatible:comunidad_id';
        }
    }
    $levels = $evaluationIssues = $keys = [];
    foreach ($evaluations[$i['id']] ?? [] as $e) {
        $u = $indexed['unidades'][$e['unidad_id']] ?? null;
        $r = $indexed['rubros'][$e['rubro_id']] ?? null;
        if ($e['periodo_id'] !== null && (int) $e['periodo_id'] !== (int) $i['periodo_id']) {
            $evaluationIssues[] = ['id' => $e['id'], 'issue' => 'periodo_distinto'];
        } elseif ($live($u) && $live($indexed['niveles'][$u['nivel_id']] ?? null)) {
            $levels[] = (int) $u['nivel_id'];
        }
        if (! $live($u) || ! $live($r)) {
            $evaluationIssues[] = ['id' => $e['id'], 'issue' => 'unidad_o_rubro_invalido'];
        }
        if ($r && $e['calificacion'] !== null && ((float) $e['calificacion'] < 0 || (float) $e['calificacion'] > (float) $r['valor'])) {
            $evaluationIssues[] = ['id' => $e['id'], 'issue' => 'calificacion_fuera_de_rango'];
        }
        $key = $e['unidad_id'].':'.$e['rubro_id'];
        if (isset($keys[$key])) {
            $evaluationIssues[] = ['id' => $e['id'], 'issue' => 'duplicada', 'other_id' => $keys[$key]];
        }
        $keys[$key] = $e['id'];
    }
    $levels = array_values(array_unique($levels));
    $sameGroup = array_values(array_filter($validAssignments, fn ($a) => (int) $a['grupo_id'] === (int) $i['grupo_id'] && (int) $a['periodo_id'] === (int) $i['periodo_id']));
    $exact = array_values(array_filter($sameGroup, fn ($a) => (int) $a['comunidad_id'] === (int) ($student['comunidad_id'] ?? 0)));
    $alternatives = array_values(array_filter($validAssignments, fn ($a) => (int) $a['periodo_id'] === (int) $i['periodo_id'] && (int) $a['comunidad_id'] === (int) ($student['comunidad_id'] ?? 0) && (! $levels || in_array((int) $a['nivel_id'], $levels, true))));
    if (! $exact) {
        $issues[] = $sameGroup ? 'grupo_asignado_a_otra_comunidad' : 'sin_asignacion_vigente_grupo_periodo';
    }
    if (count($exact) > 1) {
        $issues[] = 'varias_asignaciones_compatibles';
    }
    if ($ref && $levels && (count($levels) > 1 || ! in_array((int) $ref['nivel_id'], $levels, true))) {
        $issues[] = 'nivel_referencia_no_coincide_con_evaluaciones';
    }
    $eligible = count($exact) === 1 && (! $levels || $levels === [(int) $exact[0]['nivel_id']]) && ! $evaluationIssues
        && $live($student) && $live($indexed['grupos'][$i['grupo_id']] ?? null)
        && ($i['asigna_grupo_id'] === null || ! $ref);
    $classification = $eligible ? 'propuesta_inequivoca_solo_referencia' : ($issues || $evaluationIssues ? 'revision_secretaria' : 'coherente');
    $row = ['inscripcion_id' => $i['id'], 'alumno_id' => $i['alumno_id'], 'grupo_id' => $i['grupo_id'], 'periodo_id' => $i['periodo_id'],
        'comunidad_alumno_id' => $student['comunidad_id'] ?? null, 'asigna_grupo_id' => $i['asigna_grupo_id'],
        'niveles_evaluados' => $levels, 'issues' => $issues, 'evaluation_issues' => $evaluationIssues,
        'asignaciones_mismo_grupo_periodo' => array_column($sameGroup, 'id'), 'candidatas_exactas' => array_column($exact, 'id'),
        'alternativas_requieren_decision' => array_column($alternatives, 'id'), 'clasificacion' => $classification];
    $report[] = $row;
    if ($eligible) {
        $proposals[] = ['table' => 'inscripciones', 'id' => $i['id'], 'column' => 'asigna_grupo_id', 'before' => $i['asigna_grupo_id'], 'after' => $exact[0]['id'], 'approved' => false];
    }
}
$save('diagnostico.json', ['periodo_auditado' => $period, 'periodos_disponibles' => $periods, 'inscripciones' => $report, 'asignaciones_con_alertas' => $assignmentIssues]);
$save('propuestas.json', $proposals);
$csv = fopen($folder.'/inscripciones.csv', 'x');
fputcsv($csv, ['inscripcion_id', 'alumno_id', 'grupo_id', 'periodo_id', 'comunidad_alumno_id', 'asigna_grupo_id', 'niveles_evaluados', 'issues', 'candidatas_exactas', 'alternativas_requieren_decision', 'clasificacion'], ',', '"', '');
foreach ($report as $row) {
    fputcsv($csv, array_map(fn ($key) => is_array($row[$key]) ? implode('|', $row[$key]) : $row[$key], ['inscripcion_id', 'alumno_id', 'grupo_id', 'periodo_id', 'comunidad_alumno_id', 'asigna_grupo_id', 'niveles_evaluados', 'issues', 'candidatas_exactas', 'alternativas_requieren_decision', 'clasificacion']), ',', '"', '');
}
fclose($csv);
$counts = [];
foreach ($report as $row) {
    foreach ($row['issues'] as $issue) {
        $counts[$issue] = ($counts[$issue] ?? 0) + 1;
    }
}
$manifest = ['created_at' => date(DATE_ATOM), 'period_id' => $period['id'], 'scope' => 'Tablas académicas completas; usuarios solo referencias sin credenciales; sin sesiones ni configuración',
    'row_counts' => array_map('count', $data), 'sha256' => []];
foreach (['snapshot.json', 'schema.json', 'diagnostico.json', 'propuestas.json', 'inscripciones.csv'] as $file) {
    $manifest['sha256'][$file] = hash_file('sha256', $folder.'/'.$file);
}
$save('manifest.json', $manifest);
echo json_encode(['folder' => $folder, 'periodo' => $period, 'inscripciones_auditadas' => count($report), 'issues' => $counts,
    'clasificaciones' => array_count_values(array_column($report, 'clasificacion')), 'propuestas' => $proposals,
    'inscripcion_1' => array_values(array_filter($report, fn ($r) => $r['inscripcion_id'] == 1)), 'row_counts' => $manifest['row_counts']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
