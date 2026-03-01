<?php
session_start();
include 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user notifications
$stmt = $pdo->prepare("
    SELECT n.id, n.title, n.message, n.created_at, n.is_read, n.related_type, n.related_id
    FROM notifications n
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

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

// Get upcoming meetings
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT m.id, m.title, m.description, m.meeting_date, m.meeting_time, m.location
    FROM meetings m
    WHERE m.date >= ? AND m.attendees LIKE ?
    ORDER BY m.date ASC, m.time ASC
    LIMIT 5
");
$stmt->execute([$today, "%{$_SESSION['user_name']}%"]);
$meetings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - OfficeFlow</title>
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
                <h1>OfficeFlow Dashboard</h1>
                <div class="user-info">
                    <p>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                    <div class="notifications">
                        <span class="notification-icon">🔔</span>
                        <div class="notification-dropdown">
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
                                         data-notification-id="<?php echo $notification['id']; ?>">
                                        <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                        <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <small><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="notification-item">Nenhuma notificação</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="content-area">
                <h2>Suas Tarefas</h2>
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
                
                <h2>Próximas Reuniões</h2>
                <div class="meeting-list">
                    <?php if (count($meetings) > 0): ?>
                        <?php foreach ($meetings as $meeting): ?>
                            <div class="meeting-item">
                                <h3><?php echo htmlspecialchars($meeting['title']); ?></h3>
                                <p><?php echo htmlspecialchars($meeting['description']); ?></p>
                                <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($meeting['meeting_date'])); ?></p>
                                <p><strong>Horário:</strong> <?php echo $meeting['meeting_time']; ?></p>
                                <?php if ($meeting['location']): ?>
                                    <p><strong>Local:</strong> <?php echo htmlspecialchars($meeting['location']); ?></p>
                                <?php endif; ?>
                                <div class="meeting-actions">
                                    <button class="btn btn-danger" onclick="deleteMeeting(<?php echo $meeting['id']; ?>)">Excluir</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Você não tem reuniões agendadas nos próximos dias.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>