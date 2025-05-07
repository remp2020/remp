<?php

use Valet\Drivers\LaravelValetDriver;

/**
 * Default Laravel Valet driver doesn't know how to redirect traffic to other scripts than index.php
 */
class LocalValetDriver extends LaravelValetDriver
{
    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        $filePath = implode(DIRECTORY_SEPARATOR, [$sitePath, 'public', $uri]);
        if (file_exists($filePath) && is_file($filePath)) {
            return $filePath;
        }
        return parent::frontControllerPath($sitePath, $siteName, $uri);
    }
}