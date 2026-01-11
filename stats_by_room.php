<?php
session_start();
include 'db.php';   // ĐÃ CÓ $conn

// === KIỂM TRA ĐĂNG NHẬP + LẤY PHÒNG QUẢN LÝ ===
if (!isset($_SESSION['admin'])) { 
    header("Location: login.php"); 
    exit(); 
}

// Lấy thông tin phòng quản lý từ CSDL (nếu chưa có trong session)
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

// Nếu KHÔNG PHẢI admin và chưa được phân phòng → đá về trang phân công
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
    <title>Thống kê theo phòng thi - Hệ thống giám sát gian lận 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            min-height: 100vh; 
            font-family: 'Segoe UI', sans-serif; 
            color: white;
            margin:0; padding:0;
        }
        #particles { position:fixed; top:0; left:0; width:100%; height:100%; z-index:0; opacity:0.5; }

        /* HEADER ĐẸP GIỐNG INDEX.PHP */
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
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.3); 
            transition: all 0.4s; 
            background: rgba(255,255,255,0.97);
            color: #333;
        }
        .card:hover { transform: translateY(-10px); }

        .table thead { 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white; 
        }
        .btn-glow { 
            border-radius: 50px; 
            padding: 12px 30px; 
            font-weight: bold;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            transition: all 0.3s;
        }
        .btn-glow:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,0,0,0.4); }

        .stat-badge {
            font-size: 2.8rem;
            font-weight: 900;
            padding: 20px 40px;
            border-radius: 25px;
            color: white;
            box-shadow: 0 12px 30px rgba(0,0,0,0.4);
            min-width: 120px;
            display: inline-block;
        }
        .stat-red    { background: linear-gradient(45deg, #ff6b6b, #ee5a52); }
        .stat-purple { background: linear-gradient(45deg, #a29bfe, #6c5ce7); }
        .stat-green  { background: linear-gradient(45deg, #1dd1a1, #00d2ff); }

        .room-row:hover { 
            background: #e0f2fe !important; 
            transform: scale(1.02);
            transition: all 0.3s;
        }
        .no-data {
            background: linear-gradient(45deg, #11998e, #38ef7d);
            color: white;
            border-radius: 25px;
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: 0 20px 40px rgba(17,153,142,0.4);
        }
    </style>
</head>
<body>

<canvas id="particles"></canvas>

<!-- HEADER ĐẸP GIỐNG INDEX.PHP -->
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
                        &nbsp; | &nbsp; <i class="fas fa-door-open text-success"></i> 
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
                    <a href="stats_detail.php<?= $canbo_phong ? '?room='.$canbo_phong : '' ?>" class="btn btn-warning glow-btn btn-sm text-dark">
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
// PHẦN PHP TÍNH TOÁN GIỮ NGUYÊN 100% CỦA BẠN
$homnay = date('Y-m-d');
$stmt_today = $conn->prepare("SELECT COUNT(*) as today FROM gianlan_log WHERE DATE(thoigian) = ?");
$stmt_today->bind_param("s", $homnay);
$stmt_today->execute();
$today_count = $stmt_today->get_result()->fetch_assoc()['today'] ?? 0;

$stmt_rooms = $conn->query("SELECT COUNT(DISTINCT maphong) as total_rooms FROM phongthi");
$total_rooms = $stmt_rooms->fetch_assoc()['total_rooms'] ?? 0;

$stmt_bad = $conn->query("SELECT COUNT(DISTINCT maphong) as bad FROM gianlan_log WHERE DATE(thoigian) = '$homnay'");
$bad_rooms = $stmt_bad->fetch_assoc()['bad'] ?? 0;
$safe_rooms = $total_rooms - $bad_rooms;

$selected_room = $_GET['room'] ?? '';
?>

<div class="container py-5" style="position:relative; z-index:10;">
    <!-- Header + 4 nút – GIỮ NGUYÊN CẤU TRÚC -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-5 gap-3">
       <h1 class="text-white mb-0 display-4 fw-bold">
            <i class="fas fa-chart-bar text-warning"></i> THỐNG KÊ THEO PHÒNG THI
        </h1>
        <div class="d-flex gap-3 flex-wrap">
            
            <!-- NÚT LÀM MỚI GIỮ NGUYÊN -->
            <button id="refreshBtn" class="btn btn-success btn-glow text-white shadow-lg position-relative overflow-hidden">
                <span class="btn-text"><i class="fas fa-sync-alt"></i> Làm mới</span>
                <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
            </button>
        </div>
    </div>

    <!-- Tổng quan nhanh – GIỮ NGUYÊN CẤU TRÚC -->
    <div class="row g-4 mb-5 text-center">
        <div class="col-md-3">
            <div class="stat-badge stat-red"><?= $today_count ?></div>
            <p class="mt-3 fs-5 fw-bold"><i class="fas fa-exclamation-triangle"></i> Tổng vi phạm hôm nay</p>
        </div>
        <div class="col-md-3">
            <div class="stat-badge stat-purple"><?= $bad_rooms ?></div>
            <p class="mt-3 fs-5 fw-bold"><i class="fas fa-door-open"></i> Phòng có vi phạm</p>
        </div>
        <div class="col-md-3">
            <div class="stat-badge stat-green"><?= $safe_rooms ?></div>
            <p class="mt-3 fs-5 fw-bold"><i class="fas fa-shield-alt"></i> Phòng an toàn</p>
        </div>
        <div class="col-md-3">
            <canvas id="miniChart" height="100"></canvas>
            <p class="mt-3 fs-5 fw-bold"><i class="fas fa-chart-line"></i> Xu hướng 7 ngày</p>
        </div>
    </div>

    <!-- Lọc phòng – GIỮ NGUYÊN -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-9">
                    <label class="form-label fw-bold text-primary"><i class="fas fa-search"></i> Chọn phòng thi để xem chi tiết</label>
                    <select name="room" id="roomSelect" class="form-select form-select-lg">
                        <option value="">-- Tất cả các phòng thi --</option>
                        <?php
                        $rooms_result = $conn->query("SELECT DISTINCT maphong FROM gianlan_log WHERE maphong IS NOT NULL UNION SELECT maphong FROM phongthi ORDER BY maphong");
                        while ($r = $rooms_result->fetch_assoc()) {
                            $sel = ($selected_room === $r['maphong']) ? 'selected' : '';
                            echo "<option value='{$r['maphong']}' $sel>Phòng {$r['maphong']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" id="viewDetailBtn" class="btn btn-danger btn-lg w-100 btn-glow">
                        <i class="fas fa-eye"></i> Xem thống kê
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bảng thống kê – GIỮ NGUYÊN CẤU TRÚC -->
    <div class="card">
        <div class="card-body p-4">
            <h4 class="text-primary mb-4 text-center">
                <i class="fas fa-table"></i>
                <?= $selected_room ? "CHI TIẾT PHÒNG $selected_room" : "TỔNG HỢP TẤT CẢ PHÒNG THI" ?>
            </h4>

            <?php
            $where = $selected_room ? "AND maphong = ?" : "";
            $sql = "SELECT maphong, 
                           COUNT(*) as total_cheat,
                           COUNT(DISTINCT MaSV) as total_students,
                           ROUND(AVG(diem_gianlan),2) as avg_score,
                           MAX(thoigian) as last_cheat
                    FROM gianlan_log 
                    WHERE maphong IS NOT NULL $where
                    GROUP BY maphong 
                    ORDER BY total_cheat DESC";

            $stmt = $conn->prepare($sql);
            if ($selected_room) $stmt->bind_param("s", $selected_room);
            $stmt->execute();
            $result = $stmt->get_result();
            ?>

            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th><i class="fas fa-door-open"></i> Phòng thi</th>
                                <th class="text-center"><i class="fas fa-exclamation-triangle"></i> Tổng vi phạm</th>
                                <th class="text-center"><i class="fas fa-users"></i> SV vi phạm</th>
                                <th class="text-center"><i class="fas fa-chart-line"></i> Điểm trung bình</th>
                                <th class="text-center"><i class="far fa-clock"></i> Lần vi phạm cuối</th>
                                <th class="text-center"><i class="fas fa-eye"></i> Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="room-row <?= $row['total_cheat'] > 5 ? 'table-danger' : ($row['total_cheat'] > 0 ? 'table-warning' : 'table-success') ?>">
                                    <td><strong class="text-primary fs-5">P.<?= htmlspecialchars($row['maphong']) ?></strong></td>
                                    <td class="text-center"><span class="badge bg-danger fs-5 px-4 py-2"><?= $row['total_cheat'] ?></span></td>
                                    <td class="text-center fw-bold text-warning"><?= $row['total_students'] ?></td>
                                    <td class="text-center fw-bold text-info"><?= $row['avg_score'] ?></td>
                                    <td class="text-center"><small><?= date('H:i d/m', strtotime($row['last_cheat'])) ?></small></td>
                                    <td class="text-center">
                                        <a href="room_detail.php?room=<?= urlencode($row['maphong']) ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                            <i class="fas fa-search"></i> Xem
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-shield-alt fa-5x mb-4"></i>
                    <h3 class="text-white fw-bold">CHÚC MỪNG! TẤT CẢ PHÒNG THI ĐỀU AN TOÀN</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- GIỮ NGUYÊN TOÀN BỘ JS CỦA BẠN -->
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles", {
        particles: { number: { value: 70 }, color: { value: ["#ffffff","#c4b5fd","#93c5fd"] }, opacity: { value: 0.4 }, size: { value: 3 }, 
            line_linked: { enable: false }, move: { enable: true, speed: 1 } },
        interactivity: { events: { onhover: { enable: true, mode: "bubble" } } }
    });

    // Nút Xem chi tiết phòng
    document.getElementById('viewDetailBtn').addEventListener('click', function() {
        const room = document.getElementById('roomSelect').value;
        if (room) {
            location.href = 'room_detail.php?room=' + encodeURIComponent(room);
        } else {
            alert('Vui lòng chọn một phòng thi trước!');
        }
    });

    // NÚT LÀM MỚI GIỮ NGUYÊN 100%
    document.getElementById('refreshBtn').addEventListener('click', function() {
        const btn = this;
        const btnText = btn.querySelector('.btn-text');
        const spinner = btn.querySelector('.spinner-border');
        const currentRoom = new URLSearchParams(window.location.search).get('room');

        btn.disabled = true;
        btnText.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Đang tải...';
        spinner.classList.remove('d-none');

        let newUrl = window.location.pathname;
        if (currentRoom) newUrl += '?room=' + encodeURIComponent(currentRoom);
        const separator = newUrl.includes('?') ? '&' : '?';
        newUrl += separator + 't=' + Date.now();

        setTimeout(() => { window.location.href = newUrl; }, 500);
    });

    // Biểu đồ mini – giữ nguyên
    new Chart(document.getElementById('miniChart'), {
        type: 'line',
        data: {
            labels: ['T2','T3','T4','T5','T6','T7','CN'],
            datasets: [{ 
                data: [12,18,15,22,30,25,20], 
                borderColor: '#00ff88',
                backgroundColor: 'rgba(0,255,136,0.2)',
                tension: 0.4,
                fill: true,
                pointRadius: 3
            }]
        },
        options: { 
            plugins: { legend: { display: false } }, 
            scales: { x: { display: false }, y: { display: false } },
            animation: { duration: 1500 }
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>