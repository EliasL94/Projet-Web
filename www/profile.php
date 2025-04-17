<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('data/db.php');

$errors = [];
$success = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Traitement du formulaire de mise à jour des informations
if($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Suppression du compte utilisateur
        if (isset($_POST['delete_account'])) {
            // Supprimer la photo de profil si elle existe
            if (!empty($user['profile_image']) && file_exists('uploads/profiles/' . $user['profile_image'])) {
                unlink('uploads/profiles/' . $user['profile_image']);
            }
    
            // Supprimer l'utilisateur de la base de données
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$_SESSION['user_id']])) {
                session_destroy();
                header("Location: home.php");
                exit();
            } else {
                $errors[] = "Une erreur est survenue lors de la suppression du compte.";
            }
        }
    
    // Vérifier quelle action est demandée
    if(isset($_POST['update_info'])) {
        // Mise à jour des informations du profil
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        
        // Validation des champs
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
        
        // Vérifier si le nom d'utilisateur ou l'email existe déjà (sauf pour l'utilisateur actuel)
        if(empty($errors)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (email = ? OR username = ?) AND id != ?");
            $stmt->execute([$email, $username, $_SESSION['user_id']]);
            $count = $stmt->fetchColumn();
            
            if($count > 0) {
                // Vérifier lequel existe déjà
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                $emailExists = $stmt->fetchColumn() > 0;
                
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $_SESSION['user_id']]);
                $usernameExists = $stmt->fetchColumn() > 0;
                
                if($emailExists) {
                    $errors[] = "Cette adresse email est déjà utilisée";
                }
                
                if($usernameExists) {
                    $errors[] = "Ce nom d'utilisateur est déjà utilisé";
                }
            }
        }
        
        // Si pas d'erreurs, mettre à jour les informations
        if(empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            if($stmt->execute([$username, $email, $_SESSION['user_id']])) {
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $success = "Vos informations ont été mises à jour avec succès";
                $user['username'] = $username;
                $user['email'] = $email;
            } else {
                $errors[] = "Une erreur est survenue lors de la mise à jour de vos informations";
            }
        }
    } elseif(isset($_POST['update_password'])) {
        // Mise à jour du mot de passe
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation des champs
        if(empty($current_password)) {
            $errors[] = "Le mot de passe actuel est requis";
        } else {
            // Vérifier que le mot de passe actuel est correct
            if(!password_verify($current_password, $user['password'])) {
                $errors[] = "Le mot de passe actuel est incorrect";
            }
        }
        
        if(empty($new_password)) {
            $errors[] = "Le nouveau mot de passe est requis";
        } elseif(strlen($new_password) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        } elseif(!preg_match('/[A-Z]/', $new_password)) {
            $errors[] = "Le mot de passe doit contenir au moins une lettre majuscule";
        } elseif(!preg_match('/[a-z]/', $new_password)) {
            $errors[] = "Le mot de passe doit contenir au moins une lettre minuscule";
        } elseif(!preg_match('/[0-9]/', $new_password)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre";
        } elseif(!preg_match('/[^A-Za-z0-9]/', $new_password)) {
            $errors[] = "Le mot de passe doit contenir au moins un caractère spécial";
        }
        
        if($new_password !== $confirm_password) {
            $errors[] = "Les nouveaux mots de passe ne correspondent pas";
        }
        
        // Si pas d'erreurs, mettre à jour le mot de passe
        if(empty($errors)) {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if($stmt->execute([$hashedPassword, $_SESSION['user_id']])) {
                $success = "Votre mot de passe a été mis à jour avec succès";
            } else {
                $errors[] = "Une erreur est survenue lors de la mise à jour de votre mot de passe";
            }
        }
    } elseif(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        // Mise à jour de la photo de profil
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        $file_info = $_FILES['profile_image'];
        
        // Vérifier le type de fichier
        if(!in_array($file_info['type'], $allowed_types)) {
            $errors[] = "Le type de fichier n'est pas autorisé. Veuillez télécharger une image (JPEG, PNG ou GIF)";
        }
        
        // Vérifier la taille du fichier
        if($file_info['size'] > $max_size) {
            $errors[] = "La taille du fichier est trop importante. La taille maximale est de 2MB";
        }
        
        if(empty($errors)) {
            // Créer le dossier s'il n'existe pas
            $upload_dir = 'uploads/profiles/';
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Générer un nom de fichier unique
            $file_extension = pathinfo($file_info['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . $_SESSION['user_id'] . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            
            // Déplacer le fichier vers le dossier des téléchargements
            if(move_uploaded_file($file_info['tmp_name'], $target_path)) {
                // Supprimer l'ancienne image si elle existe
                if(!empty($user['profile_image']) && file_exists($upload_dir . $user['profile_image'])) {
                    unlink($upload_dir . $user['profile_image']);
                }
                
                // Mettre à jour la base de données
                $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                if($stmt->execute([$filename, $_SESSION['user_id']])) {
                    $_SESSION['profile_image'] = $filename;
                    $success = "Votre photo de profil a été mise à jour avec succès";
                    $user['profile_image'] = $filename;
                } else {
                    $errors[] = "Une erreur est survenue lors de la mise à jour de votre photo de profil";
                }
            } else {
                $errors[] = "Une erreur est survenue lors du téléchargement de votre photo de profil";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Fontaines à boire de Paris</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="CSS/home.css" />
</head>
<body>
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
                <div class="user-profile">
                    <div class="user-info">
                        <span>Bonjour, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <?php if(isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image'])): ?>
                            <img src="uploads/profiles/<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" alt="Photo de profil" class="profile-image">
                        <?php else: ?>
                            <div class="profile-image-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="dropdown-menu">
                        <a href="profile.php" class="dropdown-item"><i class="fas fa-user-edit"></i> Mon Profil</a>
                        <a href="logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="profile-container">
        <div class="profile-header">
            <h1>Mon Profil</h1>
            <p>Gérez vos informations personnelles et votre photo de profil</p>
        </div>
        
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
        
        <div class="profile-form">
            <div class="form-section">
                <h3>Photo de profil</h3>
                
                <div class="profile-picture-container">
                    <?php if(isset($user['profile_image']) && !empty($user['profile_image'])): ?>
                        <img src="uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Photo de profil" class="profile-picture">
                    <?php else: ?>
                        <div class="profile-picture-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="picture-edit-button" id="profile-picture-button">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
                
                <form id="profile-picture-form" action="profile.php" method="POST" enctype="multipart/form-data" style="display: none;">
                    <input type="file" id="profile-image" name="profile_image" accept="image/jpeg, image/png, image/gif">
                </form>
            </div>
            
            <div class="form-section">
                <h3>Informations personnelles</h3>
                
                <form action="profile.php" method="POST">
                    <div class="form-control">
                        <label for="username">Nom d'utilisateur</label>
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    
                    <div class="form-control">
                        <label for="email">Adresse Email</label>
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    
                    <button type="submit" name="update_info" class="auth-button">
                        <i class="fas fa-save"></i> Mettre à jour les informations
                    </button>
                </form>
            </div>
            
            <div class="form-section">
                <h3>Modifier le mot de passe</h3>
                
                <form action="profile.php" method="POST">
                    <div class="form-control">
                        <label for="current_password">Mot de passe actuel</label>
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-control">
                        <label for="new_password">Nouveau mot de passe</label>
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="new_password" name="new_password" required>
                        <div id="password-strength" class="password-strength"></div>
                    </div>
                    
                    <div class="form-control">
                        <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="update_password" class="auth-button">
                        <i class="fas fa-key"></i> Modifier le mot de passe
                    </button>
                </form>
            </div>
            <div class="form-section">
                <h3>Supprimer mon compte</h3>
                <form method="POST" onsubmit="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer définitivement votre compte ? Cette action est irréversible.')">
                    <button type="submit" name="delete_account" class="delete-account-btn">
                        <i class="fas fa-user-slash"></i> Supprimer mon profil
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
        // Gestion du téléchargement de la photo de profil
        const profilePictureButton = document.getElementById('profile-picture-button');
        const profileImageInput = document.getElementById('profile-image');
        const profilePictureForm = document.getElementById('profile-picture-form');
        
        profilePictureButton.addEventListener('click', function() {
            profileImageInput.click();
        });
        
        profileImageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                // Soumettre automatiquement le formulaire lorsqu'un fichier est sélectionné
                profilePictureForm.submit();
            }
        });
        
        // Gestion du menu déroulant du profil utilisateur
        const userProfile = document.querySelector('.user-profile');
        userProfile.addEventListener('click', function(e) {
            const dropdownMenu = this.querySelector('.dropdown-menu');
            dropdownMenu.classList.toggle('active');
            e.stopPropagation();
        });
        
        // Fermer le menu déroulant si on clique ailleurs
        document.addEventListener('click', function() {
            const dropdownMenu = document.querySelector('.dropdown-menu');
            if (dropdownMenu && dropdownMenu.classList.contains('active')) {
                dropdownMenu.classList.remove('active');
            }
        });
        
        // Indicateur de force du mot de passe
        const newPasswordInput = document.getElementById('new_password');
        const passwordStrength = document.getElementById('password-strength');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let feedback = '';
            
            // Vérifier la longueur du mot de passe
            if (password.length >= 8) {
                strength += 1;
            }
            
            // Vérifier s'il contient au moins une lettre majuscule
            if (/[A-Z]/.test(password)) {
                strength += 1;
            }
            
            // Vérifier s'il contient au moins une lettre minuscule
            if (/[a-z]/.test(password)) {
                strength += 1;
            }
            
            // Vérifier s'il contient au moins un chiffre
            if (/[0-9]/.test(password)) {
                strength += 1;
            }
            
            // Vérifier s'il contient au moins un caractère spécial
            if (/[^A-Za-z0-9]/.test(password)) {
                strength += 1;
            }
            
            // Afficher le niveau de force du mot de passe
            passwordStrength.className = 'password-strength';
            
            if (password.length === 0) {
                passwordStrength.textContent = '';
                passwordStrength.classList.remove('weak', 'medium', 'strong');
            } else if (strength < 3) {
                passwordStrength.textContent = 'Faible';
                passwordStrength.classList.add('weak');
            } else if (strength < 5) {
                passwordStrength.textContent = 'Moyen';
                passwordStrength.classList.add('medium');
            } else {
                passwordStrength.textContent = 'Fort';
                passwordStrength.classList.add('strong');
            }
        });
        
        // Vérifier si les mots de passe correspondent
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== newPasswordInput.value) {
                this.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Validation du formulaire avant soumission
        const infoForm = document.querySelector('form[name="update_info"]');
        if (infoForm) {
            infoForm.addEventListener('submit', function(e) {
                const username = document.getElementById('username').value;
                const email = document.getElementById('email').value;
                let hasErrors = false;
                
                if (username.length < 3 || username.length > 20) {
                    e.preventDefault();
                    alert("Le nom d'utilisateur doit contenir entre 3 et 20 caractères");
                    hasErrors = true;
                }
                
                if (!validateEmail(email)) {
                    e.preventDefault();
                    alert("Veuillez entrer une adresse email valide");
                    hasErrors = true;
                }
                
                return !hasErrors;
            });
        }
        
        const passwordForm = document.querySelector('form[name="update_password"]');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                const currentPassword = document.getElementById('current_password').value;
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                let hasErrors = false;
                
                if (currentPassword.length === 0) {
                    e.preventDefault();
                    alert("Veuillez entrer votre mot de passe actuel");
                    hasErrors = true;
                }
                
                if (newPassword.length < 8) {
                    e.preventDefault();
                    alert("Le nouveau mot de passe doit contenir au moins 8 caractères");
                    hasErrors = true;
                }
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert("Les nouveaux mots de passe ne correspondent pas");
                    hasErrors = true;
                }
                
                return !hasErrors;
            });
        }
        
        // Fonction de validation d'email
        function validateEmail(email) {
            const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        }
        
        // Animation pour les messages d'alerte
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }, 5000);
        });
    });
    </script>