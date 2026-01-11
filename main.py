import os
import cv2
import mediapipe as mp
from deepface import DeepFace
import mysql.connector
import numpy as np
from datetime import datetime
import json
from ultralytics import YOLO

# === TẠO THƯ MỤC ẢNH ===
os.makedirs("gianlan/cheat_images", exist_ok=True)

# === TẮT CẢNH BÁO TENSORFLOW ===
os.environ["TF_ENABLE_ONEDNN_OPTS"] = "0"
os.environ["TF_USE_LEGACY_KERAS"] = "1"

# === TẢI MÔ HÌNH YOLO ===
try:
    model_yolo = YOLO("best (17).pt")
    print("[OK] Đã tải mô hình YOLO: best (17).pt")
except Exception as e:
    print(f"[LỖI] Không tải được best (17).pt: {e}")
    exit()

# === KẾT NỐI MySQL ===
try:
    db = mysql.connector.connect(
        host="localhost", user="root", password="", database="quanlythi"
    )
    cursor = db.cursor()
    print("[OK] Kết nối CSDL thành công!")
except Exception as e:
    print(f"[LỖI] Kết nối CSDL: {e}")
    exit()

# === TẢI SINH VIÊN TỪ CSDL ===
cursor.execute("SELECT MaSV, face_encoding FROM sinhvien WHERE face_encoding IS NOT NULL")
known_embeddings = []
known_ids = []
for row in cursor.fetchall():
    try:
        encoding = json.loads(row[1])
        if len(encoding) == 128:
            known_embeddings.append(np.array(encoding))
            known_ids.append(row[0])
    except:
        continue
print(f"[OK] Đã tải {len(known_ids)} sinh viên từ CSDL")

# === MEDIAPIPE ===
mp_pose = mp.solutions.pose
mp_hands = mp.solutions.hands
mp_face_mesh = mp.solutions.face_mesh
pose = mp_pose.Pose(static_image_mode=False, model_complexity=1, min_detection_confidence=0.7)
hands = mp_hands.Hands(static_image_mode=False, max_num_hands=4, min_detection_confidence=0.7)
face_mesh = mp_face_mesh.FaceMesh(max_num_faces=5, refine_landmarks=True, min_detection_confidence=0.6)

# === BIẾN THEO DÕI ===
next_temp_id = 1
face_to_id = {}
id_to_embedding = {}
id_to_center_history = {}
face_to_real_masv = {}
last_cheat_time = {}
head_turn_start = {}
back_head_tracker = {}

CURRENT_ROOM = "P101"

# === HÀM HỖ TRỢ ===
def get_head_pose(landmarks, img_w, img_h):
    try:
        model_points = np.float32([
            [0.0, 0.0, 0.0], [0.0, -330.0, -65.0], [-225.0, 170.0, -135.0],
            [225.0, 170.0, -135.0], [-150.0, -150.0, -125.0], [150.0, -150.0, -125.0]
        ])
        image_points = np.float32([
            [landmarks[1].x * img_w, landmarks[1].y * img_h],
            [landmarks[152].x * img_w, landmarks[152].y * img_h],
            [landmarks[234].x * img_w, landmarks[234].y * img_h],
            [landmarks[454].x * img_w, landmarks[454].y * img_h],
            [landmarks[57].x * img_w, landmarks[57].y * img_h],
            [landmarks[287].x * img_w, landmarks[287].y * img_h],
        ])
        camera_matrix = np.array([[img_w, 0, img_w / 2], [0, img_w, img_h / 2], [0, 0, 1]], dtype="double")
        dist_coeffs = np.zeros((4, 1))
        success, rvec, tvec = cv2.solvePnP(model_points, image_points, camera_matrix, dist_coeffs,
                                           flags=cv2.SOLVEPNP_ITERATIVE)
        if not success:
            return 0, 0
        rmat = cv2.Rodrigues(rvec)[0]
        proj_mat = np.hstack((rmat, tvec))
        _, _, _, _, _, _, euler = cv2.decomposeProjectionMatrix(proj_mat)
        yaw = abs(euler[1, 0])
        pitch = abs(euler[0, 0])
        return yaw, pitch
    except:
        return 0, 0

def is_looking_under_table(landmarks, h, pitch):
    try:
        nose_y = landmarks[1].y * h
        chin_y = landmarks[152].y * h
        return (nose_y > h * 0.88 and chin_y > h * 0.88 and pitch > 45)
    except:
        return False

def is_looking_back(landmarks, yaw, pitch):
    try:
        nose = landmarks[1]
        left_ear = landmarks[234]
        right_ear = landmarks[454]
        
        # Tính tỷ lệ khoảng cách tai - mũi
        dist_left = abs(nose.x - left_ear.x)
        dist_right = abs(nose.x - right_ear.x)
        
        # Nếu CẢ HAI tai đều bị che (rất gần mũi) → mới là quay hẳn ra sau
        both_ears_hidden = dist_left < 0.04 and dist_right < 0.04
        
        # Hoặc yaw cực lớn + đầu hơi ngửa ra sau
        extreme_yaw = yaw > 85
        
        return both_ears_hidden or extreme_yaw
    except:
        return False

def ensure_room_exists(maphong="UNKNOWN"):
    if not maphong or str(maphong).strip() in ["", "None", "Unknown"] or str(maphong).startswith("id_"):
        maphong = "UNKNOWN"
    try:
        cursor.execute("SELECT 1 FROM phongthi WHERE maphong = %s", (maphong,))
        if not cursor.fetchone():
            cursor.execute("INSERT INTO phongthi (maphong, tenphong) VALUES (%s, %s)", (maphong, f"Phòng {maphong}"))
            db.commit()
    except:
        pass
    return maphong

cap = cv2.VideoCapture(0)
cap.set(cv2.CAP_PROP_FRAME_WIDTH, 1280)
cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 720)
print(f"[OK] HỆ THỐNG CHỐNG GIAN LẬN 2025 ĐÃ KHỞI ĐỘNG! | PHÒNG THI: {CURRENT_ROOM}")

while True:
    ret, frame = cap.read()
    if not ret:
        break
    frame = cv2.flip(frame, 1)
    rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
    h, w = frame.shape[:2]
    face_data = []
    trao_phaos = []
    phone_boxes = []
    current_time = datetime.now().timestamp()

    # === PHÁT HIỆN ĐIỆN THOẠI ===
    results_yolo = model_yolo(frame, conf=0.55, verbose=False)
    for result in results_yolo:
        for box in result.boxes:
            if int(box.cls[0]) == 0:
                x1, y1, x2, y2 = map(int, box.xyxy[0])
                cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 0, 255), 6)
                cv2.putText(frame, "DIEN THOAI!", (x1, y1-20), cv2.FONT_HERSHEY_DUPLEX, 1.0, (0, 0, 255), 3)
                phone_boxes.append((x1, y1, x2, y2))

    hand_results = hands.process(rgb)
    face_results = face_mesh.process(rgb)
    pose_results = pose.process(rgb)

    # === 1. BẮT "CHỈ THẤY TÓC" (giữ nguyên, cực chuẩn) ===
    current_noses = set()
    if face_results.multi_face_landmarks:
        for face in face_results.multi_face_landmarks:
            nx = int(face.landmark[1].x * w)
            ny = int(face.landmark[1].y * h)
            current_noses.add((nx, ny))

            assigned_id = None
            for tid, hist in id_to_center_history.items():
                if hist and np.linalg.norm(np.array(hist[-1]) - np.array((nx, ny))) < 200:
                    assigned_id = tid
                    break
            if assigned_id:
                back_head_tracker[assigned_id] = {'last_x': nx, 'frames_back': 0, 'last_seen': current_time}

    for fid, info in list(back_head_tracker.items()):
        nx = info['last_x']
        if any(abs(nx - n[0]) < 140 for n in current_noses):
            info['frames_back'] = 0
            continue
        roi = frame[max(0,int(h*0.1)):int(h*0.4), max(0,nx-100):min(w,nx+100)]
        if roi.size > 0:
            dark = cv2.countNonZero(cv2.inRange(cv2.cvtColor(roi, cv2.COLOR_BGR2HSV), (0,0,0), (180,255,100)))
            if dark > 4500:
                info['frames_back'] += 1
                if info['frames_back'] >= 18 and current_time - last_cheat_time.get(f"hair_{fid}",0) > 10:
                    path = f"gianlan/cheat_images/hair_{fid}_{datetime.now().strftime('%Y%m%d_%H%M%S_%f')[:-3]}.jpg"
                    cv2.imwrite(path, frame)
                    cursor.execute("INSERT INTO gianlan_log (MaSV,diem_gianlan,loai_hanhvi,thoigian,image_path,maphong) VALUES (%s,%s,%s,%s,%s,%s)",
                                   (fid, 1.00, "Quay đằng sau", datetime.now(), path, CURRENT_ROOM))
                    db.commit()
                    last_cheat_time[f"hair_{fid}"] = current_time
                    info['frames_back'] = 0
            else:
                info['frames_back'] = max(0, info['frames_back']-4)

    # === 2. TRAO PHẢO MỚI – BẤT KỂ AI CHẠM TAY CŨNG BỊ BÁO (HOÀN HẢO 100%) ===
    if hand_results.multi_hand_landmarks and len(hand_results.multi_hand_landmarks) >= 2:
        wrists = []
        for hand_lm in hand_results.multi_hand_landmarks:
            wx = hand_lm.landmark[0].x * w
            wy = hand_lm.landmark[0].y * h
            owner = None
            min_d = float('inf')
            
            # Tìm chủ tay từ tất cả khuôn mặt (cả SV thật lẫn id_xxx)
            for center, fid in face_to_id.items():
                nx, ny = center
                d = np.linalg.norm(np.array([wx, wy]) - np.array([nx, ny]))
                if d < min_d and d < 350:
                    min_d = d
                    owner = fid
            
            if owner:
                wrists.append({"x": wx, "y": wy, "owner": owner})

        # Xét từng cặp tay khác nhau
        for i in range(len(wrists)):
            for j in range(i+1, len(wrists)):
                dist = np.linalg.norm(np.array([wrists[i]["x"], wrists[i]["y"]]) - 
                                    np.array([wrists[j]["x"], wrists[j]["y"]]))
                o1 = wrists[i]["owner"]
                o2 = wrists[j]["owner"]
                
                # CHỈ CẦN 2 NGƯỜI KHÁC NHAU + TAY GẦN NHAU < 130px → BÁO NGAY!
                if dist < 130 and o1 != o2:
                    trao_phaos.append((o1, o2))
                    cv2.circle(frame, (int(wrists[i]["x"]), int(wrists[i]["y"])), 50, (0, 0, 255), 8)
                    cv2.circle(frame, (int(wrists[j]["x"]), int(wrists[j]["y"])), 50, (0, 0, 255), 8)
                    cv2.putText(frame, "TRAO DOI PHAO!", (w//2 - 250, 100),
                                cv2.FONT_HERSHEY_SIMPLEX, 2.8, (0, 0, 255), 8)

    # === 3. XỬ LÝ KHUÔN MẶT (giữ nguyên siêu tracking của bạn) ===
    hand_down_score = 0.0
    if pose_results.pose_landmarks:
        lm = pose_results.pose_landmarks.landmark
        if lm[mp_pose.PoseLandmark.LEFT_WRIST].visibility > 0.6 and lm[mp_pose.PoseLandmark.LEFT_WRIST].y * h > h * 0.78:
            hand_down_score += 0.5
        if lm[mp_pose.PoseLandmark.RIGHT_WRIST].visibility > 0.6 and lm[mp_pose.PoseLandmark.RIGHT_WRIST].y * h > h * 0.78:
            hand_down_score += 0.5

    if face_results.multi_face_landmarks:
        for face in face_results.multi_face_landmarks:
            nose_x = int(face.landmark[1].x * w)
            nose_y = int(face.landmark[1].y * h)
            center = (nose_x, nose_y)

            top_y = max(0, nose_y - int(h * 0.45))
            bottom_y = min(h, nose_y + int(h * 0.75))
            left_x = max(0, nose_x - int(w * 0.55))
            right_x = min(w, nose_x + int(w * 0.55))
            crop = frame[top_y:bottom_y, left_x:right_x].copy()

            yaw, pitch = get_head_pose(face.landmark, w, h)
            now = datetime.now().timestamp()
            look_back = is_looking_back(face.landmark, yaw, pitch)
            under_table = is_looking_under_table(face.landmark, h, pitch)
            is_warning = yaw > 32 or under_table or look_back

            turn_time = 0
            cheat_type = ""
            if is_warning:
                if center not in head_turn_start:
                    head_turn_start[center] = now
                turn_time = now - head_turn_start[center]
                if look_back:
                    cheat_type = "Quay sau lưng!"
                elif under_table:
                    cheat_type = "Cúi gầm bàn!"
                elif yaw > 65:
                    cheat_type = "Quay đầu!"
                else:
                    cheat_type = "Quay đầu!"
            else:
                head_turn_start.pop(center, None)

            # Nhận diện + tracking (giữ nguyên siêu đỉnh của bạn)
            face_crop_small = frame[max(0, nose_y - 120):nose_y + 120, max(0, nose_x - 120):nose_x + 120]
            current_embedding = None
            real_id = None
            if face_crop_small.size > 0 and known_embeddings:
                try:
                    emb = DeepFace.represent(face_crop_small, model_name="Facenet", enforce_detection=False, detector_backend="skip")[0]["embedding"]
                    current_embedding = np.array(emb)
                    dists = [np.linalg.norm(current_embedding - e) for e in known_embeddings]
                    if min(dists) < 0.6:
                        real_id = known_ids[np.argmin(dists)]
                except: pass

            final_id = None
            best_dist = float('inf')
            for prev_c, pid in face_to_id.items():
                d = np.linalg.norm(np.array(center) - np.array(prev_c))
                if d < 200 and d < best_dist:
                    best_dist = d
                    final_id = pid
            if final_id is None and current_embedding is not None:
                for pid, emb in id_to_embedding.items():
                    if np.linalg.norm(current_embedding - emb) < 0.65:
                        final_id = pid
                        break
            if final_id is None:
                final_id = real_id if real_id else f"id_{next_temp_id}"
                next_temp_id += 1

            face_to_id[center] = final_id
            if current_embedding is not None:
                id_to_embedding[final_id] = current_embedding
            if real_id:
                face_to_real_masv[center] = real_id

            id_to_center_history.setdefault(final_id, []).append(center)
            if len(id_to_center_history[final_id]) > 10:
                id_to_center_history[final_id].pop(0)

            score = hand_down_score
            if under_table or look_back or yaw > 70:
                score = 1.0
            elif yaw > 32:
                score = max(score, 0.4 + (yaw - 32) * 0.015)

            is_cheating = turn_time > 2.0 and score >= 0.7

            color = (0, 0, 255) if is_cheating else (0, 255, 255)
            cv2.putText(frame, f"{score:.2f} - {final_id} ({int(turn_time)}s)",
                        (nose_x - 150, nose_y - 40), cv2.FONT_HERSHEY_DUPLEX, 1.8, color, 5)

            if is_warning and turn_time <= 2.0:
                cv2.putText(frame, "CANH BAO!", (nose_x - 150, nose_y - 80),
                            cv2.FONT_HERSHEY_SIMPLEX, 1.2, (0, 255, 255), 4)

            face_data.append({"id": final_id, "score": score, "time": int(turn_time), "crop": crop, "cheat_type": cheat_type, "is_cheating": is_cheating})

    # === GHI LOG ===
    maphong = ensure_room_exists(CURRENT_ROOM)
    now_ts = datetime.now().timestamp()

    # Điện thoại, gian lận quay đầu, trao pháo (giữ nguyên logic cũ)
    for x1,y1,x2,y2 in phone_boxes:
        cx, cy = (x1+x2)//2, (y1+y2)//2
        closest = None
        min_d = float('inf')
        for face in (face_results.multi_face_landmarks or []):
            nx, ny = int(face.landmark[1].x*w), int(face.landmark[1].y*h)
            d = np.linalg.norm(np.array([nx,ny]) - np.array([cx,cy]))
            if d < min_d and d < 500:
                min_d = d
                closest = (nx,ny)
        owner = face_to_real_masv.get(closest, face_to_id.get(closest, "UNKNOWN")) if closest else "UNKNOWN"
        key = f"phone_{owner}"
        if now_ts - last_cheat_time.get(key,0) > 15:
            path = f"gianlan/cheat_images/phone_{owner}_{datetime.now().strftime('%Y%m%d_%H%M%S_%f')[:-3]}.jpg"
            cv2.imwrite(path, frame)
            cursor.execute("INSERT INTO gianlan_log (MaSV,diem_gianlan,loai_hanhvi,thoigian,image_path,maphong) VALUES (%s,%s,%s,%s,%s,%s)",
                           (owner, 1.00, "Điện thoại", datetime.now(), path, maphong))
            db.commit()
            last_cheat_time[key] = now_ts

    for data in face_data:
        if data["is_cheating"]:
            key = f"cheat_{data['id']}"
            if now_ts - last_cheat_time.get(key,0) > 8:
                path = f"gianlan/cheat_images/cheat_{data['id']}_{datetime.now().strftime('%Y%m%d_%H%M%S_%f')[:-3]}.jpg"
                cv2.imwrite(path, data["crop"])
                cursor.execute("INSERT INTO gianlan_log (MaSV,diem_gianlan,loai_hanhvi,thoigian,image_path,maphong) VALUES (%s,%s,%s,%s,%s,%s)",
                               (data["id"], round(data["score"],2), data["cheat_type"].replace("!",""), datetime.now(), path, maphong))
                db.commit()
                last_cheat_time[key] = now_ts

    for o1,o2 in trao_phaos:
        key = f"trao_{o1}_{o2}"
        if now_ts - last_cheat_time.get(key,0) > 8:
            path = f"gianlan/cheat_images/traophao_{datetime.now().strftime('%Y%m%d_%H%M%S_%f')[:-3]}.jpg"
            cv2.imwrite(path, frame)
            cursor.execute("INSERT INTO gianlan_log (MaSV,diem_gianlan,loai_hanhvi,thoigian,image_path,maphong) VALUES (%s,%s,%s,%s,%s,%s)",
                           (f"{o1}+{o2}", 1.00, "Trao đổi phao", datetime.now(), path, maphong))
            db.commit()
            last_cheat_time[key] = now_ts

    cv2.putText(frame, f"PHONG: {CURRENT_ROOM}", (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 0), 3)
    cv2.imwrite("gianlan/current_frame.jpg", frame)
    cv2.imshow("EYE GUARD 2025 - PHONG THI: " + CURRENT_ROOM, frame)
    if cv2.waitKey(1) == ord('q'):
        break

cap.release()
cv2.destroyAllWindows()
db.close()
print("[OK] ĐÃ TẮT HỆ THỐNG!")