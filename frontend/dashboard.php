<?php
date_default_timezone_set('Asia/Jakarta');

// Ambil data pembayaran dari API
$pembayaran_data = [];
$pembayaran_error = null;
$api_url_pembayaran = 'http://localhost:5000/pembayaran';
$ch = curl_init($api_url_pembayaran);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $pembayaran_data = json_decode($response, true);
    usort($pembayaran_data, function($a, $b) {
        return strtotime($b['tanggal_bayar']) - strtotime($a['tanggal_bayar']);
    });
} else {
    $pembayaran_error = 'Gagal mengambil data: HTTP ' . $http_code;
}

$total_pembayaran = 0;
$jumlah_transaksi = count($pembayaran_data);
$tanggal_terbaru = '-';

if ($jumlah_transaksi > 0) {
    foreach ($pembayaran_data as $item) {
        $total_pembayaran += floatval($item['total_bayar']);
    }
    $tanggal_terbaru = date('d M Y, H:i', strtotime($pembayaran_data[0]['tanggal_bayar']));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pembayaran Zakat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        .zoom-in {
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
        .zoom-in-from {
            opacity: 0;
            transform: scale(0.95);
        }
        .zoom-in-to {
            opacity: 1;
            transform: scale(1);
        }
        .table-row-hover {
            transition: background-color 0.2s ease;
        }
        .table-row-hover:hover {
            background-color: #fef3c7;
        }
        .nav-button {
            transition: all 0.2s ease;
        }
        .nav-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-b from-amber-50 to-gray-100 min-h-screen flex items-center justify-center p-4 font-sans">
    <div class="w-full max-w-6xl bg-white rounded-xl shadow-xl overflow-hidden">
        <!-- Header -->
        <header class="bg-gradient-to-r from-amber-600 to-yellow-500 text-white p-6">
            <h1 class="text-2xl md:text-3xl font-bold text-center">Dashboard Pembayaran Zakat</h1>
            <p class="text-center text-amber-100 text-sm mt-2">Lacak dan kelola transaksi zakat Anda</p>
        </header>

        <!-- Main Content -->
        <main class="p-6 md:p-8">
            <?php if ($pembayaran_error): ?>
                <div class="bg-red-50 text-red-700 border border-red-200 rounded-lg p-4 mb-6 animate-pulse flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= htmlspecialchars($pembayaran_error) ?>
                </div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg p-6 shadow-md zoom-in zoom-in-from border border-amber-100">
                    <div class="flex items-center space-x-4">
                        <div class="bg-amber-100 p-3 rounded-full">
                            <i class="fas fa-coins text-amber-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Pembayaran</p>
                            <p class="text-xl font-semibold text-gray-800">Rp <?= number_format($total_pembayaran, 2, ',', '.') ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-md zoom-in zoom-in-from border border-amber-100" style="transition-delay: 0.1s;">
                    <div class="flex items-center space-x-4">
                        <div class="bg-amber-100 p-3 rounded-full">
                            <i class="fas fa-exchange-alt text-amber-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Jumlah Transaksi</p>
                            <p class="text-xl font-semibold text-gray-800"><?= $jumlah_transaksi ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-md zoom-in zoom-in-from border border-amber-100" style="transition-delay: 0.2s;">
                    <div class="flex items-center space-x-4">
                        <div class="bg-amber-100 p-3 rounded-full">
                            <i class="fas fa-calendar-day text-amber-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Tanggal Terbaru</p>
                            <p class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($tanggal_terbaru) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Payments Table -->
            <div class="bg-white rounded-lg shadow-md p-6 border border-amber-100">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Daftar Pembayaran Terbaru</h2>
                <?php if ($jumlah_transaksi === 0): ?>
                    <div class="text-center text-gray-500 py-6 flex items-center justify-center">
                        <i class="fas fa-info-circle mr-2 text-lg"></i>Belum ada data pembayaran.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-amber-50 text-gray-700">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Nama</th>
                                    <th class="px-6 py-3 font-semibold">Jenis Zakat</th>
                                    <th class="px-6 py-3 font-semibold text-right">Total Bayar (Rp)</th>
                                    <th class="px-6 py-3 font-semibold">Tanggal Bayar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-amber-100">
                                <?php foreach (array_slice($pembayaran_data, 0, 5) as $data): ?>
                                    <tr class="table-row-hover">
                                        <td class="px-6 py-4 text-gray-800"><?= htmlspecialchars($data['nama']) ?></td>
                                        <td class="px-6 py-4 text-gray-800"><?= htmlspecialchars($data['jenis_zakat']) ?></td>
                                        <td class="px-6 py-4 text-gray-800 text-right"><?= number_format($data['total_bayar'], 2, ',', '.') ?></td>
                                        <td class="px-6 py-4 text-gray-800"><?= date('d M Y, H:i', strtotime($data['tanggal_bayar'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Navigation -->
            <nav class="flex justify-center gap-4 mt-8">
                <a href="pembayaran.php" class="nav-button bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 flex items-center">
                    <i class="fas fa-plus mr-2"></i>Tambah Pembayaran
                </a>
                <a href="index.php" class="nav-button bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 flex items-center">
                    <i class="fas fa-history mr-2"></i>History Pembayaran
                </a>
                <a href="beras.php" class="nav-button bg-yellow-600 text-white px-6 py-3 rounded-lg hover:bg-yellow-700 flex items-center">
                    <i class="fas fa-seedling mr-2"></i>Data Beras
                </a>
            </nav>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const elements = document.querySelectorAll('.zoom-in');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.classList.remove('zoom-in-from');
                    el.classList.add('zoom-in-to');
                }, index * 100);
            });
        });
    </script>
</body>
</html>