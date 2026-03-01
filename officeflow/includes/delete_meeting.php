<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meeting_id = filter_input(INPUT_POST, 'meeting_id', FILTER_VALIDATE_INT);
    
    if ($meeting_id) {
        $user_id = $_SESSION['user_id'];
        
        // Verify that the user is the organizer
        $stmt = $pdo->prepare("SELECT id FROM meetings WHERE id = ? AND organizer = ?");
        $stmt->execute([$meeting_id, $user_id]);
        if ($stmt->fetch()) {
            // Delete the meeting
            $stmt = $pdo->prepare("DELETE FROM meetings WHERE id = ?");
            $result = $stmt->execute([$meeting_id]);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir reunião']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Permissão negada']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID de reunião inválido']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido']);
}