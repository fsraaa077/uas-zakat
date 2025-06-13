<?php
// Set zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// URL endpoint API Flask
$api_url = 'http://localhost:5000/beras';

// Inisialisasi variabel
$data = [];
$error = null;
$success = null;

// Inisialisasi cURL untuk GET
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Dekode respons JSON
if ($response !== false && $http_code === 200) {
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error = 'Gagal mendekode data JSON: ' . json_last_error_msg();
    }
} else {
    $error = 'Gagal mengambil data beras: ' . ($http_code ? "HTTP $http_code" : 'Koneksi gagal');
}

// Fungsi untuk menambah data beras
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_harga'])) {
    $data_to_send = ['harga' => floatval($_POST['add_harga'])];
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_to_send));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 201) {
        $success = "Data beras berhasil ditambahkan.";
        // Ambil data terbaru
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response !== false && $http_code === 200) {
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'Gagal mendekode data JSON setelah penambahan: ' . json_last_error_msg();
            }
        } else {
            $error = 'Gagal mengambil data beras setelah penambahan: ' . ($http_code ? "HTTP $http_code" : 'Koneksi gagal');
        }
    } else {
        $error = "Gagal menambahkan data beras: HTTP $http_code";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Harga Beras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        .modal-enter {
            transition: all 0.3s ease;
        }
        .modal-enter-from {
            opacity: 0;
            transform: scale(0.95);
        }
        .modal-enter-to {
            opacity: 1;
            transform: scale(1);
        }
        .table-row-hover {
            transition: background-color 0.2s;
        }
        .table-row-hover:hover {
            background-color: #f1f5f9;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200 min-h-screen flex items-center justify-center p-4 font-sans">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-5xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <i class="fas fa-sack text-2xl text-yellow-300"></i>
                <h1 class="text-2xl font-bold text-white">Data Harga Beras</h1>
            </div>
            <div class="space-x-3">
                <a href="dashboard.php" class="bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition duration-200">Kembali</a>
                <button onclick="openAddModal()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition duration-200">Tambah Data</button>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <?php if ($error): ?>
                <div class="bg-red-50 text-red-700 border border-red-200 rounded-lg p-4 mb-6 animate-pulse">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php elseif (isset($success)): ?>
                <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-4 mb-6 animate-pulse">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 font-semibold text-gray-700">ID</th>
                            <th class="px-6 py-3 font-semibold text-gray-700">Harga (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($data) && is_array($data)): ?>
                            <?php foreach ($data as $record): ?>
                                <tr class="table-row-hover">
                                    <td class="px-6 py-4 text-gray-800"><?= htmlspecialchars($record['id']) ?></td>
                                    <td class="px-6 py-4 text-gray-800"><?= number_format($record['harga'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-center text-gray-500">Tidak ada data ditemukan</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="addModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-8 shadow-2xl w-full max-w-lg modal-enter">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Tambah Data Beras</h2>
            <form id="addForm" method="POST" class="space-y-6">
                <div>
                    <label for="add_harga" class="block text-sm font-medium text-gray-700 mb-2">Harga (Rp)</label>
                    <input type="number" id="add_harga" name="add_harga" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Masukkan harga" step="0.01" required>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-200">Simpan</button>
                    <button type="button" onclick="closeAddModal()" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition duration-200">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
            setTimeout(() => {
                document.querySelector('.modal-enter').classList.remove('modal-enter-from');
                document.querySelector('.modal-enter').classList.add('modal-enter-to');
            }, 10);
        }

        function closeAddModal() {
            document.querySelector('.modal-enter').classList.add('modal-enter-from');
            document.querySelector('.modal-enter').classList.remove('modal-enter-to');
            setTimeout(() => {
                document.getElementById('addModal').classList.add('hidden');
            }, 300);
        }
    </script>
</body>
</html>