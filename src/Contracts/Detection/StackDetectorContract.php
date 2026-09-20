<?php

namespace Lace\Ainstruct\Contracts\Detection;

use Lace\Ainstruct\Values\DetectedStack;
use Lace\Ainstruct\Values\Template;

interface StackDetectorContract
{
    /**
     * Evaluasi satu template terhadap direktori proyek konsumen: baca
     * `ainstruct-detect.txt` (format `bobot|tipe|argumen|label`), hitung skor
     * dan keyakinan. Tanpa detect file → skor 0 / UNKNOWN.
     */
    public function evaluate(Template $template, string $projectDir): DetectedStack;
}
