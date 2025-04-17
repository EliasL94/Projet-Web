<?php 
    include('data/db.php');
    
    session_start();
    
    $userLat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
    $userLon = isset($_GET['lon']) ? floatval($_GET['lon']) : null;
    $shouldLocate = isset($_GET['locate']) && $_GET['locate'] === 'true';
    
    $searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
    $searchDistrict = isset($_GET['district']) ? $_GET['district'] : '';
    $searchType = isset($_GET['type']) ? $_GET['type'] : '';

    include('search.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fontaines à boire de Paris</title>
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
                <?php if(isset($_SESSION['user_id'])): ?>
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
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline"><i class="fas fa-sign-in-alt"></i> Se connecter</a>
                    <a href="signup.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> S'inscrire</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="welcome-card">
            <div class="logo">
                <i class="fas fa-tint"></i>
            </div>
            <h1>Fontaines à boire de Paris</h1>
            <p class="tagline">Découvrez les fontaines d'eau potable près de chez vous</p>
            
            <div class="actions">
                <button id="locate-button" class="btn primary">
                    <i class="fas fa-location-arrow"></i> Me localiser
                </button>
                <a href="map.php" class="btn secondary" id="search-button">
                    <i class="fas fa-search"></i> Rechercher
                </a>
            </div>
            
            <div id="search-form" class="search-form">
                <h3>Recherche de fontaines</h3>
                <form id="search-direct-form" action="map.php" method="GET">
                    <div class="form-group">
                        <label for="search-text">Mot-clé</label>
                        <input type="text" id="search-text" name="search" placeholder="Rue, arrondissement, type...">
                    </div>
                    
                    <div class="form-group">
                        <label for="district-select">Arrondissement</label>
                        <select id="district-select" name="district">
                            <option value="">Tous les arrondissements</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="type-select">Type de fontaine</label>
                        <select id="type-select" name="type">
                            <option value="">Tous les types</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn primary full-width">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                    </div>
                </form>
            </div>
            
            <div id="loading" class="loading-indicator">
                <div class="spinner"></div>
                <p>Localisation en cours...</p>
            </div>
            
            <div id="error-message" class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Impossible d'obtenir votre position. Veuillez vérifier vos paramètres de localisation.</p>
                <button id="retry-button" class="btn secondary">Réessayer</button>
            </div>
        </div>
        
        <div class="footer">
            <p>Données fournies par la Ville de Paris | 2025</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const locateButton = document.getElementById('locate-button');
            const searchButton = document.getElementById('search-button');
            const searchForm = document.getElementById('search-form');
            const loadingIndicator = document.getElementById('loading');
            const errorMessage = document.getElementById('error-message');
            const retryButton = document.getElementById('retry-button');
            const districtSelect = document.getElementById('district-select');
            const typeSelect = document.getElementById('type-select');
            
            searchForm.style.display = 'none';
            loadingIndicator.style.display = 'none';
            errorMessage.style.display = 'none';
            
            loadDistricts();
            loadFountainTypes();
            
            locateButton.addEventListener('click', function() {
                startGeolocation();
            });
            
            searchButton.addEventListener('click', function() {
                toggleSearchForm();
            });
            
            retryButton.addEventListener('click', function() {
                errorMessage.style.display = 'none';
                startGeolocation();
            });
            
            function toggleSearchForm() {
                if (searchForm.style.display === 'none') {
                    searchForm.style.display = 'block';
                    errorMessage.style.display = 'none';
                } else {
                    searchForm.style.display = 'none';
                }
            }
            
            function startGeolocation() {
                if (navigator.geolocation) {
                    loadingIndicator.style.display = 'flex';
                    searchForm.style.display = 'none';
                    errorMessage.style.display = 'none';
                    
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const lat = position.coords.latitude;
                            const lon = position.coords.longitude;
                            window.location.href = `map.php?lat=${lat}&lon=${lon}&locate=true`;
                        },
                        function(error) {
                            console.error("Erreur de géolocalisation:", error);
                            loadingIndicator.style.display = 'none';
                            errorMessage.style.display = 'flex';
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        }
                    );
                } else {
                    alert("Votre navigateur ne supporte pas la géolocalisation.");
                }
            }
            
            function loadDistricts() {
                fetch('search.php?action=getDistricts')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            data.data.forEach(district => {
                                const option = document.createElement('option');
                                option.value = district;
                                option.textContent = district;
                                districtSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Erreur lors du chargement des arrondissements:', error));
            }
            
            function loadFountainTypes() {
                fetch('search.php?action=getTypes')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            data.data.forEach(type => {
                                const option = document.createElement('option');
                                option.value = type;
                                option.textContent = type;
                                typeSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Erreur lors du chargement des types de fontaines:', error));
            }

            const userProfile = document.querySelector('.user-profile');
            if (userProfile) {
                userProfile.addEventListener('click', function(e) {
                    const dropdownMenu = this.querySelector('.dropdown-menu');
                    dropdownMenu.classList.toggle('show');
                    e.stopPropagation();
                });
                
                document.addEventListener('click', function() {
                    const dropdownMenu = document.querySelector('.dropdown-menu');
                    if (dropdownMenu && dropdownMenu.classList.contains('show')) {
                        dropdownMenu.classList.remove('show');
                    }
                });
            }
        });
    </script>
</body>
</html>