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
}

// CHỈ ADMIN ĐƯỢC VÀO
if ($_SESSION['admin']['username'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$msg = '';

// ==================== THÊM CÁN BỘ ====================
if (isset($_POST['add_user'])) {
    $hoten    = trim($_POST['hoten']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password']);

    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $msg = '<div class="alert-danger">Tên đăng nhập đã tồn tại!</div>';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (hoten, username, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $hoten, $username, $hash);
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">Thêm cán bộ thành công!</div>';
        }
    }
}

// ==================== SỬA CÁN BỘ ====================
if (isset($_POST['edit_user'])) {
    $id       = (int)$_POST['edit_id'];
    $hoten    = trim($_POST['edit_hoten']);
    $username = trim($_POST['edit_username']);
    $password = trim($_POST['edit_password']);

    if ($password === '') {
        // chỉ sửa tên + username
        $stmt = $conn->prepare("UPDATE users SET hoten = ?, username = ? WHERE id = ?");
        $stmt->bind_param("ssi", $hoten, $username, $id);
    } else {
        // có đổi mật khẩu
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET hoten = ?, username = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssi", $hoten, $username, $hash, $id);
    }
    if ($stmt->execute()) {
        $msg = '<div class="alert alert-success">Cập nhật thông tin thành công!</div>';
    } else {
        $msg = '<div class="alert alert-danger">Lỗi khi cập nhật!</div>';
    }
}

// ==================== XÓA CÁN BỘ ====================
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id !== $_SESSION['admin']['id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $msg = '<div class="alert alert-success">Xóa cán bộ thành công!</div>';
    }
}

// ==================== PHÂN CÔNG PHÒNG (GIỮ NGUYÊN) ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $phong   = $_POST['phong'] === '' ? null : trim($_POST['phong']);

    $stmt = $conn->prepare("UPDATE users SET phong_quanly = ? WHERE id = ?");
    $stmt->bind_param("si", $phong, $user_id);
    if ($stmt->execute()) {
        $msg = '<div class="alert alert-success">Phân công phòng thành công!</div>';
        if ($user_id == $_SESSION['admin']['id']) {
            $_SESSION['admin']['phong_quanly'] = $phong;
        }
    }
}

// Lấy dữ liệu
$users = $conn->query("SELECT id, username, hoten, phong_quanly FROM users ORDER BY id");
$phong_list = $conn->query("SELECT DISTINCT maphong FROM phongthi ORDER BY maphong");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công phòng thi - Hệ thống giám sát 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            min-height: 100vh; 
            color: white; 
            font-family: 'Segoe UI', sans-serif;
        }
        .top-header {
            background: rgba(0,0,0,0.4); 
            backdrop-filter: blur(12px); 
            border-bottom: 1px solid rgba(255,255,255,0.15);
            position: sticky; top: 0; z-index: 1030; padding: 12px 0;
        }
        .glow-btn {
            border-radius: 50px; padding: 9px 20px; font-weight: bold; font-size: 0.95rem;
            box-shadow: 0 6px 18px rgba(0,0,0,0.4); transition: all 0.3s;
        }
        .glow-btn:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0,0,0,0.5); }
        .card {
            background: rgba(255,255,255,0.97); 
            color: #333; 
            border-radius: 20px; 
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        .table thead { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
    </style>
</head>
<body>

<!-- HEADER GIỮ NGUYÊN -->
<div class="top-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 text-white">
                <h3 class="mb-0 fw-bold">
                    <i class="fas fa-eye text-warning"></i> GIÁM SÁT GIAN LẬN 2025
                </h3>
                <small class="opacity-80">
                    <i class="fas fa-user-tie"></i> <?= htmlspecialchars($_SESSION['admin']['hoten']) ?>
                </small>
            </div>
            <div class="col-lg-6 text-end">
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <a href="index.php" class="btn btn-light glow-btn btn-sm"><i class="fas fa-home"></i> Trang chủ</a>
                    <a href="stats_by_room.php" class="btn btn-info glow-btn btn-sm text-white"><i class="fas fa-building"></i> Theo phòng thi</a>
                    <a href="stats_detail.php" class="btn btn-warning glow-btn btn-sm text-dark"><i class="fas fa-chart-pie"></i> Thống kê chi tiết</a>
                    <button id="refreshBtn" class="btn btn-success glow-btn btn-sm position-relative">
                        <span class="btn-text"><i class="fas fa-sync-alt"></i> Làm mới</span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                    </button>
                    <a href="logout.php" class="btn btn-danger glow-btn btn-sm"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="card p-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary text-center mb-4 fw-bold">
            <i class="fas fa-users-cog fa-2x"></i><br>
            QUẢN LÝ CÁN BỘ & PHÂN CÔNG PHÒNG
        </h2>
            <button class="btn btn-success glow-btn" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-user-plus"></i> Thêm cán bộ
            </button>
        </div>

        <?= $msg ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Họ tên</th>
                        <th>Tài khoản</th>
                        <th>Phòng quản lý</th>
                        <th>Phân công phòng</th>
                        <th width="180">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-bold"><?= $u['id'] ?></td>
                        <td><strong><?= htmlspecialchars($u['hoten']) ?></strong></td>
                        <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                        <td>
                            <?php if($u['phong_quanly']): ?>
                                <span class="badge bg-success fs-5 px-3">Phòng <?= htmlspecialchars($u['phong_quanly']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Chưa phân công</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="phong" class="form-select form-select-sm">
                                    <option value="">-- Chọn phòng --</option>
                                    <?php 
                                    $phong_list->data_seek(0);
                                    while($p = $phong_list->fetch_assoc()): 
                                        $sel = ($u['phong_quanly'] == $p['maphong']) ? 'selected' : '';
                                    ?>
                                        <option value="<?= $p['maphong'] ?>" <?= $sel ?>>Phòng <?= $p['maphong'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
                            </form>
                        </td>
                        <td class="text-center">
                            <!-- NÚT SỬA -->
                            <button class="btn btn-warning btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $u['id'] ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <!-- NÚT XÓA -->
                            <?php if($u['username'] !== 'admin'): ?>
                            <a href="?delete=<?= $u['id'] ?>" 
                               onclick="return confirm('Xóa cán bộ <?= addslashes(htmlspecialchars($u['hoten'])) ?>?')"
                               class="btn btn-danger btn-sm">
                               <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- MODAL SỬA -->
                    <div class="modal fade" id="editModal<?= $u['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST">
                                <div class="modal-content">
                                    <div class="modal-header bg-warning text-dark">
                                        <h5 class="modal-title">Sửa thông tin cán bộ</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="edit_id" value="<?= $u['id'] ?>">
                                        <div class="mb-3">
                                            <label>Họ và tên</label>
                                            <input type="text" name="edit_hoten" class="form-control" value="<?= htmlspecialchars($u['hoten']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Tên đăng nhập</label>
                                            <input type="text" name="edit_username" class="form-control" value="<?= htmlspecialchars($u['username']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Mật khẩu mới (để trống nếu không đổi)</label>
                                            <input type="password" name="edit_password" class="form-control">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                        <button type="submit" name="edit_user" class="btn btn-warning">Lưu thay đổi</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL THÊM CÁN BỘ -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Thêm cán bộ mới</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><input type="text" name="hoten" class="form-control" placeholder="Họ và tên" required></div>
                    <div class="mb-3"><input type="text" name="username" class="form-control" placeholder="Tên đăng nhập" required></div>
                    <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Mật khẩu" required></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="add_user" class="btn btn-success">Thêm cán bộ</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('refreshBtn').addEventListener('click', function() {
    const btn = this;
    const text = btn.querySelector('.btn-text');
    const spinner = btn.querySelector('.spinner-border');
    btn.disabled = true;
    text.innerHTML = 'Đang tải...';
    spinner.classList.remove('d-none');
    setTimeout(() => location.reload(), 600);
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>