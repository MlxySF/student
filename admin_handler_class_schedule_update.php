<?php
// This file contains ONLY the updated CLASS MANAGEMENT section
// Copy this code and replace the CLASS MANAGEMENT section in admin_handler.php

// ============ CLASS MANAGEMENT (UPDATED WITH SCHEDULE) ============

if ($action === 'create_class') {
    $class_code = strtoupper($_POST['class_code']);
    $class_name = $_POST['class_name'];
    $monthly_fee = $_POST['monthly_fee'];
    $description = $_POST['description'] ?? '';
    $day_of_week = $_POST['day_of_week'] ?? null;
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;

    try {
        $stmt = $pdo->prepare("INSERT INTO classes (class_code, class_name, monthly_fee, description, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$class_code, $class_name, $monthly_fee, $description, $day_of_week, $start_time, $end_time]);
        $_SESSION['success'] = "Class created with schedule!";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Class code already exists.";
    }
    header('Location: admin.php?page=classes');
    exit;
}

if ($action === 'edit_class') {
    $id = $_POST['class_id'];
    $class_code = strtoupper($_POST['class_code']);
    $class_name = $_POST['class_name'];
    $monthly_fee = $_POST['monthly_fee'];
    $description = $_POST['description'] ?? '';
    $day_of_week = $_POST['day_of_week'] ?? null;
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;

    $stmt = $pdo->prepare("UPDATE classes SET class_code = ?, class_name = ?, monthly_fee = ?, description = ?, day_of_week = ?, start_time = ?, end_time = ? WHERE id = ?");
    $stmt->execute([$class_code, $class_name, $monthly_fee, $description, $day_of_week, $start_time, $end_time, $id]);
    $_SESSION['success'] = "Class updated with schedule!";
    header('Location: admin.php?page=classes');
    exit;
}

if ($action === 'delete_class') {
    $id = $_POST['class_id'];
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = "Class deleted!";
    header('Location: admin.php?page=classes');
    exit;
}

?>