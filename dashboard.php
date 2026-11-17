<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['login'])) {
    header('Location: login.php');
    exit;
}

$pesan = '';

// ============ TAMBAH LAPORAN MANUAL ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_manual'])) {
    $nama_pelapor = mysqli_real_escape_string($koneksi, trim($_POST['nama_pelapor'] ?? ''));
    $no_hp        = mysqli_real_escape_string($koneksi, trim($_POST['no_hp'] ?? ''));
    $id_kategori  = (int)($_POST['id_kategori'] ?? 0);
    $lokasi       = mysqli_real_escape_string($koneksi, trim($_POST['lokasi'] ?? ''));
    $isi_laporan  = mysqli_real_escape_string($koneksi, trim($_POST['isi_laporan'] ?? ''));

    if ($nama_pelapor && $no_hp && $id_kategori > 0 && $lokasi && $isi_laporan) {
        do {
            $kode_tiket = 'ADU-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
            $cek = mysqli_query($koneksi, "SELECT id FROM pengaduan WHERE kode_tiket = '$kode_tiket'");
        } while ($cek && mysqli_num_rows($cek) > 0);

        $sql = "INSERT INTO pengaduan (kode_tiket, nama_pelapor, no_hp, id_kategori, lokasi, isi_laporan, status, tanggapan)
                VALUES ('$kode_tiket', '$nama_pelapor', '$no_hp', $id_kategori, '$lokasi', '$isi_laporan', 'Menunggu', NULL)";
        if (mysqli_query($koneksi, $sql)) {
            $pesan = 'Laporan manual ditambahkan. Kode tiket: ' . $kode_tiket;
        } else {
            $pesan = 'Gagal menambah laporan: ' . mysqli_error($koneksi);
        }
    } else {
        $pesan = 'Lengkapi semua field laporan manual.';
    }
}

// ============ UPDATE STATUS & TANGGAPAN ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_laporan'])) {
    $id         = (int)($_POST['id'] ?? 0);
    $status     = mysqli_real_escape_string($koneksi, $_POST['status'] ?? 'Menunggu');
    $tanggapan  = mysqli_real_escape_string($koneksi, trim($_POST['tanggapan'] ?? ''));

    if (!in_array($status, ['Menunggu', 'Diproses', 'Selesai'])) {
        $status = 'Menunggu';
    }

    $sql = "UPDATE pengaduan SET status = '$status', tanggapan = " . ($tanggapan === '' ? "NULL" : "'$tanggapan'") . " WHERE id = $id";
    if (mysqli_query($koneksi, $sql)) {
        $pesan = 'Laporan berhasil diperbarui.';
    } else {
        $pesan = 'Gagal memperbarui: ' . mysqli_error($koneksi);
    }
}

// ============ HAPUS ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_laporan'])) {
    $id = (int)($_POST['id'] ?? 0);
    if (mysqli_query($koneksi, "DELETE FROM pengaduan WHERE id = $id")) {
        $pesan = 'Laporan berhasil dihapus.';
    } else {
        $pesan = 'Gagal menghapus: ' . mysqli_error($koneksi);
    }
}

// ============ STATISTIK ============
$stat = ['total' => 0, 'Menunggu' => 0, 'Diproses' => 0, 'Selesai' => 0];
$qs = mysqli_query($koneksi, "SELECT status, COUNT(*) AS jml FROM pengaduan GROUP BY status");
if ($qs) {
    while ($r = mysqli_fetch_assoc($qs)) {
        if (isset($stat[$r['status']])) $stat[$r['status']] = (int)$r['jml'];
        $stat['total'] += (int)$r['jml'];
    }
}

// Kategori untuk form manual
$kategori_list = [];
$qk = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
if ($qk) { while ($r = mysqli_fetch_assoc($qk)) $kategori_list[] = $r; }

// Daftar pengaduan
$daftar = [];
$qd = mysqli_query($koneksi, "SELECT p.*, k.nama_kategori
                              FROM pengaduan p
                              LEFT JOIN kategori k ON p.id_kategori = k.id
                              ORDER BY p.id DESC");
if ($qd) { while ($r = mysqli_fetch_assoc($qd)) $daftar[] = $r; }

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
<title>Dashboard Petugas — SIPADU</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans">

<!-- NAVBAR -->
<nav class="bg-blue-900 text-white sticky top-0 z-40">
  <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <div class="w-9 h-9 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center font-bold">SP</div>
      <div>
        <div class="font-bold leading-tight">SIPADU</div>
        <div class="text-[11px] text-blue-200 leading-tight">Dashboard Petugas</div>
      </div>
    </div>
    <div class="flex items-center gap-3 text-sm">
      <span class="hidden sm:inline text-blue-200">Halo, <?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin') ?></span>
      <a href="index.php" class="px-3 py-1.5 rounded-lg bg-white/10 border border-white/20 hover:bg-white/20">Beranda</a>
      <a href="logout.php" class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 font-semibold" onclick="return confirm('Yakin ingin logout?')">Logout</a>
    </div>
  </div>
</nav>

<main class="max-w-7xl mx-auto px-4 py-8 space-y-8">

  <?php if ($pesan): ?>
    <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3"><?= htmlspecialchars($pesan) ?></div>
  <?php endif; ?>

  <!-- STATISTIK -->
  <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
      <div class="text-sm text-slate-500">Total Laporan</div>
      <div class="text-3xl font-bold text-blue-900 mt-1"><?= $stat['total'] ?></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
      <div class="text-sm text-slate-500">Menunggu</div>
      <div class="text-3xl font-bold text-amber-600 mt-1"><?= $stat['Menunggu'] ?></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
      <div class="text-sm text-slate-500">Diproses</div>
      <div class="text-3xl font-bold text-blue-600 mt-1"><?= $stat['Diproses'] ?></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
      <div class="text-sm text-slate-500">Selesai</div>
      <div class="text-3xl font-bold text-emerald-600 mt-1"><?= $stat['Selesai'] ?></div>
    </div>
  </section>

  <!-- FORM TAMBAH MANUAL -->
  <section class="bg-white rounded-2xl border border-slate-200 p-6">
    <h2 class="text-lg font-bold text-blue-900 mb-1">Tambah Laporan Manual</h2>
    <p class="text-sm text-slate-500 mb-4">Untuk pengaduan yang diterima via telepon/loket.</p>
    <form method="POST" action="dashboard.php" class="grid md:grid-cols-2 gap-4">
      <input type="text" name="nama_pelapor" placeholder="Nama pelapor" required class="rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
      <input type="text" name="no_hp" placeholder="No. HP" required class="rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
      <select name="id_kategori" required class="rounded-lg border border-slate-300 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">— Pilih Kategori —</option>
        <?php foreach ($kategori_list as $kat): ?>
          <option value="<?= (int)$kat['id'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="lokasi" placeholder="Lokasi fasilitas" required class="rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
      <textarea name="isi_laporan" rows="2" placeholder="Isi laporan" required class="md:col-span-2 rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
      <div class="md:col-span-2">
        <button type="submit" name="tambah_manual" class="px-5 py-2.5 rounded-lg bg-blue-900 hover:bg-blue-800 text-white font-semibold transition">Tambah Laporan</button>
      </div>
    </form>
  </section>

  <!-- DAFTAR PENGADUAN -->
  <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200">
      <h2 class="text-lg font-bold text-blue-900">Daftar Pengaduan Masyarakat</h2>
      <p class="text-sm text-slate-500">Diurutkan dari yang terbaru.</p>
    </div>

    <?php if (empty($daftar)): ?>
      <div class="p-8 text-center text-slate-400">Belum ada pengaduan.</div>
    <?php else: ?>
      <div class="divide-y divide-slate-100">
        <?php foreach ($daftar as $d): ?>
          <div class="p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-mono font-bold text-blue-900"><?= htmlspecialchars($d['kode_tiket']) ?></span>
                  <span class="text-xs font-semibold px-2 py-0.5 rounded-full border <?= badge_status($d['status']) ?>"><?= htmlspecialchars($d['status']) ?></span>
                  <span class="text-xs text-slate-400"><?= htmlspecialchars($d['created_at'] ?? '') ?></span>
                </div>
                <div class="mt-2 text-sm text-slate-600">
                  <span class="font-medium text-slate-800"><?= htmlspecialchars($d['nama_pelapor']) ?></span>
                  &middot; <?= htmlspecialchars($d['no_hp']) ?>
                  &middot; <span class="text-blue-700"><?= htmlspecialchars($d['nama_kategori'] ?? '-') ?></span>
                  &middot; <?= htmlspecialchars($d['lokasi']) ?>
                </div>
                <div class="mt-2 text-sm bg-slate-50 rounded-lg p-3 whitespace-pre-line"><?= htmlspecialchars($d['isi_laporan']) ?></div>
              </div>
              <div class="flex gap-2">
                <a href="cetak.php?id=<?= (int)$d['id'] ?>" target="_blank" class="px-3 py-1.5 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">Cetak</a>
                <form method="POST" action="dashboard.php" onsubmit="return confirm('Yakin ingin menghapus laporan ini?')">
                  <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                  <button type="submit" name="hapus_laporan" class="px-3 py-1.5 text-sm rounded-lg border border-red-200 text-red-700 bg-red-50 hover:bg-red-100">Hapus</button>
                </form>
              </div>
            </div>

            <form method="POST" action="dashboard.php" class="mt-4 rounded-xl bg-slate-50 border border-slate-200 p-4">
              <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
              <div class="grid md:grid-cols-[180px_1fr] gap-3 items-start">
                <label class="text-sm font-medium text-slate-700 pt-2">Status</label>
                <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <?php foreach (['Menunggu', 'Diproses', 'Selesai'] as $opt): ?>
                    <option value="<?= htmlspecialchars($opt) ?>" <?= $d['status'] === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                  <?php endforeach; ?>
                </select>

                <label class="text-sm font-medium text-slate-700 pt-2">Tanggapan</label>
                <textarea name="tanggapan" rows="2" placeholder="Masukkan tanggapan petugas..." class="rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($d['tanggapan'] ?? '') ?></textarea>
              </div>
              <div class="mt-3 flex justify-end">
                <button type="submit" name="update_laporan" class="px-4 py-2 rounded-lg bg-blue-900 hover:bg-blue-800 text-white font-semibold transition">Simpan Perubahan</button>
              </div>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

</main>

<footer class="border-t border-slate-200 bg-white">
  <div class="max-w-7xl mx-auto px-4 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-slate-500">
    <span>&copy; <?= date('Y') ?> SIPADU — Dashboard Petugas</span>
    <a href="logout.php" class="hover:text-blue-900 underline">Logout</a>
  </div>
</footer>

</body>
</html> 