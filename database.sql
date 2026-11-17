-- =====================================================
-- DATABASE: db_pengaduan_publik
-- Sistem Informasi Pengaduan Fasilitas Publik (SIPADU)
-- =====================================================

CREATE DATABASE IF NOT EXISTS `db_pengaduan_publik`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `db_pengaduan_publik`;

-- =====================================================
-- TABEL: kategori
-- =====================================================
CREATE TABLE IF NOT EXISTS `kategori` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nama_kategori` (`nama_kategori`)
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
-- TABEL: pengaduan
-- =====================================================
CREATE TABLE IF NOT EXISTS `pengaduan` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_tiket` VARCHAR(20) NOT NULL,
  `nama_pelapor` VARCHAR(100) NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  `id_kategori` INT(11) NOT NULL,
  `lokasi` VARCHAR(255) NOT NULL,
  `isi_laporan` TEXT NOT NULL,
  `status` ENUM('Menunggu','Diproses','Selesai') NOT NULL DEFAULT 'Menunggu',
  `tanggapan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_kode_tiket` (`kode_tiket`),
  KEY `idx_status` (`status`),
  KEY `idx_kategori` (`id_kategori`),
  CONSTRAINT `fk_pengaduan_kategori`
    FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEED: kategori
-- =====================================================
INSERT INTO `kategori` (`nama_kategori`) VALUES
('Jalan & Trotoar'),
('Penerangan Jalan'),
('Saluran Air / Drainase'),
('Sampah & Kebersihan'),
('Taman & Ruang Publik'),
('Fasilitas Umum Lainnya');

-- =====================================================
-- SEED: admin
-- Password plain: admin123  (login.php mendukung plain & hash)
-- Password hash alternatif (bcrypt admin123):
--   $2y$10$e0MYzXyjpJS7Pd0RVvHwHe1H6J9c1gH8rB9f1PZ1k1Q1r1Z1u1z1u
-- =====================================================
INSERT INTO `admin` (`nama`, `username`, `password`) VALUES
('Administrator', 'admin', 'admin123'),
('Petugas Lapangan', 'petugas', 'petugas123');

-- =====================================================
-- SEED: contoh pengaduan (opsional, untuk uji coba)
-- =====================================================
INSERT INTO `pengaduan`
  (`kode_tiket`, `nama_pelapor`, `no_hp`, `id_kategori`, `lokasi`, `isi_laporan`, `status`, `tanggapan`)
VALUES
('ADU-A1B2C3', 'Budi Santoso', '081234567890', 1, 'Jl. Merdeka No. 10',
 'Lubang besar di trotoar depan pasar, membahayakan pejalan kaki.',
 'Menunggu', NULL),

('ADU-D4E5F6', 'Siti Aminah', '081298765432', 2, 'Jl. Sudirman KM 3',
 'Lampu jalan mati sejak seminggu, jalan jadi gelap di malam hari.',
 'Diproses', 'Petugas sudah mengecek lokasi, penggantian lampu dijadwalkan minggu ini.'),

('ADU-G7H8I9', 'Ahmad Rizki', '081377788899', 4, 'Kelurahan Sukamaju',
 'Tempat sampah penuh dan berbau, belum diangkut 3 hari.',
 'Selesai', 'Sampah sudah diangkut pada 12/11. Terima kasih atas laporannya.');

-- =====================================================
-- SELESAI
-- =====================================================