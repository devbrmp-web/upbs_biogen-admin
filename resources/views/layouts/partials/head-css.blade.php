@vite(['resources/scss/icons.scss','resources/scss/app.scss'])
@vite(['resources/js/config.js','resources/js/layout.js','resources/js/responsive-enhancements.js','resources/js/theme-improvements.js'])

{{-- Premium UI Libraries --}}
<link rel="stylesheet" href="https://unpkg.com/nprogress@0.2.0/nprogress.css" />
<style>
    #nprogress .bar { background: #10b981 !important; height: 3px !important; }
    #nprogress .spinner-icon { border-top-color: #10b981 !important; border-left-color: #10b981 !important; }

    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* Premium Select Styling for Admin */
    .form-select, select.form-control {
        appearance: none !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 0.75rem center !important;
        background-size: 1rem !important;
        padding-right: 2.5rem !important;
        border-radius: 0.5rem !important;
    }
    .form-select:focus, select.form-control:focus {
        border-color: #10b981 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
    }

    /* Admin Button Loading State */
    .btn-loading {
        position: relative;
        color: transparent !important;
        pointer-events: none;
    }
    .btn-loading::after {
        content: "";
        position: absolute;
        width: 1.25rem;
        height: 1.25rem;
        top: 50%;
        left: 50%;
        margin: -0.625rem 0 0 -0.625rem;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 0.6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>
