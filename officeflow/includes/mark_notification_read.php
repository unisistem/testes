<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_id = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
    
    if ($notification_id) {
        $user_id = $_SESSION['user_id'];
        
        // Update notification as read
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$notification_id, $user_id]);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar notificação']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID de notificação inválido']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido']);
}