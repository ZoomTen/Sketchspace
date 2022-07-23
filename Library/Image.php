<?php declare(strict_types = 1);

namespace Sketchspace\Library;

/**
 * Functions to help with image processing. Depends on `gd`.
 */
class Image
{
    public static function createThumbnail(string $source, string $thumb, $width): bool
    {
        $finfo = new \finfo();
        $image_mime = $finfo->file($source, FILEINFO_MIME_TYPE);
        $im = null;
        switch ($image_mime) {
            case 'image/x-ms-bmp':
            case 'image/bmp':
                $im = imagecreatefrombmp($source);
                break;
            case 'image/jpeg':
                $im = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $im = imagecreatefrompng($source);
                break;
            case 'image/gif':
                $im = imagecreatefromgif($source);
                break;
            default:
                return false; // not a supported image
        }
        
        if (!$im) {
            return false; // errored out
        }
        
        // make new image
        $ny = (int) floor(imagesy($im) * ($width / imagesx($im)));
        
        $nm = imagecreatetruecolor(
            $width,
            $ny
        );
        
        if (!$nm) {
            return false;
        }
        
        // make thumbnail
        if (!imagecopyresampled($nm, $im, 0,0,0,0,$width,$ny,imagesx($im),imagesy($im))) {
            return false;
        }
        
        // thumbnails are always jpeg
        return imagejpeg($nm, $thumb);
    }
}
