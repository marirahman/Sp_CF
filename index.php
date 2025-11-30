<?php
// ==========================================
// 1. KNOWLEDGE BASE (Basis Pengetahuan Pakar)
// ==========================================
// Format: [Kode Gejala => [Nama Gejala, Bobot Pakar (CF Pakar)]]
$gejala_db = [
    'G01' => ['nama' => 'Nyeri ulu hati (pedih)', 'bobot' => 0.8],
    'G02' => ['nama' => 'Mual dan ingin muntah', 'bobot' => 0.6],
    'G03' => ['nama' => 'Perut terasa kembung/begah', 'bobot' => 0.5],
    'G04' => ['nama' => 'Sering bersendawa', 'bobot' => 0.4],
    'G05' => ['nama' => 'Nafsu makan menurun drastis', 'bobot' => 0.5],
    'G06' => ['nama' => 'Cepat kenyang saat makan', 'bobot' => 0.4],
    'G07' => ['nama' => 'Panas di dada (Heartburn)', 'bobot' => 0.7],
    'G08' => ['nama' => 'BAB berwarna hitam/gelap', 'bobot' => 0.9] // Gejala Vital
];

// Opsi Pilihan User (Nilai Keyakinan)
$pilihan_user = [
    '0'   => 'Tidak (0)',
    '0.2' => 'Tidak Tahu (0.2)',
    '0.4' => 'Sedikit Yakin (0.4)',
    '0.6' => 'Cukup Yakin (0.6)',
    '0.8' => 'Yakin (0.8)',
    '1'   => 'Sangat Yakin (1.0)'
];

// Inisialisasi variabel hasil
$hasil_diagnosa = null;
$log_perhitungan = [];

// ==========================================
// 2. LOGIKA SISTEM PAKAR (PHP Process)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_gejala = $_POST['gejala']; // Ambil array input dari form
    $cf_total = 0;
    
    foreach ($input_gejala as $kode => $nilai_user) {
        $nilai_user = (float) $nilai_user;
        
        // Hanya proses jika user memilih keyakinan > 0
        if ($nilai_user > 0) {
            $bobot_pakar = $gejala_db[$kode]['bobot'];
            
            // Langkah 1: Hitung CF Gejala (CF User * CF Pakar)
            $cf_gejala = $nilai_user * $bobot_pakar;
            
            // Simpan CF lama sebelum diupdate (untuk log)
            $cf_lama = $cf_total;

            // Langkah 2: Hitung CF Combine (Sequential)
            if ($cf_total == 0) {
                $cf_total = $cf_gejala;
            } else {
                // Rumus: CF_old + CF_new * (1 - CF_old)
                $cf_total = $cf_total + ($cf_gejala * (1 - $cf_total));
            }

            // Simpan log untuk tabel detail
            $log_perhitungan[] = [
                'gejala' => $gejala_db[$kode]['nama'],
                'user' => $nilai_user,
                'pakar' => $bobot_pakar,
                'cf_gejala' => $cf_gejala,
                'cf_lama' => $cf_lama,
                'cf_baru' => $cf_total
            ];
        }
    }

    // Hitung Persentase
    $persentase = number_format($cf_total * 100);

    // Tentukan Kesimpulan (Threshold)
    if ($persentase >= 80) {
        $tingkat = "RISIKO TINGGI (HIGH RISK)";
        $solusi = "Segera hubungi dokter spesialis penyakit dalam. Gejala mengarah pada Gastritis Kronis atau Tukak Lambung.";
        $warna = "alert-danger";
    } elseif ($persentase >= 50) {
        $tingkat = "RISIKO SEDANG (MODERATE RISK)";
        $solusi = "Istirahat cukup, hindari makanan pedas/asam, makan teratur. Minum obat maag yang dijual bebas.";
        $warna = "alert-warning";
    } elseif ($persentase > 0) {
        $tingkat = "RISIKO RENDAH (LOW RISK)";
        $solusi = "Kemungkinan hanya masuk angin atau gangguan pencernaan ringan. Perbaiki pola makan.";
        $warna = "alert-success";
    } else {
        $tingkat = "TIDAK ADA INDIKASI";
        $solusi = "Anda sehat walafiat berdasarkan gejala yang dipilih.";
        $warna = "alert-secondary";
    }

    $hasil_diagnosa = [
        'nilai' => $cf_total,
        'persen' => $persentase,
        'tingkat' => $tingkat,
        'solusi' => $solusi,
        'warna' => $warna
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pakar CF - Penyakit Lambung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: none; }
        .header-title { color: #2c3e50; font-weight: bold; }
    </style>
</head>
<body class="py-5">

<div class="container">
    <div class="text-center mb-5">
        <h1 class="header-title">SISTEM PAKAR DIAGNOSA LAMBUNG</h1>
        <p class="text-muted">Metode Certainty Factor (CF)</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4 mb-4">
                <h5 class="card-title mb-4">Pilih Gejala yang Anda Rasakan</h5>
                <form method="POST">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Gejala</th>
                                <th style="width: 30%;">Tingkat Keyakinan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach($gejala_db as $kode => $data): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= $data['nama'] ?></td>
                                <td>
                                    <select name="gejala[<?= $kode ?>]" class="form-select form-select-sm">
                                        <?php foreach($pilihan_user as $nilai => $label): ?>
                                            <option value="<?= $nilai ?>"><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">ANALISA SEKARANG</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($hasil_diagnosa): ?>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <h3 class="text-center mb-4">HASIL ANALISA</h3>
                
                <div class="alert <?= $hasil_diagnosa['warna'] ?> text-center">
                    <h4><?= $hasil_diagnosa['tingkat'] ?> (<?= $hasil_diagnosa['persen'] ?>%)</h4>
                    <p class="mb-0 fw-bold"><?= $hasil_diagnosa['solusi'] ?></p>
                </div>

                <div class="mt-4">
                    <h6>Detail Perhitungan (Sistem Trace):</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm text-center" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Gejala</th>
                                    <th>CF User</th>
                                    <th>CF Pakar</th>
                                    <th>CF Gejala (U*P)</th>
                                    <th>CF Combine (Update)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($log_perhitungan as $log): ?>
                                <tr>
                                    <td class="text-start"><?= $log['gejala'] ?></td>
                                    <td><?= $log['user'] ?></td>
                                    <td><?= $log['pakar'] ?></td>
                                    <td><?= number_format($log['cf_gejala'], 2) ?></td>
                                    <td class="fw-bold text-primary"><?= number_format($log['cf_baru'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">*Rumus Combine: CF_Lama + CF_Baru * (1 - CF_Lama)</small>
                </div>
                
                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-outline-secondary">Reset Diagnosa</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

</body>
</html>