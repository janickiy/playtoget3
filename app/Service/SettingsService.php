<?php

namespace App\Service;

use App\Http\Traits\File;
use App\Models\Settings;
use Illuminate\Http\Request;
use Exception;

class SettingsService
{
    use File;

    /**
     * Saves a configuration file and returns the name of the saved file.
     *
     * @param Request $request
     * @return string
     * @throws Exception
     */
    public function storeFile(Request $request): string
    {
        $extension = $request->file('value')->getClientOriginalExtension();
        $filename = time() . '.' . $extension;
        $originName = $request->file('value')->getClientOriginalName();

        if ($request->file('value')->move('uploads/' . Settings::getTableName(), $filename) === false) {
            throw new Exception(sprintf('Failed to save %s!', $originName));
        }

        return $filename;
    }

    /**
     * Replaces the configuration file and returns the name of the new file.
     *
     * @param Settings $settings
     * @param Request $request
     * @return string
     * @throws Exception
     */
    public function updateFile(Settings $settings, Request $request): string
    {
        self::deleteFile($settings->filePath(), Settings::getTableName());

        $extension = $request->file('value')->getClientOriginalExtension();
        $filename = time() . '.' . $extension;
        $originName = $request->file('value')->getClientOriginalName();

        if ($request->file('value')->move('uploads/' . Settings::getTableName(), $filename) === false) {
            throw new Exception(sprintf('Failed to save %s!', $originName));
        }

        return $filename;
    }
}
