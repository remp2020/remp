<?php

/**
 * Default Laravel Valet driver doesn't know how to redirect traffic to other scripts than index.php
 */
class LocalValetDriver extends LaravelValetDriver
{
    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        $filePath = implode(DIRECTORY_SEPARATOR, [$sitePath, 'public', $uri]);
        if (file_exists($filePath) && is_file($filePath)) {
            return $filePath;
        }
        return parent::frontControllerPath($sitePath, $siteName, $uri);
    }
}