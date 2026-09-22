<?php

namespace Lace\Ainstruct\Web;

use Illuminate\Container\Container;
use Lace\Ainstruct\Exceptions\AinstructException;
use Lace\Ainstruct\Exceptions\InvalidOperationException;
use Lace\Ainstruct\Exceptions\SessionNotFoundException;
use Lace\Ainstruct\Exceptions\TemplateNotFoundException;
use Lace\Ainstruct\Exceptions\TemplateProtectedException;
use Lace\Ainstruct\Exceptions\ValidationException;
use Lace\Ainstruct\Web\Controllers\OpencodeController;
use Lace\Ainstruct\Web\Controllers\TemplateController;

/**
 * Kernel HTTP WebUI: routing, guard origin lokal, dan pemetaan exception
 * menjadi status HTTP. Server hanya bind ke 127.0.0.1 secara default, dan
 * permintaan mutasi dari origin selain localhost ditolak (guard CSRF lokal).
 */
final class Kernel
{
    /**
     * Route: [metode, pattern nama template, aksi controller].
     */
    private const ROUTES = [
        ['GET', '#^/api/templates$#', 'templates'],
        ['POST', '#^/api/templates$#', 'create'],
        ['GET', '#^/api/templates/(?P<name>[A-Za-z0-9_-]+)$#', 'detail'],
        ['POST', '#^/api/templates/(?P<name>[A-Za-z0-9_-]+)/clone$#', 'clone'],
        ['PUT', '#^/api/templates/(?P<name>[A-Za-z0-9_-]+)$#', 'update'],
        ['DELETE', '#^/api/templates/(?P<name>[A-Za-z0-9_-]+)$#', 'delete'],
        ['GET', '#^/api/templates/(?P<name>[A-Za-z0-9_-]+)/tree$#', 'tree'],
        ['GET', '#^/api/templates/(?P<name>[A-Za-z0-9_-]+)/file$#', 'file'],
        ['PUT', '#^/api/templates/(?P<name>[A-Za-z0-9_-]+)/file$#', 'file'],
        ['GET', '#^/api/opencode/status$#', 'opencodeStatus'],
        ['GET', '#^/api/opencode/sessions$#', 'opencodeList'],
        ['POST', '#^/api/opencode/sessions$#', 'opencodeStart'],
        ['GET', '#^/api/opencode/sessions/(?P<session>[A-Za-z0-9_-]+)$#', 'opencodeDetail'],
        ['GET', '#^/api/opencode/sessions/(?P<session>[A-Za-z0-9_-]+)/stream$#', 'opencodeStream'],
        ['POST', '#^/api/opencode/sessions/(?P<session>[A-Za-z0-9_-]+)/stop$#', 'opencodeStop'],
        ['DELETE', '#^/api/opencode/sessions/(?P<session>[A-Za-z0-9_-]+)$#', 'opencodeDelete'],
    ];

    public function __construct(private Container $container) {}

    public function handle(Request $request): Response
    {
        if (! $this->originAllowed($request)) {
            return Response::json(403, [
                'ok' => false,
                'error' => 'Origin tidak diizinkan. WebUI hanya menerima permintaan dari alamat lokal server ini.',
            ]);
        }

        $route = $this->match($request);

        if ($route === null) {
            $allowed = $this->allowedMethods($request->path());

            if ($allowed !== []) {
                return Response::json(405, [
                    'ok' => false,
                    'error' => 'Metode HTTP tidak diizinkan untuk rute ini.',
                ], ['Allow' => implode(', ', $allowed)]);
            }

            return Response::json(404, ['ok' => false, 'error' => 'Rute tidak ditemukan.']);
        }

        [$action, $matches] = $route;

        /** @var TemplateController $controller */
        $controller = $this->container->make(TemplateController::class);

        /** @var OpencodeController $opencode */
        $opencode = $this->container->make(OpencodeController::class);

        try {
            if ($this->isOpencodeAction($action)) {
                return $this->dispatch($opencode, $action, $request, $matches);
            }

            return $this->dispatch($controller, $action, $request, $matches);
        } catch (\Throwable $e) {
            return $this->error($e);
        }
    }

    private function dispatch(TemplateController|OpencodeController $controller, string $action, Request $request, array $matches): Response
    {
        if ($controller instanceof OpencodeController) {
            return $this->dispatchOpencode($controller, $action, $request, $matches);
        }

        return match ($action) {
            'templates' => $controller->templates($request),
            'create' => $controller->create($request),
            'detail' => $controller->detail($request, $matches),
            'clone' => $controller->clone($request, $matches),
            'update' => $controller->update($request, $matches),
            'delete' => $controller->delete($request, $matches),
            'tree' => $controller->tree($request, $matches),
            'file' => $controller->file($request, $matches),
            default => Response::json(500, ['ok' => false, 'error' => 'Aksi tidak dikenal.']),
        };
    }

    private function dispatchOpencode(OpencodeController $controller, string $action, Request $request, array $matches): Response
    {
        return match ($action) {
            'opencodeStatus' => $controller->status($request),
            'opencodeList' => $controller->index($request),
            'opencodeStart' => $controller->start($request),
            'opencodeDetail' => $controller->detail($request, $matches),
            'opencodeStream' => $controller->stream($request, $matches),
            'opencodeStop' => $controller->stop($request, $matches),
            'opencodeDelete' => $controller->delete($request, $matches),
            default => Response::json(500, ['ok' => false, 'error' => 'Aksi tidak dikenal.']),
        };
    }

    private function isOpencodeAction(string $action): bool
    {
        return str_starts_with($action, 'opencode');
    }

    /**
     * @return array{0: string, 1: array<string, string>}|null [aksi, match]
     */
    private function match(Request $request): ?array
    {
        foreach (self::ROUTES as [$routeMethod, $pattern, $action]) {
            if ($routeMethod !== $request->method()) {
                continue;
            }

            if (preg_match($pattern, $request->path(), $matches) === 1) {
                return [$action, $matches];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function allowedMethods(string $path): array
    {
        $methods = [];

        foreach (self::ROUTES as [$routeMethod, $pattern]) {
            if (preg_match($pattern, $path) === 1) {
                $methods[] = $routeMethod;
            }
        }

        return array_values(array_unique($methods));
    }

    /**
     * Guard CSRF lokal. Permintaan mutasi (POST/PUT/DELETE) hanya diterima
     * bila header Origin (saat ada) menunjuk host loopback pada port server.
     * Klien non-browser tanpa Origin tetap diterima (curl, script lokal).
     */
    private function originAllowed(Request $request): bool
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'DELETE'], true)) {
            return true;
        }

        $origin = $request->header('Origin');

        if ($origin === null || $origin === '') {
            return true;
        }

        $host = parse_url($origin, PHP_URL_HOST);
        $port = parse_url($origin, PHP_URL_PORT);

        if (! in_array($host, ['127.0.0.1', 'localhost', '::1', '[::1]'], true)) {
            return false;
        }

        $serverPort = (int) ($request->server('SERVER_PORT') ?? 80);

        return $port === null || $port === $serverPort;
    }

    private function error(\Throwable $e): Response
    {
        $status = match (true) {
            $e instanceof ValidationException => 422,
            $e instanceof TemplateNotFoundException => 404,
            $e instanceof SessionNotFoundException => 404,
            $e instanceof TemplateProtectedException => 403,
            $e instanceof InvalidOperationException => 409,
            $e instanceof AinstructException => 500,
            default => 500,
        };

        return Response::json($status, ['ok' => false, 'error' => $e->getMessage()]);
    }
}
