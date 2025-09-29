<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category; // add model import

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $categories = Category::latest('updated_at')->paginate(10);
        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        abort(404);
    }

    /**
     * Store a newly created category in storage.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified category.
     * 
     * @param  \App\Models\Category  $category
     * @return \Illuminate\View\View
     */
    public function edit()
    {
        abort(404);
    }

    /**
     * Update the specified category in storage.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update()
    {
        abort(404);
    }

    /**
     * Remove the specified category from storage.
     * 
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy()
    {
        abort(404);
    }
}
