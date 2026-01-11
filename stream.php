<?php
// File này sẽ nhận stream từ Python qua POST
header('Content-Type: multipart/x-mixed-replace; boundary=frame');
header('Cache-Control: no-cache');

$boundary = "frame";
while (true) {
    if (file_exists("current_frame.jpg")) {
        $img = file_get_contents("current_frame.jpg");
        echo "--$boundary\r\n";
        echo "Content-Type: image/jpeg\r\n";
        echo "Content-Length: " . strlen($img) . "\r\n\r\n";
        echo $img . "\r\n";
    }
    usleep(100000); // 10 FPS
}
?>