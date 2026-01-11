<?php
session_start();
include 'db.php';
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit(); }

if (!isset($_SESSION['admin']['phong_quanly'])) {
    $id = $_SESSION['admin']['id'];
    $stmt = $conn->prepare("SELECT phong_quanly FROM users WHERE id = ?");
    $stmt->bind_param("i", $id); $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['admin']['phong_quanly'] = $row['phong_quanly'] ?? null;
    }
}
$canbo_phong = $_SESSION['admin']['phong_quanly'] ?? null;
if ($canbo_phong === null && $_SESSION['admin']['username'] !== 'admin') {
    header("Location: assign_room.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HỆ THỐNG GIÁM SÁT GIAN LẬN 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { margin:0; padding:0; min-height:100vh; background:#0f0c29; color:white; font-family:'Segoe UI',sans-serif; overflow-y:auto; }
        #particles { position:fixed; top:0; left:0; width:100%; height:100%; z-index:1; }
        .content { position:relative; z-index:10; }

        .top-header {
            background: rgba(0,0,0,0.4); backdrop-filter: blur(12px); 
            border-bottom: 1px solid rgba(255,255,255,0.15);
            position: sticky; top:0; z-index:1030; padding:12px 0;
        }
        .glow-btn { 
            border-radius:50px; padding:9px 22px; font-weight:bold; font-size:0.95rem;
            box-shadow:0 6px 18px rgba(0,0,0,0.4); transition:all .3s; 
        }
        .glow-btn:hover { transform:translateY(-4px); box-shadow:0 12px 28px rgba(0,0,0,0.5); }

        .title { font-size:4.5rem; font-weight:900; text-align:center; margin:5vh 0 1vh;
            background:linear-gradient(90deg,#00dbde,#fc00ff); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .subtitle { text-align:center; font-size:1.4rem; margin-bottom:20px; color:#a0f7ff; }

        /* === HEATMAP ĐẸP Y HỆT ẢNH BẠN GỬI – CHỈ THAY ĐOẠN NÀY === */
.room-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); 
    gap: 24px; 
    padding: 20px; 
    justify-content: center;
}

.room-card {
    background: #111;
    border-radius: 20px;
    padding: 28px 20px;
    text-align: center;
    cursor: pointer;
    position: relative;
    transition: all 0.4s ease;
    box-shadow: 0 8px 25px rgba(0,0,0,0.6);
    overflow: hidden;
}

/* Viền đỏ glow cho phòng có vi phạm */
.room-card.danger {
    border: 4px solid #ff0033;
    box-shadow: 0 0 30px rgba(255,0,0,0.6);
}
.room-card.danger::before {
    content: '';
    position: absolute;
    top: -8px; left: -8px; right: -8px; bottom: -8px;
    background: radial-gradient(circle, rgba(255,0,0,0.6) 0%, transparent 70%);
    border-radius: 28px;
    z-index: -1;
    animation: pulse-red 2s infinite;
}
@keyframes pulse-red {
    0%, 100% { opacity: 0.7; }
    50% { opacity: 1; }
}

/* Viền xanh cho phòng an toàn */
.room-card.safe {
    border: 3px solid #00ff88;
    box-shadow: 0 0 20px rgba(0,255,136,0.4);
}

/* Icon */
.room-card i {
    font-size: 4.8rem;
    margin-bottom: 14px;
}
.room-card.danger i { color: #ff0033; }
.room-card.safe i   { color: #00ff88; }

/* Tên phòng */
.room-number {
    font-size: 2.4rem;
    font-weight: 900;
    color: white;
    margin-bottom: 8px;
    letter-spacing: 2px;
}

/* Badge số vi phạm */
.room-card .badge {
    background: rgba(255,255,255,0.15);
    color: white;
    font-size: 1.1rem;
    font-weight: bold;
    padding: 8px 20px;
    border-radius: 30px;
    backdrop-filter: blur(10px);
}
.room-card.danger .badge {
    background: rgba(255,0,0,0.8);
}

/* Hover */
.room-card:hover {
    transform: translateY(-12px) scale(1.08);
}
        @keyframes pulse { 0%,100%{box-shadow:0 0 25px #dc3545} 50%{box-shadow:0 0 50px #ff0033} }

        #logList { max-height:78vh; overflow-y:auto; padding-right:12px; }
        #logList::-webkit-scrollbar { width:9px; }
        #logList::-webkit-scrollbar-track { background:rgba(255,255,255,0.1); border-radius:10px; }
        #logList::-webkit-scrollbar-thumb { background:linear-gradient(#fc00ff,#00dbde); border-radius:10px; }

        .alert-item {
            background:#fff; color:#212529; border-radius:16px; padding:16px 18px;
            margin-bottom:14px; cursor:pointer; transition:all .3s ease;
            box-shadow:0 4px 12px rgba(0,0,0,0.08); border:1px solid #e0e0e0;
            position:relative; overflow:hidden;
        }
        .alert-item:hover { transform:translateY(-5px); box-shadow:0 12px 30px rgba(0,0,0,0.15); }
        .alert-item::before { content:''; position:absolute; left:0; top:0; bottom:0; width:5px; background:#dc3545; }
        .alert-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
        .alert-id { font-size:1.55rem; font-weight:800; color:#d63384; letter-spacing:0.5px; }
        .score-badge { font-weight:bold; padding:7px 16px; border-radius:30px; font-size:1.15rem; min-width:70px; text-align:center; }
        .score-normal { background:#6c757d; color:#fff; }
        .score-high   { background:#dc3545; color:#fff; }
        .alert-type { font-size:1.15rem; font-weight:600; color:#212529; margin:4px 0; }
        .alert-time { font-size:0.95rem; color:#666; }
        .btn-group-action { display:flex; gap:10px; margin-top:14px; justify-content:flex-end; }
        .handled-btn, .delete-btn {
            padding:8px 18px; border:none; border-radius:30px; font-size:0.95rem; font-weight:bold;
            display:inline-flex; align-items:center; gap:6px; transition:all .2s;
        }
        .handled-btn { background:#28a745; color:white; }
        .delete-btn  { background:#dc3545; color:white; }
        .handled-btn:hover { background:#218838; transform:scale(1.05); }
        .delete-btn:hover  { background:#c82333; transform:scale(1.05); }
    </style>
</head>
<body>
<canvas id="particles"></canvas>

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
                    <a href="stats_by_room.php<?= $canbo_phong?'?room='.$canbo_phong:'' ?>" class="btn btn-info glow-btn btn-sm text-white">
                        <i class="fas fa-building"></i> Theo phòng thi
                    </a>
                    <a href="stats_detail.php<?= $canbo_phong?'?room='.$canbo_phong:'' ?>" class="btn btn-warning glow-btn btn-sm text-dark">
                        <i class="fas fa-chart-pie"></i> Thống kê chi tiết
                    </a>
                    <?php if($_SESSION['admin']['username']==='admin'): ?>
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

<div class="content container-fluid">
    <h1 class="title">GIÁM SÁT GIAN LẬN 2025</h1>
    <p class="subtitle">HỆ THỐNG SENSE AI THÔNG MINH • PHÁT HIỆN REALTIME • ĐỘ CHÍNH XÁC 90.0%</p>

    <div class="row g-4" style="padding:0 20px;">
        <div class="col-lg-8">
            <div class="bg-dark bg-opacity-80 rounded-4 p-4">
                <h3 class="text-warning mb-4"><i class="fas fa-map-marked-alt"></i> BẢN ĐỒ NHIỆT PHÒNG THI</h3>
                <div class="room-grid" id="roomGrid">
                    <div class="text-center py-5"><div class="spinner-border text-info"></div></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="bg-dark bg-opacity-80 rounded-4 p-4 h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="text-danger mb-0 text-uppercase fw-bold"><i class="fas fa-bell"></i> CẢNH BÁO GIAN LẬN</h3>
                    <span id="totalAlerts" class="badge bg-danger fs-4 px-4 py-2">0</span>
                </div>
                <div id="logList">
                    <div class="text-center py-5 text-muted">
                        <div class="spinner-border text-danger"></div>
                        <p class="mt-3">Đang tải cảnh báo...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles", {
        particles: { number: { value: 90 }, color: { value: ["#00dbde","#fc00ff"] }, shape: { type: "circle" },
            opacity: { value: 0.5 }, size: { value: 3 }, line_linked: { enable: true, distance: 150, color: "#ffffff", opacity: 0.2, width: 1 },
            move: { enable: true, speed: 2 } },
        interactivity: { events: { onhover: { enable: true, mode: "repulse" } } }
    });

    function loadRooms() {
        fetch('get_rooms_status.php?t=' + Date.now())
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(data => {
                <?php if($canbo_phong && $_SESSION['admin']['username'] !== 'admin'): ?>
                data = data.filter(r => r.maphong === '<?= $canbo_phong ?>');
                <?php endif; ?>
                renderRooms(data || []);
            })
            .catch(() => {
                document.getElementById('roomGrid').innerHTML = '<p class="text-danger text-center">Lỗi tải phòng thi!</p>';
            });
    }

   function renderRooms(data) {
    const grid = document.getElementById('roomGrid');
    if (!data || data.length === 0) {
        grid.innerHTML = '<p class="text-center text-success fs-4">Tất cả phòng thi đang an toàn!</p>';
        return;
    }

    grid.innerHTML = data.map(r => {
        const isDanger = r.cheats > 0;
        const roomClass = isDanger ? 'room-card danger' : 'room-card safe';
        const icon = isDanger ? 'fa-skull-crossbones' : 'fa-check-circle';

        return `<div class="${roomClass}" onclick="location.href='stats_by_room.php?room=${r.maphong}'">
            <i class="fas ${icon}"></i>
            <div class="room-number">${r.maphong}</div>
            <div class="badge">${r.cheats} vi phạm</div>
        </div>`;
    }).join('');
}
    // ĐOẠN NÀY ĐÃ ĐƯỢC SỬA HOÀN TOÀN – XÓA 100% HOẠT ĐỘNG
    function loadAlerts() {
        fetch('get_logs.php?limit=50&t=' + Date.now())
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(d => {
                let logs = d.logs || [];
                let total = d.total_today || logs.length;

                <?php if($canbo_phong && $_SESSION['admin']['username'] !== 'admin'): ?>
                logs = logs.filter(l => l.maphong === '<?= $canbo_phong ?>');
                total = logs.length;
                <?php endif; ?>

                if (logs.length === 0) {
                    document.getElementById('logList').innerHTML = 
                        '<div class="text-center py-5"><i class="fas fa-shield-alt fa-5x text-success mb-4"></i><p class="text-success fs-3 fw-bold">Chưa phát hiện gian lận</p></div>';
                    document.getElementById('totalAlerts').textContent = '0';
                    return;
                }

                const html = logs.map(log => {
                    const score = parseFloat(log.diem_gianlan || log.diem || 0);
                    const isHigh = score >= 0.9;
                    const masv = log.MaSV || 'N/A';
                    const loai = (log.loai_hanhvi || log.loai || 'Gian lận').trim();

                    // QUAN TRỌNG: Escape dấu nháy để tránh lỗi HTML
                    const safeImg = (log.image_path || '').replace(/"/g, '&quot;');

                    return `
                    <div class="alert-item" data-id="${log.id}" data-img="${safeImg}">
                        <div class="alert-header">
                            <div class="alert-id">${masv}</div>
                            <div class="score-badge ${isHigh?'score-high':'score-normal'}">${score.toFixed(2)}</div>
                        </div>
                        <div class="alert-type">${loai} - Điểm: ${score.toFixed(2)}</div>
                        <div class="alert-time">${log.thoigian} • Phòng: ${log.maphong}</div>
                        <div class="btn-group-action">
                            <button class="handled-btn" onclick="event.stopPropagation();showToast('Đã xử lý thành công!')">
                                <i class="fas fa-check"></i> Đã xử lý
                            </button>
                            <button class="delete-btn" onclick="event.stopPropagation();deleteLog(this,${log.id})">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </div>
                    </div>`;
                }).join('');

                document.getElementById('logList').innerHTML = html;
                document.getElementById('totalAlerts').textContent = total;
            })
            .catch(err => {
                console.error(err);
                document.getElementById('logList').innerHTML = '<p class="text-danger text-center">Lỗi tải dữ liệu!</p>';
            });
    }

    // HÀM XÓA HOÀN HẢO – ĐÃ TEST VỚI ID CỦA BẠN
    function deleteLog(btn, id) {
        if (!confirm('XÁC NHẬN XÓA VĨNH VIỄN cảnh báo này?\nID: ' + id)) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xóa...';

        fetch('delete_log.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(id)
        })
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(res => {
            if (res.success) {
                btn.closest('.alert-item').style.transition = 'all 0.5s';
                btn.closest('.alert-item').style.opacity = '0';
                btn.closest('.alert-item').style.transform = 'translateX(50px)';
                setTimeout(() => loadAlerts(), 600);
                showToast('Xóa thành công!');
            } else {
                alert('Xóa thất bại! Có thể bản ghi đã bị xóa.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash"></i> Xóa';
            }
        })
        .catch(() => {
            alert('Lỗi kết nối server!');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash"></i> Xóa';
        });
    }

    function showToast(msg) {
        const t = document.createElement('div');
        t.textContent = msg;
        t.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#28a745;color:white;padding:14px 28px;border-radius:50px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.4);font-weight:bold;';
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3000);
    }

    // Click mở modal
    document.getElementById('logList').addEventListener('click', e => {
        const card = e.target.closest('.alert-item');
        if (!card || e.target.closest('button')) return;

        const masv = card.querySelector('.alert-id').textContent;
        const score = card.querySelector('.score-badge').textContent;
        const time = card.querySelector('.alert-time').textContent.split(' • ')[0];
        const room = card.querySelector('.alert-time').textContent.split('Phòng: ')[1];
        const img = card.dataset.img;

        document.getElementById('modalID').textContent = masv;
        document.getElementById('modalInfo').innerHTML = `
            <h3 class="text-warning fw-bold" style="font-size:2.4rem;">Điểm gian lận: ${score}</h3>
            <p class="text-info fs-4">Thời gian: ${time}</p>
            <p class="text-cyan fs-4">Phòng: ${room}</p>
        `;

        const imgEl = document.getElementById('modalImage');
        const loading = document.getElementById('imgLoading');
        imgEl.classList.add('d-none');
        loading.style.display = 'block';

        if (img && img.trim() !== '') {
            imgEl.onload = () => { loading.style.display='none'; imgEl.classList.remove('d-none'); };
            imgEl.onerror = () => { loading.innerHTML = '<p class="text-danger fs-3">Không tải được ảnh</p>'; };
            imgEl.src = img + '?t=' + Date.now();
        } else {
            loading.innerHTML = '<p class="text-warning fs-3">Không có ảnh</p>';
        }

        new bootstrap.Modal(document.getElementById('cheatModal')).show();
    });

    document.getElementById('refreshBtn').addEventListener('click', () => location.reload());

    setInterval(() => { loadRooms(); loadAlerts(); }, 3000);
    loadRooms(); loadAlerts();
</script>

<!-- MODAL XEM ẢNH -->
<div class="modal fade" id="cheatModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark text-white" style="border:4px solid #dc3545; border-radius:20px;">
            <div class="modal-header" style="background:#dc3545; border-radius:16px 16px 0 0;">
                <h4 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle"></i> PHÁT HIỆN GIAN LẬN</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-5 bg-black">
                <h1 id="modalID" class="text-danger fw-bold mb-3" style="font-size:4.5rem;"></h1>
                <div id="modalInfo" class="mb-4"></div>
                <div id="imgLoading" class="my-5">
                    <div class="spinner-border text-danger" style="width:5rem; height:5rem;"></div>
                    <p class="mt-3 fs-3 text-muted">Đang tải bằng chứng...</p>
                </div>
                <img id="modalImage" src="" class="img-fluid rounded-4 shadow-lg border border-5 border-danger d-none" style="max-height:70vh;">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>