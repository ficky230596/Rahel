<style>
  /* Warna khas Damkar */
  :root {
    --damkar-red: #d62828;
    /* Merah Utama */
    --damkar-dark-red: #a31c1c;
    /* Merah Tua untuk hover/active */
    --text-light: #f8f9fa;
    /* Teks Putih */
    --text-dark: #212529;
    /* Teks Gelap */
  }

  /* 1. Styling Navbar Utama */
  .navbar {
    background-color: var(--damkar-red) !important;
    /* Timpa warna bg-primary Bootstrap */
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    /* Memberi kedalaman */
    transition: background-color 0.3s ease;
  }

  /* 2. Styling Brand (Nama Aplikasi) */
  .navbar-brand {
    font-weight: 700;
    letter-spacing: 1px;
    font-size: 1.4rem;
    color: var(--text-light) !important;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
  }

  /* 3. Styling Link Navigasi */
  .navbar-nav .nav-link {
    color: var(--text-light) !important;
    padding-left: 1rem;
    padding-right: 1rem;
    border-radius: 5px;
    /* Sedikit lengkungan */
    transition: background-color 0.3s ease, color 0.3s ease;
  }

  /* 4. Efek Hover dan Active */
  .navbar-nav .nav-link:hover,
  .navbar-nav .nav-item.active .nav-link {
    /* Asumsi .active untuk halaman saat ini */
    background-color: var(--damkar-dark-red);
    /* Warna merah lebih gelap saat disentuh */
    color: #fff !important;
    /* Putih bersih */
    transform: translateY(-1px);
    /* Efek angkat sedikit */
  }

  /* 5. Styling Link Logout (Membuatnya lebih menonjol) */
  /* Kita buat link logout dengan background kontras */
  .navbar-nav:last-child .nav-link {
    border: 1px solid var(--text-light);
    margin-left: 15px;
    padding: 6px 15px;
    border-radius: 5px;
  }

  .navbar-nav:last-child .nav-link:hover {
    background-color: var(--text-light);
    /* Background putih */
    color: var(--damkar-red) !important;
    /* Teks merah kontras */
    border-color: var(--text-light);
  }

  /* 6. Tambahan: Efek Separator */
  /* Untuk memisahkan visual antara menu utama dan Logout */
  .navbar-collapse {
    border-left: 1px solid rgba(255, 255, 255, 0.2);
    padding-left: 15px;
  }

  /* Jika Anda ingin menghilangkan separator, hapus blok di atas */
</style>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">Damkar Scheduler</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="pegawai.php">Pegawai</a></li>
        <li class="nav-item"><a class="nav-link" href="peleton.php">Peleton</a></li>
        <li class="nav-item"><a class="nav-link" href="regu.php">Regu</a></li>
        <li class="nav-item"><a class="nav-link" href="pos.php">Pos</a></li>
        <li class="nav-item"><a class="nav-link" href="jadwal.php">Jadwal (GA)</a></li>
        <li class="nav-item"><a class="nav-link" href="verifikasi.php">Verifikasi Izin</a></li>
        <li class="nav-item"><a class="nav-link" href="laporan.php">Rekap</a></li>
        <li class="nav-item"><a class="nav-link" href="notification_settings.php">Pengaturan Notifikasi</a></li>
      </ul>
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="../index.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>