<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['login'])) {
    header('Location: login.php');
    exit;
}

$pesan = '';

// ============ TAMBAH PENGAJUAN MANUAL ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_manual'])) {
    $nama_pemohon = mysqli_real_escape_string($koneksi, trim($_POST['nama_pemohon'] ?? ''));
    $no_hp        = mysqli_real_escape_string($koneksi, trim($_POST['no_hp'] ?? ''));
    $id_jenis  = (int)($_POST['id_jenis'] ?? 0);
    $alamat       = mysqli_real_escape_string($koneksi, trim($_POST['alamat'] ?? ''));
    $alasan  = mysqli_real_escape_string($koneksi, trim($_POST['alasan'] ?? ''));

    if ($nama_pemohon && $no_hp && $id_jenis > 0 && $alamat && $alasan) {
        do {
            $kode_tiket = 'BSO-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
            $cek = mysqli_query($koneksi, "SELECT id FROM pengajuan WHERE kode_tiket = '$kode_tiket'");
        } while ($cek && mysqli_num_rows($cek) > 0);

        $sql = "INSERT INTO pengajuan (kode_tiket, nama_pemohon, no_hp, id_jenis, alamat, alasan, status, tanggapan)
                VALUES ('$kode_tiket', '$nama_pemohon', '$no_hp', $id_jenis, '$alamat', '$alasan', 'Menunggu', NULL)";
        if (mysqli_query($koneksi, $sql)) {
            $pesan = 'Pengajuan manual ditambahkan. Kode tiket: ' . $kode_tiket;
        } else {
            $pesan = 'Gagal menambah pengajuan: ' . mysqli_error($koneksi);
        }
    } else {
        $pesan = 'Lengkapi semua field pengajuan manual.';
    }
}

// ============ UPDATE STATUS & TANGGAPAN ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pengajuan'])) {
    $id         = (int)($_POST['id'] ?? 0);
    $status     = mysqli_real_escape_string($koneksi, $_POST['status'] ?? 'Menunggu');
    $tanggapan  = mysqli_real_escape_string($koneksi, trim($_POST['tanggapan'] ?? ''));

    if (!in_array($status, ['Menunggu', 'Diverifikasi', 'Disalurkan'])) {
        $status = 'Menunggu';
    }

    $sql = "UPDATE pengajuan SET status = '$status', tanggapan = " . ($tanggapan === '' ? "NULL" : "'$tanggapan'") . " WHERE id = $id";
    if (mysqli_query($koneksi, $sql)) {
        $pesan = 'Pengajuan berhasil diperbarui.';
    } else {
        $pesan = 'Gagal memperbarui: ' . mysqli_error($koneksi);
    }
}

// ============ HAPUS ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pengajuan'])) {
    $id = (int)($_POST['id'] ?? 0);
    if (mysqli_query($koneksi, "DELETE FROM pengajuan WHERE id = $id")) {
        $pesan = 'Pengajuan berhasil dihapus.';
    } else {
        $pesan = 'Gagal menghapus: ' . mysqli_error($koneksi);
    }
}

// ============ STATISTIK ============
$stat = ['total' => 0, 'Menunggu' => 0, 'Diverifikasi' => 0, 'Disalurkan' => 0];
$qs = mysqli_query($koneksi, "SELECT status, COUNT(*) AS jml FROM pengajuan GROUP BY status");
if ($qs) {
    while ($r = mysqli_fetch_assoc($qs)) {
        if (isset($stat[$r['status']])) $stat[$r['status']] = (int)$r['jml'];
        $stat['total'] += (int)$r['jml'];
    }
}

// Jenis bantuan untuk form manual
$jenis_list = [];
$qk = mysqli_query($koneksi, "SELECT * FROM jenis_bantuan ORDER BY nama_jenis ASC");
if ($qk) { while ($r = mysqli_fetch_assoc($qk)) $jenis_list[] = $r; }

// Daftar pengajuan
$daftar = [];
$qd = mysqli_query($koneksi, "SELECT p.*, k.nama_jenis
                              FROM pengajuan p
                              LEFT JOIN jenis_bantuan k ON p.id_jenis = k.id
                              ORDER BY p.id DESC");
if ($qd) { while ($r = mysqli_fetch_assoc($qd)) $daftar[] = $r; }

function badge_status($status) {
    if ($status === 'Menunggu') return 'bg-amber-100 text-amber-700 border-amber-200';
    if ($status === 'Diverifikasi') return 'bg-blue-100 text-blue-700 border-blue-200';
    if ($status === 'Disalurkan')  return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    return 'bg-slate-100 text-slate-600 border-slate-200';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Petugas — SIBANTU</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans">

<!-- NAVBAR -->
<nav class="bg-teal-900 text-white sticky top-0 z-40">
  <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <div class="w-9 h-9 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center font-bold">SB</div>
      <div>
        <div class="font-bold leading-tight">SIBANTU</div>
        <div class="text-[11px] text-teal-200 leading-tight">Dashboard Petugas</div>
      </div>
    </div>
    <div class="flex items-center gap-3 text-sm">
      <span class="hidden sm:inline text-teal-200">Halo, <?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin') ?></span>
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
      <div class="text-sm text-slate-500">Total Pengajuan</div>
      <div class="text-3xl font-bold text-teal-900 mt-1"><?= $stat['total'] ?></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
      <div class="text-sm text-slate-500">Menunggu</div>
      <div class="text-3xl font-bold text-amber-600 mt-1"><?= $stat['Menunggu'] ?></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
      <div class="text-sm text-slate-500">Diverifikasi</div>
      <div class="text-3xl font-bold text-blue-600 mt-1"><?= $stat['Diverifikasi'] ?></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
      <div class="text-sm text-slate-500">Disalurkan</div>
      <div class="text-3xl font-bold text-emerald-600 mt-1"><?= $stat['Disalurkan'] ?></div>
    </div>
  </section>

  <!-- FORM TAMBAH MANUAL -->
  <section class="bg-white rounded-2xl border border-slate-200 p-6">
    <h2 class="text-lg font-bold text-teal-900 mb-1">Tambah Pengajuan Manual</h2>
    <p class="text-sm text-slate-500 mb-4">Untuk pengajuan yang diterima via telepon, loket, atau kunjungan ke desa/kelurahan.</p>
    <form method="POST" action="dashboard.php" class="grid md:grid-cols-2 gap-4">
      <input type="text" name="nama_pemohon" placeholder="Nama pemohon" required class="rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
      <input type="text" name="no_hp" placeholder="No. HP" required class="rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
      <select name="id_jenis" required class="rounded-lg border border-slate-300 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
        <option value="">— Pilih Jenis Bantuan —</option>
        <?php foreach ($jenis_list as $jns): ?>
          <option value="<?= (int)$jns['id'] ?>"><?= htmlspecialchars($jns['nama_jenis']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="alamat" placeholder="Alamat domisili" required class="rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
      <textarea name="alasan" rows="2" placeholder="Alasan / kondisi pemohon" required class="md:col-span-2 rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
      <div class="md:col-span-2">
        <button type="submit" name="tambah_manual" class="px-5 py-2.5 rounded-lg bg-teal-900 hover:bg-teal-800 text-white font-semibold transition">Tambah Pengajuan</button>
      </div>
    </form>
  </section>

  <!-- DAFTAR PENGAJUAN -->
  <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200">
      <h2 class="text-lg font-bold text-teal-900">Daftar Pengajuan Bantuan Sosial</h2>
      <p class="text-sm text-slate-500">Diurutkan dari yang terbaru.</p>
    </div>

    <?php if (empty($daftar)): ?>
      <div class="p-8 text-center text-slate-400">Belum ada pengajuan bantuan.</div>
    <?php else: ?>
      <div class="divide-y divide-slate-100">
        <?php foreach ($daftar as $d): ?>
          <div class="p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-mono font-bold text-teal-900"><?= htmlspecialchars($d['kode_tiket']) ?></span>
                  <span class="text-xs font-semibold px-2 py-0.5 rounded-full border <?= badge_status($d['status']) ?>"><?= htmlspecialchars($d['status']) ?></span>
                  <span class="text-xs text-slate-400"><?= htmlspecialchars($d['created_at'] ?? '') ?></span>
                </div>
                <div class="mt-2 text-sm text-slate-600">
                  <span class="font-medium text-slate-800"><?= htmlspecialchars($d['nama_pemohon']) ?></span>
                  &middot; <?= htmlspecialchars($d['no_hp']) ?>
                  &middot; <span class="text-teal-700"><?= htmlspecialchars($d['nama_jenis'] ?? '-') ?></span>
                  &middot; <?= htmlspecialchars($d['alamat']) ?>
                </div>
                <div class="mt-2 text-sm bg-slate-50 rounded-lg p-3 whitespace-pre-line"><?= htmlspecialchars($d['alasan']) ?></div>
              </div>
              <div class="flex gap-2">
                <a href="cetak.php?id=<?= (int)$d['id'] ?>" target="_blank" class="px-3 py-1.5 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">Cetak</a>
                <form method="POST" action="dashboard.php" onsubmit="return confirm('Yakin ingin menghapus pengajuan ini?')">
                  <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                  <button type="submit" name="hapus_pengajuan" class="px-3 py-1.5 text-sm rounded-lg border border-red-200 text-red-700 bg-red-50 hover:bg-red-100">Hapus</button>
                </form>
              </div>
            </div>

            <form method="POST" action="dashboard.php" class="mt-4 rounded-xl bg-slate-50 border border-slate-200 p-4">
              <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
              <div class="grid md:grid-cols-[180px_1fr] gap-3 items-start">
                <label class="text-sm font-medium text-slate-700 pt-2">Status</label>
                <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                  <?php foreach (['Menunggu', 'Diverifikasi', 'Disalurkan'] as $opt): ?>
                    <option value="<?= htmlspecialchars($opt) ?>" <?= $d['status'] === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                  <?php endforeach; ?>
                </select>

                <label class="text-sm font-medium text-slate-700 pt-2">Tanggapan</label>
                <textarea name="tanggapan" rows="2" placeholder="Masukkan hasil verifikasi / tanggapan petugas..." class="rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500"><?= htmlspecialchars($d['tanggapan'] ?? '') ?></textarea>
              </div>
              <div class="mt-3 flex justify-end">
                <button type="submit" name="update_pengajuan" class="px-4 py-2 rounded-lg bg-teal-900 hover:bg-teal-800 text-white font-semibold transition">Simpan Perubahan</button>
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
    <span>&copy; <?= date('Y') ?> SIBANTU — Dashboard Petugas</span>
    <a href="logout.php" class="hover:text-teal-900 underline">Logout</a>
  </div>
</footer>

</body>
</html> 