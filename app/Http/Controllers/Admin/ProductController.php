<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $products = Product::with('category')->latest('updated_at')->paginate(10);
        return view('apps.ecommerce.products', compact('products'));
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

    // Disabled methods to enforce read-only
    public function create() { abort(404); }
    public function store() { abort(404); }
    public function edit() { abort(404); }
    public function update() { abort(404); }
    public function destroy() { abort(404); }
}
