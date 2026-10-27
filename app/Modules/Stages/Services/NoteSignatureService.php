<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Signature numérique des notes de service (RD / FC-P).
 *
 * L'image envoyée est toujours réécrite en PNG propre (aucune donnée du
 * fichier d'origine n'est gardée) : fond rendu transparent s'il ne l'est
 * pas déjà, marges vides retirées. Dans le PDF, elle tient dans un cadre
 * fixe en gardant ses proportions : jamais trop grande, trop petite ni
 * déformée, quelles que soient ses dimensions d'origine.
 */
class NoteSignatureService
{
    private const SETTING = 'note_signature';
    private const DIR = 'stages/signature';

    /** Cadre de la signature dans le PDF (mm). */
    public const BOX_W_MM = 60;
    public const BOX_H_MM = 24;

    /** Taille de travail et taille finale (px, plus grand côté). */
    private const WORK_PX = 1600;
    private const FINAL_PX = 1200;

    public function __construct(private readonly StageSettingsService $settings) {}

    /** @return array{width: int, height: int, updated_at: string} */
    public function store(UploadedFile $file, ?int $userId): array
    {
        $png = $this->clean((string) file_get_contents($file->getRealPath()));
        [$w, $h] = getimagesizefromstring($png);

        $path = self::DIR . '/note-' . Str::random(24) . '.png';
        Storage::disk(StageRequestService::DISK)->put($path, $png);

        $previous = $this->current();
        $meta = ['path' => $path, 'width' => $w, 'height' => $h, 'updated_at' => now()->toIso8601String()];
        $this->settings->set(self::SETTING, $meta, $userId);

        if ($previous) {
            Storage::disk(StageRequestService::DISK)->delete($previous['path']);
        }

        return $this->publicMeta($meta);
    }

    public function remove(?int $userId): void
    {
        $previous = $this->current();
        $this->settings->set(self::SETTING, null, $userId);
        if ($previous) {
            Storage::disk(StageRequestService::DISK)->delete($previous['path']);
        }
    }

    /** Ce que le progiciel peut savoir (jamais le chemin sur le disque). */
    public function publicMeta(?array $meta = null): ?array
    {
        $meta ??= $this->current();

        return $meta ? ['width' => $meta['width'], 'height' => $meta['height'], 'updated_at' => $meta['updated_at']] : null;
    }

    public function content(): ?string
    {
        $meta = $this->current();

        return $meta ? Storage::disk(StageRequestService::DISK)->get($meta['path']) : null;
    }

    /**
     * Image prête pour le PDF : data URI et dimensions (mm) qui tiennent dans
     * le cadre en gardant les proportions.
     *
     * @return array{src: string, width_mm: float, height_mm: float}|null
     */
    public function forPdf(): ?array
    {
        $meta = $this->current();
        $png  = $meta ? $this->content() : null;
        if (!$png) {
            return null;
        }

        $scale = min(self::BOX_W_MM / $meta['width'], self::BOX_H_MM / $meta['height']);

        return [
            'src'       => 'data:image/png;base64,' . base64_encode($png),
            'width_mm'  => round($meta['width'] * $scale, 2),
            'height_mm' => round($meta['height'] * $scale, 2),
        ];
    }

    // ── Interne ──────────────────────────────────────────────────────────

    private function current(): ?array
    {
        $meta = $this->settings->get(self::SETTING);

        return is_array($meta) && !empty($meta['path']) ? $meta : null;
    }

    /** Fond transparent, marges retirées, PNG réécrit. */
    private function clean(string $bytes): string
    {
        $src = @imagecreatefromstring($bytes);
        if (!$src) {
            throw new BusinessException("Cette image n'a pas pu être lue. Envoyez un PNG ou un JPG.", 'STAGE_SIGNATURE_UNREADABLE', 422);
        }

        $img = $this->resized($src, self::WORK_PX);
        imagedestroy($src);

        if (!$this->hasTransparency($img)) {
            $this->removeBackground($img);
        }

        $box = $this->visibleBox($img);
        if (!$box) {
            imagedestroy($img);
            throw new BusinessException("Aucune signature n'est visible sur cette image.", 'STAGE_SIGNATURE_EMPTY', 422);
        }

        // Petite marge autour du tracé pour ne rien couper.
        [$x0, $y0, $x1, $y1] = $box;
        $pad = (int) round(max($x1 - $x0, $y1 - $y0) * 0.02) + 2;
        $x0 = max(0, $x0 - $pad);
        $y0 = max(0, $y0 - $pad);
        $x1 = min(imagesx($img) - 1, $x1 + $pad);
        $y1 = min(imagesy($img) - 1, $y1 + $pad);

        $crop = $this->canvas($x1 - $x0 + 1, $y1 - $y0 + 1);
        imagecopy($crop, $img, 0, 0, $x0, $y0, $x1 - $x0 + 1, $y1 - $y0 + 1);
        imagedestroy($img);

        $final = $this->resized($crop, self::FINAL_PX);
        imagedestroy($crop);

        ob_start();
        imagepng($final, null, 9);
        imagedestroy($final);

        return (string) ob_get_clean();
    }

    /** Copie en couleurs vraies avec transparence, réduite si plus grande que $max. */
    private function resized(\GdImage $src, int $max): \GdImage
    {
        if (!imageistruecolor($src)) {
            imagepalettetotruecolor($src);
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $ratio = min(1, $max / max($w, $h));
        $nw = max(1, (int) round($w * $ratio));
        $nh = max(1, (int) round($h * $ratio));

        $dst = $this->canvas($nw, $nh);
        imagealphablending($src, false);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }

    private function canvas(int $w, int $h): \GdImage
    {
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 255, 255, 255, 127));

        return $img;
    }

    /** Au moins 1 % de pixels transparents : le fond est déjà retiré. */
    private function hasTransparency(\GdImage $img): bool
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $step = max(1, (int) floor(sqrt($w * $h / 40000)));
        $seen = $clear = 0;
        for ($y = 0; $y < $h; $y += $step) {
            for ($x = 0; $x < $w; $x += $step) {
                $seen++;
                if (((imagecolorat($img, $x, $y) >> 24) & 0x7F) > 100) {
                    $clear++;
                }
            }
        }

        return $clear > $seen * 0.01;
    }

    /**
     * Rend le papier transparent : la teinte du fond est mesurée sur l'image
     * (photo d'une feuille blanche ou grisée), l'encre garde sa couleur et
     * ses bords restent doux.
     */
    private function removeBackground(\GdImage $img): void
    {
        $w = imagesx($img);
        $h = imagesy($img);

        $hist = array_fill(0, 256, 0);
        $lum = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($img, $x, $y);
                $l = (int) round(0.299 * (($c >> 16) & 0xFF) + 0.587 * (($c >> 8) & 0xFF) + 0.114 * ($c & 0xFF));
                $lum[$y * $w + $x] = $l;
                $hist[$l]++;
            }
        }

        // Fond = luminosité atteinte par 60 % des pixels (le papier domine).
        $target = $w * $h * 0.4;
        $bg = 255;
        for ($acc = 0; $bg > 0; $bg--) {
            $acc += $hist[$bg];
            if ($acc >= $target) {
                break;
            }
        }
        $range = max(40, $bg * 0.5);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $ink = ($bg - $lum[$y * $w + $x]) / $range;
                $c = imagecolorat($img, $x, $y);
                $alpha = $ink <= 0.12 ? 127 : (int) round(127 * (1 - min(1, $ink)));
                imagesetpixel($img, $x, $y, imagecolorallocatealpha($img, ($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF, $alpha));
            }
        }
    }

    /** @return array{0: int, 1: int, 2: int, 3: int}|null Cadre du tracé visible. */
    private function visibleBox(\GdImage $img): ?array
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $x0 = $w;
        $y0 = $h;
        $x1 = $y1 = -1;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if (((imagecolorat($img, $x, $y) >> 24) & 0x7F) < 100) {
                    $x0 = min($x0, $x);
                    $x1 = max($x1, $x);
                    $y0 = min($y0, $y);
                    $y1 = max($y1, $y);
                }
            }
        }

        return $x1 < 0 ? null : [$x0, $y0, $x1, $y1];
    }
}
