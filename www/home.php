<?php 
    include('data/db.php');
    
    
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
                <button id="search-button" class="btn secondary">
                    <i class="fas fa-search"></i> Rechercher
                </button>
            </div>
            
            <div id="search-form" class="search-form">
                <h3>Recherche de fontaines</h3>
                <form id="search-direct-form" action="index.php" method="GET">
                    <div class="form-group">
                        <label for="search-text">Mot-clé</label>
                        <input type="text" id="search-text" name="search" placeholder="Rue, arrondissement, type...">
                    </div>
                    
                    <div class="form-group">
                        <label for="district-select">Arrondissement</label>
                        <select id="district-select" name="district">
                            <option value="">Tous les arrondissements</option>
                            <!-- Options chargées dynamiquement -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="type-select">Type de fontaine</label>
                        <select id="type-select" name="type">
                            <option value="">Tous les types</option>
                            <!-- Options chargées dynamiquement -->
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
            // Éléments du DOM
            const locateButton = document.getElementById('locate-button');
            const searchButton = document.getElementById('search-button');
            const searchForm = document.getElementById('search-form');
            const loadingIndicator = document.getElementById('loading');
            const errorMessage = document.getElementById('error-message');
            const retryButton = document.getElementById('retry-button');
            const districtSelect = document.getElementById('district-select');
            const typeSelect = document.getElementById('type-select');
            
            // Cacher le formulaire de recherche et le message d'erreur au chargement
            searchForm.style.display = 'none';
            loadingIndicator.style.display = 'none';
            errorMessage.style.display = 'none';
            
            // Charger les arrondissements et types de fontaines
            loadDistricts();
            loadFountainTypes();
            
            // Gestionnaire d'événement pour le bouton de localisation
            locateButton.addEventListener('click', function() {
                startGeolocation();
            });
            
            // Gestionnaire d'événement pour le bouton de recherche
            searchButton.addEventListener('click', function() {
                toggleSearchForm();
            });
            
            // Gestionnaire d'événement pour le bouton de réessai
            retryButton.addEventListener('click', function() {
                errorMessage.style.display = 'none';
                startGeolocation();
            });
            
            // Fonction pour basculer l'affichage du formulaire de recherche
            function toggleSearchForm() {
                if (searchForm.style.display === 'none') {
                    searchForm.style.display = 'block';
                    errorMessage.style.display = 'none';
                } else {
                    searchForm.style.display = 'none';
                }
            }
            
            // Fonction pour démarrer la géolocalisation
            function startGeolocation() {
                if (navigator.geolocation) {
                    loadingIndicator.style.display = 'flex';
                    searchForm.style.display = 'none';
                    errorMessage.style.display = 'none';
                    
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            // Succès - rediriger vers index.php avec les coordonnées
                            const lat = position.coords.latitude;
                            const lon = position.coords.longitude;
                            window.location.href = `index.php?lat=${lat}&lon=${lon}&locate=true`;
                        },
                        function(error) {
                            // Erreur de géolocalisation
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
                    // Navigateur ne supporte pas la géolocalisation
                    alert("Votre navigateur ne supporte pas la géolocalisation.");
                }
            }
            
            // Fonction pour charger les arrondissements
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
            
            // Fonction pour charger les types de fontaines
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
        });
    </script>
</body>
</html>