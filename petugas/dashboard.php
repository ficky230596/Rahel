<?php
session_start();
include "../config/db.php";
include "header.php";
include "sidebar.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Cek apakah ada jadwal besok
// Ambil jadwal besok
$stmt = $pdo->prepare("
    SELECT j.*, p.nama AS peleton_nama, r.nama AS regu_nama, ps.nama AS pos_nama
    FROM jadwal j
    LEFT JOIN peleton p ON j.peleton_id = p.id
    LEFT JOIN regu r ON j.regu_id = r.id
    LEFT JOIN pos ps ON j.pos_id = ps.id
    WHERE j.pegawai_id = ? AND j.tanggal = ? AND j.status = 'aktif'
    LIMIT 1
");
$stmt->execute([$user_id, $tomorrow]);
$jadwal = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika tidak ada jadwal besok → cari jadwal terdekat berikutnya
if (!$jadwal) {
    $stmt_next = $pdo->prepare("
        SELECT j.*, p.nama AS peleton_nama, r.nama AS regu_nama, ps.nama AS pos_nama
        FROM jadwal j
        LEFT JOIN peleton p ON j.peleton_id = p.id
        LEFT JOIN regu r ON j.regu_id = r.id
        LEFT JOIN pos ps ON j.pos_id = ps.id
        WHERE j.pegawai_id = ? 
          AND j.tanggal > CURDATE()
          AND j.status = 'aktif'
        ORDER BY j.tanggal ASC
        LIMIT 1
    ");
    $stmt_next->execute([$user_id]);
    $jadwal = $stmt_next->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Petugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            background: #0f172a;
            color: #fff;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 50px;
        }

        .container {
            max-width: 900px;
        }

        .card {
            border: none;
            border-radius: 16px;
            background: linear-gradient(145deg, #1e293b, #0f172a);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            color: #fff;
            text-align: center;
            padding: 15px 100px;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
            background-color: #f59e0b;
        }

        .card h5 {
            margin-bottom: 10px;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .card.peleton {
            border-top: 5px solid #ef4444;
        }

        .card.regu {
            border-top: 5px solid #f59e0b;
        }

        .card.pos {
            border-top: 5px solid #10b981;
        }

        .no-schedule {
            text-align: center;
            font-size: 1.2rem;
            color: #94a3b8;
        }
    </style>
</head>

<body>
    <div class="container">
        <h3 class="text-center mb-4">
            Jadwal Piket
            <?= ($jadwal && $jadwal['tanggal'] == $tomorrow) ? 'Besok' : 'Terdekat' ?>
            (<?= $jadwal ? date('d M Y', strtotime($jadwal['tanggal'])) : date('d M Y', strtotime($tomorrow)) ?>)
        </h3>

        <?php if ($jadwal): ?>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card peleton" data-type="peleton" data-id="<?= $jadwal['peleton_id'] ?>">
                        <h5>Peleton</h5>
                        <p><?= htmlspecialchars($jadwal['peleton_nama'] ?? '-') ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card regu" data-type="regu" data-id="<?= $jadwal['regu_id'] ?>">
                        <h5>Regu</h5>
                        <p><?= htmlspecialchars($jadwal['regu_nama'] ?? '-') ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card pos" data-type="pos" data-id="<?= $jadwal['pos_id'] ?>">
                        <h5>Pos</h5>
                        <p><?= htmlspecialchars($jadwal['pos_nama'] ?? '-') ?></p>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <p class="no-schedule mt-5">Anda tidak memiliki jadwal piket aktif terdekat.</p>
        <?php endif; ?>

    </div>

    <!-- Modal Detail Tim -->
    <div class="modal fade" id="teamModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Tim</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="teamList">Memuat data...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $(".card").on("click", function() {
                const type = $(this).data("type");
                const id = $(this).data("id");
                const tanggal = "<?= $jadwal['tanggal'] ?? $tomorrow ?>";


                $("#teamModal").modal("show");
                $("#teamList").html("<div class='text-center text-muted'>Memuat data...</div>");

                $.ajax({
                    url: "get_team.php",
                    type: "GET",
                    data: {
                        type,
                        id,
                        tanggal
                    },
                    success: function(data) {
                        $("#teamList").html(data);
                    },
                    error: function() {
                        $("#teamList").html("<div class='text-danger text-center'>Gagal memuat data.</div>");
                    }
                });
            });

            // === Grafik Bar (contoh data statis, bisa diganti dari DB) ===
            const ctx = document.getElementById("peletonChart").getContext("2d");
            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: ["Peleton A", "Peleton B", "Peleton C", "Peleton D"],
                    datasets: [{
                        label: "Jumlah Anggota",
                        data: [10, 8, 12, 9],
                        backgroundColor: ["#ef4444", "#f59e0b", "#10b981", "#3b82f6"]
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            labels: {
                                color: "#fff"
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: "#fff"
                            }
                        },
                        y: {
                            ticks: {
                                color: "#fff"
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>