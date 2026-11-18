<?php
session_start();
require_once 'koneksi.php';

// Halaman cetak hanya dapat diakses oleh petugas yang sudah login.
if (!isset($_SESSION['login'])) {
    header('Location: login.php');
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID pengajuan tidak valid.');
}

$stmt = mysqli_prepare(
    $koneksi,
    'SELECT p.*, k.nama_jenis FROM pengajuan p
     LEFT JOIN jenis_bantuan k ON p.id_jenis = k.id
     WHERE p.id = ? LIMIT 1'
);

if (!$stmt) {
    http_response_code(500);
    exit('Gagal menyiapkan data pengajuan.');
}

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$data) {
    http_response_code(404);
    exit('Pengajuan tidak ditemukan.');
}

function e($value): string {
    return htmlspecialchars((string)($value ?? '-'), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cetak Pengajuan <?= e($data['kode_tiket']) ?> - SIBANTU</title>
<style>
    * { box-sizing: border-box; }
    body { margin: 0; padding: 32px; color: #1e293b; font-family: Arial, sans-serif; background: #f1f5f9; }
    .sheet { max-width: 760px; margin: 0 auto; padding: 36px; background: #fff; border: 1px solid #e2e8f0; }
    h1 { margin: 0; color: #115e59; font-size: 24px; }
    .subtitle { margin: 6px 0 24px; color: #64748b; font-size: 13px; }
    .ticket { padding: 14px 16px; margin-bottom: 22px; border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc; }
    .ticket strong { display: block; margin-bottom: 5px; color: #115e59; font-size: 18px; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 10px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; font-size: 14px; }
    td:first-child { width: 180px; color: #64748b; }
    .section { margin-top: 22px; }
    .section h2 { margin: 0 0 8px; color: #115e59; font-size: 15px; }
    .content { padding: 12px; border: 1px solid #e2e8f0; border-radius: 7px; white-space: pre-wrap; line-height: 1.5; font-size: 14px; }
    .actions { max-width: 760px; margin: 16px auto 0; display: flex; gap: 8px; }
    button, a { padding: 10px 14px; border: 0; border-radius: 6px; color: #fff; background: #115e59; text-decoration: none; cursor: pointer; font-size: 14px; }
    a { background: #475569; }
    @media print {
        body { padding: 0; background: #fff; }
        .sheet { max-width: none; border: 0; padding: 0; }
        .actions { display: none; }
    }
</style>
</head>
<body>
<main class="sheet">
    <h1>SIBANTU</h1>
    <p class="subtitle">Sistem Informasi Bantuan Sosial</p>

    <div class="ticket">
        <strong><?= e($data['kode_tiket']) ?></strong>
        Status pengajuan: <?= e($data['status']) ?>
    </div>

    <table>
        <tr><td>Nama Pemohon</td><td><?= e($data['nama_pemohon']) ?></td></tr>
        <tr><td>Nomor HP</td><td><?= e($data['no_hp']) ?></td></tr>
        <tr><td>Jenis Bantuan</td><td><?= e($data['nama_jenis']) ?></td></tr>
        <tr><td>Alamat</td><td><?= e($data['alamat']) ?></td></tr>
        <tr><td>Tanggal Pengajuan</td><td><?= e($data['created_at']) ?></td></tr>
    </table>

    <section class="section">
        <h2>Alasan Pengajuan</h2>
        <div class="content"><?= e($data['alasan']) ?></div>
    </section>

    <section class="section">
        <h2>Tanggapan Petugas</h2>
        <div class="content"><?= e($data['tanggapan'] ?: 'Belum ada tanggapan.') ?></div>
    </section>
</main>
<div class="actions">
    <button type="button" onclick="window.print()">Cetak Pengajuan</button>
    <a href="dashboard.php">Kembali ke Dashboard</a>
</div>
</body>
</html>
