<?php
// Set zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Impor namespace PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// URL of the Flask API endpoint
$api_url = 'http://localhost:5000/pembayaran';

// Initialize cURL
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Execute cURL request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Decode JSON response
$data = json_decode($response, true);
$error = null;
if ($http_code !== 200 || json_last_error() !== JSON_ERROR_NONE) {
    $error = 'Gagal mengambil data pembayaran: ' . ($http_code ? "HTTP $http_code" : 'Koneksi gagal');
}

// Fungsi untuk memperbarui data pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = intval($_POST['edit_id']);
    $data_update = [
        'nama' => $_POST['nama'],
        'jumlah_jiwa' => intval($_POST['jumlah_jiwa']),
        'jenis_zakat' => $_POST['jenis_zakat'],
        'metode_pembayaran' => $_POST['metode_pembayaran'],
        'total_bayar' => floatval($_POST['total_bayar']),
        'nominal_dibayar' => floatval($_POST['nominal_dibayar']),
        'kembalian' => floatval($_POST['kembalian']),
        'keterangan' => $_POST['keterangan'],
        'tanggal_bayar' => $_POST['tanggal_bayar']
    ];

    $api_url_put = "http://localhost:5000/pembayaran/$id";
    $ch = curl_init($api_url_put);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_update));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $success = "Data pembayaran berhasil diperbarui.";
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response, true);
    } else {
        $error = "Gagal memperbarui data pembayaran: HTTP $http_code";
    }
}

// Fungsi untuk menghapus data pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $api_url_delete = "http://localhost:5000/pembayaran/$id";
    $ch = curl_init($api_url_delete);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $success = "Data pembayaran berhasil dihapus.";
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response, true);
    } else {
        $error = "Gagal menghapus data pembayaran: HTTP $http_code";
    }
}

// Fungsi untuk generate Excel
if (isset($_GET['generate_excel']) && !$error && !empty($data)) {
    require 'vendor/autoload.php';

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Jumlah Jiwa');
    $sheet->setCellValue('C1', 'Jenis Zakat');
    $sheet->setCellValue('D1', 'Nama');
    $sheet->setCellValue('E1', 'Metode Pembayaran');
    $sheet->setCellValue('F1', 'Total Bayar');
    $sheet->setCellValue('G1', 'Nominal Dibayar');
    $sheet->setCellValue('H1', 'Kembalian');
    $sheet->setCellValue('I1', 'Keterangan');
    $sheet->setCellValue('J1', 'Tanggal Bayar');

    // Data
    $row = 2;
    foreach ($data as $record) {
        $sheet->setCellValue('A' . $row, $record['id']);
        $sheet->setCellValue('B' . $row, $record['jumlah_jiwa']);
        $sheet->setCellValue('C' . $row, $record['jenis_zakat']);
        $sheet->setCellValue('D' . $row, $record['nama']);
        $sheet->setCellValue('E' . $row, $record['metode_pembayaran']);
        $sheet->setCellValue('F' . $row, $record['total_bayar']);
        $sheet->setCellValue('G' . $row, $record['nominal_dibayar']);
        $sheet->setCellValue('H' . $row, $record['kembalian']);
        $sheet->setCellValue('I' . $row, $record['keterangan']);
        $sheet->setCellValue('J' . $row, $record['tanggal_bayar']);
        $row++;
    }

    // Styling
    $sheet->getStyle('A1:J1')->getFont()->setBold(true);
    $sheet->getStyle('A1:J' . ($row-1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    // Unduh file
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="pembayaran_zakat_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pembayaran Zakat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .slide-in {
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
        .slide-in-from {
            opacity: 0;
            transform: translateX(-20px);
        }
        .slide-in-to {
            opacity: 1;
            transform: translateX(0);
        }
        .table-row-hover {
            transition: background-color 0.2s ease, transform 0.2s ease;
        }
        .table-row-hover:hover {
            background-color: #f3fafd;
            transform: scale(1.01);
        }
        .button-hover {
            transition: all 0.3s ease;
        }
        .button-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .tooltip {
            visibility: hidden;
            opacity: 0;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .group:hover .tooltip {
            visibility: visible;
            opacity: 1;
        }
        .modal-content {
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        .modal-open {
            transform: scale(1);
            opacity: 1;
        }
        .modal-closed {
            transform: scale(0.95);
            opacity: 0;
        }
        .input-focus {
            transition: all 0.2s ease;
        }
        .input-focus:focus {
            border-color: #1e40af;
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.2);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-7xl bg-white rounded-2xl shadow-2xl overflow-hidden slide-in slide-in-from">
        <!-- Header -->
        <header class="bg-gradient-to-r from-blue-900 to-indigo-800 text-white p-6">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl md:text-3xl font-bold flex items-center">
                    <i class="fas fa-history mr-3 text-yellow-400"></i>Riwayat Pembayaran Zakat
                </h1>
                <a href="dashboard.php" class="text-blue-200 hover:text-white font-medium flex items-center transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dashboard
                </a>
            </div>
            <p class="text-blue-200 text-sm mt-2 text-center">Kelola riwayat transaksi zakat dengan mudah dan aman</p>
        </header>

        <!-- Main Content -->
        <main class="p-6 md:p-8">
            <!-- Messages -->
            <?php if ($error): ?>
                <div class="bg-red-50 text-red-700 border border-red-200 rounded-lg p-4 mb-6 animate-pulse flex items-center slide-in slide-in-from">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-4 mb-6 animate-pulse flex items-center slide-in slide-in-from">
                    <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="flex justify-end mb-6 space-x-4">
                <a href="?generate_excel=1" class="group button-hover bg-yellow-600 text-white px-5 py-2 rounded-lg flex items-center relative">
                    <i class="fas fa-file-excel mr-2"></i>Ekspor Excel
                    <span class="tooltip absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded shadow">Unduh data sebagai Excel</span>
                </a>
            </div>

            <!-- Table -->
            <?php if ($error): ?>
                <div class="text-center text-red-600 py-6 slide-in slide-in-from">Tidak dapat memuat data.</div>
            <?php elseif (empty($data)): ?>
                <div class="text-center text-gray-500 py-6 flex items-center justify-center slide-in slide-in-from">
                    <i class="fas fa-info-circle mr-2 text-lg"></i>Tidak ada data pembayaran ditemukan.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto bg-white rounded-lg shadow-md border border-blue-100">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-blue-50 text-gray-700">
                            <tr>
                                <th class="px-6 py-4 font-semibold text-xs md:text-sm">ID</th>
                                <th class="px-6 py-4 font-semibold text-xs md:text-sm">Jumlah Jiwa</th>
                                <th class="px-6 py-4 font-semibold text-xs md:text-sm">Jenis Zakat</th>
                                <th class="px-6 py-4 font-semibold text-xs md:text-sm">Nama</th>
                                <th class="px-6 py-4 font-semibold text-xs md:text-sm">Metode Pembayaran</th>
                                <th class="px-6 py-4 font-semibold text-right text-xs md:text-sm">Total Bayar (Rp)</th>
                                <th class="px-6 py-4 font-semibold text-right text-xs md:text-sm">Nominal Dibayar (Rp)</th>
                                <th class="px-6 py-4 font-semibold text-right text-xs md:text-sm">Kembalian (Rp)</th>
                                <th class="px-6 py-4 font-semibold text-xs md:text-sm">Keterangan</th>
                                <th class="px-6 py-4 font-semibold text-xs md:text-sm">Tanggal Bayar</th>
                                <th class="px-6 py-4 font-semibold text-xs md:text-sm">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-blue-100">
                            <?php foreach ($data as $record): ?>
                                <tr class="table-row-hover">
                                    <td class="px-6 py-4 text-gray-800 text-xs md:text-sm"><?= htmlspecialchars($record['id']) ?></td>
                                    <td class="px-6 py-4 text-gray-800 text-xs md:text-sm"><?= htmlspecialchars($record['jumlah_jiwa']) ?></td>
                                    <td class="px-6 py-4 text-gray-800 text-xs md:text-sm"><?= htmlspecialchars($record['jenis_zakat']) ?></td>
                                    <td class="px-6 py-4 text-gray-800 text-xs md:text-sm"><?= htmlspecialchars($record['nama']) ?></td>
                                    <td class="px-6 py-4 text-gray-800 text-xs md:text-sm"><?= htmlspecialchars($record['metode_pembayaran']) ?></td>
                                    <td class="px-6 py-4 text-gray-800 text-right text-xs md:text-sm"><?= number_format($record['total_bayar'], 2, ',', '.') ?></td>
                                    <td class="px-6 py-4 text-gray-800 text-right text-xs md:text-sm"><?= number_format($record['nominal_dibayar'], 2, ',', '.') ?></td>
                                    <td class="px-6 py-4 text-gray-800 text-right text-xs md:text-sm"><?= number_format($record['kembalian'], 2, ',', '.') ?></td>
                                    <td class="px-6 py-4 text-gray-800 text-xs md:text-sm"><?= htmlspecialchars($record['keterangan']) ?></td>
                                    <td class="px-6 py-4 text-gray-800 text-xs md:text-sm"><?= date('d M Y, H:i', strtotime($record['tanggal_bayar'])) ?></td>
                                    <td class="px-6 py-4 flex items-center space-x-3">
                                        <button onclick="openEditModal(<?= $record['id'] ?>, '<?= htmlspecialchars(addslashes($record['nama'])) ?>', <?= $record['jumlah_jiwa'] ?>, '<?= htmlspecialchars(addslashes($record['jenis_zakat'])) ?>', '<?= htmlspecialchars(addslashes($record['metode_pembayaran'])) ?>', <?= $record['total_bayar'] ?>, <?= $record['nominal_dibayar'] ?>, <?= $record['kembalian'] ?>, '<?= htmlspecialchars(addslashes($record['keterangan'])) ?>', '<?= $record['tanggal_bayar'] ?>')" class="group button-hover bg-blue-600 text-white px-3 py-1 rounded-lg text-xs hover:bg-blue-700 relative">
                                            <i class="fas fa-edit"></i>
                                            <span class="tooltip absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded shadow">Edit Data</span>
                                        </button>
                                        <button onclick="deletePayment(<?= $record['id'] ?>)" class="group button-hover bg-red-600 text-white px-3 py-1 rounded-lg text-xs hover:bg-red-700 relative">
                                            <i class="fas fa-trash"></i>
                                            <span class="tooltip absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded shadow">Hapus Data</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal untuk Edit -->
    <div id="editModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden flex items-center justify-center z-50 transition-opacity duration-300">
        <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-3xl modal-content modal-closed" id="editModalContent">
            <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center">
                <i class="fas fa-edit mr-3 text-blue-600"></i>Edit Data Pembayaran
            </h2>
            <form id="editForm" method="POST" action="" class="space-y-6">
                <input type="hidden" id="edit_id" name="edit_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" id="edit_nama" name="nama" class="mt-1 p-3 border border-gray-300 rounded-lg w-full input-focus text-sm bg-gray-50" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jumlah Jiwa</label>
                        <input type="number" id="edit_jumlah_jiwa" name="jumlah_jiwa" class="mt-1 p-3 border border-gray-300 rounded-lg w-full input-focus text-sm bg-gray-50" required min="1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jenis Zakat</label>
                        <select id="edit_jenis_zakat" name="jenis_zakat" class="mt-1 p-3 border border-gray-300 rounded-lg w-full input-focus text-sm bg-gray-50" required>
                            <option value="beras">Beras</option>
                            <option value="uang">Uang</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Metode Pembayaran</label>
                        <input type="text" id="edit_metode_pembayaran" name="metode_pembayaran" class="mt-1 p-3 border border-gray-300 rounded-lg w-full input-focus text-sm bg-gray-50" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Total Bayar (Rp)</label>
                        <input type="number" step="0.01" id="edit_total_bayar" name="total_bayar" class="mt-1 p-3 border border-gray-300 rounded-lg w-full input-focus text-sm bg-gray-50" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nominal Dibayar (Rp)</label>
                        <input type="number" step="0.01" id="edit_nominal_dibayar" name="nominal_dibayar" class="mt-1 p-3 border border-gray-300 rounded-lg w-full input-focus text-sm bg-gray-50" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kembalian (Rp)</label>
                        <input type="number" step="0.01" id="edit_kembalian" name="kembalian" class="mt-1 p-3 border border-gray-300 rounded-lg w-full input-focus text-sm bg-gray-50" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                        <textarea id="edit_keterangan" name="keterangan" class="mt-1 p-3 border border-gray-300 rounded-lg w-full input-focus text-sm bg-gray-50" rows="4"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Tanggal Bayar</label>
                        <input type="datetime-local" id="edit_tanggal_bayar" name="tanggal_bayar" class="mt-1 p-3 border border-gray-300 rounded-lg w-full input-focus text-sm bg-gray-50" required>
                    </div>
                </div>
                <div class="flex justify-end gap-4 mt-6">
                    <button type="submit" class="button-hover bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">Simpan</button>
                    <button type="button" onclick="closeEditModal()" class="button-hover bg-gray-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-700">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, nama, jumlah_jiwa, jenis_zakat, metode_pembayaran, total_bayar, nominal_dibayar, kembalian, keterangan, tanggal_bayar) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_jumlah_jiwa').value = jumlah_jiwa;
            document.getElementById('edit_jenis_zakat').value = jenis_zakat;
            document.getElementById('edit_metode_pembayaran').value = metode_pembayaran;
            document.getElementById('edit_total_bayar').value = total_bayar;
            document.getElementById('edit_nominal_dibayar').value = nominal_dibayar;
            document.getElementById('edit_kembalian').value = kembalian;
            document.getElementById('edit_keterangan').value = keterangan;
            document.getElementById('edit_tanggal_bayar').value = tanggal_bayar.replace(' ', 'T');
            const modal = document.getElementById('editModal');
            const content = document.getElementById('editModalContent');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('opacity-100');
                content.classList.remove('modal-closed');
                content.classList.add('modal-open');
            }, 10);
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            const content = document.getElementById('editModalContent');
            modal.classList.remove('opacity-100');
            content.classList.remove('modal-open');
            content.classList.add('modal-closed');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        function deletePayment(id) {
            if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                fetch(`http://localhost:5000/pembayaran/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Gagal menghapus data');
                    return response.json();
                })
                .then(result => {
                    if (result.message === "Pembayaran deleted successfully") {
                        alert("Data berhasil dihapus!");
                        location.reload();
                    } else {
                        throw new Error(result.message || 'Error tidak diketahui');
                    }
                })
                .catch(error => {
                    alert("Terjadi kesalahan: " + error.message);
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const elements = document.querySelectorAll('.slide-in');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.classList.remove('slide-in-from');
                    el.classList.add('slide-in-to');
                }, index * 100);
            });
        });
    </script>
</body>
</html>