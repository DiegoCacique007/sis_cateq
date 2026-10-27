<?php

// Verifica archivos y ensaya restauración/reversión exclusivamente en SQLite en memoria.
$folder = $argv[1] ?? throw new RuntimeException('Indica el directorio del respaldo.');
$manifest = json_decode(file_get_contents($folder.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
foreach ($manifest['sha256'] as $file => $expected) {
    if (! hash_equals($expected, hash_file('sha256', $folder.'/'.$file))) {
        throw new RuntimeException('Checksum incorrecto: '.$file);
    }
}
$snapshot = json_decode(file_get_contents($folder.'/snapshot.json'), true, flags: JSON_THROW_ON_ERROR);
$pdo = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$normalized = fn ($row) => array_map(fn ($value) => $value === null ? null : (string) $value, $row);
$total = 0;
foreach ($snapshot as $table => $rows) {
    if (count($rows) !== $manifest['row_counts'][$table]) {
        throw new RuntimeException('Conteo incorrecto: '.$table);
    }
    if (! $rows) {
        continue;
    }
    $columns = array_keys($rows[0]);
    $pdo->exec('CREATE TABLE "'.$table.'" ('.implode(',', array_map(fn ($column) => '"'.$column.'" TEXT', $columns)).')');
    $insert = $pdo->prepare('INSERT INTO "'.$table.'" VALUES ('.implode(',', array_fill(0, count($columns), '?')).')');
    foreach ($rows as $row) {
        $insert->execute(array_values($row));
    }
    $restored = $pdo->query('SELECT * FROM "'.$table.'"')->fetchAll(PDO::FETCH_ASSOC);
    if ($restored !== array_map($normalized, $rows)) {
        throw new RuntimeException('Restauración distinta: '.$table);
    }
    $total += count($rows);
}
$plan = dirname(__DIR__).'/docs/auditoria-boletas-2026';
$extractUpdate = function (string $file): string {
    preg_match('/UPDATE inscripciones\s[\s\S]*?;/', file_get_contents($file), $matches);

    return $matches[0] ?? throw new RuntimeException('Falta UPDATE de prueba.');
};
$apply = $extractUpdate($plan.'/01-limpieza-propuesta.sql');
$reverse = $extractUpdate($plan.'/02-reversion.sql');
$read = fn () => $pdo->query('SELECT * FROM inscripciones ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$before = $read();
$pdo->beginTransaction();
if ($pdo->exec($apply) !== 1) {
    throw new RuntimeException('El plan no coincide con una única fila del respaldo.');
}
$pdo->rollBack();
if ($read() !== $before) {
    throw new RuntimeException('Falló el rollback.');
}
$pdo->beginTransaction();
if ($pdo->exec($apply) !== 1) {
    throw new RuntimeException('Falló aplicación simulada.');
}
$pdo->commit();
$pdo->beginTransaction();
if ($pdo->exec($reverse) !== 1) {
    throw new RuntimeException('Falló reversión simulada.');
}
$pdo->commit();
if ($read() !== $before) {
    throw new RuntimeException('La reversión no restituyó los valores originales.');
}
echo json_encode(['hashes_verificados' => count($manifest['sha256']), 'filas_restauradas_en_memoria' => $total,
    'rollback_simulado' => 'correcto', 'reversion_simulada' => 'correcta', 'base_real_modificada' => false], JSON_PRETTY_PRINT);
