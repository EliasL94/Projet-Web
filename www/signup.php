<?php
session_start();

// Redirection si l'utilisateur est déjà connecté
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include('data/db.php');

$errors = [];
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($username)) {
        $errors[] = "Le nom d'utilisateur est requis";
    } elseif(strlen($username) < 3 || strlen($username) > 20) {
        $errors[] = "Le nom d'utilisateur doit contenir entre 3 et 20 caractères";
    }
    
    if(empty($email)) {
        $errors[] = "L'adresse email est requise";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email est invalide";
    }
    
    if(empty($password)) {
        $errors[] = "Le mot de passe est requis";
    } elseif(strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
    } elseif(!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une lettre majuscule";
    } elseif(!preg_match('/[a-z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une lettre minuscule";
    } elseif(!preg_match('/[0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un chiffre";
    } elseif(!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un caractère spécial";
    }
    
    if($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    
    if(empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        $count = $stmt->fetchColumn();
        
        if($count > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $emailExists = $stmt->fetchColumn() > 0;
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $usernameExists = $stmt->fetchColumn() > 0;
            
            if($emailExists) {
                $errors[] = "Cette adresse email est déjà utilisée";
            }
            
            if($usernameExists) {
                $errors[] = "Ce nom d'utilisateur est déjà utilisé";
            }
        }
    }
    
    if(empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
        if($stmt->execute([$username, $email, $hashedPassword])) {
            $success = "Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.";
            header("refresh:2;url=login.php");
        } else {
            $errors[] = "Une erreur est survenue lors de la création de votre compte";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Fontaines à boire de Paris</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="CSS/home.css" />
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="header-container">
            <div class="logo-container">
                <a href="index.php" class="logo-link">
                    <i class="fas fa-tint"></i>
                    <span>Fontaines Paris</span>
                </a>
            </div>
            
            <div class="auth-buttons">
                <a href="home.php" class="back-to-home">
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
                <i class="fas fa-user-plus"></i>
            </div>
            <h1 class="auth-title">Créer un compte</h1>
            <p class="auth-subtitle">Inscrivez-vous pour accéder à toutes les fonctionnalités</p>
            
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
            
            <form class="auth-form" method="POST" action="signup.php">
                <div class="form-control">
                    <label for="username">Nom d'utilisateur</label>
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" placeholder="Choisissez un nom d'utilisateur" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                
                <div class="form-control">
                    <label for="email">Adresse Email</label>
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" placeholder="Votre adresse email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="form-control">
                    <label for="password">Mot de passe</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Choisissez un mot de passe" required>
                    <div id="password-strength" class="password-strength"></div>
                </div>
                
                <div class="form-control">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmez votre mot de passe" required>
                </div>
                
                <button type="submit" class="auth-button">
                    <i class="fas fa-user-plus"></i> S'inscrire
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Vous avez déjà un compte ? <a href="login.php">Se connecter</a></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.auth-form');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrength = document.getElementById('password-strength');
            
            // Afficher la force du mot de passe
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let message = '';
                
                if(password.length > 0) {
                    // Longueur minimale
                    if(password.length >= 8) strength += 1;
                    
                    // Présence de lettres majuscules
                    if(/[A-Z]/.test(password)) strength += 1;
                    
                    // Présence de lettres minuscules
                    if(/[a-z]/.test(password)) strength += 1;
                    
                    // Présence de chiffres
                    if(/[0-9]/.test(password)) strength += 1;
                    
                    // Présence de caractères spéciaux
                    if(/[^A-Za-z0-9]/.test(password)) strength += 1;
                    
                    // Message selon la force
                    if(strength <= 2) {
                        message = '<span class="strength-weak"><i class="fas fa-exclamation-circle"></i> Mot de passe faible</span>';
                    } else if(strength <= 4) {
                        message = '<span class="strength-medium"><i class="fas fa-info-circle"></i> Mot de passe moyen</span>';
                    } else {
                        message = '<span class="strength-strong"><i class="fas fa-check-circle"></i> Mot de passe fort</span>';
                    }
                }
                
                passwordStrength.innerHTML = message;
            });
            
            // Validation du formulaire
            form.addEventListener('submit', function(e) {
                const username = document.getElementById('username').value.trim();
                const email = document.getElementById('email').value.trim();
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                let isValid = true;
                let errorMessages = [];
                
                // Validation du nom d'utilisateur
                if(username === '') {
                    errorMessages.push("Le nom d'utilisateur est requis");
                    isValid = false;
                } else if(username.length < 3 || username.length > 20) {
                    errorMessages.push("Le nom d'utilisateur doit contenir entre 3 et 20 caractères");
                    isValid = false;
                }
                
                // Validation de l'email
                if(email === '') {
                    errorMessages.push("L'adresse email est requise");
                    isValid = false;
                } else if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    errorMessages.push("L'adresse email est invalide");
                    isValid = false;
                }
                
                // Validation du mot de passe
                if(password === '') {
                    errorMessages.push("Le mot de passe est requis");
                    isValid = false;
                } else {
                    if(password.length < 8) {
                        errorMessages.push("Le mot de passe doit contenir au moins 8 caractères");
                        isValid = false;
                    }
                    if(!/[A-Z]/.test(password)) {
                        errorMessages.push("Le mot de passe doit contenir au moins une lettre majuscule");
                        isValid = false;
                    }
                    if(!/[a-z]/.test(password)) {
                        errorMessages.push("Le mot de passe doit contenir au moins une lettre minuscule");
                        isValid = false;
                    }
                    if(!/[0-9]/.test(password)) {
                        errorMessages.push("Le mot de passe doit contenir au moins un chiffre");
                        isValid = false;
                    }
                    if(!/[^A-Za-z0-9]/.test(password)) {
                        errorMessages.push("Le mot de passe doit contenir au moins un caractère spécial");
                        isValid = false;
                    }
                }
                
                // Vérification de la correspondance des mots de passe
                if(password !== confirmPassword) {
                    errorMessages.push("Les mots de passe ne correspondent pas");
                    isValid = false;
                }
                
                if(!isValid) {
                    e.preventDefault();
                    
                    // Afficher les erreurs
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i><div>${errorMessages.map(msg => `<p>${msg}</p>`).join('')}</div>`;
                    
                    // Supprimer les anciennes alertes
                    const oldAlerts = document.querySelectorAll('.alert');
                    oldAlerts.forEach(alert => alert.remove());
                    
                    // Insérer la nouvelle alerte au début du formulaire
                    form.insertBefore(alertDiv, form.firstChild);
                }
            });
        });
    </script>
</body>
</html>