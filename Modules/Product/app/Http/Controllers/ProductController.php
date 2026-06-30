<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Product\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $stats = [
            'totalProducts' => Product::query()->count(),
            'inventoryCount' => (int) Product::query()->sum('quantity'),
            'lowInventoryCount' => Product::query()
                ->where('quantity', '>', 0)
                ->where('quantity', '<=', 10)
                ->count(),
            'catalogValue' => (float) Product::query()
                ->selectRaw('COALESCE(SUM(price * quantity), 0) as total')
                ->value('total'),
        ];

        return view('product::index', compact('stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('product::create');
    }

    /**
     * Show the specified resource.
     */
    public function show(Product $product): View
    {
        return view('product::show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): View
    {
        return view('product::edit', compact('product'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('product.index')->with('success', 'Product deleted successfully.');
    }
}
