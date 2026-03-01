<?php
session_start();
include 'includes/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, email, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(50));
            
            // Store token in database
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
            $stmt->execute([$token, $user['id']]);
            
            // In a real application, send email with reset link
            // For now, we'll just display the token for demonstration purposes
            $reset_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]?token=$token";
            $message = "Link de redefinição enviado para seu email. (Para demonstração: $reset_link)";
        } else {
            $message = 'Nenhuma conta encontrada com este email.';
        }
    } else {
        $message = 'Por favor, informe seu email.';
    }
}

// Handle password reset form
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token
    $stmt = $pdo->prepare("SELECT id, reset_token, token_expiry FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user && $user['token_expiry'] > date('Y-m-d H:i:s')) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_password'])) {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($new_password === $confirm_password && strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE id = ?");
                $stmt->execute([$hashed_password, $user['id']]);
                
                $message = 'Senha redefinida com sucesso! <a href="index.php">Faça login aqui</a>.';
            } else {
                $message = 'As senhas não coincidem ou são muito curtas.';
            }
        }
        
        // Show password reset form
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Redefinir Senha - OfficeFlow</title>
            <link rel="stylesheet" href="css/style.css">
        </head>
        <body>
            <div class="login-container">
                <h2>Redefinir Senha</h2>
                <?php if ($message): ?>
                    <div class="error"><?php echo $message; ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="password" name="new_password" placeholder="Nova senha" required>
                    <input type="password" name="confirm_password" placeholder="Confirmar nova senha" required>
                    <button type="submit">Redefinir Senha</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        $message = 'Token inválido ou expirado.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha - OfficeFlow</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Recuperar Senha</h2>
        <?php if ($message): ?>
            <div class="error"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="email" name="email" placeholder="Seu email" required>
            <button type="submit">Enviar link de redefinição</button>
        </form>
        <p><a href="index.php">Voltar para login</a></p>
    </div>
</body>
</html>