<?php
session_start();
include 'includes/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (!empty($name) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            if (strlen($password) >= 6) {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if (!$stmt->fetch()) {
                    // Create new user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                    if ($stmt->execute([$name, $email, $hashed_password])) {
                        $message = 'Conta criada com sucesso! <a href="index.php">Faça login aqui</a>.';
                    } else {
                        $message = 'Erro ao criar conta.';
                    }
                } else {
                    $message = 'Este email já está registrado.';
                }
            } else {
                $message = 'A senha deve ter pelo menos 6 caracteres.';
            }
        } else {
            $message = 'As senhas não coincidem.';
        }
    } else {
        $message = 'Por favor, preencha todos os campos.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Criar Conta - OfficeFlow</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Criar Conta</h2>
        <?php if ($message): ?>
            <div class="error"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="name" placeholder="Nome completo" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Senha" required>
            <input type="password" name="confirm_password" placeholder="Confirmar senha" required>
            <button type="submit">Criar Conta</button>
        </form>
        <p><a href="index.php">Voltar para login</a></p>
    </div>
</body>
</html>