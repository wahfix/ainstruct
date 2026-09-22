<?php

namespace Lace\Ainstruct\Web\Controllers;

use Lace\Ainstruct\Exceptions\InvalidOperationException;
use Lace\Ainstruct\Services\Opencode\OpencodeService;
use Lace\Ainstruct\Web\Request;
use Lace\Ainstruct\Web\Response;

/**
 * Controller REST sesi opencode WebUI. Semua eksekusi lewat OpencodeService:
 * hanya `opencode run` dengan argumen ter-escape, proses terdetach, dan
 * status dibaca via streaming SSE (atau fallback polling via detail endpoint).
 */
final class OpencodeController
{
    public function __construct(private OpencodeService $opencode) {}

    public function status(Request $request): Response
    {
        return Response::json(200, ['ok' => true, 'data' => $this->opencode->status()]);
    }

    public function index(Request $request): Response
    {
        return Response::json(200, [
            'ok' => true,
            'data' => [
                'sessions' => $this->opencode->all(),
                'defaultDirectory' => $this->opencode->status()['defaultDirectory'],
            ],
        ]);
    }

    public function start(Request $request): Response
    {
        $session = $this->opencode->start($request->body());

        return Response::json(201, ['ok' => true, 'data' => $session]);
    }

    public function detail(Request $request, array $matches): Response
    {
        return Response::json(200, [
            'ok' => true,
            'data' => $this->opencode->session($matches['session']),
        ]);
    }

    public function stop(Request $request, array $matches): Response
    {
        $id = $matches['session'];

        $this->opencode->stop($id);

        return Response::json(200, ['ok' => true, 'data' => ['id' => $id, 'status' => 'stopped']]);
    }

    public function stream(Request $request, array $matches): Response
    {
        $id = $matches['session'];

        // Validasi sesi ada sebelum memulai streaming (404 jika tidak ada).
        $this->opencode->session($id);

        return Response::stream(function () use ($id): void {
            $this->opencode->streamOutput($id, function (string $event, array $data): void {
                echo "event: {$event}\n";
                echo 'data: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n";
                flush();
            });
        });
    }

    public function delete(Request $request, array $matches): Response
    {
        $id = $matches['session'];

        if (! (bool) ($request->body()['force'] ?? false)) {
            throw new InvalidOperationException(
                'Penghapusan catatan sesi bersifat permanen. Konfirmasi dulu lewat dialog (kirim force: true).'
            );
        }

        $this->opencode->delete($id);

        return Response::json(200, [
            'ok' => true,
            'data' => ['id' => $id, 'removed' => true],
        ]);
    }
}
