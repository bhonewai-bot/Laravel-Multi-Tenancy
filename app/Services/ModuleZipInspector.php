<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ModuleZipInspector
{
    /**
     * Inspect the uploaded archive before we move anything into Modules/.
     */
    public function inspect(UploadedFile $file)
    {
        $zip = new ZipArchive();
        $zipPath = $file->getRealPath();

        // We fail before any extraction so invalid archives never touch the Modules directory.
        if (!$zipPath || $zip->open($zipPath) !== true) {
            throw new RuntimeException('Could not open the uploaded ZIP file.');
        }

        $foundModuleJson = false;
        $moduleJsonPathInZip = null;
        $moduleRootInZip = '';

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filePath = $zip->getNameIndex($i);

            // Block zip-slip style paths that could escape the intended extraction directory.
            if (
                Str::startsWith($filePath, ['../', '/']) ||
                Str::contains($filePath, ['/../', '..\\'])
            ) {
                $zip->close();
                throw new RuntimeException("Invalid ZIP path detected: {$filePath}");
            }

            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (in_array($extension, ['php', 'phtml', 'sh', 'exe', 'bat', 'cgi'], true)) {
                // allow php only inside a valid module package; do not block here
            }

            if (basename($filePath) === 'module.json') {
                $foundModuleJson = true;
                $moduleJsonPathInZip = $filePath;

                // We only extract the package folder that owns module.json, not arbitrary sibling files.
                $moduleRootInZip = pathinfo($filePath, PATHINFO_DIRNAME);
                if ($moduleRootInZip === '.') {
                    $moduleRootInZip = '';
                } elseif ($moduleRootInZip !== '') {
                    $moduleRootInZip .= '/';
                }

                break;
            }
        }

        if (!$foundModuleJson || !$moduleJsonPathInZip) {
            $zip->close();
            throw new RuntimeException('The ZIP is not a valid module package. Missing module.json.');
        }

        $moduleJsonContents = $zip->getFromName($moduleJsonPathInZip);
        $zip->close();

        // module.json becomes the package metadata source for the central catalog row.
        $moduleInfo = json_decode((string) $moduleJsonContents, true);
        if (!is_array($moduleInfo) || empty($moduleInfo['name'])) {
            throw new RuntimeException('module.json is invalid or missing  the "name" field.');
        }

        // Keep the destination folder name conservative so uploads cannot create odd filesystem paths.
        $moduleName = preg_replace('/[^A-Za-z0-9\-_]/', '', (string) $moduleInfo['name']);
        if (!$moduleName) {
            throw new RuntimeException('Module name in module.json is invalid.');
        }

        $migrationCandidates = [
            $moduleRootInZip . 'database/migrations/',
            $moduleRootInZip . 'Database/Migrations/',
        ];

        $hasMigrationDir = false;
        $hasMigrationFiles = false;

        $zip = new ZipArchive();
        $zip->open($zipPath);

        for ($i = 0; $i < $zip->numFiles; $i++) { 
            $filePath = $zip->getNameIndex($i);

            foreach ($migrationCandidates as $candidate) {
                // Phase 1 requires tenant migrations because installation relies on tenant DB provisioning.
                if (Str::startsWith($filePath, $candidate)) {
                    $hasMigrationDir = true;
                    
                    if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'php') {
                        $hasMigrationFiles = true;
                    }
                }
            }
        }

        $zip->close();

        if (!$hasMigrationDir) {
            throw new RuntimeException('The module package is missing a tenant migration directory.');
        }

        if (!$hasMigrationFiles) {
            throw new RuntimeException('No tenant migration files were found in the module package.');
        }

        return [
            'module_name' => $moduleName,
            'module_info' => $moduleInfo,
            'module_root_in_zip' => $moduleRootInZip,
        ];
    }

    /**
     * Extract the validated package into a temp directory, then move it into Modules/.
     */
    public function extract(UploadedFile $file, string $moduleRootInZip, string $moduleName): string
    {
        $zip = new ZipArchive();
        $zipPath = $file->getRealPath();

        // Re-open the same validated archive for the actual extraction step.
        if (!$zipPath || $zip->open($zipPath) !== true) {
            throw new RuntimeException('Could not re-open the uploaded ZIP file.');
        }

        // Extract to a temp location first so a failed upload never leaves a half-installed module behind.
        $tempExtractPath = storage_path('app/tmp/module_' . Str::random(12));
        File::ensureDirectoryExists($tempExtractPath);

        $filesToExtract = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filePath = $zip->getNameIndex($i);

            // Only extract the validated package subtree rooted at module.json.
            if ($moduleRootInZip === '' || Str::startsWith($filePath, $moduleRootInZip)) {
                $filesToExtract[] = $filePath;
            }
        }

        $zip->extractTo($tempExtractPath, $filesToExtract);
        $zip->close();

        // ZIPs may either contain a top-level module folder or place module.json at the root.
        $sourcePath = $moduleRootInZip === '' 
            ? $tempExtractPath
            : $tempExtractPath . '/' . rtrim($moduleRootInZip, '/');

        $destinationPath = base_path('Modules/' . $moduleName);

        // Reject accidental overwrites until module update/versioning is designed explicitly.
        if (File::isDirectory($destinationPath)) {
            File::deleteDirectory($tempExtractPath);
            throw new RuntimeException("A module named '{$moduleName}' already exists.");
        }

        // The move into Modules/ is the point where the package becomes part of the app codebase.
        if (!File::moveDirectory($sourcePath, $destinationPath)) {
            File::deleteDirectory($tempExtractPath);
            throw new RuntimeException('Failed to move the extracted module into the Modules directory.');
        }

        File::deleteDirectory($tempExtractPath);

        return $destinationPath;
    }
}
