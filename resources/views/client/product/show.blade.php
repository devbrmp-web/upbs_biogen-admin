@extends('layouts.vertical')

@section('title', $variety->name)

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <h4 class="page-title">{{ $variety->name }}</h4>
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="#!">Catalog</a></li>
            <li class="breadcrumb-item active">{{ $variety->name }}</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-6 mb-3">
      <div class="card">
        <div class="card-body">
          <div id="mainImageContainer" class="text-center">
            @php
              $first = $images->first();
              $initialUrl = $first ? $first['url'] : null;
            @endphp
            @if($initialUrl)
              <img id="mainImage" src="{{ $initialUrl }}" alt="{{ $variety->name }}" class="img-fluid rounded" style="width:100%;max-height:420px;object-fit:contain;"/>
            @else
              <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height:320px;">
                <i class="bx bx-image fs-1 text-muted"></i>
              </div>
            @endif
          </div>
          @if($images->count() > 1)
          <div class="mt-3">
            <div id="thumbStrip" class="d-flex gap-2 overflow-auto" style="scroll-snap-type:x mandatory;">
              @foreach($images as $img)
                <button type="button"
                        class="btn p-0 border-0 position-relative"
                        data-url="{{ $img['url'] }}"
                        aria-label="Thumbnail"
                        style="scroll-snap-align:start;">
                  <img src="{{ $img['url'] }}" alt="thumb" class="rounded"
                       style="width:72px;height:72px;object-fit:cover;border:2px solid {{ $img['is_primary'] ? '#0d6efd' : '#dee2e6' }};" />
                </button>
              @endforeach
            </div>
          </div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-body">
          <h5 class="mb-2">{{ $variety->commodity->name ?? 'Commodity' }}</h5>
          <h3 class="mb-2">{{ $variety->name }}</h3>
          <div class="mb-3">
            <span class="text-muted">SKU:</span>
            <code>{{ $variety->sku }}</code>
          </div>
          <div class="mb-3">
            <h5 class="mb-1">Price</h5>
            <h4>Rp {{ number_format((int) $variety->price, 0, ',', '.') }}</h4>
          </div>
          @if($variety->description)
          <div class="mb-3">
            <h5 class="mb-1">Description</h5>
            <div class="text-muted">{!! nl2br(e($variety->description)) !!}</div>
          </div>
          @endif
          <div class="d-flex gap-2">
            <a href="#!" class="btn btn-outline-primary">View Cart</a>
            <a href="#!" class="btn btn-success">Checkout</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const mainImage = document.getElementById('mainImage');
  const thumbStrip = document.getElementById('thumbStrip');

  if (thumbStrip && mainImage) {
    thumbStrip.addEventListener('click', function(e){
      const btn = e.target.closest('button[data-url]');
      if (!btn) return;
      const url = btn.getAttribute('data-url');
      if (url) {
        mainImage.src = url;
        Array.from(thumbStrip.querySelectorAll('img')).forEach(img => {
          img.style.borderColor = '#dee2e6';
        });
        const imgEl = btn.querySelector('img');
        if (imgEl) imgEl.style.borderColor = '#0d6efd';
      }
    });
  }

  let startX = 0;
  let deltaX = 0;
  if (mainImage) {
    mainImage.addEventListener('touchstart', function(e){
      if (!e.touches || !e.touches[0]) return;
      startX = e.touches[0].clientX;
      deltaX = 0;
    }, {passive:true});
    mainImage.addEventListener('touchmove', function(e){
      if (!e.touches || !e.touches[0]) return;
      deltaX = e.touches[0].clientX - startX;
    }, {passive:true});
    mainImage.addEventListener('touchend', function(){
      const imgs = Array.from(thumbStrip ? thumbStrip.querySelectorAll('button[data-url] img') : []);
      const urls = imgs.map(img => img.parentElement.getAttribute('data-url'));
      const currentIndex = urls.indexOf(mainImage.src);
      if (Math.abs(deltaX) > 50) {
        let nextIndex = currentIndex;
        if (deltaX < 0) nextIndex = Math.min(urls.length - 1, currentIndex + 1);
        else nextIndex = Math.max(0, currentIndex - 1);
        const nextUrl = urls[nextIndex];
        if (nextUrl) {
          mainImage.src = nextUrl;
          imgs.forEach(img => { img.style.borderColor = '#dee2e6'; });
          const activeBtn = thumbStrip.querySelector(`button[data-url="${nextUrl}"]`);
          if (activeBtn) {
            const activeImg = activeBtn.querySelector('img');
            if (activeImg) activeImg.style.borderColor = '#0d6efd';
          }
        }
      }
      startX = 0;
      deltaX = 0;
    }, {passive:true});
  }
});
</script>
@endpush
