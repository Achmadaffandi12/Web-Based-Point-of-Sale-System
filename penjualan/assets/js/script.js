document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }

    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    if (passwordInput && togglePassword) {
        togglePassword.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            this.innerHTML = isPassword ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
        });
    }

    if (typeof $ !== 'undefined' && $.fn && $.fn.DataTable) {
        $('.datatable').DataTable({
            responsive: true,
            paging: true,
            searching: true,
            ordering: true,
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            language: {
                search: 'Cari:',
                lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                paginate: {
                    previous: 'Sebelumnya',
                    next: 'Berikutnya'
                }
            }
        });
    }

    const alertButtons = document.querySelectorAll('[data-confirm]');
    alertButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const url = button.getAttribute('href');
            Swal.fire({
                title: 'Konfirmasi',
                text: button.dataset.confirm || 'Apakah Anda yakin?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, lanjutkan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed && url) {
                    window.location.href = url;
                }
            });
        });
    });

    window.addEventListener('click', function (event) {
        if (window.innerWidth < 992 && sidebar && !sidebar.contains(event.target) && !sidebarToggle?.contains(event.target)) {
            sidebar.classList.remove('show');
        }
    });
});
