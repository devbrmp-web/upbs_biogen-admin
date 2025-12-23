@extends('layouts.vertical')

@section('title', 'Cari Dokumen Kerjasama')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Dokumen Kerjasama</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Cari Dokumen via Kode Pesanan</h4>
                    
                    <form action="{{ route('admin.documents.search') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="order_code" class="form-label">Kode Pesanan</label>
                            <input type="text" class="form-control" id="order_code" name="order_code" placeholder="WUB-2025..." required>
                            @error('order_code')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bx bx-search"></i> Cari Dokumen</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
