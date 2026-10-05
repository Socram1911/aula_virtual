<?php

declare(strict_types=1);

/**
 * Ejecutor de pruebas liviano (sin dependencias externas).
 * Uso: php tests/run.php
 *
 * Compatible con PHPUnit: los mismos archivos de prueba pueden ejecutarse
 * con `vendor/bin/phpunit` cuando PHPUnit esté instalado.
 */

require __DIR__ . '/bootstrap.php';

$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;
$GLOBALS['__failures'] = [];

function assertTrue(bool $condition, string $message = ''): void
{
    if ($condition) {
        $GLOBALS['__pass']++;
    } else {
        $GLOBALS['__fail']++;
        $GLOBALS['__failures'][] = $message ?: 'La condición esperada era verdadera.';
    }
}

function assertSame(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected === $actual) {
        $GLOBALS['__pass']++;
    } else {
        $GLOBALS['__fail']++;
        $GLOBALS['__failures'][] = ($message ?: 'Valores no coinciden') . ' — esperado: ' . var_export($expected, true) . ', recibido: ' . var_export($actual, true);
    }
}

function assertThrows(callable $fn, string $class, string $message = ''): void
{
    try {
        $fn();
        $GLOBALS['__fail']++;
        $GLOBALS['__failures'][] = ($message ?: "Se esperaba una excepción {$class}.");
    } catch (\Throwable $e) {
        if ($e instanceof $class) {
            $GLOBALS['__pass']++;
        } else {
            $GLOBALS['__fail']++;
            $GLOBALS['__failures'][] = ($message ?: 'Excepción inesperada') . ' — se recibió ' . get_class($e) . ': ' . $e->getMessage();
        }
    }
}

$files = array_merge(
    glob(__DIR__ . '/Contract/*Test.php') ?: [],
    glob(__DIR__ . '/Unit/*Test.php') ?: [],
    glob(__DIR__ . '/Integration/*Test.php') ?: []
);

foreach ($files as $file) {
    require $file;
}

echo "\nResultados: {$GLOBALS['__pass']} aserciones pasaron, {$GLOBALS['__fail']} fallaron.\n";
foreach ($GLOBALS['__failures'] as $failure) {
    echo "  ✗ {$failure}\n";
}
exit($GLOBALS['__fail'] > 0 ? 1 : 0);
