<!-- bundle -->
@yield('script')
<!-- App js -->
@yield('script-bottom')

{{-- Premium UI Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/nprogress@0.2.0/nprogress.js"></script>

<script>
    // Initialize NProgress
    NProgress.configure({ showSpinner: false, trickleSpeed: 200 });
    window.addEventListener('beforeunload', function() {
        NProgress.start();
    });
    document.addEventListener('DOMContentLoaded', function() {
        NProgress.done();
    });

    // Global Alert Helper for Admin
    window.showAlert = function(title, text, icon = 'success') {
        return Swal.fire({
            title: title,
            text: text,
            icon: icon,
            confirmButtonColor: '#10b981',
            customClass: { popup: 'rounded-lg' }
        });
    };

    // Global Confirm Helper
    window.confirmAction = function(title, text, icon = 'warning') {
        return Swal.fire({
            title: title,
            html: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: { 
                popup: 'rounded-xl',
                confirmButton: 'rounded-lg px-4 py-2',
                cancelButton: 'rounded-lg px-4 py-2'
            }
        });
    };

    // Auto-replace native confirm for forms
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.dataset.confirmed) return;
        
        // Check for onsubmit confirm
        let confirmMsg = form.getAttribute('onsubmit')?.match(/confirm\(['"](.+?)['"]\)/)?.[1];
        
        // Also check for data-confirm attribute
        if (!confirmMsg && form.dataset.confirm) {
            confirmMsg = form.dataset.confirm;
        }

        if (confirmMsg) {
            e.preventDefault();
            window.confirmAction('Confirmation Required', confirmMsg).then((result) => {
                if (result.isConfirmed) {
                    form.dataset.confirmed = "true";
                    form.submit();
                }
            });
        }
    });

    // Global Form Loading State for Admin
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.classList.contains('no-loader')) {
                btn.classList.add('btn-loading');
            }
        });
    });

    // EMERGENCY FIX: Cleanup lingering backdrops on page load
    (function() {
        const cleanup = () => {
            // The "Sakti" Line: Force remove all backdrops and reset overflow
            document.querySelectorAll('.modal-backdrop, .offcanvas-backdrop, .mobile-backdrop').forEach(el => el.remove()); 
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open', 'sidebar-enable');
            document.body.style.paddingRight = '';
            
            console.log('Global backdrop security executed.');
        };
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', cleanup);
        } else {
            cleanup();
        }
        // Also run after a small delay to catch any late-initialized modals
        setTimeout(cleanup, 500);
        setTimeout(cleanup, 1500);
    })();
</script>
