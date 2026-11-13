<?php
session_start();
require_once 'koneksi.php';

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['login'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        // Asumsi tabel admin: id, username, password (password hash) / nama
        $q = "SELECT * FROM admin WHERE username = '$username' LIMIT 1";
        $res = mysqli_query($koneksi, $q);

        if ($res && mysqli_num_rows($res) === 1) {
            $admin = mysqli_fetch_assoc($res);
            $valid = false;

            // Coba verifikasi hash, fallback plain text (untuk data seed sederhana)
            if (password_verify($password, $admin['password'])) {
                $valid = true;
            } elseif ($password === $admin['password']) {
                $valid = true;
            }

            if ($valid) {
                $_SESSION['login'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_nama'] = $admin['nama'] ?? $admin['username'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Password salah.';
            }
        } else {
            $error = 'Username tidak ditemukan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Petugas â€” SIPADU</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 to-blue-50 flex items-center justify-center p-4">

<div class="w-full max-w-md">
  <div class="text-center mb-6">
    <div class="inline-flex w-12 h-12 rounded-2xl bg-blue-900 text-white items-center justify-center font-bold text-lg">SP</div>
    <h1 class="mt-3 text-2xl font-bold text-blue-900">SIPADU</h1>
    <p class="text-sm text-slate-500">Login Petugas / Admin</p>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
    <?php if ($error): ?>
      <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Username</label>
        <input type="text" name="username" required autofocus
               class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
        <input type="password" name="password" required
               class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <button type="submit" class="w-full rounded-lg bg-blue-900 hover:bg-blue-800 text-white font-semibold py-2.5 transition">Masuk</button>
    </form>

    <div class="mt-5 text-center text-sm">
      <a href="index.php" class="text-slate-500 hover:text-blue-900">&larr; Kembali ke Beranda</a>
    </div>
  </div>
</div>

</body>
</html>