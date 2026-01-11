# Exam_cheating
 🧠 AI Exam Cheating Detection System

An intelligent real-time exam proctoring system that uses Computer Vision + Deep Learning to detect cheating behaviors in examination rooms.

🚀 Project Overview

This system monitors exam rooms via camera streams and automatically detects suspicious behaviors such as:

Looking around

Looking down at notes

Using mobile phones

Interacting with others

All violations are logged with student ID, time, image evidence, and room information for review.

🧩 System Architecture

The system consists of 3 main components:

Component	Technology
Web Dashboard	PHP, HTML, JavaScript
AI Engine	Python, OpenCV, YOLOv8, Dlib
Database	MySQL / JSON logs
* DEMO
[  https://youtu.be/NawC7Hq7atI?si=_chlVJPs0c1t6Umu
](https://youtu.be/PLJ4Scf_OT0)🔍 AI Features

Face recognition 

Head pose estimation (detect looking left, right, down)

Hand & body behavior detection

YOLOv8 object detection (detect mobile phones)

Real-time violation logging

Multi-room exam monitoring

📂 Main Files
File	Function
main.py	AI engine (camera, detection, tracking)
save_encodings.py	Face encoding
stream.php	Live camera stream
index.php	Main dashboard
stats_by_room.php	Room statistics
get_logs.php	Violation data API
db.php	Database connection
🖥️ How to Run
1️⃣ Install Python packages
pip install -r requirements.txt

2️⃣ Start AI detection
python main.py

3️⃣ Run web system

Use XAMPP or Apache
Open:

[http://localhost/quanlythi/index.php
](http://localhost/phpmyadmin/index.php?route=/database/structure&db=quanlythi)
📊 Output

Real-time room status

Violation logs

Student cheating reports

Exportable Excel reports

🎯 Applications

Universities

Online & offline exams

Smart classrooms

Proctoring systems

👨‍💻 Author

Developed by Doha (AI Engineer)
AI, Computer Vision, Deep Learning





