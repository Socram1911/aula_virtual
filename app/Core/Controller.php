<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Auth\Auth;
use App\Core\Csrf\Csrf;
use App\Core\Request\Request;
use App\Core\Response\Response;
use App\Core\Session\Session;

/**
 * Controlador base: renderizado de vistas, JSON, redirecciones y protección CSRF.
 */
abstract class Controller
{
    protected Request $request;

    public function __construct()
    {
        $this->request = Request::capture();
        if ($this->request->isPost()) {
            $token = $this->request->input('_token') ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
            Csrf::validate($token);
        }
    }

    /** Renderiza una vista dentro del layout. La vista se indica como 'Modulo/vista'. */
    protected function render(string $view, array $data = []): void
    {
        $segments = explode('/', $view);
        $module = array_shift($segments);
        $viewFile = dirname(__DIR__) . '/Modules/' . $module . '/Views/' . implode('/', $segments) . '.php';
        if (! is_file($viewFile)) {
            throw new \RuntimeException("Vista no encontrada: {$viewFile}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layout = dirname(__DIR__) . '/Shared/Views/layout.php';
        require $layout;
    }

    protected function json(mixed $data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    protected function redirect(string $url): void
    {
        Response::redirect($url);
    }

    protected function back(): void
    {
        Response::back();
    }

    protected function requireAuth(): void
    {
        Auth::requireAuth();
    }

    protected function requireRole(string|array $roles): void
    {
        Auth::requireRole($roles);
    }

    /** Redirige con errores y conserva la entrada anterior del formulario. */
    protected function backWithErrors(string $url, array $errors, ?array $input = null): void
    {
        Session::flash('_errors', $errors);
        if ($input !== null) {
            Session::flash('_old', $input);
        }
        $this->redirect($url);
    }

    protected function errors(): array
    {
        return Session::pullFlash('_errors', []);
    }

    /**
     * Ejecuta una operación y redirige; si falla con HttpException,
     * guarda los errores en flash y conserva la entrada del formulario.
     */
    protected function handle(callable $fn, string $redirectTo, ?array $input = null): void
    {
        try {
            $fn();
        } catch (\App\Core\Exceptions\HttpException $e) {
            $this->backWithErrors($redirectTo, $e->details() ?: [$e->getMessage()], $input ?? $this->request->all());
            return;
        }
        $this->redirect($redirectTo);
    }

    protected function user(): ?array
    {
        return Auth::user();
    }
}
