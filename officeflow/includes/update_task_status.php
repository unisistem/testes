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
    $status = filter_input(INPUT_POST, 'status');
    
    // Validate status
    $valid_statuses = ['pending', 'in_progress', 'completed'];
    if ($task_id && in_array($status, $valid_statuses)) {
        $user_id = $_SESSION['user_id'];
        
        // Verify that the task belongs to the user
        $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$task_id, $user_id]);
        if ($stmt->fetch()) {
            // Update task status
            $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
            $result = $stmt->execute([$status, $task_id]);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar tarefa']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Permissão negada']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido']);
}