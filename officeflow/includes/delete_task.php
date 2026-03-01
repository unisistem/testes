<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    
    if ($task_id) {
        $user_id = $_SESSION['user_id'];
        
        // Verify that the task belongs to the user
        $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$task_id, $user_id]);
        if ($stmt->fetch()) {
            // Delete the task
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $result = $stmt->execute([$task_id]);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir tarefa']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Permissão negada']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID de tarefa inválido']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido']);
}