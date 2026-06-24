<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Services\ModuleZipInspector;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Manages the central module catalog that tenants can request and install.
 */
class ModuleController extends Controller
{
    /**
     * Display the paginated module catalog.
     */
    public function index(): View
    {
        $modules = Module::latest()->paginate(15);

        return view('modules.index', compact('modules'));
    }

    /**
     * Show the module creation form.
     */
    public function create(): View
    {
        return view('modules.create');
    }

    /**
     * Persist a new module definition in the central database.
     *
     * Side effects:
     * - Writes to the modules table.
     */
    public function store(Request $request, ModuleZipInspector $inspector): RedirectResponse
    {
        $request->validate([
            'module_file' => ['required', 'file', 'mimes:zip'],
        ]);

        try {
            $inspection = $inspector->inspect($request->file('module_file'));
            $moduleInfo = $inspection['module_info'];

            // Only create the catalog row after the package has been safely extracted into Modules/.
            $inspector->extract(
                $request->file('module_file'),
                $inspection['module_root_in_zip'],
                $inspection['module_name']
            );

            Module::create([
                'name' => $moduleInfo['name'] ?? $inspection['module_name'],
                'slug' => Str::slug($moduleInfo['alias'] ?? $inspection['module_name']),
                'version' => $moduleInfo['version'] ?? '1.0.0',
                'description' => $moduleInfo['description'] ?? null,
                'icon_path' => $moduleInfo['icon'] ?? $moduleInfo['icon_path'] ?? null,
                'price' => $moduleInfo['price'] ?? 0,
                'is_active' => false,
            ]);

            return redirect()->route('modules.index')->with('success', "Module '{$inspection['module_name']}' uploaded successfully.");

        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Toggle whether a module is available to tenants.
     *
     * Side effects:
     * - Writes to the modules table.
     */
    public function toggleStatus(Module $module): RedirectResponse
    {
        $module->is_active = ! $module->is_active;
        $module->save();

        return back()->with('success', "Module '{$module->name}' updated successfully.");
    }
}
