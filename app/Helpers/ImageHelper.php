<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    /**
     * Generate a full URL for a stored image, or return null if the file
     * doesn't physically exist on disk.
     *
     * This prevents broken image URLs in the frontend when the database
     * contains a path but the file was never uploaded (e.g. seeded data)
     * or has since been deleted.
     */
    public static function url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        // Check that the file actually exists on the public disk
        if (!Storage::disk('public')->exists($path)) {
            return null;
        }

        return asset('storage/' . $path);
    }
}
