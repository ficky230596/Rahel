<!-- 🔥 Sidebar -->
<link rel="stylesheet" href="../assets/css/sidebar.css">

<!-- Tombol Hamburger (muncul di mobile) -->


<nav id="sidebar" class="sidebar">
    <!-- <div class="sidebar-header text-center mb-3">
        <img src="../assets/img/logo-damkar.png" alt="Logo Damkar" class="sidebar-logo">

        <hr class="divider">
    </div> -->
    <!-- <h4 style="text-align: center;">Damkar Scheduler</h4> -->
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="bi bi-house-door"></i> Dashboard</a></li>
        <li><a href="pegawai.php" class="<?= basename($_SERVER['PHP_SELF']) == 'pegawai.php' ? 'active' : '' ?>"><i class="bi bi-person-badge"></i> Pegawai</a></li>
        <li><a href="peleton.php" class="<?= basename($_SERVER['PHP_SELF']) == 'peleton.php' ? 'active' : '' ?>"><i class="bi bi-flag"></i> Peleton</a></li>
        <li><a href="regu.php" class="<?= basename($_SERVER['PHP_SELF']) == 'regu.php' ? 'active' : '' ?>"><i class="bi bi-people"></i> Regu</a></li>
        <li><a href="pos.php" class="<?= basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : '' ?>"><i class="bi bi-geo-alt"></i> Pos</a></li>
        <li><a href="jadwal.php" class="<?= basename($_SERVER['PHP_SELF']) == 'jadwal.php' ? 'active' : '' ?>"><i class="bi bi-calendar-week"></i> Jadwal (GA)</a></li>
        <li><a href="verifikasi.php" class="<?= basename($_SERVER['PHP_SELF']) == 'verifikasi.php' ? 'active' : '' ?>"><i class="bi bi-check2-circle"></i> Verifikasi Izin</a></li>
        <li><a href="laporan.php" class="<?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : '' ?>"><i class="bi bi-bar-chart-line"></i> Rekap</a></li>
        <li><a href="notification_settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'notification_settings.php' ? 'active' : '' ?>"><i class="bi bi-bell"></i> Notifikasi</a></li>
    </ul>

    <div class="sidebar-footer">
        <a href="../logout.php" class="logout-btn">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</nav>

<!-- Konten utama -->
<main class="main-content p-4">
    <!-- ===== JS Submenu Sederhana ===== -->
    <!-- ===== JS Submenu Sederhana dengan Ikon & Aktif ===== -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rekapLink = document.querySelector('.sidebar-menu a[href="laporan.php"]');
            if (!rekapLink) return;

            const currentPage = '<?= basename($_SERVER["PHP_SELF"]) ?>'; // Halaman saat ini

            // Buat container submenu
            const submenuContainer = document.createElement('div');
            submenuContainer.style.display = 'none';
            submenuContainer.style.marginLeft = '15px';
            submenuContainer.style.flexDirection = 'column';

            // Fungsi buat link submenu dengan ikon
            function createSubLink(href, text, iconClass) {
                const a = document.createElement('a');
                a.href = href;
                a.style.display = 'block';
                a.style.padding = '2px 0';
                a.innerHTML = `<i class="${iconClass}" style="margin-right:5px"></i> ${text}`;

                // Jika halaman ini sama dengan href, jadikan active
                if (currentPage === href) {
                    a.classList.add('active');
                    submenuContainer.style.display = 'block'; // tampilkan submenu otomatis
                    rekapLink.classList.add('active'); // biar link Rekap juga tetap aktif
                }
                return a;
            }

            // Buat tombol Rekap Sakit
            const btnSakit = createSubLink('rekap_sakit.php', 'Rekap Sakit', 'bi bi-heart-pulse');
            // Buat tombol Jadwal
            const btnJadwal = createSubLink('rekap_jadwal.php', 'Jadwal', 'bi bi-calendar-week');

            submenuContainer.appendChild(btnSakit);
            submenuContainer.appendChild(btnJadwal);

            // Masukkan setelah link Rekap
            rekapLink.parentNode.appendChild(submenuContainer);

            // Toggle submenu saat klik Rekap
            rekapLink.addEventListener('click', function(e) {
                e.preventDefault();
                submenuContainer.style.display = submenuContainer.style.display === 'none' ? 'block' : 'none';
            });
        });
    </script>