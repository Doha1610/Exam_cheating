<?php 
session_start();
include 'db.php';

// === NGƯỜI ĐÃ ĐĂNG NHẬP RỒI → ĐI ĐÚNG ĐƯỜNG, KHÔNG LOOP ===
if (isset($_SESSION['admin'])) {
    // Nếu là admin → cho vào luôn
    if ($_SESSION['admin']['username'] === 'admin') {
        header("Location: index.php");
        exit();
    }
    // Nếu đã có phòng → vào thẳng index
    if (!empty($_SESSION['admin']['phong_quanly'])) {
        header("Location: index.php");
        exit();
    }
    // Nếu chưa có phòng → đẩy sang welcome chọn phòng (chỉ hiện 1 lần duy nhất)
    header("Location: welcome.php");
    exit();
}

// === XỬ LÝ ĐĂNG NHẬP ===
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, username, hoten, phong_quanly FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        // Lưu đầy đủ thông tin vào session
        $_SESSION['admin'] = [
            'id'            => $row['id'],
            'username'      => $row['username'],
            'hoten'         => $row['hoten'],
            'phong_quanly'  => $row['phong_quanly'] ?? null
        ];

        // === ĐIỀU HƯỚNG ĐÚNG SAU KHI ĐĂNG NHẬP ===
        if ($row['username'] === 'admin') {
            header("Location: index.php");
            exit();
        }
        if (!empty($row['phong_quanly'])) {
            header("Location: index.php");
            exit();
        }
        // Chưa có phòng → lần đầu phải chọn
        header("Location: welcome.php");
        exit();

    } else {
        $error = "Sai tên đăng nhập hoặc mật khẩu!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Giám sát gian lận 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            margin: 0; padding: 0;
            overflow: hidden;
        }
        canvas#particles { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; opacity: 0.6; }

        .login-container {
            position: relative; z-index: 10;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; padding: 20px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 25px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
            padding: 50px 40px;
            width: 100%; max-width: 460px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.3);
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-15px)} }

        .login-header i {
            font-size: 4.5rem;
            color: #667eea;
            margin-bottom: 15px;
            text-shadow: 0 10px 20px rgba(102,126,234,0.4);
        }
        .login-header h1 {
            background: linear-gradient(135deg,#667eea,#764ba2);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
            font-weight:900;
            font-size:2.4rem;
            margin:0;
        }
        .login-header p { color:#666; font-size:1.1rem; margin-top:8px; }

        .form-control { border-radius:50px; padding:14px 20px; font-size:1.05rem; border:2px solid #e0e0e0; transition:all .3s; }
        .form-control:focus { border-color:#667eea; box-shadow:0 0 0 .25rem rgba(102,126,234,.25); }
        .input-group-text { border-radius:50px 0 0 50px; background:linear-gradient(135deg,#667eea,#764ba2); color:white; border:none; }

        .btn-login {
            border-radius:50px; padding:14px; font-weight:bold; font-size:1.1rem;
            background:linear-gradient(135deg,#667eea,#764ba2); border:none;
            box-shadow:0 10px 30px rgba(102,126,234,.4); transition:all .4s;
        }
        .btn-login:hover { transform:translateY(-5px); box-shadow:0 20px 40px rgba(102,126,234,.6); }

        .alert { border-radius:15px; padding:15px; text-align:center; font-weight:bold; }

        .register-link {
            text-align: center;
            margin-top: 28px;
            font-size: 1.05rem;
        }
        .register-link a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
            padding: 10px 25px;
            border-radius: 50px;
            background: rgba(102,126,234,0.1);
            transition: all 0.3s;
        }
        .register-link a:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.4);
        }

        .footer-text { text-align:center; margin-top:30px; color:rgba(255,255,255,0.8); font-size:0.95rem; }
    </style>
</head>
<body>

<canvas id="particles"></canvas>

<div class="login-container">
    <div class="login-card">
        <div class="login-header text-center mb-4">
            <i class="fas fa-eye text-warning" style="font-size: 5rem; text-shadow: 0 10px 25px rgba(255,193,7,0.4);"></i>
            <h1>GIÁM SÁT 2025</h1>
            <p>Hệ thống giám sát gian lận thi cử</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập" required autocomplete="off">
                </div>
            </div>
            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-login">
                <i class="fas fa-sign-in-alt"></i> ĐĂNG NHẬP NGAY
            </button>
        </form>

        <!-- DÒNG BẠN MUỐN – ĐÃ CÓ LẠI VÀ ĐẸP LUNG LINH -->
        <div class="register-link">
            <i class="fas fa-user-plus"></i> Chưa có tài khoản? 
            <a href="register.php">Đăng ký ngay</a>
        </div>

        <div class="footer-text">
            <small>© 2025 Hệ thống giám sát thi cử - Bảo mật tối cao</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles", {
        "particles": {
            "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
            "color": { "value": ["#ffffff", "#c4b5fd", "#93c5fd"] },
            "shape": { "type": "circle" },
            "opacity": { "value": 0.5, "random": true },
            "size": { "value": 3, "random": true },
            "line_linked": { "enable": false },
            "move": { "enable": true, "speed": 1.5, "direction": "none", "random": false, "straight": false, "out_mode": "out" }
        },
        "interactivity": {
            "detect_on": "canvas",
            "events": {
                "onhover": { "enable": true, "mode": "bubble" },
                "onclick": { "enable": true, "mode": "repulse" },
                "resize": true
            }
        },
        "retina_detect": true
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>