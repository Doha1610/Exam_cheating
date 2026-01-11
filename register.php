<?php
session_start();
include 'db.php';

if (isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hoten    = trim($_POST['hoten']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);   // lưu thường, không băm

    // 1. Kiểm tra độ dài
    if (strlen($username) < 3) {
        $msg = "<div class='alert alert-warning text-center'>Tên đăng nhập phải ít nhất 3 ký tự!</div>";
    }
    // 2. Kiểm tra trùng username
    elseif ($stmt = $conn->prepare("SELECT id FROM users WHERE username = ?")) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $msg = "<div class='alert alert-warning text-center'>Tên đăng nhập đã có người sử dụng!</div>";
        }
        $stmt->close();
    }
    // 3. Nếu mọi thứ OK → insert
    if ($msg === "") {   // ← quan trọng: chỉ insert khi $msg rỗng
        $stmt = $conn->prepare("INSERT INTO users (username, password, hoten) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $hoten);
        
        if ($stmt->execute()) {
            $msg = "<div class='alert alert-success text-center p-4 rounded-4 shadow-lg'>
                        <i class='fas fa-check-circle fa-3x mb-3 text-success'></i><br>
                        <h4>Đăng ký thành công!</h4>
                        <p>Chuyển đến trang đăng nhập trong <strong id='countdown'>3</strong> giây...</p>
                    </div>
                    <script>
                        let t=3; setInterval(()=>{if(t>0){document.getElementById('countdown').innerText=t--;}else{location='login.php';}},1000);
                    </script>";
        } else {
            $msg = "<div class='alert alert-danger text-center'>Lỗi hệ thống, thử lại sau!</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản - Giám sát gian lận 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; overflow: hidden; }
        canvas#particles { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; opacity: 0.6; }

        .register-container { position: relative; z-index: 10; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .register-card { background: rgba(255,255,255,0.97); border-radius: 25px; box-shadow: 0 25px 60px rgba(0,0,0,0.4); padding: 50px 45px; max-width: 480px; backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.3); animation: float 6s ease-in-out infinite; }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-15px)} }

        .register-header i { font-size: 4.8rem; color: #667eea; margin-bottom: 15px; text-shadow: 0 10px 20px rgba(102,126,234,0.4); }
        .register-header h1 { background: linear-gradient(135deg,#667eea,#764ba2); -webkit-background-clip:text; -webkit-text-fill-color:transparent; font-weight:900; font-size:2.5rem; }
        .register-header p { color:#666; font-size:1.1rem; margin-top:10px; }

        .form-control { border-radius:50px; padding:14px 20px; font-size:1.05rem; border:2px solid #e0e0e0; transition:all .3s; }
        .form-control:focus { border-color:#667eea; box-shadow:0 0 0 .25rem rgba(102,126,234,.25); }
        .input-group-text { border-radius:50px 0 0 50px; background:linear-gradient(135deg,#667eea,#764ba2); color:white; border:none; font-size:1.1rem; }

        .btn-register { border-radius:50px; padding:16px; font-weight:bold; font-size:1.2rem; background:linear-gradient(135deg,#667eea,#764ba2); border:none; box-shadow:0 10px 30px rgba(102,126,234,.4); transition:all .4s; }
        .btn-register:hover { transform:translateY(-6px); box-shadow:0 20px 40px rgba(102,126,234,.6); }

        .alert { border-radius:20px; padding:25px; text-align:center; font-weight:bold; margin:20px 0; }
        .login-link a { color:#667eea; font-weight:600; text-decoration:none; padding:12px 28px; border-radius:50px; background:rgba(102,126,234,0.1); transition:all .3s; }
        .login-link a:hover { background:#667eea; color:white; transform:translateY(-3px); box-shadow:0 10px 25px rgba(102,126,234,0.4); }
    </style>
</head>
<body>

<canvas id="particles"></canvas>

<div class="register-container">
    <div class="register-card">
        <div class="register-header text-center mb-4">
            <i class="fas fa-user-plus"></i>
            <h1>ĐĂNG KÝ TÀI KHOẢN</h1>
            <p>Hệ thống giám sát gian lận thi cử 2025</p>
        </div>

        <?= $msg ?>

        <?php if (!$msg || strpos($msg, 'thành công') === false): ?>
        <form method="POST">
            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="hoten" class="form-control" placeholder="Họ và tên" required>
                </div>
            </div>

            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập (tối thiểu 3 ký tự)" required minlength="3">
                </div>
            </div>

            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required minlength="4">
                </div>
            </div>

            <button type="submit" class="btn btn-success w-100 btn-register">
                TẠO TÀI KHOẢN NGAY
            </button>
        </form>

        <div class="login-link text-center mt-4">
            Đã có tài khoản? 
            <a href="login.php">Đăng nhập tại đây</a>
        </div>
        <?php endif; ?>

        <div class="text-center mt-4 text-white opacity-80">
            <small>© 2025 Hệ thống giám sát thi cử</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles", { 
        "particles": { "number": { "value": 80 }, "color": { "value": ["#ffffff","#c4b5fd","#93c5fd"] },
            "opacity": { "value": 0.5, "random": true }, "size": { "value": 3, "random": true },
            "move": { "enable": true, "speed": 1.5 } },
        "interactivity": { "events": { "onhover": { "enable": true, "mode": "bubble" } } }
    });

    // Thông báo required tiếng Việt đẹp
    document.querySelectorAll('input[required]').forEach(input => {
        input.addEventListener('invalid', e => {
            e.preventDefault();
            if (!e.target.value.trim()) {
                if (e.target.name === 'username') e.target.setCustomValidity('Nhập tên đăng nhập (tối thiểu 3 ký tự)');
                else if (e.target.name === 'password') e.target.setCustomValidity('Nhập mật khẩu');
                else if (e.target.name === 'hoten') e.target.setCustomValidity('Nhập họ tên');
            }
        });
        input.addEventListener('input', () => input.setCustomValidity(''));
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>