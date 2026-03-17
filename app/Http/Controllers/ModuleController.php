<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Manages the central module catalog that tenants can request and install.
 */
class ModuleController extends Controller
{
    /**
     * Display the paginated module catalog.
     *
     * @return View
     */
    public function index(): View
    {
        $modules = Module::latest()->paginate(15);
        return view('modules.index', compact('modules'));
    }

    /**
     * Show the module creation form.
     *
     * @return View
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
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:modules,name'],
            'slug' => ['required', 'string', 'max:255', 'unique:modules,slug'],
            'version' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'icon_path' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $data['price'] = $data['price'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? true;

        Module::create($data);

        return redirect()->route('modules.index')->with('success', 'Module created successfully.');
    }

    /**
     * Toggle whether a module is available to tenants.
     *
     * Side effects:
     * - Writes to the modules table.
     *
     * @param  Module  $module
     * @return RedirectResponse
     */
    public function toggleStatus(Module $module): RedirectResponse
    {
        $module->is_active = !$module->is_active;
        $module->save();

        return back()->with('success', "Module '{$module->name}' updated successfully.");
    }
}
