<?php
session_start();
include 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_task'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $due_date = $_POST['due_date'];
        
        if (!empty($title)) {
            $stmt = $pdo->prepare("INSERT INTO tasks (title, description, due_date, assigned_to, assigned_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $due_date, $user_id, $user_id]);
            
            // Add notification for the user
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, related_type, related_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, 'Nova Tarefa Atribuída', 'Uma nova tarefa foi atribuída a você: ' . $title, 'task', $pdo->lastInsertId()]);
        }
    } elseif (isset($_POST['update_status'])) {
        $task_id = $_POST['task_id'];
        $status = $_POST['status'];
        
        // Verify that the task belongs to the user
        $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$task_id, $user_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
            $stmt->execute([$status, $task_id]);
        }
    } elseif (isset($_POST['delete_task'])) {
        $task_id = $_POST['task_id'];
        
        // Verify that the task belongs to the user
        $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$task_id, $user_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$task_id]);
        }
    }
}

// Get user tasks
$stmt = $pdo->prepare("
    SELECT t.id, t.title, t.description, t.status, t.due_date, t.assigned_by, u.name as assigned_by_name
    FROM tasks t
    LEFT JOIN users u ON t.assigned_by = u.id
    WHERE t.assigned_to = ?
    ORDER BY CASE 
        WHEN t.status = 'pending' THEN 1
        WHEN t.status = 'in_progress' THEN 2
        WHEN t.status = 'completed' THEN 3
        ELSE 4
    END, t.due_date ASC
");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Tarefas - OfficeFlow</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <ul>
                <li><a href="dashboard.php">Início</a></li>
                <li><a href="tasks.php">Tarefas</a></li>
                <li><a href="meetings.php">Reuniões</a></li>
                <li><a href="reports.php">Relatórios</a></li>
                <li><a href="settings.php">Configurações</a></li>
                <li><a href="logout.php">Sair</a></li>
            </ul>
        </div>
        <div class="main-content">
            <div class="header">
                <h1>Gerenciamento de Tarefas</h1>
                <div class="user-info">
                    <p>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                </div>
            </div>
            
            <div class="content-area">
                <h2>Suas Tarefas</h2>
                <button class="btn btn-primary" data-modal-target="#addTaskModal">Adicionar Nova Tarefa</button>
                
                <div class="task-list">
                    <?php if (count($tasks) > 0): ?>
                        <?php foreach ($tasks as $task): ?>
                            <div class="task-item">
                                <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                                <p><?php echo htmlspecialchars($task['description']); ?></p>
                                <p><strong>Status:</strong> 
                                    <span id="status-<?php echo $task['id']; ?>">
                                        <?php 
                                            switch($task['status']) {
                                                case 'pending': echo 'Pendente'; break;
                                                case 'in_progress': echo 'Em Progresso'; break;
                                                case 'completed': echo 'Concluída'; break;
                                                default: echo ucfirst($task['status']);
                                            }
                                        ?>
                                    </span>
                                </p>
                                <?php if ($task['due_date']): ?>
                                    <p><strong>Data de vencimento:</strong> <?php echo date('d/m/Y', strtotime($task['due_date'])); ?></p>
                                <?php endif; ?>
                                <?php if ($task['assigned_by_name']): ?>
                                    <p><strong>Atribuída por:</strong> <?php echo htmlspecialchars($task['assigned_by_name']); ?></p>
                                <?php endif; ?>
                                <div class="task-actions">
                                    <?php if($task['status'] !== 'completed'): ?>
                                        <button class="btn btn-success" onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'completed')">Marcar como Concluída</button>
                                        <button class="btn btn-primary" onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'in_progress')">Iniciar Tarefa</button>
                                    <?php endif; ?>
                                    <button class="btn btn-danger" onclick="deleteTask(<?php echo $task['id']; ?>)">Excluir</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Você não tem tarefas atribuídas no momento.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Task Modal -->
    <div class="modal" id="addTaskModal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Adicionar Nova Tarefa</h2>
            <form method="post">
                <div class="form-group">
                    <label for="title">Título *</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="description">Descrição</label>
                    <textarea id="description" name="description" class="form-control" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="due_date">Data de Vencimento</label>
                    <input type="date" id="due_date" name="due_date" class="form-control">
                </div>
                <input type="hidden" name="add_task" value="1">
                <button type="submit" class="btn btn-primary">Adicionar Tarefa</button>
            </form>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>