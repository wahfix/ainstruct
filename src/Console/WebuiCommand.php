<?php

namespace Lace\Ainstruct\Console;

use Lace\Ainstruct\Abstractions\Commands\Command;
use Lace\Ainstruct\Exceptions\InvalidOperationException;
use Lace\Ainstruct\Support\Paths;

/**
 * `ainstruct webui` — jalankan server lokal + antarmuka browser untuk
 * mengelola template. Hanya manajemen template (list/create/clone/update/
 * delete/path + editor file), tidak pernah menjalankan distribusi, jadi aman
 * dari direktori mana pun. Default bind 127.0.0.1; jangan expose ke publik.
 */
final class WebuiCommand extends Command
{
    private const DEFAULT_PORT = 8787;

    public function handle(Input $input): int
    {
        $host = trim((string) ($input->flagValue('--host') ?? '127.0.0.1'));
        $requestedPort = $input->flagValue('--port');
        $noOpen = $input->hasFlag('--no-open');

        $paths = Paths::fromEnvironment();
        $docRoot = $paths->packageRoot().DIRECTORY_SEPARATOR.'web';
        $router = $docRoot.DIRECTORY_SEPARATOR.'index.php';

        if (! is_dir($docRoot) || ! is_file($router)) {
            $this->style()->error('Direktori web tidak ditemukan di paket ainstruct (butuh web/index.php).');
            $this->style()->blank();

            return 1;
        }

        if ($requestedPort !== null && $requestedPort !== '' && ! $this->validPort($requestedPort)) {
            $this->style()->error("Port tidak valid: {$requestedPort} (gunakan 1-65535).");
            $this->style()->blank();

            return 1;
        }

        try {
            if ($requestedPort !== null && $requestedPort !== '') {
                $port = (int) $requestedPort;

                if (! $this->isPortFree($host, $port)) {
                    throw new InvalidOperationException("Port {$port} pada {$host} sedang dipakai. Pilih port lain atau biarkan default agar port bebas dipilih otomatis.");
                }
            } else {
                $port = $this->findFreePort($host, self::DEFAULT_PORT);
            }
        } catch (InvalidOperationException $e) {
            $this->style()->error($e->getMessage());
            $this->style()->blank();

            return 1;
        }

        $url = 'http://'.$this->displayHost($host).':'.$port.'/';

        $this->style()->info('WebUI Template ainstruct');
        $this->style()->keyValue('Alamat', $url);
        $this->style()->keyValue('Bind', $host);
        $this->style()->keyValue('Berhenti', 'Ctrl+C');
        $this->style()->blank();

        if (! $noOpen) {
            $this->openBrowser($url);
        }

        $command = 'php -S '.escapeshellarg($this->listenHost($host).':'.$port)
            .' -t '.escapeshellarg($docRoot)
            .' '.escapeshellarg($router);

        passthru($command, $exitCode);

        return $exitCode;
    }

    private function validPort(string $port): bool
    {
        if (! ctype_digit($port)) {
            return false;
        }

        $value = (int) $port;

        return $value >= 1 && $value <= 65535;
    }

    private function findFreePort(string $host, int $preferred): int
    {
        for ($offset = 0; $offset < 100; $offset++) {
            $port = $preferred + $offset;

            if ($this->isPortFree($host, $port)) {
                return $port;
            }
        }

        throw new InvalidOperationException("Tidak ada port bebas mulai dari {$preferred}. Hentikan server lain lalu coba lagi.");
    }

    private function isPortFree(string $host, int $port): bool
    {
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_server('tcp://'.$this->listenHost($host).':'.$port, $errno, $errstr);

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }

    private function displayHost(string $host): string
    {
        return match ($host) {
            '0.0.0.0' => '127.0.0.1',
            '::' => '[::1]',
            default => $host,
        };
    }

    /**
     * Host IPv6 perlu dibungkus tanda kurung siku dalam argumen listen
     * (tcp://[::]:port dan php -S '[::]:port').
     */
    private function listenHost(string $host): string
    {
        if (str_contains($host, ':') && ! str_starts_with($host, '[')) {
            return '['.$host.']';
        }

        return $host;
    }

    private function openBrowser(string $url): void
    {
        $arg = escapeshellarg($url);

        $command = match (PHP_OS_FAMILY) {
            'Darwin' => 'open '.$arg,
            'Windows' => 'start "" '.$arg,
            default => 'xdg-open '.$arg,
        };

        exec($command.' >/dev/null 2>&1 &');
    }
}
