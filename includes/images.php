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
