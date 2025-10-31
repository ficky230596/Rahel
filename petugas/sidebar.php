<!-- Tombol hamburger (muncul di mobile/tablet) -->
<style>
    /* === SIDEBAR RESPONSIF === */
    .sidebar {
        width: 220px;
        min-height: 100vh;
        position: fixed;
        top: 64px;
        left: 0;
        transition: transform 0.3s ease-in-out;
        z-index: 1040;
        text-decoration: none;
    }

    .main-content {
        margin-left: 220px;
        transition: margin-left 0.3s ease-in-out;
        padding: 20px;
    }

    /* MOBILE & TABLET (sidebar tersembunyi) */
    @media (max-width: 991px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0 !important;
        }
    }

    /* Tombol hamburger */
    #sidebarToggle {
        background-color: #b30000;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 22px;
        padding: 6px 12px;
        transition: all 0.3s ease;
    }

    #sidebarToggle:hover {
        background-color: #dc2626;
        transform: scale(1.1);
    }

    #sidebarToggle:focus {
        outline: none;
        box-shadow: 0 0 6px rgba(255, 0, 0, 0.6);
    }

    /* Saat aktif (sidebar terbuka) */
    #sidebarToggle.active {
        background-color: #dc2626;
        transform: rotate(90deg);
    }

    /* Navigasi di sidebar */
    .sidebar .nav-link {
        color: #e5e7eb;
        border-radius: 6px;
        padding: 8px 12px;
        transition: all 0.2s ease;
    }

    .sidebar .nav-link:hover {
        background-color: #b30000;
        color: #fff;
        text-decoration: none;
    }

    /* Link aktif */
    .sidebar .nav-link.active {
        background-color: #dc2626;
        font-weight: bold;
        color: #fff;
    }
</style>

<!-- Tombol Hamburger -->
<button id="sidebarToggle" class="btn btn-danger d-lg-none"
    style="position: fixed; top: 12px; left: 12px; z-index: 1050;">
    ☰
</button>

<!-- SIDEBAR -->
<aside id="sidebar" class="sidebar bg-dark text-white p-3">
    <h6 class="" style="text-align: center;">Menu Pegawai</h6>
    <ul class="nav flex-column">
        <li class="nav-item mb-2">
            <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="izin.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'izin.php' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice"></i> Izin
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="jadwal.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'jadwal.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> Jadwal Piket
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="profil.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'profil.php' ? 'active' : '' ?>">
                <i class="fas fa-user-circle"></i> Profil
            </a>
        </li>
    </ul>
</aside>

<!-- Konten utama -->
<div id="mainContent" class="main-content">

    <script>
        // Tombol hamburger aktif/tidak
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('shifted');
            this.classList.toggle('active');

            // Ganti ikon tombol ☰ ↔ ✕
            this.textContent = sidebar.classList.contains('active') ? '✕' : '☰';
        });
    </script>