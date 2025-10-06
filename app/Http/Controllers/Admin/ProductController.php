<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     * 
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Filters: q (name/description/sku or category name), category_id
        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhereHas('category', function ($cat) use ($q) {
                        $cat->where('name', 'like', "%{$q}%");
                    });
            });
        }

        if ($categoryId = $request->integer('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Remove status dropdown filter: status is computed in model and shown in UI only
        $products = $query->latest('updated_at')->paginate(10)->appends($request->query());
        $categories = Category::orderBy('name')->get();

        return view('apps.ecommerce.products', compact('products', 'categories'));
    }

    /**
     * Display the specified product.
     * 
     * @param  \App\Models\Product  $product
     * @return \Illuminate\View\View
     */
    public function show(Product $product)
    {
        $product->load('category');
        return view('apps.ecommerce.product-details', compact('product'));
    }

    /**
     * Show the form for creating a new product.
     * Only accessible via signed route.
     */
    public function create(Request $request)
    {
        $categories = Category::orderBy('name')->get();
        return view('apps.ecommerce.product-create', compact('categories'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id'   => ['required', 'exists:categories,id'],
            'name'          => ['required', 'string', 'min:3', 'max:255'],
            'price'         => ['required', 'numeric', 'min:0'],
            'description'   => ['nullable', 'string'],
            'sku'           => ['nullable', 'string', 'max:100'],
            'stock'         => ['nullable', 'integer', 'min:0'],
            'stock_bs_kg'   => ['nullable', 'numeric', 'min:0'],
            'stock_fs_kg'   => ['nullable', 'numeric', 'min:0'],
            'minimum_limit' => ['required', 'numeric', 'min:1'],
            'image'         => ['nullable', 'image', 'max:2048'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
            $file->move(public_path('images/products'), $filename);
            $imagePath = 'images/products/'.$filename;
        }

        Product::create([
            'category_id' => $validated['category_id'],
            'name'        => $validated['name'],
            'slug'        => null, // auto-generated in model
            'sku'         => $validated['sku'] ?? null,
            'price'       => $validated['price'],
            'stock'       => $validated['stock'] ?? 0,
            'description' => $validated['description'] ?? null,
            'image_path'  => $imagePath,
            'stock_bs_kg' => $validated['stock_bs_kg'] ?? 0,
            'stock_fs_kg' => $validated['stock_fs_kg'] ?? 0,
            'minimum_limit' => $validated['minimum_limit'],
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Show the form for editing the specified product.
     * 
     * @param  \App\Models\Product  $product
     * @return \Illuminate\View\View
     */
    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified product in storage.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id'   => ['required', 'exists:categories,id'],
            'name'          => ['required', 'string', 'min:3', 'max:255'],
            'price'         => ['required', 'numeric', 'min:0'],
            'description'   => ['nullable', 'string'],
            'sku'           => ['nullable', 'string', 'max:100'],
            'stock'         => ['nullable', 'integer', 'min:0'],
            'stock_bs_kg'   => ['nullable', 'numeric', 'min:0'],
            'stock_fs_kg'   => ['nullable', 'numeric', 'min:0'],
            'minimum_limit' => ['required', 'numeric', 'min:1'],
            'image'         => ['nullable', 'image', 'max:2048'],
        ]);

        $imagePath = $product->image_path;
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($imagePath && file_exists(public_path($imagePath))) {
                unlink(public_path($imagePath));
            }
            
            $file = $request->file('image');
            $filename = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
            $file->move(public_path('images/products'), $filename);
            $imagePath = 'images/products/'.$filename;
        }

        $product->update([
            'category_id' => $validated['category_id'],
            'name'        => $validated['name'],
            'sku'         => $validated['sku'] ?? null,
            'price'       => $validated['price'],
            'stock'       => $validated['stock'] ?? 0,
            'description' => $validated['description'] ?? null,
            'image_path'  => $imagePath,
            'stock_bs_kg' => $validated['stock_bs_kg'] ?? 0,
            'stock_fs_kg' => $validated['stock_fs_kg'] ?? 0,
            'minimum_limit' => $validated['minimum_limit'],
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product from storage.
     * 
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Product $product)
    {
        // Delete image if exists
        if ($product->image_path && file_exists(public_path($product->image_path))) {
            unlink(public_path($product->image_path));
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
