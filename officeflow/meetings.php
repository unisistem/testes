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
    if (isset($_POST['add_meeting'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $date = $_POST['date'];
        $time = $_POST['time'];
        $location = trim($_POST['location']);
        $attendees = trim($_POST['attendees']); // Comma-separated emails
        
        if (!empty($title) && !empty($date) && !empty($time)) {
            // Insert the meeting
            $stmt = $pdo->prepare("INSERT INTO meetings (title, description, date, time, location, organizer) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $date, $time, $location, $user_id]);
            $meeting_id = $pdo->lastInsertId();
            
            // Add attendees and send notifications
            $attendee_emails = array_map('trim', explode(',', $attendees));
            foreach ($attendee_emails as $email) {
                if (!empty($email)) {
                    // Find user by email
                    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        // Add to meeting attendees
                        $stmt = $pdo->prepare("INSERT IGNORE INTO meeting_attendees (meeting_id, user_id, status) VALUES (?, ?, 'invited')");
                        $stmt->execute([$meeting_id, $user['id']]);
                        
                        // Add notification for the attendee
                        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, related_type, related_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$user['id'], 'Convite para Reunião', 'Você foi convidado para a reunião: ' . $title, 'meeting', $meeting_id]);
                    }
                }
            }
        }
    } elseif (isset($_POST['delete_meeting'])) {
        $meeting_id = $_POST['meeting_id'];
        
        // Verify that the user is the organizer
        $stmt = $pdo->prepare("SELECT id FROM meetings WHERE id = ? AND organizer = ?");
        $stmt->execute([$meeting_id, $user_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM meetings WHERE id = ?");
            $stmt->execute([$meeting_id]);
        }
    }
}

// Get user meetings (organized by user or attending)
$stmt = $pdo->prepare("
    SELECT DISTINCT m.id, m.title, m.description, m.date, m.time, m.location, u.name as organizer_name
    FROM meetings m
    LEFT JOIN users u ON m.organizer = u.id
    LEFT JOIN meeting_attendees ma ON m.id = ma.meeting_id
    WHERE m.organizer = ? OR ma.user_id = ?
    ORDER BY m.date ASC, m.time ASC
");
$stmt->execute([$user_id, $user_id]);
$meetings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Reuniões - OfficeFlow</title>
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
                <h1>Gerenciamento de Reuniões</h1>
                <div class="user-info">
                    <p>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                </div>
            </div>
            
            <div class="content-area">
                <h2>Suas Reuniões</h2>
                <button class="btn btn-primary" data-modal-target="#addMeetingModal">Agendar Nova Reunião</button>
                
                <div class="meeting-list">
                    <?php if (count($meetings) > 0): ?>
                        <?php foreach ($meetings as $meeting): ?>
                            <div class="meeting-item">
                                <h3><?php echo htmlspecialchars($meeting['title']); ?></h3>
                                <p><?php echo htmlspecialchars($meeting['description']); ?></p>
                                <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($meeting['date'])); ?></p>
                                <p><strong>Horário:</strong> <?php echo $meeting['time']; ?></p>
                                <?php if ($meeting['location']): ?>
                                    <p><strong>Local:</strong> <?php echo htmlspecialchars($meeting['location']); ?></p>
                                <?php endif; ?>
                                <?php if ($meeting['organizer_name']): ?>
                                    <p><strong>Organizador:</strong> <?php echo htmlspecialchars($meeting['organizer_name']); ?></p>
                                <?php endif; ?>
                                <div class="meeting-actions">
                                    <button class="btn btn-danger" onclick="deleteMeeting(<?php echo $meeting['id']; ?>)">Excluir</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Você não tem reuniões agendadas.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Meeting Modal -->
    <div class="modal" id="addMeetingModal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Agendar Nova Reunião</h2>
            <form method="post">
                <div class="form-group">
                    <label for="title">Título *</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="description">Descrição</label>
                    <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="date">Data *</label>
                    <input type="date" id="date" name="date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="time">Horário *</label>
                    <input type="time" id="time" name="time" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="location">Local</label>
                    <input type="text" id="location" name="location" class="form-control">
                </div>
                <div class="form-group">
                    <label for="attendees">Participantes (emails separados por vírgula)</label>
                    <input type="text" id="attendees" name="attendees" class="form-control" placeholder="exemplo@email.com, outro@email.com">
                </div>
                <input type="hidden" name="add_meeting" value="1">
                <button type="submit" class="btn btn-primary">Agendar Reunião</button>
            </form>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>