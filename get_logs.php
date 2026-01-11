<?php
// get_logs.php  –  PHIÊN BẢN MỚI 2025 (trả JSON cho modal chi tiết)
header('Content-Type: application/json');
include 'db.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

// Tổng số cảnh báo hôm nay (để hiện badge)
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) FROM gianlan_log WHERE DATE(thoigian) = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$total_today = $stmt->get_result()->fetch_row()[0] ?? 0;

// Lấy log mới nhất
$sql = "SELECT 
            id,
            MaSV,
            diem_gianlan AS diem,
            loai_hanhvi AS loai,
            thoigian,
            maphong,
            image_path
        FROM gianlan_log 
        WHERE image_path IS NOT NULL AND image_path != ''
        ORDER BY thoigian DESC 
        LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    // Định dạng thời gian đẹp như ảnh bạn chụp
    $row['thoigian'] = date('H:i:s d/m/Y', strtotime($row['thoigian']));
    // Nếu không có loại hành vi → để trống hoặc để "Gian lận"
    if(empty(trim($row['loai']))) $row['loai'] = 'Gian lận';
    $logs[] = $row;
}

echo json_encode([
    'total_today' => $total_today,
    'logs'        => $logs
]);

$conn->close();
?>