# save_faces_fixed.py - Lưu ảnh sinh viên an toàn 100%
from deepface import DeepFace
import mysql.connector
import os
import json

db = mysql.connector.connect(host="localhost", user="root", password="", database="quanlythi")
cursor = db.cursor()

folder = "faces"
if not os.path.exists(folder):
    os.makedirs(folder)
    print("Tạo thư mục 'faces' - hãy bỏ ảnh SV005.jpg, SV001.jpg vào đây!")
    exit()

for file in os.listdir(folder):
    if file.lower().endswith(('.jpg', '.jpeg', '.png')):
        path = os.path.join(folder, file)
        MaSV = os.path.splitext(file)[0]

        try:
            embedding = DeepFace.represent(path, model_name="Facenet", enforce_detection=False)[0]["embedding"]
            embedding_json = json.dumps(embedding)  # Dùng json thay vì str + eval

            sql = "INSERT INTO sinhvien (MaSV, Hoten, face_encoding) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE face_encoding = %s"
            cursor.execute(sql, (MaSV, f"Sinh viên {MaSV}", embedding_json, embedding_json))
            print(f"Đã lưu: {MaSV}")
        except Exception as e:
            print(f"Lỗi {file}: {e}")

db.commit()
db.close()
print("HOÀN TẤT! Giờ chạy main.py sẽ nhận diện + ghi log ngay!")