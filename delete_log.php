<?php
session_start();
include 'db.php';
if (!isset($_SESSION['admin'])) { echo json_encode(['success'=>false]); exit; }

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success'=>false]); exit;
}

$id = (int)$_POST['id'];
$stmt = $conn->prepare("DELETE FROM gianlan_log WHERE id = ?");
$stmt->bind_param("i", $id);
echo json_encode(['success' => $stmt->execute()]);
?>