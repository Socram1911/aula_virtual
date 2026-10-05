<?php

namespace App\Core\Router;

use App\Core\Exceptions\HttpException;
use App\Core\Request\Request;

/**
 * Enrutador simple con parámetros {id} y despacho a controladores.
 */
final class Router
{
    private static array $routes = [];

    public static function add(string $method, string $path, string $action): void
    {
        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => '/' . trim($path, '/'),
            'action' => $action,
        ];
    }

    public static function get(string $path, string $action): void
    {
        self::add('GET', $path, $action);
    }

    public static function post(string $path, string $action): void
    {
        self::add('POST', $path, $action);
    }

    /** Carga todos los archivos Routes/routes.php de cada módulo. */
    public static function load(string $modulesDir): void
    {
        $files = glob($modulesDir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'routes.php');
        foreach ($files ?: [] as $file) {
            require $file;
        }
    }

    public static function dispatch(): void
    {
        $request = Request::capture();
        $method = $request->method();
        $path = $request->path();

        // Las rutas estáticas (sin {parámetros}) tienen prioridad sobre las parametrizadas.
        $routes = self::$routes;
        usort($routes, function (array $a, array $b): int {
            $aDynamic = str_contains($a['path'], '{') ? 1 : 0;
            $bDynamic = str_contains($b['path'], '{') ? 1 : 0;
            return $aDynamic <=> $bDynamic;
        });

        foreach ($routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            [$matches, $params] = self::match($route['path'], $path);
            if ($matches) {
                self::invoke($route['action'], $params);
                return;
            }
        }

        throw HttpException::notFound('La página solicitada no existe.');
    }

    private static function match(string $pattern, string $path): array
    {
        // Los parámetros "path"/"file" capturan rutas con barras (comodín); el resto, un solo segmento.
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function (array $m): string {
            return in_array($m[1], ['path', 'file', 'wildcard'], true) ? '(.+)' : '([^/]+)';
        }, $pattern);
        $regex = '#^' . $regex . '$#';
        if (preg_match($regex, $path, $raw) !== 1) {
            return [false, []];
        }
        preg_match_all('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', $pattern, $names);
        $params = [];
        foreach ($names[1] as $i => $name) {
            $params[$name] = urldecode($raw[$i + 1]);
        }
        return [true, $params];
    }

    private static function invoke(string $action, array $params): void
    {
        if (! str_contains($action, '@')) {
            throw new \RuntimeException("Acción de ruta inválida: {$action}");
        }
        [$class, $method] = explode('@', $action, 2);
        if (! class_exists($class)) {
            throw new \RuntimeException("Controlador no encontrado: {$class}");
        }
        $controller = new $class();
        $controller->{$method}(...array_values($params));
    }
}
