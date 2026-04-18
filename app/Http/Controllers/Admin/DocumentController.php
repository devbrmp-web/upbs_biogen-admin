<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index()
    {
        return view('admin.documents.index');
    }

    public function search(Request $request)
    {
        $request->validate([
            'order_code' => 'required|string|exists:orders,order_code',
        ]);

        // Redirect to client app
        return redirect()->away(rtrim(config('app.client_url'), '/') . '/pesanan/signature/' . $request->order_code);
    }
}
