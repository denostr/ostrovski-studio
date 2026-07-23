<?php

namespace App\Console\Commands;

use GdImage;
use Illuminate\Console\Command;

/**
 * Generate the WebP variants the views reference from the JPG originals in
 * public/media/ (which live outside git and are deployed separately).
 * Re-run after adding or replacing originals; the name => [width, quality]
 * map lives in config/ostrovski.php.
 *
 * Uses GD (imagewebp) — the ImageMagick build on the dev VM silently
 * ignores its WebP quality setting, GD honours it. GD reads raw JPEG
 * samples, so EXIF orientation is applied manually below; ICC profiles are
 * dropped (fine for sRGB originals — a Display P3 photo would desaturate,
 * convert such originals to sRGB before adding them).
 */
class OptimizeMedia extends Command
{
    protected $signature = 'media:optimize';

    protected $description = 'Generate the WebP media variants the views reference from the JPG originals';

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('PHP GD with WebP support is required.');

            return self::FAILURE;
        }

        // The 3808x5711 hero original needs ~110MB peak in GD — more than
        // a typical production CLI memory_limit of 128M.
        ini_set('memory_limit', '512M');

        foreach (config('ostrovski.media') as $name => [$maxWidth, $quality]) {
            $src = public_path("media/$name.JPG");
            $out = public_path("media/$name.webp");

            if (! is_file($src)) {
                $this->error("Missing original $src");

                return self::FAILURE;
            }

            $image = imagecreatefromjpeg($src);

            if ($image === false) {
                $this->error("Could not decode $src");

                return self::FAILURE;
            }

            $image = $this->applyExifOrientation($image, $src);

            if (imagesx($image) > $maxWidth) {
                $scaled = imagescale($image, $maxWidth, -1, IMG_BICUBIC);

                if ($scaled === false) {
                    $this->error("Could not resize $src");

                    return self::FAILURE;
                }

                imagedestroy($image);
                $image = $scaled;
            }

            if (! imagewebp($image, $out, $quality)) {
                $this->error("Could not write $out");

                return self::FAILURE;
            }

            imagedestroy($image);

            $this->line(sprintf(
                '%s.webp: %dK q%d (from %dK)',
                $name,
                (int) round(filesize($out) / 1024),
                $quality,
                (int) round(filesize($src) / 1024),
            ));
        }

        return self::SUCCESS;
    }

    /**
     * GD ignores the EXIF Orientation tag, so a phone JPG stored rotated
     * would end up sideways on the site — normalise the pixels here (the
     * WebP output carries no EXIF for the browser to correct it with).
     */
    private function applyExifOrientation(GdImage $image, string $path): GdImage
    {
        if (! function_exists('exif_read_data')) {
            $this->warn("exif extension missing — EXIF orientation of $path not checked.");

            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        if (in_array($orientation, [2, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        } elseif ($orientation === 4) {
            imageflip($image, IMG_FLIP_VERTICAL);
        }

        // imagerotate() angles are counter-clockwise.
        $angle = match ($orientation) {
            3 => 180,
            5, 6 => -90,
            7, 8 => 90,
            default => 0,
        };

        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);

            if ($rotated !== false) {
                imagedestroy($image);
                $image = $rotated;
            }
        }

        return $image;
    }
}
