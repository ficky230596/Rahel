<?php
session_start(); // Tambahkan ini di paling atas untuk mengaktifkan session
include "config/db.php"; // File ini membuat koneksi $pdo

$error = null; // Inisialisasi variabel error

if (isset($_SESSION['error_message'])) {
  $error = $_SESSION['error_message'];
  unset($_SESSION['error_message']); // Hapus pesan error dari session setelah diambil
}

if (isset($_POST['login'])) {
  $username = $_POST['username'] ?? ''; // Ambil username, gunakan string kosong jika tidak ada
  $password = $_POST['password'] ?? ''; // Ambil password, gunakan string kosong jika tidak ada

  // Pastikan username dan password tidak kosong
  if (empty($username) || empty($password)) {
    // Ini adalah case jika form dikirim kosong (jarang terjadi karena ada 'required')
    $_SESSION['error_message'] = "Username dan password harus diisi!";
    header("Location: index.php"); // Redirect ke halaman login itu sendiri
    exit;
  }

  $stmt = $pdo->prepare("SELECT id, password_hash, role FROM pegawai WHERE username = ?");
  $stmt->execute([$username]);
  $data = $stmt->fetch();

  if ($data) {
    if (password_verify($password, $data['password_hash'])) {
      // LOGIN BERHASIL
      $_SESSION['user_id'] = $data['id'];
      $_SESSION['role']    = $data['role'];

      // LAKUKAN REDIRECT SETELAH BERHASIL LOGIN
      if ($data['role'] === 'admin') {
        header("Location: admin/dashboard.php");
      } else {
        header("Location: petugas/dashboard.php");
      }
      exit; // PENTING: selalu gunakan exit setelah header()
    } else {
      // PASSWORD SALAH
      $_SESSION['error_message'] = "Password salah!";
    }
  } else {
    // USERNAME TIDAK DITEMUKAN
    $_SESSION['error_message'] = "Username tidak ditemukan!";
  }

  // LOGIN GAGAL: LAKUKAN REDIRECT PRG
  header("Location: index.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Login - Damkar Scheduler</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');

    :root {
      --bg1: #0f1724;
      --bg2: #081226;
      --accent1: #ff5f6d;
      --accent2: #ffc371;
      --glass: rgba(255, 255, 255, 0.06);
      --muted: rgba(255, 255, 255, 0.7);
    }

    * {
      box-sizing: border-box
    }

    html,
    body {
      height: 100%
    }

    body {
      margin: 0;
      font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: radial-gradient(1200px 600px at 10% 10%, rgba(79, 70, 229, 0.12), transparent 8%),
        radial-gradient(900px 500px at 90% 90%, rgba(99, 102, 241, 0.08), transparent 10%),
        linear-gradient(180deg, var(--bg1), var(--bg2));
      display: flex;
      align-items: center;
      justify-content: center;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      padding: 40px;
      color: var(--muted);
    }

    /* Decorative glow shapes */
    body::before,
    body::after {
      content: "";
      position: fixed;
      z-index: 0;
      filter: blur(60px);
      opacity: 0.6;
      pointer-events: none;
    }

    body::before {
      width: 420px;
      height: 420px;
      left: -80px;
      top: -60px;
      background: linear-gradient(45deg, var(--accent1), transparent 40%);
      transform: rotate(20deg);
    }

    body::after {
      width: 360px;
      height: 360px;
      right: -60px;
      bottom: -40px;
      background: linear-gradient(135deg, transparent 20%, var(--accent2));
      transform: rotate(-10deg);
    }

    /* Card */
    .login-card {
      position: relative;
      z-index: 1;
      width: 380px;
      max-width: calc(100% - 48px);
      padding: 28px;
      border-radius: 16px;
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.02));
      box-shadow:
        0 8px 30px rgba(2, 6, 23, 0.6),
        inset 0 1px 0 rgba(255, 255, 255, 0.03);
      backdrop-filter: blur(8px) saturate(120%);
      border: 1px solid rgba(255, 255, 255, 0.04);
      color: white;
    }

    /* Heading */
    .login-card h4 {
      margin: 0 0 18px 0;
      font-weight: 700;
      font-size: 1.05rem;
      letter-spacing: 0.4px;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Form controls */
    .form-label {
      color: rgba(255, 255, 255, 0.75);
      font-size: 0.88rem;
    }

    .form-control {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.06);
      color: #fff;
      padding: 10px 12px;
      height: 44px;
      border-radius: 10px;
      transition: all .18s ease;
      box-shadow: none;
    }

    .form-control::placeholder {
      color: rgba(255, 255, 255, 0.35);
    }

    .form-control:focus {
      outline: none;
      border-color: rgba(255, 255, 255, 0.14);
      box-shadow: 0 6px 18px rgba(99, 102, 241, 0.12), 0 2px 6px rgba(15, 23, 42, 0.6);
      transform: translateY(-1px);
    }

    /* Button (override bootstrap .btn-danger visual) */
    .btn-danger {
      display: inline-block;
      width: 100%;
      padding: 10px 14px;
      height: 46px;
      border-radius: 12px;
      border: none;
      background: linear-gradient(90deg, var(--accent1), var(--accent2));
      color: #09101a;
      font-weight: 700;
      letter-spacing: 0.4px;
      box-shadow: 0 8px 20px rgba(255, 95, 109, 0.14), 0 2px 6px rgba(0, 0, 0, 0.25);
      transition: transform .12s ease, box-shadow .12s ease, filter .12s ease;
    }

    .btn-danger:hover {
      transform: translateY(-3px);
      filter: brightness(1.02);
      box-shadow: 0 14px 30px rgba(255, 95, 109, 0.18);
    }

    .btn-danger:active {
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.28);
    }

    /* Spacing tweaks */
    .mb-3 {
      margin-bottom: 14px;
    }

    /* Small helper text */
    .small-muted {
      font-size: 0.82rem;
      color: rgba(255, 255, 255, 0.55);
      margin-top: 10px;
      text-align: center;
    }

    /* Responsive */
    @media (max-width:420px) {
      .login-card {
        padding: 20px;
        border-radius: 12px;
        width: 100%;
      }

      .btn-danger {
        height: 44px;
        border-radius: 10px;
      }
    }
  </style>
</head>

<body>
  <div class="login-card">
    <div class="text-center mb-3">
      <img src="assets/img/logo.png" alt="Logo" style="width:80px; height:auto;">
    </div>
    <h4 class="text-center">Login Pegawai</h4>
    <?php if (isset($error)): ?>
      <script>
        Swal.fire({
          icon: 'error',
          title: 'Login gagal',
          text: '<?= $error ?>'
        });
      </script>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input id="username" type="text" name="username" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input id="password" type="password" name="password" class="form-control" required>
      </div>
      <button name="login" class="btn btn-danger w-100">Login</button>
    </form>
  </div>
</body>

</html>