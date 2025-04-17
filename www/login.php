<?php
session_start();

if(isset($_SESSION['user_id'])) {
    header("Location: map.php");
    exit();
}

include('data/db.php');

$errors = [];
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if(empty($email)) {
        $errors[] = "L'adresse email est requise";
    }
    if(empty($password)) {
        $errors[] = "Le mot de passe est requis";
    }
    
    if(empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, username, email, password, profile_image FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['profile_image'] = $user['profile_image'];
            
            header("Location: map.php");
            exit();
        } else {
            $errors[] = "Identifiants incorrects";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Fontaines à boire de Paris</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="CSS/home.css" />
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <div class="logo-container">
                <a href="map.php" class="logo-link">
                    <i class="fas fa-tint"></i>
                    <span>Fontaines Paris</span>
                </a>
            </div>
            
            <div class="auth-buttons">
                <a href="index.php" class="back-to-home">
                    <i class="fas fa-home"></i>
                </a>
                <a href="login.php" class="btn btn-outline"><i class="fas fa-sign-in-alt"></i> Se connecter</a>
                <a href="signup.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> S'inscrire</a>
            </div>
        </div>
    </header>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <h1 class="auth-title">Connexion</h1>
            <p class="auth-subtitle">Entrez vos identifiants pour accéder à votre compte</p>
            
            <?php if(!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <?php foreach($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>
            
            <form class="auth-form" method="POST" action="login.php">
                <div class="form-control">
                    <label for="email">Adresse Email</label>
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" placeholder="Votre adresse email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="form-control">
                    <label for="password">Mot de passe</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Votre mot de passe" required>
                </div>
                
                <button type="submit" class="auth-button">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Vous n'avez pas de compte ? <a href="signup.php">Créer un compte</a></p>
            </div>
        </div>
    </div>

    <script>
        // Validation côté client
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.auth-form');
            
            form.addEventListener('submit', function(e) {
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value.trim();
                let isValid = true;
                let errorMessages = [];
                
                if(email === '') {
                    errorMessages.push("L'adresse email est requise");
                    isValid = false;
                }
                
                if(password === '') {
                    errorMessages.push("Le mot de passe est requis");
                    isValid = false;
                }
                
                if(!isValid) {
                    e.preventDefault();
                    
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i><div>${errorMessages.map(msg => `<p>${msg}</p>`).join('')}</div>`;
                    
                    const oldAlerts = document.querySelectorAll('.alert');
                    oldAlerts.forEach(alert => alert.remove());
                    
                    form.insertBefore(alertDiv, form.firstChild);
                }
            });
        });
    </script>
</body>
</html>