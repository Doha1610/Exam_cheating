<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$conn = new mysqli("localhost", "root", "", "quanlythi");  // user + pass để trống nếu bạn dùng root không mật khẩu
if ($conn->connect_error) {
    die(json_encode([]));
}

// LẤY TẤT CẢ PHÒNG THI TỪ BẢNG phongthi (loại bỏ Unknown + rác)
$valid_rooms = [];
$result = $conn->query("
    SELECT DISTINCT TRIM(maphong) AS maphong 
    FROM phongthi 
    WHERE maphong IS NOT NULL 
      AND TRIM(maphong) != '' 
      AND TRIM(maphong) != 'Unknown'
    ORDER BY maphong
");

while ($row = $result->fetch_assoc()) {
    $valid_rooms[] = $row['maphong'];
}

// Nếu bảng phongthi bị lỗi hoặc trống → lấy từ gianlan_log (dự phòng cực mạnh)
if (empty($valid_rooms)) {
    $result = $conn->query("SELECT DISTINCT maphong FROM gianlan_log ORDER BY maphong");
    while ($row = $result->fetch_assoc()) {
        $mp = trim($row['maphong']);
        if ($mp != '' && $mp != 'Unknown') {
            $valid_rooms[] = $mp;
        }
    }
}

// Đếm gian lận hôm nay
$cheats = [];
$result = $conn->query("
    SELECT maphong, COUNT(*) AS cnt 
    FROM gianlan_log 
    WHERE DATE(thoigian) = CURDATE() 
    GROUP BY maphong
");
while ($row = $result->fetch_assoc()) {
    $cheats[$row['maphong']] = (int)$row['cnt'];
}

// Tạo danh sách cuối cùng
$final = [];
foreach ($valid_rooms as $mp) {
    $final[] = [
        'maphong' => $mp,
        'cheats'  => $cheats[$mp] ?? 0
    ];
}

echo json_encode($final);
?>