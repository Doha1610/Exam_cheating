<?php
session_start();
include 'db.php';   // ĐÃ CÓ $conn

// === KIỂM TRA ĐĂNG NHẬP + LẤY PHÒNG QUẢN LÝ ===
if (!isset($_SESSION['admin'])) { 
    header("Location: login.php"); 
    exit(); 
}

if (!isset($_SESSION['admin']['phong_quanly'])) {
    $id = $_SESSION['admin']['id'];
    $stmt = $conn->prepare("SELECT phong_quanly FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['admin']['phong_quanly'] = $row['phong_quanly'] ?? null;
    }
}
$canbo_phong = $_SESSION['admin']['phong_quanly'] ?? null;

if ($canbo_phong === null && $_SESSION['admin']['username'] !== 'admin') {
    header("Location: assign_room.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê chi tiết gian lận - Hệ thống AI 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            font-family: 'Segoe UI', sans-serif; 
            color: white;
            margin:0; padding:0;
        }
        #particles { position:fixed; top:0; left:0; width:100%; height:100%; z-index:0; opacity:0.5; }

        .top-header {
            background: rgba(0,0,0,0.4); backdrop-filter: blur(12px);
            position: sticky; top: 0; z-index: 1030; padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.15);
        }
        .glow-btn {
            border-radius: 50px; padding: 9px 22px; font-weight: bold; font-size: 0.95rem;
            box-shadow: 0 6px 18px rgba(0,0,0,0.4); transition: all 0.3s;
        }
        .glow-btn:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0,0,0,0.5); }

        .card { 
            background: rgba(255,255,255,0.97); 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.3); 
            transition: all 0.4s; 
            color: #333;
        }
        .card:hover { transform: translateY(-10px); }

        .stat-card h2 { 
            font-size: 4.8rem; 
            font-weight: 900; 
        }
        .stat-total { color: #e74c3c; }
        .stat-students { color: #f39c12; }
        .stat-avg { color: #3498db; }

        .chart-container { position: relative; height: 360px; padding: 15px 0; }
        .no-data { display: flex; align-items: center; justify-content: center; height: 100%; color: #95a5a6; font-style: italic; font-size: 1.3rem; }

        .top-student { 
            background: rgba(220,53,69,0.1); 
            border-left: 6px solid #dc3545; 
            border-radius: 12px; 
            transition: all 0.3s;
        }
        .top-student:hover { transform: scale(1.05); box-shadow: 0 15px 30px rgba(220,53,69,0.3); }

        .glow-btn-lg { 
            padding: 14px 35px; border-radius: 50px; font-weight: bold; font-size: 1.1rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3); transition: all 0.4s;
        }
        .glow-btn-lg:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.4); }
    </style>
</head>
<body>

<canvas id="particles"></canvas>

<!-- HEADER ĐỒNG BỘ VỚI INDEX.PHP -->
<div class="top-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 text-white">
                <h3 class="mb-0 fw-bold">
                    <i class="fas fa-eye text-warning"></i> GIÁM SÁT GIAN LẬN 2025
                </h3>
                <small class="opacity-80">
                    <i class="fas fa-user-tie"></i> <?= htmlspecialchars($_SESSION['admin']['hoten']) ?>
                    <?php if($canbo_phong): ?>
                        | <i class="fas fa-door-open text-success"></i> 
                        <strong class="text-warning">Phòng <?= htmlspecialchars($canbo_phong) ?></strong>
                    <?php endif; ?>
                </small>
            </div>
            <div class="col-lg-6 text-end">
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <a href="index.php" class="btn btn-light glow-btn btn-sm"><i class="fas fa-home"></i> Trang chủ</a>
                    <a href="stats_by_room.php<?= $canbo_phong ? '?room='.$canbo_phong : '' ?>" class="btn btn-info glow-btn btn-sm text-white">
                        <i class="fas fa-building"></i> Theo phòng thi
                    </a>
                    <a href="stats_detail.php<?= $canbo_phong ? '?room='.$canbo_phong : '' ?>" class="btn btn-warning glow-btn btn-sm text-dark fw-bold">
                        <i class="fas fa-chart-pie"></i> Thống kê chi tiết
                    </a>
                    <?php if($_SESSION['admin']['username'] === 'admin'): ?>
                    <a href="assign_room.php" class="btn btn-secondary glow-btn btn-sm">
                        <i class="fas fa-users-cog"></i> Phân công phòng
                    </a>
                    <?php endif; ?>
                    <button id="refreshBtn" class="btn btn-success glow-btn btn-sm position-relative">
                        <span class="btn-text"><i class="fas fa-sync-alt"></i> Làm mới</span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                    </button>
                    <a href="logout.php" class="btn btn-danger glow-btn btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$selected_room = $_GET['room'] ?? '';
$where_room = $selected_room ? "WHERE maphong = ?" : "";
$params = $selected_room ? [$selected_room] : [];
$types = $selected_room ? 's' : '';

function safe_query($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    if ($types && !empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

// === GIỮ NGUYÊN LOGIC CŨ CỦA BẠN ===
$total = $students = $avg = 0;
$r = safe_query($conn, "SELECT COUNT(*) FROM gianlan_log $where_room", $types, $params);
if ($r) $total = $r->fetch_row()[0] ?? 0;
$r = safe_query($conn, "SELECT COUNT(DISTINCT MaSV) FROM gianlan_log $where_room", $types, $params);
if ($r) $students = $r->fetch_row()[0] ?? 0;
$r = safe_query($conn, "SELECT ROUND(AVG(diem_gianlan),2) FROM gianlan_log $where_room", $types, $params);
if ($r) $avg = $r->fetch_row()[0] ?? 0;

$type_labels = $type_data = $type_colors = [];
$color_map = ['Trao phao'=>'#dc3545','Cúi gầm bàn'=>'#fd7e14','Quay đầu'=>'#ffc107','Điện thoại'=>'#6ed4f7ff','Quay sau lưng'=>'#9b59b6','Khác'=>'#6c757d'];
$r = safe_query($conn, "SELECT COALESCE(NULLIF(loai_hanhvi,''),'Khác') AS loai, COUNT(*) AS c FROM gianlan_log $where_room GROUP BY loai", $types, $params);
if ($r) while ($row = $r->fetch_assoc()) {
    $type_labels[] = $row['loai'];
    $type_data[] = (int)$row['c'];
    $type_colors[] = $color_map[$row['loai']] ?? '#6c757d';
}

$hour_data = array_fill(0,24,0);
$r = safe_query($conn, "SELECT HOUR(thoigian) as h, COUNT(*) as c FROM gianlan_log $where_room GROUP BY h", $types, $params);
if ($r) while ($row = $r->fetch_assoc()) $hour_data[(int)$row['h']] = (int)$row['c'];

// === MỚI: TOP 5 PHÒNG VI PHẠM NHIỀU NHẤT (giữ nguyên giao diện cũ) ===
$top_rooms = [];
$top_sql = "SELECT 
                gl.maphong,
                COALESCE(pt.tenphong, gl.maphong) AS tenphong,
                COUNT(*) AS cnt,
                ROUND(AVG(gl.diem_gianlan), 2) AS diem_tb
            FROM gianlan_log gl
            LEFT JOIN phongthi pt ON gl.maphong = pt.maphong
            $where_room
            GROUP BY gl.maphong
            ORDER BY cnt DESC, diem_tb DESC
            LIMIT 5";

$top_result = safe_query($conn, $top_sql, $types, $params);
if ($top_result) {
    while ($row = $top_result->fetch_assoc()) {
        $top_rooms[] = $row;
    }
}
?>

<div class="container py-5" style="position:relative; z-index:10;">
    <!-- TIÊU ĐỀ -->
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold text-white">
            <i class="fas fa-chart-bar text-warning"></i> THỐNG KÊ CHI TIẾT GIAN LẬN
        </h1>
    </div>

    <!-- Lọc phòng – giữ nguyên -->
    <div class="card bg-white p-4 mb-4">
        <div class="row align-items-center">
            <div class="col-md-5">
                <label class="form-label fw-bold text-primary"><i class="fas fa-filter"></i> Lọc theo phòng:</label>
                <select id="roomFilter" class="form-select form-select-lg">
                    <option value="">-- Tất cả phòng --</option>
                    <?php
                    $rooms = $conn->query("SELECT maphong, tenphong FROM phongthi ORDER BY maphong");
                    while ($r = $rooms->fetch_assoc()) {
                        $sel = ($selected_room === $r['maphong']) ? 'selected' : '';
                        echo "<option value='{$r['maphong']}' $sel>{$r['tenphong']} ({$r['maphong']})</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-7 text-end">
                <h5 class="text-muted mb-0">Đang xem: <strong class="text-primary" id="currentRoom"><?= $selected_room ? "Phòng $selected_room" : "Tất cả phòng" ?></strong></h5>
            </div>
        </div>
    </div>

    <!-- 3 số lớn – giữ nguyên -->
    <div class="row g-4 mb-5">
        <div class="col-md-4"><div class="card text-center p-5 stat-card"><h2 class="stat-total"><?= $total ?></h2><p class="fs-4 mb-0 text-muted">Tổng lần gian lận</p></div></div>
        <div class="col-md-4"><div class="card text-center p-5 stat-card"><h2 class="stat-students"><?= $students ?></h2><p class="fs-4 mb-0 text-muted">SV vi phạm</p></div></div>
        <div class="col-md-4"><div class="card text-center p-5 stat-card"><h2 class="stat-avg"><?= $avg ?></h2><p class="fs-4 mb-0 text-muted">Điểm gian lận TB</p></div></div>
    </div>

    <!-- Biểu đồ – giữ nguyên cấu trúc -->
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="card p-4">
                <h5 class="text-center mb-4 text-primary">Phân bố theo loại hành vi</h5>
                <div class="chart-container"><canvas id="pieChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card p-4">
                <h5 class="text-center mb-4 text-primary">Gian lận theo giờ trong ngày</h5>
                <div class="chart-container"><canvas id="barChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- TOP 5 PHÒNG VI PHẠM NHIỀU NHẤT – GIỮ NGUYÊN GIAO DIỆN CŨ -->
    <?php if (!empty($top_rooms)): ?>
    <div class="card p-5 mb-5">
        <h3 class="text-center text-danger mb-4">
            <i class="fas fa-exclamation-triangle"></i> TOP 5 PHÒNG CÓ NHIỀU VI PHẠM NHẤT
        </h3>
        <div class="row g-4">
            <?php foreach ($top_rooms as $i => $r): ?>
            <div class="col-md">
                <div class="top-student p-4 text-center rounded shadow-sm">
                    <h1 class="display-5 fw-bold text-danger"><?= $i+1 ?></h1>
                    <h5 class="mb-2"><?= htmlspecialchars($r['maphong']) ?></h5>
                    <small class="text-muted d-block"><?= htmlspecialchars($r['tenphong']) ?></small>
                    <span class="badge bg-danger fs-4 px-4 py-3"><?= $r['cnt'] ?> lần</span>
                    <?php if($r['diem_tb'] > 0): ?>
                        <small class="d-block mt-2 text-warning">Điểm TB: <?= $r['diem_tb'] ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Nút xuất + in – giữ nguyên -->
    <div class="text-center">
        <a href="export_excel.php<?= $selected_room ? '?room='.$selected_room : '' ?>" class="btn btn-danger glow-btn-lg mx-3">
            <i class="fas fa-file-excel"></i> Xuất Excel
        </a>
        <button onclick="window.print()" class="btn btn-success glow-btn-lg mx-3">
            <i class="fas fa-print"></i> In báo cáo
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles", {
        particles: { number: { value: 70 }, color: { value: ["#ffffff","#c4b5fd","#93c5fd"] }, opacity: { value: 0.4 }, size: { value: 3 }, 
            line_linked: { enable: false }, move: { enable: true, speed: 1 } },
        interactivity: { events: { onhover: { enable: true, mode: "bubble" } } }
    });

    document.getElementById('refreshBtn').addEventListener('click', function() {
        const btn = this;
        const text = btn.querySelector('.btn-text');
        const spinner = btn.querySelector('.spinner-border');
        btn.disabled = true;
        text.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Đang tải...';
        spinner.classList.remove('d-none');
        setTimeout(() => location.reload(), 600);
    });

    const typeLabels = <?= json_encode($type_labels) ?>;
    const typeData = <?= json_encode($type_data) ?>;
    const typeColors = <?= json_encode($type_colors) ?>;
    const hourData = <?= json_encode($hour_data) ?>;
    const totalCount = typeData.reduce((a,b) => a+b, 0);

    if (totalCount > 0) {
        new Chart(document.getElementById('pieChart'), {
            type: 'doughnut',
            data: { labels: typeLabels, datasets: [{ data: typeData, backgroundColor: typeColors, borderWidth: 4, borderColor: '#fff', hoverOffset: 25 }] },
            options: {
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: { size: 14 },
                            generateLabels: chart => chart.data.labels.map((label, i) => ({
                                text: `${label}: ${typeData[i]} lần (${((typeData[i]/totalCount)*100).toFixed(1)}%)`,
                                fillStyle: typeColors[i],
                                strokeStyle: '#fff',
                                lineWidth: 3
                            }))
                        }
                    }
                }
            }
        });
    } else {
        document.querySelector('#pieChart').parentElement.innerHTML = '<div class="no-data">Chưa có dữ liệu hành vi</div>';
    }

    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: { labels: Array.from({length:24},(_,i)=>i+':00'), datasets: [{ label: 'Số lần', data: hourData, backgroundColor: '#667eea', borderRadius: 8 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    document.getElementById('roomFilter').addEventListener('change', e => {
        location.href = e.target.value ? '?room=' + e.target.value : 'stats_detail.php';
    });

    setInterval(() => location.reload(), 15000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>