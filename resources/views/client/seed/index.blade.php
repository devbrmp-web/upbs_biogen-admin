@extends('layouts.vertical')

@section('title', 'Catalog - Seed Classes & Lots')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Catalog</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('client.catalog') }}">Catalog</a></li>
                        <li class="breadcrumb-item active">Seed Info</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h4 class="card-title mb-0">Seed Classes</h4></div>
                <div class="card-body">
                    <div id="seed-classes" class="list-group"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h4 class="card-title mb-0">Seed Lots</h4></div>
                <div class="card-body">
                    <div id="seed-lots" class="list-group"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const classesEl = document.getElementById('seed-classes');
  const lotsEl = document.getElementById('seed-lots');

  fetch('/api/seed-classes')
    .then(r => r.json())
    .then(({ data }) => {
      classesEl.innerHTML = '';
      data.forEach(c => {
        const item = document.createElement('a');
        item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
        item.href = '#';
        item.innerHTML = `<span><strong>${c.code}</strong> — ${c.name}</span><span class="badge bg-${c.is_active ? 'success' : 'secondary'}">${c.is_active ? 'Active' : 'Inactive'}</span>`;
        classesEl.appendChild(item);
      });
    });

  fetch('/api/seed-lots')
    .then(r => r.json())
    .then(({ data }) => {
      lotsEl.innerHTML = '';
      data.forEach(sl => {
        const item = document.createElement('div');
        item.className = 'list-group-item';
        const cls = sl.seed_class?.code ?? '-';
        const vname = sl.variety?.name ?? '-';
        item.innerHTML = `<div class="d-flex justify-content-between"><div><strong>${sl.lot_code}</strong> — ${vname} <span class="badge bg-info ms-2">${cls}</span></div><div>Qty: ${sl.quantity} ${sl.unit}</div></div><div class="text-muted small">Price: ${sl.price_idr} per ${sl.unit} • Year: ${sl.production_year}</div>`;
        lotsEl.appendChild(item);
      });
    });
});
</script>
@endpush

