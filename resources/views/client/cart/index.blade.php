@extends('layouts.vertical')

@section('title', 'Cart')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <h4 class="page-title">Cart</h4>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <p class="text-muted">Cart feature placeholder.</p>
      <a href="{{ route('client.catalog') }}" class="btn btn-primary">Browse Catalog</a>
    </div>
  </div>
</div>
@endsection

