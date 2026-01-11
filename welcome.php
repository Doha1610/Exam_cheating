<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Nếu đã có phòng rồi → vào thẳng index
if (!empty($_SESSION['admin']['phong_quanly'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['admin']['id'];
$hoten   = $_SESSION['admin']['hoten'];

// Xử lý chọn phòng
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phong = trim($_POST['phong']);
    if ($phong !== '') {
        $stmt = $conn->prepare("UPDATE users SET phong_quanly = ? WHERE id = ?");
        $stmt->bind_param("si", $phong, $user_id);
        $stmt->execute();
        $_SESSION['admin']['phong_quanly'] = $phong;
        header("Location: index.php");
        exit();
    }
}

// LẤY DANH SÁCH PHÒNG TỪ BẢNG PHONGTHI (CHUẨN NHẤT)
$rooms = [];
$result = $conn->query("SELECT maphong, tenphong FROM phongthi ORDER BY maphong");
while ($row = $result->fetch_assoc()) {
    $rooms[] = [
        'ma' => $row['maphong'],
        'ten' => $row['tenphong'] ?: $row['maphong']
    ];
}

// Nếu bảng phòngthi trống thì lấy từ gianlan_log
if (empty($rooms)) {
    $result = $conn->query("SELECT DISTINCT maphong FROM gianlan_log WHERE maphong IS NOT NULL AND maphong != '' ORDER BY maphong");
    while ($row = $result->fetch_assoc()) {
        $rooms[] = ['ma' => $row['maphong'], 'ten' => $row['maphong']];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chọn phòng giám sát</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body{background:linear-gradient(135deg,#667eea,#764ba2);min-height:100vh;display:flex;align-items:center;justify-content:center;margin:0}
        .card{background:rgba(255,255,255,0.97);border-radius:25px;box-shadow:0 25px 60px rgba(0,0,0,0.4);padding:50px;max-width:520px;width:100%;text-align:center}
        select{font-size:1.2rem;padding:18px;border-radius:50px}
        .btn-success{background:linear-gradient(135deg,#28a745,#20c997);border:none;padding:18px;font-size:1.3rem;border-radius:50px}
        .btn-success:hover{transform:translateY(-5px);box-shadow:0 15px 35px rgba(0,0,0,0.3)}
    </style>
</head>
<body>
<div class="card">
    <i class="fas fa-door-open fa-5x text-success mb-4"></i>
    <h2>Xin chào <strong><?= htmlspecialchars($hoten) ?></strong>!</h2>
    <p class="text-muted mb-4">Vui lòng chọn phòng thi bạn phụ trách để vào hệ thống</p>
    
    <form method="POST">
        <div class="mb-4">
            <select name="phong" class="form-select form-select-lg" required>
                <option value="">-- Chọn phòng thi --</option>
                <?php foreach($rooms as $r): ?>
                    <option value="<?= htmlspecialchars($r['ma']) ?>">
                        <?= htmlspecialchars($r['ma']) ?> - <?= htmlspecialchars($r['ten']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-success w-100 text-white">
            <i class="fas fa-arrow-right"></i> Vào hệ thống giám sát
        </button>
    </form>
    
    <small class="text-muted d-block mt-4">
        Lần sau bạn sẽ vào thẳng hệ thống mà không cần chọn lại
    </small>
</div>
</body>
</html>