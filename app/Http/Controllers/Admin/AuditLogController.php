<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')
            ->orderBy('created_at', 'desc');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by model type
        if ($request->filled('model_type')) {
            $query->where('model_type', 'like', '%' . $request->model_type . '%');
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by description or IP address
        $search = $request->input('q', $request->input('search'));
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('route_name', 'like', "%{$search}%");
            });
        }

        $auditLogs = $query->paginate(10)->withQueryString();

        // Get filter options for dropdowns
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $actions = [
            AuditLog::ACTION_CREATE => 'Create',
            AuditLog::ACTION_UPDATE => 'Update',
            AuditLog::ACTION_DELETE => 'Delete',
            AuditLog::ACTION_VIEW => 'View',
            AuditLog::ACTION_LOGIN => 'Login',
            AuditLog::ACTION_LOGOUT => 'Logout',
        ];

        $categories = [
            AuditLog::CATEGORY_ORDER_MANAGEMENT => 'Order Management',
            AuditLog::CATEGORY_INVENTORY_MANAGEMENT => 'Inventory Management',
            AuditLog::CATEGORY_USER_MANAGEMENT => 'User Management',
            AuditLog::CATEGORY_PAYMENT_PROCESSING => 'Payment Processing',
            AuditLog::CATEGORY_SHIPPING_FULFILLMENT => 'Shipping & Fulfillment',
            AuditLog::CATEGORY_SYSTEM_CONFIGURATION => 'System Configuration',
            AuditLog::CATEGORY_DATA_EXPORT => 'Data Export',
            AuditLog::CATEGORY_AUTHENTICATION => 'Authentication',
        ];

        $modelTypes = [
            'Order' => 'Order',
            'Product' => 'Product',
            'Category' => 'Category',
            'User' => 'User',
            'Variety' => 'Variety',
            'SeedLot' => 'Seed Lot',
            'SeedClass' => 'Seed Class',
            'Commodity' => 'Commodity',
        ];

        return view('admin.audit-logs.index', compact(
            'auditLogs',
            'users',
            'actions',
            'categories',
            'modelTypes'
        ));
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');

        return view('admin.audit-logs.show', compact('auditLog'));
    }
}