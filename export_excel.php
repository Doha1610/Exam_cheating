<?php
session_start();
include 'db.php'; // ĐÃ CÓ $conn SẴN Ở ĐÂY

// === BẢO VỆ ĐĂNG NHẬP + LẤY PHÒNG QUẢN LÝ ===
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Lấy phòng quản lý từ session (đã có từ các trang trước)
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

// Nếu không phải admin và chưa có phòng → không cho xuất
if ($canbo_phong === null && $_SESSION['admin']['username'] !== 'admin') {
    die("Bạn chưa được phân công phòng thi. Vui lòng liên hệ Admin.");
}

// === TẠO ĐIỀU KIỆN LỌC THEO PHÒNG ===
$where_phong = "";
$params = [];
$types = "";

if ($canbo_phong && $_SESSION['admin']['username'] !== 'admin') {
    $where_phong = "WHERE gl.maphong = ?";
    $params[] = $canbo_phong;
    $types = "s";
    $ten_phong_file = "_Phong_{$canbo_phong}";
} else {
    $ten_phong_file = "_Tat_Ca_Phong";
}

// === XUẤT FILE EXCEL ===
ob_clean();
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="BaoCao_GianLan' . $ten_phong_file . '_' . date('d-m-Y_His') . '.xls"');
header('Cache-Control: max-age=0');

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Báo cáo gian lận</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #333; padding: 10px; text-align: center; }
        th { background: #4e73df; color: white; font-weight: bold; }
        .title { font-size: 28px; font-weight: bold; text-align: center; background: #e7f3ff; padding: 15px; }
        .sub { font-size: 16px; text-align: center; background: #f8f9fa; padding: 10px; }
        .high { background: #ffb3b3 !important; }
        .med { background: #fff3cd !important; }
        .footer { text-align: right; font-style: italic; margin: 30px 0; }
    </style>
</head>
<body>';

echo '<table>
        <tr><td colspan="10" class="title">BÁO CÁO GIAN LẬN THI CỬ</td></tr>
        <tr><td colspan="10" class="sub">
            Hệ thống Sense AI 2025<br>
            Xuất lúc: ' . date('d/m/Y H:i:s') . '<br>
            Người xuất: <strong>' . htmlspecialchars($_SESSION['admin']['hoten']) . '</strong>
            ' . ($canbo_phong ? '<br>Phòng được xem: <strong>Phòng ' . $canbo_phong . '</strong>' : '(Xem tất cả phòng)') . '
        </td></tr>
      </table>';

echo '<table>
        <tr>
            <th>STT</th>
            <th>Mã SV</th>
            <th>Họ tên SV</th>
            <th>Phòng thi</th>
            <th>Loại hành vi</th>
            <th>Điểm gian lận</th>
            <th>Thời gian phát hiện</th>
            <th>Thời gian quay</th>
            <th>Ảnh bằng chứng</th>
            <th>Trạng thái</th>
        </tr>';

// === TRUY VẤN AN TOÀN ===
$sql = "SELECT 
            gl.MaSV,
            gl.diem_gianlan,
            gl.loai_hanhvi,
            gl.thoigian,
            gl.maphong,
            gl.thoi_gian_quay,
            gl.image_path,
            sv.hoten
        FROM gianlan_log gl
        LEFT JOIN sinhvien sv ON gl.MaSV = sv.MaSV
        $where_phong
        ORDER BY gl.thoigian DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$stt = 1;
$total = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $total++;
        $diem = number_format($row['diem_gianlan'] ?? 0, 2);
        $thoigian = $row['thoigian'] ? date('d/m/Y H:i:s', strtotime($row['thoigian'])) : 'N/A';
        $tg_quay = !empty($row['thoi_gian_quay']) ? date('d/m/Y H:i:s', strtotime($row['thoi_gian_quay'])) : 'N/A';
        $anh = !empty($row['image_path']) ? 'Có ảnh' : 'Không có';
        $hoten = $row['hoten'] ?? 'Chưa có thông tin SV';
        $phong = $row['maphong'] ?? 'N/A';
        $loai = $row['loai_hanhvi'] ?? 'Không xác định';

        $bg = '';
        if (($row['diem_gianlan'] ?? 0) >= 0.9) $bg = 'class="high"';
        elseif (($row['diem_gianlan'] ?? 0) >= 0.7) $bg = 'class="med"';

        echo "<tr $bg>
                <td>$stt</td>
                <td>" . htmlspecialchars($row['MaSV']) . "</td>
                <td>" . htmlspecialchars($hoten) . "</td>
                <td>" . htmlspecialchars($phong) . "</td>
                <td>" . htmlspecialchars($loai) . "</td>
                <td>$diem</td>
                <td>$thoigian</td>
                <td>$tg_quay</td>
                <td>$anh</td>
                <td>Chưa xử lý</td>
              </tr>";
        $stt++;
    }
} else {
    echo '<tr><td colspan="10" style="text-align:center; color:#666; font-style:italic;">
            Chưa có dữ liệu gian lận nào ' . ($canbo_phong ? 'trong phòng này.' : '.') . '
          </td></tr>';
}

echo '</table>';

echo '<div class="footer">
        <strong>Tổng cộng: ' . $total . ' trường hợp vi phạm</strong><br>
        Hệ thống giám sát gian lận Sense AI 2025 – Độ chính xác 90.0%
      </div>';

echo '</body></html>';
exit;
?>