<?php

namespace Lace\Ainstruct\Services\Detection;

use Lace\Ainstruct\Contracts\Detection\StackDetectorContract;
use Lace\Ainstruct\Contracts\Repository\InstructionFileRepositoryContract;
use Lace\Ainstruct\Enums\DetectionConfidence;
use Lace\Ainstruct\Values\DetectedStack;
use Lace\Ainstruct\Values\Template;

/**
 * SATU-SATUNYA service di aplikasi (warranted) — deteksi stack menyilang
 * beberapa template + filesystem. Membaca `ainstruct-detect.txt` per template:
 *   <bobot>|<tipe>|<argumen>|<label>
 *   tipe 'file' : argumen path relatif; cocok bila file ada.
 *   tipe 'dir'  : argumen path relatif; cocok bila direktori ada.
 *   tipe 'grep' : argumen <path>:<pola>; cocok bila file ada & memuat pola.
 * Skor = jumlah bobot; keyakinan: >=6 CONFIRMED, >=4 STRONG, >=2 WEAK, else UNKNOWN.
 */
final class StackDetectionService implements StackDetectorContract
{
    public function __construct(private InstructionFileRepositoryContract $files) {}

    public function evaluate(Template $template, string $projectDir): DetectedStack
    {
        $detectFile = $template->directory.DIRECTORY_SEPARATOR.'ainstruct-detect.txt';

        if (! $this->files->isFile($detectFile)) {
            return new DetectedStack(
                templateName: $template->name,
                templateDir: $template->directory,
                score: 0,
                confidence: DetectionConfidence::UNKNOWN,
                signals: [],
            );
        }

        $score = 0;
        $signals = [];

        foreach (preg_split('/\r\n|\r|\n/', $this->files->readFile($detectFile), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('|', $line);

            if (count($parts) < 4) {
                continue;
            }

            [$weight, $type, $argument, $label] = $parts;

            $matched = $this->matches($type, $argument, $projectDir);

            if ($matched) {
                $score += (int) $weight;
                $signals[] = $label;
            }
        }

        return new DetectedStack(
            templateName: $template->name,
            templateDir: $template->directory,
            score: $score,
            confidence: DetectionConfidence::fromScore($score),
            signals: $signals,
        );
    }

    private function matches(string $type, string $argument, string $projectDir): bool
    {
        return match ($type) {
            'file' => $this->files->isFile($projectDir.DIRECTORY_SEPARATOR.$argument),
            'dir' => $this->files->isDirectory($projectDir.DIRECTORY_SEPARATOR.$argument),
            'grep' => $this->matchesGrep($argument, $projectDir),
            default => false,
        };
    }

    private function matchesGrep(string $argument, string $projectDir): bool
    {
        $separator = strpos($argument, ':');

        if ($separator === false) {
            return false;
        }

        $path = substr($argument, 0, $separator);
        $pattern = substr($argument, $separator + 1);
        $file = $projectDir.DIRECTORY_SEPARATOR.$path;

        if (! $this->files->isFile($file)) {
            return false;
        }

        // Bash `grep -qE` memakai ERE tanpa delimiter; PHP butuh delimiter.
        // Pakai `~` (tidak mengganggu pola `/` seperti "laravel/framework"),
        // escape `~` dalam pola agar tidak memecah delimiter.
        $escaped = str_replace('~', '\~', $pattern);

        return @preg_match('~'.$escaped.'~', $this->files->readFile($file)) === 1;
    }
}
