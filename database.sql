-- =====================================================
-- DATABASE: db_bantuan_sosial
-- Sistem Informasi Bantuan Sosial (SIBANTU)
-- =====================================================

CREATE DATABASE IF NOT EXISTS `db_bantuan_sosial`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `db_bantuan_sosial`;

-- =====================================================
-- TABEL: jenis_bantuan
-- =====================================================
CREATE TABLE IF NOT EXISTS `jenis_bantuan` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_jenis` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nama_jenis` (`nama_jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL: admin
-- =====================================================
CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABEL: pengajuan
-- =====================================================
CREATE TABLE IF NOT EXISTS `pengajuan` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_tiket` VARCHAR(20) NOT NULL,
  `nama_pemohon` VARCHAR(100) NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  `id_jenis` INT(11) NOT NULL,
  `alamat` VARCHAR(255) NOT NULL,
  `alasan` TEXT NOT NULL,
  `status` ENUM('Menunggu','Diverifikasi','Disalurkan') NOT NULL DEFAULT 'Menunggu',
  `tanggapan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_kode_tiket` (`kode_tiket`),
  KEY `idx_status` (`status`),
  KEY `idx_jenis` (`id_jenis`),
  CONSTRAINT `fk_pengajuan_jenis`
    FOREIGN KEY (`id_jenis`) REFERENCES `jenis_bantuan` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEED: jenis_bantuan
-- =====================================================
INSERT IGNORE INTO `jenis_bantuan` (`nama_jenis`) VALUES
('Bantuan Pangan / Sembako'),
('Bantuan Tunai Keluarga Prasejahtera'),
('Bantuan Lansia'),
('Bantuan Penyandang Disabilitas'),
('Bantuan Pendidikan Anak'),
('Bantuan Perbaikan Rumah Tidak Layak Huni');

-- =====================================================
-- SEED: admin
-- Password plain: admin123  (login.php mendukung plain & hash)
-- Untuk memakai hash, jalankan di PHP: echo password_hash('admin123', PASSWORD_DEFAULT);
-- lalu tempel hasilnya ke kolom password.
-- =====================================================
INSERT IGNORE INTO `admin` (`nama`, `username`, `password`) VALUES
('Administrator', 'admin', 'admin123'),
('Petugas Verifikasi', 'petugas', 'petugas123');

-- =====================================================
-- SEED: contoh pengajuan (opsional, untuk uji coba)
-- =====================================================
INSERT IGNORE INTO `pengajuan`
  (`kode_tiket`, `nama_pemohon`, `no_hp`, `id_jenis`, `alamat`, `alasan`, `status`, `tanggapan`)
VALUES
('BSO-A1B2C3', 'Budi Santoso', '081234567890', 1, 'Dusun Krajan RT 02/RW 01, Desa Sukamaju',
 'Kepala keluarga kehilangan pekerjaan sejak 3 bulan lalu, kebutuhan pangan untuk 4 anggota keluarga tidak tercukupi.',
 'Menunggu', NULL),

('BSO-D4E5F6', 'Siti Aminah', '081298765432', 3, 'Jl. Melati No. 7, Kelurahan Karanganyar',
 'Lansia berusia 72 tahun, hidup sendiri, tidak memiliki penghasilan tetap dan membutuhkan bantuan kebutuhan sehari-hari.',
 'Diverifikasi', 'Petugas telah melakukan kunjungan rumah. Berkas dinyatakan lengkap dan sedang menunggu jadwal penyaluran.'),

('BSO-G7H8I9', 'Ahmad Rizki', '081377788899', 5, 'Desa Wonolelo RT 04/RW 02',
 'Dua anak usia sekolah tidak mampu membeli perlengkapan sekolah dan membayar iuran bulanan.',
 'Disalurkan', 'Bantuan pendidikan telah disalurkan pada 12/11. Terima kasih atas pengajuannya.');

-- =====================================================
-- SELESAI
-- =====================================================
