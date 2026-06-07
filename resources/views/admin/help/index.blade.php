@extends('layouts.vertical', ['title' => $title, 'subTitle' => $subTitle])

@section('content')
<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-help-circle me-2"></i>Admin Panel Help Guide
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="accordion" id="helpAccordion">
                            
                            <!-- Order Management -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="orderManagementHeading">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#orderManagement" aria-expanded="true" aria-controls="orderManagement">
                                        <i class="bx bx-shopping-bag me-2"></i>Order Management
                                    </button>
                                </h2>
                                <div id="orderManagement" class="accordion-collapse collapse show" aria-labelledby="orderManagementHeading" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        <h6>Managing Orders</h6>
                                        <p>The Order Management section allows you to view, track, and manage all customer orders.</p>
                                        <ul>
                                            <li><strong>View Orders:</strong> Access all orders from the Orders menu in the sidebar</li>
                                            <li><strong>Filter Orders:</strong> Use the status filters to view orders by their current status</li>
                                            <li><strong>Search Orders:</strong> Use the search box to find specific orders by order code or customer details</li>
                                            <li><strong>Export Orders:</strong> Use the export button to download order data as CSV</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Processing -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="orderProcessingHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#orderProcessing" aria-expanded="false" aria-controls="orderProcessing">
                                        <i class="bx bx-cog me-2"></i>Order Processing Workflow
                                    </button>
                                </h2>
                                <div id="orderProcessing" class="accordion-collapse collapse" aria-labelledby="orderProcessingHeading" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        <h6>Complete Order Processing Flow</h6>
                                        <div class="timeline">
                                            <div class="timeline-item">
                                                <span class="badge bg-info">1</span>
                                                <strong>Awaiting Payment:</strong> Customer places order, waiting for payment confirmation
                                            </div>
                                            <div class="timeline-item mt-3">
                                                <span class="badge bg-success">2</span>
                                                <strong>Paid:</strong> Admin manually confirms after verifying uploaded transfer receipt
                                            </div>
                                            <div class="timeline-item mt-3">
                                                <span class="badge bg-warning">3</span>
                                                <strong>Processing:</strong> Order is being prepared for shipment
                                            </div>
                                            <div class="timeline-item mt-3">
                                                <span class="badge bg-primary">4</span>
                                                <strong>Shipped:</strong> Order has been dispatched to customer
                                            </div>
                                            <div class="timeline-item mt-3">
                                                <span class="badge bg-success">5</span>
                                                <strong>Completed:</strong> Order successfully delivered to customer
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Shipping Status -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="shippingStatusHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#shippingStatus" aria-expanded="false" aria-controls="shippingStatus">
                                        <i class="bx bx-package me-2"></i>Shipping Status & Updates
                                    </button>
                                </h2>
                                <div id="shippingStatus" class="accordion-collapse collapse" aria-labelledby="shippingStatusHeading" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        <h6>Managing Shipping Information</h6>
                                        <p>Update shipping details and track order delivery status:</p>
                                        <ul>
                                            <li><strong>Update Status:</strong> Click the "Actions" button on any order to update its status</li>
                                            <li><strong>Add Tracking Number:</strong> When shipping, add the courier tracking number</li>
                                            <li><strong>Select Courier:</strong> Choose between Pos Indonesia (max 10kg) or Indah Cargo</li>
                                            <li><strong>Add Notes:</strong> Include any relevant notes when updating status</li>
                                        </ul>
                                        
                                        <div class="alert alert-info">
                                            <i class="bx bx-info-circle me-2"></i>
                                            <strong>Note:</strong> Only orders with "Paid" status can be moved to "Processing" and then "Shipped".
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Audit History -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="auditHistoryHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#auditHistory" aria-expanded="false" aria-controls="auditHistory">
                                        <i class="bx bx-history me-2"></i>Audit History
                                    </button>
                                </h2>
                                <div id="auditHistory" class="accordion-collapse collapse" aria-labelledby="auditHistoryHeading" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        <h6>Viewing System Audit Logs</h6>
                                        <p>The audit log tracks all important system activities:</p>
                                        <ul>
                                            <li><strong>User Actions:</strong> Login, logout, and user management activities</li>
                                            <li><strong>Order Changes:</strong> Status updates, modifications, and deletions</li>
                                            <li><strong>Product Management:</strong> Product and category changes</li>
                                            <li><strong>System Events:</strong> Payment confirmations and shipping updates</li>
                                        </ul>
                                        
                                        <p>Access audit logs from the main dashboard or through the system menu.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Shipping Methods -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="shippingMethodsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#shippingMethods" aria-expanded="false" aria-controls="shippingMethods">
                                        <i class="bx bx-truck me-2"></i>Shipping Methods & Payment
                                    </button>
                                </h2>
                                <div id="shippingMethods" class="accordion-collapse collapse" aria-labelledby="shippingMethodsHeading" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        <h6>Available Shipping Options</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card border">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Pos Indonesia</h6>
                                                        <ul class="list-unstyled">
                                                            <li><i class="bx bx-check text-success"></i> Maximum weight: 10kg</li>
                                                            <li><i class="bx bx-check text-success"></i> Nationwide coverage</li>
                                                            <li><i class="bx bx-check text-success"></i> Tracking available</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card border">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Indah Cargo</h6>
                                                        <ul class="list-unstyled">
                                                            <li><i class="bx bx-check text-success"></i> No weight limit</li>
                                                            <li><i class="bx bx-check text-success"></i> Bulk shipments</li>
                                                            <li><i class="bx bx-check text-success"></i> Competitive rates</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <h6 class="mt-4">Payment Processing</h6>
                                        <p>All payments are handled through <strong>manual bank transfer verification</strong>. Customers transfer directly to official BRMP Biogen bank accounts and upload proof of payment through the client portal.</p>

                                        <div class="row mt-3">
                                            <div class="col-md-6 mb-3">
                                                <div class="card border border-success-subtle">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-success"><i class="bx bx-bank me-2"></i>Bank BRI</h6>
                                                        <ul class="list-unstyled mb-0">
                                                            <li><i class="bx bx-check text-success"></i> No. Rekening: <strong>0123-01-012345-56-7</strong></li>
                                                            <li><i class="bx bx-check text-success"></i> A.n.: <strong>Bendahara Pengeluaran BRMP Biogen</strong></li>
                                                            <li><i class="bx bx-info-circle text-muted"></i> Nominal transfer harus sesuai total pesanan</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="card border border-primary-subtle">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-primary"><i class="bx bx-bank me-2"></i>Bank Mandiri</h6>
                                                        <ul class="list-unstyled mb-0">
                                                            <li><i class="bx bx-check text-success"></i> No. Rekening: <strong>123-00-1234567-8</strong></li>
                                                            <li><i class="bx bx-check text-success"></i> A.n.: <strong>Bendahara Pengeluaran BRMP Biogen</strong></li>
                                                            <li><i class="bx bx-info-circle text-muted"></i> Sertakan kode pesanan pada keterangan transfer</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <h6 class="mt-2">Admin Verification Workflow</h6>
                                        <ul>
                                            <li><strong>Step 1 — Receive Proof:</strong> Customer uploads transfer receipt (bukti transfer) via the order detail page on the client portal.</li>
                                            <li><strong>Step 2 — Cross-Check:</strong> Admin verifies the uploaded image against the bank's actual transaction record (e-banking or SMS notification).</li>
                                            <li><strong>Step 3 — Confirm Payment:</strong> If the amount and order code match, admin manually changes order status from <code>Awaiting Payment</code> → <code>Paid</code> via the order Actions menu.</li>
                                            <li><strong>Step 4 — Release Invoice:</strong> Upon marking as <code>Paid</code>, the system automatically generates and makes the digital invoice/receipt available to the customer.</li>
                                        </ul>

                                        <div class="alert alert-info">
                                            <i class="bx bx-info-circle me-2"></i>
                                            <strong>Note:</strong> Payment status is updated <strong>manually by the admin</strong> after verifying the uploaded transfer receipt. Do not mark an order as Paid without first confirming the transfer against bank records.
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-zap me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="d-grid">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-warning">
                                <i class="bx bx-chart me-2"></i>Dashboard
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-grid">
                            <a href="{{ route('admin.commodities.index') }}" class="btn btn-outline-info">
                                <i class="bx bx-category me-2"></i>Categories
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-grid">
                            <a href="{{ route('admin.varieties.index') }}" class="btn btn-outline-success">
                                <i class="bx bx-package me-2"></i>Manage Products
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-grid">
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-primary">
                                <i class="bx bx-shopping-bag me-2"></i>View Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.timeline-item {
    display: flex;
    align-items: center;
    gap: 15px;
}
.timeline-item .badge {
    min-width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}
</style>
@endpush
@endsection