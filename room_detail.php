<?php
session_start();
include 'db.php';

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
    $stmt->close();
}
$canbo_phong = $_SESSION['admin']['phong_quanly'] ?? null;

if ($canbo_phong === null && $_SESSION['admin']['username'] !== 'admin') {
    header("Location: assign_room.php");
    exit();
}

$room = trim($_GET['room'] ?? '');
if (empty($room)) {
    die('<div class="container py-5 text-center"><div class="alert alert-danger fs-3">Không xác định được phòng thi!</div></div>');
}

if ($canbo_phong !== null && $_SESSION['admin']['username'] !== 'admin' && $room !== $canbo_phong) {
    die('<div class="container py-5 text-center"><div class="alert alert-warning fs-3">Bạn chỉ được xem chi tiết phòng mà mình quản lý!</div></div>');
}

$stmt = $conn->prepare("SELECT tenphong FROM phongthi WHERE maphong = ?");
$stmt->bind_param("s", $room);
$stmt->execute();
$result = $stmt->get_result();
$tenphong = "Phòng $room";
if ($row = $result->fetch_assoc()) {
    $tenphong = htmlspecialchars($row['tenphong']) . " ($room)";
}
$stmt->close();

$sql = "SELECT MaSV, loai_hanhvi, diem_gianlan, thoigian, image_path 
        FROM gianlan_log 
        WHERE maphong = ? 
        ORDER BY thoigian DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $room);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết gian lận • <?= $tenphong ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #fff;
            margin: 0;
            padding: 0;
        }
        #particles { position:fixed; top:0; left:0; width:100%; height:100%; z-index:0; opacity: 0.6; }

        .top-header {
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 1030;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.15);
        }
        .glow-btn {
            border-radius: 50px;
            padding: 9px 22px;
            font-weight: bold;
            box-shadow: 0 6px 18px rgba(0,0,0,0.4);
            transition: all 0.3s;
        }
        .glow-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.5);
        }

        .header-title {
            font-weight: 800;
            text-shadow: 0 4px 10px rgba(0,0,0,0.3);
            letter-spacing: 1px;
        }
        .card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.25);
            transition: transform 0.3s ease;
            background: rgba(255,255,255,0.97);
            color: #333;
        }
        .card:hover { transform: translateY(-10px); }

        .room-header {
            background: linear-gradient(45deg, #11998e, #38ef7d);
            color: white;
            padding: 2rem;
            text-align: center;
            border-radius: 20px 20px 0 0;
            margin: -1.5rem -1.5rem 1.5rem -1.5rem;
        }
        .cheat-img {
            width: 100%;
            height: 280px;
            object-fit: cover;
            border-radius: 15px;
            border: 4px solid #fff;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        .cheat-img:hover {
            transform: scale(1.05);
            border-color: #dc3545;
        }
        .badge-cheat {
            font-size: 1rem;
            padding: 0.6em 1.2em;
            border-radius: 50px;
            font-weight: 700;
        }
        .student-id {
            font-family: 'Courier New', monospace;
            background: #000;
            color: #0f0;
            padding: 0.4rem 1rem;
            border-radius: 10px;
            font-size: 1.3rem;
            letter-spacing: 3px;
            box-shadow: 0 0 20px rgba(0,255,0,0.5);
            display: inline-block;
        }
        .no-cheat {
            background: linear-gradient(45deg, #11998e, #38ef7d);
            color: white;
            border-radius: 25px;
            padding: 3rem;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 20px 40px rgba(17,153,142,0.4);
        }
        .back-btn {
            border-radius: 50px;
            padding: 0.8rem 2rem;
            font-weight: bold;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .time-text {
            font-size: 0.95rem;
            color: #6c757d;
        }
    </style>
</head>
<body>

<canvas id="particles"></canvas>

<!-- HEADER ĐẸP Y HỆT INDEX.PHP -->
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
                         | <i class="fas fa-door-open text-success"></i> <strong class="text-warning">Phòng <?= htmlspecialchars($canbo_phong) ?></strong>
                    <?php endif; ?>
                </small>
            </div>
            <div class="col-lg-6 text-end">
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <a href="index.php" class="btn btn-light glow-btn btn-sm"><i class="fas fa-home"></i> Trang chủ</a>
                    <a href="stats_by_room.php<?= $canbo_phong ? '?room='.$canbo_phong : '' ?>" class="btn btn-info glow-btn btn-sm text-white"><i class="fas fa-building"></i> Theo phòng thi</a>
                    <a href="stats_detail.php<?= $canbo_phong ? '?room='.$canbo_phong : '' ?>" class="btn btn-warning glow-btn btn-sm text-dark"><i class="fas fa-chart-pie"></i> Thống kê chi tiết</a>
                    <?php if($_SESSION['admin']['username'] === 'admin'): ?>
                    <a href="assign_room.php" class="btn btn-secondary glow-btn btn-sm"><i class="fas fa-users-cog"></i> Phân công phòng</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-danger glow-btn btn-sm"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5" style="position:relative;z-index:10;">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="header-title display-5">
            <i class="fas fa-video fa-beat-fade text-warning me-3"></i>
            CHI TIẾT GIAN LẬN
        </h1>
        <a href="stats_by_room.php<?= $canbo_phong ? '?room='.$canbo_phong : '' ?>" class="btn btn-light back-btn">
            <i class="fas fa-arrow-left me-2"></i> Quay lại danh sách phòng
        </a>
    </div>

    <div class="text-center mb-5">
        <div class="room-header d-inline-block px-5 py-4">
            <h2 class="mb-0">
                <i class="fas fa-school fa-bounce me-3"></i>
                <?= $tenphong ?>
            </h2>
        </div>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="row g-4">
            <?php while ($row = $result->fetch_assoc()): 
                $masv = htmlspecialchars($row['MaSV']);
                $loai = $row['loai_hanhvi'] ?: 'Không xác định';
                $diem = number_format($row['diem_gianlan'], 2);
                $thoigian = date('d/m/Y H:i:s', strtotime($row['thoigian']));
                $img_path = $row['image_path'] ?? '';
                $img_exists = $img_path !== '' && file_exists($img_path);

                $icon = match(true) {
                    str_contains($loai, 'Điện thoại') => 'fa-mobile-alt text-dark',
                    str_contains($loai, 'Trao đổi') => 'fa-comments text-danger',
                    str_contains($loai, 'Phao') => 'fa-scroll text-info',
                    str_contains($loai, 'Quay') || str_contains($loai, 'Nhìn') => 'fa-eye text-warning',
                    default => 'fa-exclamation-triangle text-secondary'
                };
            ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body p-4 text-center">
                            <div class="student-id mb-3"><?= $masv ?></div>
                            <div class="mb-3">
                                <span class="badge badge-cheat bg-danger">
                                    <i class="fas <?= $icon ?> me-2"></i>
                                    <?= htmlspecialchars($loai) ?>
                                </span>
                            </div>
                            <h5 class="text-danger fw-bold mb-3">
                                <i class="fas fa-fire text-warning"></i> Điểm: <?= $diem ?>
                            </h5>
                            <p class="time-text mb-4">
                                <i class="far fa-clock"></i> <?= $thoigian ?>
                            </p>

                            <?php if ($img_exists): ?>
                                <a href="<?= htmlspecialchars($img_path) ?>" target="_blank">
                                    <img src="<?= htmlspecialchars($img_path) ?>" alt="Bằng chứng gian lận" class="cheat-img">
                                </a>
                            <?php else: ?>
                                <div class="bg-secondary rounded p-5 d-flex align-items-center justify-content-center" style="height: 280px;">
                                    <div class="text-center text-white">
                                        <i class="fas fa-image fa-4x mb-3 opacity-50"></i>
                                        <p class="mb-0">Không có ảnh</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center no-cheat mx-auto p-5 rounded-5">
            <i class="fas fa-shield-alt fa-5x mb-4"></i>
            <h2 class="fw-bold">HOÀN HẢO!</h2>
            <p class="fs-4">Không phát hiện hành vi gian lận nào trong phòng này</p>
            <p class="opacity-80">Các thí sinh đang thi rất nghiêm túc!</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles", {
        particles: { 
            number: { value: 60 }, 
            color: { value: ["#ffffff","#a78bfa","#818cf8"] }, 
            opacity: { value: 0.4 }, 
            size: { value: 3 }, 
            line_linked: { enable: false },
            move: { enable: true, speed: 1 }
        },
        interactivity: { events: { onhover: { enable: true, mode: "bubble" } } }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $stmt->close(); ?>