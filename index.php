<?php
session_start();
require_once 'koneksi.php';

$notif_tiket = '';
$error_form  = '';

// ============ PROSES KIRIM PENGADUAN ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_pengaduan'])) {
    $nama_pelapor = trim($_POST['nama_pelapor'] ?? '');
    $no_hp        = trim($_POST['no_hp'] ?? '');
    $id_kategori  = (int)($_POST['id_kategori'] ?? 0);
    $lokasi       = trim($_POST['lokasi'] ?? '');
    $isi_laporan  = trim($_POST['isi_laporan'] ?? '');

    if ($nama_pelapor === '' || $no_hp === '' || $id_kategori <= 0 || $lokasi === '' || $isi_laporan === '') {
        $error_form = 'Semua field wajib diisi.';
    } else {
        $nama_pelapor = mysqli_real_escape_string($koneksi, $nama_pelapor);
        $no_hp        = mysqli_real_escape_string($koneksi, $no_hp);
        $lokasi       = mysqli_real_escape_string($koneksi, $lokasi);
        $isi_laporan  = mysqli_real_escape_string($koneksi, $isi_laporan);

        // Generate kode tiket unik ADU-XXXXXX
        do {
            $kode_tiket = 'ADU-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
            $cek = mysqli_query($koneksi, "SELECT id FROM pengaduan WHERE kode_tiket = '$kode_tiket'");
        } while ($cek && mysqli_num_rows($cek) > 0);

        $sql = "INSERT INTO pengaduan (kode_tiket, nama_pelapor, no_hp, id_kategori, lokasi, isi_laporan, status, tanggapan)
                VALUES ('$kode_tiket', '$nama_pelapor', '$no_hp', $id_kategori, '$lokasi', '$isi_laporan', 'Menunggu', NULL)";

        if (mysqli_query($koneksi, $sql)) {
            $notif_tiket = $kode_tiket;
            $_POST = [];
        } else {
            $error_form = 'Gagal menyimpan pengaduan: ' . mysqli_error($koneksi);
        }
    }
}

// ============ PROSES CEK STATUS TIKET (GET) ============
$hasil_cek = null;
$cek_error = '';
if (isset($_GET['kode_tiket']) && trim($_GET['kode_tiket']) !== '') {
    $kode_cek = mysqli_real_escape_string($koneksi, trim($_GET['kode_tiket']));
    $q = "SELECT p.*, k.nama_kategori
          FROM pengaduan p
          LEFT JOIN kategori k ON p.id_kategori = k.id
          WHERE p.kode_tiket = '$kode_cek' LIMIT 1";
    $res = mysqli_query($koneksi, $q);
    if ($res && mysqli_num_rows($res) > 0) {
        $hasil_cek = mysqli_fetch_assoc($res);
    } else {
        $cek_error = 'Kode tiket tidak ditemukan. Periksa kembali kode Anda.';
    }
}

// Ambil kategori untuk form
$kategori_list = [];
$qk = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
if ($qk) {
    while ($row = mysqli_fetch_assoc($qk)) {
        $kategori_list[] = $row;
    }
}

function badge_status($status) {
    if ($status === 'Menunggu') return 'bg-amber-100 text-amber-700 border-amber-200';
    if ($status === 'Diproses') return 'bg-blue-100 text-blue-700 border-blue-200';
    if ($status === 'Selesai')  return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    return 'bg-slate-100 text-slate-600 border-slate-200';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIPADU — Sistem Informasi Pengaduan Fasilitas Publik</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans">

<!-- NAVBAR -->
<nav class="bg-white border-b border-slate-200 sticky top-0 z-40">
  <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <div class="w-9 h-9 rounded-xl bg-blue-900 text-white flex items-center justify-center font-bold">SP</div>
      <div>
        <div class="font-bold text-blue-900 leading-tight">SIPADU</div>
        <div class="text-[11px] text-slate-500 leading-tight">Pengaduan Fasilitas Publik</div>
      </div>
    </div>
    <div class="flex items-center gap-4 text-sm">
      <a href="#form-pengaduan" class="hidden sm:inline text-slate-600 hover:text-blue-900">Kirim Pengaduan</a>
      <a href="#cek-status" class="hidden sm:inline text-slate-600 hover:text-blue-900">Cek Status</a>
      <a href="login.php" class="px-3 py-2 rounded-lg bg-blue-900 text-white hover:bg-blue-800 transition">Login Petugas</a>
    </div>
  </div>
</nav>

<!-- HERO -->
<header class="bg-gradient-to-br from-blue-950 via-blue-900 to-emerald-800 text-white">
  <div class="max-w-6xl mx-auto px-4 py-14">
    <span class="inline-block text-xs font-semibold bg-white/10 border border-white/20 px-3 py-1 rounded-full mb-4">Layanan Aspirasi Warga</span>
    <h1 class="text-3xl sm:text-4xl font-bold leading-tight max-w-2xl">Sampaikan Pengaduan Fasilitas Publik dengan Cepat & Transparan</h1>
    <p class="mt-4 text-blue-100 max-w-2xl">SIPADU membantu warga melaporkan kerusakan fasilitas umum dan memantau tindak lanjut secara real-time. Setiap laporan mendapat kode tiket unik.</p>
    <div class="mt-6 flex flex-wrap gap-3">
      <a href="#form-pengaduan" class="px-5 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 font-semibold transition">Buat Pengaduan</a>
      <a href="#cek-status" class="px-5 py-3 rounded-xl bg-white/10 border border-white/30 hover:bg-white/20 font-semibold transition">Cek Status Tiket</a>
    </div>
  </div>
</header>

<main class="max-w-6xl mx-auto px-4 py-10 space-y-10">

  <?php if ($notif_tiket): ?>
  <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
    <div class="font-semibold text-emerald-800">✅ Pengaduan berhasil dikirim!</div>
    <p class="text-sm text-emerald-700 mt-1">Simpan kode tiket berikut untuk memantau status pengaduan Anda:</p>
    <div class="mt-3 inline-block px-4 py-2 rounded-lg bg-white border border-emerald-300 font-mono text-lg font-bold text-emerald-800 tracking-wider"><?= htmlspecialchars($notif_tiket) ?></div>
  </div>
  <script>alert('Pengaduan berhasil dikirim!\n\nKode Tiket Anda: <?= htmlspecialchars($notif_tiket) ?>\n\nSimpan kode ini untuk cek status.');</script>
  <?php endif; ?>

  <div class="grid lg:grid-cols-2 gap-8">

    <!-- FORM PENGADUAN -->
    <section id="form-pengaduan" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
      <h2 class="text-xl font-bold text-blue-900">Kirim Pengaduan Warga</h2>
      <p class="text-sm text-slate-500 mt-1 mb-5">Lengkapi data berikut. Kode tiket akan diberikan otomatis.</p>

      <?php if ($error_form): ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3"><?= htmlspecialchars($error_form) ?></div>
      <?php endif; ?>

      <form method="POST" action="index.php#form-pengaduan" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Nama Pelapor</label>
          <input type="text" name="nama_pelapor" value="<?= htmlspecialchars($_POST['nama_pelapor'] ?? '') ?>" required
                 class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">No. HP</label>
          <input type="text" name="no_hp" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" required
                 class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Kategori</label>
          <select name="id_kategori" required class="w-full rounded-lg border border-slate-300 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">— Pilih Kategori —</option>
            <?php foreach ($kategori_list as $kat): ?>
              <option value="<?= (int)$kat['id'] ?>" <?= (isset($_POST['id_kategori']) && (int)$_POST['id_kategori'] === (int)$kat['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($kat['nama_kategori']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Lokasi Fasilitas</label>
          <input type="text" name="lokasi" value="<?= htmlspecialchars($_POST['lokasi'] ?? '') ?>" required placeholder="Contoh: Jl. Merdeka No. 10"
                 class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Isi Laporan</label>
          <textarea name="isi_laporan" rows="4" required placeholder="Jelaskan kondisi fasilitas yang dilaporkan..."
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($_POST['isi_laporan'] ?? '') ?></textarea>
        </div>
        <button type="submit" name="kirim_pengaduan" class="w-full rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-3 transition">
          Kirim Pengaduan
        </button>
      </form>
    </section>

    <!-- CEK STATUS -->
    <section id="cek-status" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 h-fit">
      <h2 class="text-xl font-bold text-blue-900">Cek Status Tiket</h2>
      <p class="text-sm text-slate-500 mt-1 mb-5">Masukkan kode tiket Anda (contoh: ADU-AB12CD).</p>

      <form method="GET" action="index.php#cek-status" class="flex gap-2">
        <input type="text" name="kode_tiket" value="<?= htmlspecialchars($_GET['kode_tiket'] ?? '') ?>" required placeholder="ADU-XXXXXX"
               class="flex-1 rounded-lg border border-slate-300 px-3 py-2 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-900 hover:bg-blue-800 text-white font-semibold transition">Cek</button>
      </form>

      <?php if ($cek_error): ?>
        <div class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3"><?= htmlspecialchars($cek_error) ?></div>
      <?php endif; ?>

      <?php if ($hasil_cek): ?>
        <div class="mt-5 border border-slate-200 rounded-xl overflow-hidden">
          <div class="bg-slate-50 px-4 py-3 flex items-center justify-between">
            <span class="font-mono font-bold text-blue-900"><?= htmlspecialchars($hasil_cek['kode_tiket']) ?></span>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full border <?= badge_status($hasil_cek['status']) ?>"><?= htmlspecialchars($hasil_cek['status']) ?></span>
          </div>
          <div class="p-4 space-y-3 text-sm">
            <div class="grid grid-cols-3 gap-2">
              <span class="text-slate-500">Nama</span><span class="col-span-2 font-medium"><?= htmlspecialchars($hasil_cek['nama_pelapor']) ?></span>
              <span class="text-slate-500">No. HP</span><span class="col-span-2 font-medium"><?= htmlspecialchars($hasil_cek['no_hp']) ?></span>
              <span class="text-slate-500">Kategori</span><span class="col-span-2 font-medium"><?= htmlspecialchars($hasil_cek['nama_kategori'] ?? '-') ?></span>
              <span class="text-slate-500">Lokasi</span><span class="col-span-2 font-medium"><?= htmlspecialchars($hasil_cek['lokasi']) ?></span>
              <span class="text-slate-500">Tanggal</span><span class="col-span-2 font-medium"><?= htmlspecialchars($hasil_cek['created_at'] ?? '-') ?></span>
            </div>
            <div>
              <div class="text-slate-500 mb-1">Isi Laporan</div>
              <div class="bg-slate-50 rounded-lg p-3 whitespace-pre-line"><?= htmlspecialchars($hasil_cek['isi_laporan']) ?></div>
            </div>
            <div>
              <div class="text-slate-500 mb-1">Tanggapan Petugas</div>
              <?php if (!empty($hasil_cek['tanggapan'])): ?>
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 whitespace-pre-line text-emerald-900"><?= htmlspecialchars($hasil_cek['tanggapan']) ?></div>
              <?php else: ?>
                <div class="bg-slate-50 rounded-lg p-3 text-slate-400 italic">Belum ada tanggapan.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </section>

  </div>
</main>

<footer class="border-t border-slate-200 bg-white">
  <div class="max-w-6xl mx-auto px-4 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-slate-500">
    <span>&copy; <?= date('Y') ?> SIPADU — Sistem Informasi Pengaduan Fasilitas Publik</span>
    <a href="login.php" class="hover:text-blue-900 underline">Login Petugas</a>
  </div>
</footer>

</body>
</html>