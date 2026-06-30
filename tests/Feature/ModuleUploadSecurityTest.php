<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\User;
use App\Services\ModuleZipInspector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class ModuleUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'email' => config('auth.central_admin.email'),
        ]);
    }

    /**
     * Build a ZIP file containing the given relative paths.
     *
     * @param  array<string, string>  $files  path => contents
     */
    private function buildZip(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'upload').'.zip';
        $zip = new ZipArchive;

        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $filePath => $contents) {
            $zip->addFromString($filePath, $contents);
        }

        $zip->close();

        return $path;
    }

    private function uploadModule(string $zipPath): \Illuminate\Testing\TestResponse
    {
        $uploaded = new UploadedFile(
            $zipPath,
            'module.zip',
            'application/zip',
            null,
            true
        );

        return $this
            ->actingAs($this->adminUser())
            ->post('/modules', ['module_file' => $uploaded]);
    }

    // ----------------------------------------------------------------
    // UPLOAD-02: Dangerous file types blocked
    // ----------------------------------------------------------------

    public function test_php_in_allowed_directory_is_accepted(): void
    {
        Storage::disk('local')->makeDirectory('tmp');

        $zipPath = $this->buildZip([
            'TestModule/module.json' => '{"name":"PhpOkTest","version":"1.0.0","alias":"phpok"}',
            'TestModule/database/migrations/001_create_table.php' => '<?php',
            'TestModule/config/app.php' => '<?php',
        ]);

        $inspector = app(ModuleZipInspector::class);
        $file = new UploadedFile($zipPath, 'module.zip', 'application/zip', null, true);

        $result = $inspector->inspect($file);
        $dest = $inspector->extract($file, $result['module_root_in_zip'], $result['module_name']);

        $this->assertDirectoryExists($dest);

        if (is_dir($dest)) {
            \File::deleteDirectory($dest);
        }
    }

    public function test_php_in_disallowed_directory_is_blocked_by_allowlist(): void
    {
        $zipPath = $this->buildZip([
            'TestModule/module.json' => '{"name":"Test","version":"1.0.0"}',
            'TestModule/database/migrations/001_create_table.php' => '<?php',
            'TestModule/malicious.php' => '<?php echo "hacked";',
        ]);

        $inspector = app(ModuleZipInspector::class);
        $file = new UploadedFile($zipPath, 'module.zip', 'application/zip', null, true);

        $result = $inspector->inspect($file);

        // Root PHP is in a disallowed location — allowlist catches it during extraction.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Blocked disallowed file type');

        $inspector->extract($file, $result['module_root_in_zip'], $result['module_name']);
    }

    public function test_zip_containing_phar_is_rejected(): void
    {
        $zipPath = $this->buildZip([
            'TestModule/module.json' => '{"name":"Test","version":"1.0.0"}',
            'TestModule/payload.phar' => 'phar content',
        ]);

        $inspector = app(ModuleZipInspector::class);
        $file = new UploadedFile($zipPath, 'module.zip', 'application/zip', null, true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Blocked dangerous file type');

        $inspector->inspect($file);
    }

    public function test_zip_containing_shell_script_is_rejected(): void
    {
        $zipPath = $this->buildZip([
            'TestModule/module.json' => '{"name":"Test","version":"1.0.0"}',
            'TestModule/setup.sh' => '#!/bin/bash',
        ]);

        $inspector = app(ModuleZipInspector::class);
        $file = new UploadedFile($zipPath, 'module.zip', 'application/zip', null, true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Blocked dangerous file type');

        $inspector->inspect($file);
    }

    public function test_zip_containing_exe_is_rejected(): void
    {
        $zipPath = $this->buildZip([
            'TestModule/module.json' => '{"name":"Test","version":"1.0.0"}',
            'TestModule/virus.exe' => 'MZ binary',
        ]);

        $inspector = app(ModuleZipInspector::class);
        $file = new UploadedFile($zipPath, 'module.zip', 'application/zip', null, true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Blocked dangerous file type');

        $inspector->inspect($file);
    }

    public function test_zip_containing_phtml_is_rejected(): void
    {
        $zipPath = $this->buildZip([
            'TestModule/module.json' => '{"name":"Test","version":"1.0.0"}',
            'TestModule/backdoor.phtml' => '<?php system($_GET["cmd"]);',
        ]);

        $inspector = app(ModuleZipInspector::class);
        $file = new UploadedFile($zipPath, 'module.zip', 'application/zip', null, true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Blocked dangerous file type');

        $inspector->inspect($file);
    }

    // ----------------------------------------------------------------
    // UPLOAD-03: Allowlist enforcement during extraction
    // ----------------------------------------------------------------

    public function test_extraction_blocks_disallowed_file_types(): void
    {
        $zipPath = $this->buildZip([
            'TestModule/module.json' => '{"name":"Test","version":"1.0.0"}',
            'TestModule/database/migrations/001_create_table.php' => '<?php',
            'TestModule/config/settings.php' => '<?php',
            'TestModule/unknown.xyz' => 'not allowed',
        ]);

        $inspector = app(ModuleZipInspector::class);
        $file = new UploadedFile($zipPath, 'module.zip', 'application/zip', null, true);

        $result = $inspector->inspect($file);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Blocked disallowed file type');

        $inspector->extract($file, $result['module_root_in_zip'], $result['module_name']);
    }

    public function test_valid_module_passes_all_checks(): void
    {
        Storage::disk('local')->makeDirectory('tmp');

        $zipPath = $this->buildZip([
            'TestModule/module.json' => '{"name":"TestModule","version":"1.0.0","alias":"test"}',
            'TestModule/database/migrations/2026_01_01_create_table.php' => '<?php',
            'TestModule/database/seeders/TestSeeder.php' => '<?php',
            'TestModule/config/settings.php' => '<?php',
            'TestModule/Routes/web.php' => '<?php',
            'TestModule/Resources/views/index.blade.php' => '<div>Hello</div>',
            'TestModule/Resources/css/app.css' => 'body {}',
            'TestModule/Resources/js/app.js' => 'console.log()',
        ]);

        $inspector = app(ModuleZipInspector::class);
        $file = new UploadedFile($zipPath, 'module.zip', 'application/zip', null, true);

        $result = $inspector->inspect($file);

        $this->assertSame('TestModule', $result['module_name']);
        $this->assertIsArray($result['module_info']);

        $dest = $inspector->extract($file, $result['module_root_in_zip'], $result['module_name']);

        $this->assertDirectoryExists($dest);

        // Cleanup
        if (is_dir($dest)) {
            \File::deleteDirectory($dest);
        }
    }

    // ----------------------------------------------------------------
    // UPLOAD-04: Error masking in controller
    // ----------------------------------------------------------------

    public function test_upload_failure_shows_generic_error_to_user(): void
    {
        $admin = User::factory()->create([
            'email' => config('auth.central_admin.email'),
        ]);

        $zipPath = $this->buildZip([
            'TestModule/module.json' => '{"name":"Test","version":"1.0.0"}',
            'TestModule/malicious.php' => '<?php echo "hacked";',
        ]);

        $uploaded = new UploadedFile($zipPath, 'module.zip', 'application/zip', null, true);
        $request = \Illuminate\Http\Request::create('/modules', 'POST', [], [], ['module_file' => $uploaded]);
        $request->setUserResolver(fn () => $admin);

        $controller = new \App\Http\Controllers\ModuleController;
        $response = $controller->store($request, app(\App\Services\ModuleZipInspector::class));

        // User sees a generic error, not the internal exception message.
        $errors = $response->getSession()->get('error');
        $this->assertSame('Module upload failed. Please check the package and try again.', $errors);
    }

    public function test_upload_success_creates_module_record(): void
    {
        $admin = User::factory()->create([
            'email' => config('auth.central_admin.email'),
        ]);

        $zipPath = $this->buildZip([
            'TestModule/module.json' => '{"name":"UploadTest","version":"1.0.0","alias":"uploadtest"}',
            'TestModule/database/migrations/001_create_table.php' => '<?php',
        ]);

        $uploaded = new UploadedFile($zipPath, 'module.zip', 'application/zip', null, true);
        $request = \Illuminate\Http\Request::create('/modules', 'POST', [], [], ['module_file' => $uploaded]);
        $request->setUserResolver(fn () => $admin);

        $controller = new \App\Http\Controllers\ModuleController;
        $response = $controller->store($request, app(\App\Services\ModuleZipInspector::class));

        $this->assertSame(route('modules.index'), $response->getTargetUrl());
        $this->assertDatabaseHas('modules', ['slug' => 'uploadtest']);

        // Cleanup created module directory
        $dest = base_path('Modules/UploadTest');
        if (is_dir($dest)) {
            \File::deleteDirectory($dest);
        }
    }

    // ----------------------------------------------------------------
    // UPLOAD-01: Route-level protection (inherited from Phase 1)
    // ----------------------------------------------------------------

    public function test_non_admin_cannot_access_module_upload_route(): void
    {
        $user = User::factory()->create(['email' => 'regular@example.com']);

        $response = $this
            ->actingAs($user)
            ->get('/modules/create');

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_store_module(): void
    {
        $user = User::factory()->create(['email' => 'regular@example.com']);

        $zipPath = $this->buildZip([
            'TestModule/module.json' => '{"name":"Test","version":"1.0.0"}',
        ]);

        $uploaded = new UploadedFile($zipPath, 'module.zip', 'application/zip', null, true);

        $response = $this
            ->actingAs($user)
            ->post('/modules', ['module_file' => $uploaded]);

        $response->assertForbidden();
    }
}
