<?php

function image_tools_available(): bool
{
    return function_exists('imagecreatefromjpeg') && function_exists('imagejpeg');
}

function image_load(string $path)
{
    $info = @getimagesize($path);
    if (!$info) {
        return null;
    }
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $image = @imagecreatefromjpeg($path);
            break;
        case IMAGETYPE_PNG:
            $image = @imagecreatefrompng($path);
            break;
        case IMAGETYPE_WEBP:
            $image = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
            break;
        case IMAGETYPE_GIF:
            $image = @imagecreatefromgif($path);
            break;
        default:
            $image = false;
    }
    if (!$image) {
        return null;
    }
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $angles = [3 => 180, 6 => -90, 8 => 90];
        if (isset($angles[$orientation])) {
            $rotated = imagerotate($image, $angles[$orientation], 0);
            if ($rotated) {
                imagedestroy($image);
                $image = $rotated;
            }
        }
    }
    return $image;
}

/**
 * Crops empty (transparent or white) margins around a logo and scales it down
 * to $maxHeight, saving a PNG with transparency next to the original.
 * Returns the new absolute path, or null when nothing could be done.
 */
function image_fit_logo(string $path, int $maxHeight = 240, int $pad = 4): ?string
{
    if (!image_tools_available() || !function_exists('imagecreatefrompng')) {
        return null;
    }
    $image = image_load($path);
    if (!$image) {
        return null;
    }
    $width = imagesx($image);
    $height = imagesy($image);
    $isContent = static function (int $x, int $y) use ($image): bool {
        $c = imagecolorat($image, $x, $y);
        $alpha = ($c >> 24) & 127;
        if ($alpha > 100) {
            return false;
        }
        return min(($c >> 16) & 255, ($c >> 8) & 255, $c & 255) < 240;
    };
    $rowHas = static function (int $y) use ($width, $isContent): bool {
        for ($x = 0; $x < $width; $x += 2) {
            if ($isContent($x, $y)) {
                return true;
            }
        }
        return false;
    };
    $colHas = static function (int $x, int $top, int $bottom) use ($isContent): bool {
        for ($y = $top; $y <= $bottom; $y += 2) {
            if ($isContent($x, $y)) {
                return true;
            }
        }
        return false;
    };
    $top = 0;
    while ($top < $height && !$rowHas($top)) {
        $top++;
    }
    if ($top >= $height) {
        imagedestroy($image);
        return null;
    }
    $bottom = $height - 1;
    while ($bottom > $top && !$rowHas($bottom)) {
        $bottom--;
    }
    $left = 0;
    while ($left < $width && !$colHas($left, $top, $bottom)) {
        $left++;
    }
    $right = $width - 1;
    while ($right > $left && !$colHas($right, $top, $bottom)) {
        $right--;
    }
    $left = max(0, $left - $pad);
    $top = max(0, $top - $pad);
    $cropWidth = min($width, $right + $pad + 1) - $left;
    $cropHeight = min($height, $bottom + $pad + 1) - $top;

    $scale = min(1, $maxHeight / $cropHeight);
    $outWidth = max(1, (int) round($cropWidth * $scale));
    $outHeight = max(1, (int) round($cropHeight * $scale));
    $out = imagecreatetruecolor($outWidth, $outHeight);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagefill($out, 0, 0, imagecolorallocatealpha($out, 255, 255, 255, 127));
    imagecopyresampled($out, $image, 0, 0, $left, $top, $outWidth, $outHeight, $cropWidth, $cropHeight);

    $target = preg_replace('/\.[a-z0-9]+$/i', '', $path) . '-logo.png';
    $ok = imagepng($out, $target, 9);
    imagedestroy($image);
    imagedestroy($out);
    return $ok ? $target : null;
}

/**
 * Re-encodes an image as JPEG no wider than $maxWidth. PNG/WEBP/GIF with
 * transparency are flattened on white, which suits photos but not logos.
 */
function image_resize_to_jpeg(string $source, string $target, int $maxWidth, int $quality = 78): bool
{
    if (!image_tools_available()) {
        return false;
    }
    $image = image_load($source);
    if (!$image) {
        return false;
    }
    $width = imagesx($image);
    $height = imagesy($image);
    $newWidth = min($width, $maxWidth);
    $newHeight = (int) round($height * ($newWidth / $width));
    $canvas = imagecreatetruecolor($newWidth, $newHeight);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
    imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imageinterlace($canvas, true);
    $ok = imagejpeg($canvas, $target, $quality);
    imagedestroy($image);
    imagedestroy($canvas);
    return $ok;
}
