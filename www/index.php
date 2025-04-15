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
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet-routing-machine/3.2.12/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="CSS/index.css" />
    
</head>
<body>
    <div class="header">
        <h1>Fontaines à boire de Paris</h1>
        <a href="home.php" class="back-to-home">
            <i class="fas fa-home"></i>
        </a>
    </div>
    
    <div id="map"></div>
    
    <div class="info-panel">
        <h3>Informations</h3>
        <p>Nombre de fontaines: <span id="fountain-count">0</span></p>
        <p>Cliquez sur un marqueur pour voir les détails de la fontaine.</p>
        <div id="route-details" style="display: none;">
            <h4>Itinéraire</h4>
            <div id="route-info" class="route-info">
                Distance: <span id="route-distance">0</span> m<br>
                Temps estimé: <span id="route-time">0</span> min
            </div>
            <button id="cancel-route-button" class="cancel-route-button">
                <i class="fas fa-times"></i> Annuler l'itinéraire
            </button>
        </div>
    </div>
    
    <button id="location-button" class="location-button" title="Trouver ma position">
        <i class="fas fa-location-arrow"></i>
    </button>
    
    
    
    <div id="zoom-notification" class="zoom-notification">
        Zoom pour voir les détails des fontaines
    </div>

    <div class="search-container">
        <div class="search-header">
            <h2 class="search-title">Recherche de fontaines</h2>
        </div>
        <form id="search-form">
            <div class="form-group">
                <label for="search-text">Recherche par mot-clé</label>
                <div class="search-input-group">
                    <input type="text" id="search-text" name="search" placeholder="Rue, arrondissement, type..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
            </div>
        
        <div class="form-group">
            <label>Type de fontaine</label>
            <div class="checkbox-group scrollable" id="fountain-types">
                <!-- Sera rempli dynamiquement -->
            </div>
        </div>
        
        <div class="form-group">
            <label for="district-select">Arrondissement</label>
            <select id="district-select" name="district">
                <option value="">Tous les arrondissements</option>
                <!-- Sera rempli dynamiquement -->
            </select>
        </div>
        
        <div class="form-group">
            <label for="model-select">Modèle de fontaine</label>
            <select id="model-select" name="model">
                <option value="">Tous les modèles</option>
                <!-- Sera rempli dynamiquement -->
            </select>
        </div>
        
        <div class="form-group">
            <label for="available-select">Disponibilité</label>
            <select id="available-select" name="available">
                <option value="">Toutes</option>
                <option value="1">Disponibles uniquement</option>
                <option value="0">Non disponibles uniquement</option>
            </select>
        </div>
        
        <div class="form-group location-filter">
            <label for="use-location">Utiliser ma position</label>
            <label class="switch">
                <input type="checkbox" id="use-location" <?php echo ($userLat && $userLon) ? 'checked' : ''; ?>>
                <span class="slider round"></span>
            </label>
        </div>
        
        <div class="form-group distance-filter" style="display: <?php echo ($userLat && $userLon) ? 'block' : 'none'; ?>;">
            <label for="max-distance">Distance maximum (m)</label>
            <div class="distance-slider-container">
                <input type="range" id="max-distance" name="maxDistance" min="100" max="5000" step="100" value="1000">
                <span id="distance-value">1000 m</span>
            </div>
        </div>
        
        <div class="form-group">
            <label for="sort-select">Trier par</label>
            <select id="sort-select" name="sort">
                <option value="type">Type de fontaine</option>
                <option value="district">Arrondissement</option>
                <option value="model">Modèle</option>
                <option value="availability">Disponibilité</option>
                <option value="distance" <?php echo ($userLat && $userLon) ? '' : 'disabled'; ?>>Distance</option>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="search-button">
                <i class="fas fa-search"></i> Rechercher
                </button>
                <button type="reset" class="reset-button">
                    <i class="fas fa-undo"></i> Réinitialiser
                </button>
            </div>
        </form>
        
        <div class="results-container">
            <h3 class="results-title">Résultats <span id="results-count">(0)</span></h3>
            <div id="results-list"></div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-routing-machine/3.2.12/leaflet-routing-machine.min.js"></script>
    
    <script>

        const initialUserLat = <?php echo $userLat ? $userLat : 'null'; ?>;
        const initialUserLon = <?php echo $userLon ? $userLon : 'null'; ?>;
        const shouldLocateUser = <?php echo $shouldLocate ? 'true' : 'false'; ?>;
        const searchQuery = "<?php echo addslashes($searchQuery); ?>";
        const searchDistrict = "<?php echo addslashes($searchDistrict); ?>";
        const searchType = "<?php echo addslashes($searchType); ?>";


        let map;
        let userMarker = null;
        let userPosition = null;
        let watchId = null;
        let routingControl = null;
        let selectedFountain = null;
        let fountainMarkers = [];
        let fountainClusters = [];
        let zoomNotificationTimeout = null;
        
        map = L.map('map').setView([48.8566, 2.3522], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        const userIconSvg = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="34" height="34">
            <circle cx="12" cy="7" r="4" fill="#ff4757"/>
            <path d="M15 12h-3l-1 7-3-5-3 5 2-10h3l1-2h5z" fill="#ff4757"/>
        </svg>`;
        
        const userIconUrl = 'data:image/svg+xml;base64,' + btoa(userIconSvg);
        
        const userIcon = L.icon({
            iconUrl: userIconUrl,
            iconSize: [34, 34],
            iconAnchor: [17, 17],
            popupAnchor: [0, -17]
        });
        
        const fountainDetailedSvg = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32">
            <circle cx="12" cy="12" r="10" fill="#3498db" fill-opacity="0.2"/>
            <path d="M12,2 C14,2 16,3 16,5 C16,6 15,7 14,7 C14,10 17,10 17,14 C17,17 15,19 12,19 C9,19 7,17 7,14 C7,10 10,10 10,7 C9,7 8,6 8,5 C8,3 10,2 12,2 Z" fill="#3498db"/>
            <circle cx="12" cy="14" r="2" fill="#2980b9"/>
        </svg>`;
        
        const fountainSimpleSvg = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="12" height="12">
            <circle cx="12" cy="12" r="6" fill="#3498db"/>
        </svg>`;
        
        const fountainDetailedIconUrl = 'data:image/svg+xml;base64,' + btoa(fountainDetailedSvg);
        const fountainSimpleIconUrl = 'data:image/svg+xml;base64,' + btoa(fountainSimpleSvg);
        
        const fountainDetailedIcon = L.icon({
            iconUrl: fountainDetailedIconUrl,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -16]
        });
        
        const fountainSimpleIcon = L.icon({
            iconUrl: fountainSimpleIconUrl,
            iconSize: [12, 12],
            iconAnchor: [6, 6],
            popupAnchor: [0, -6]
        });
        
        async function loadFountainData() {
            try {
                const response = await fetch('data/fontaines-a-boire.json');
                const data = await response.json();
                return data;
            } catch (error) {
                console.error("Erreur lors du chargement des données:", error);
                return [
                    {"type_objet": "FONTAINE_2EN1", "modele": "Mât source", "voie": "RUE RAMPAL", 
                     "commune": "PARIS 19EME ARRONDISSEMENT", "dispo": "OUI", 
                     "geo_point_2d": {"lon": 2.379856252563879, "lat": 48.87314712367769}},
                    {"type_objet": "FONTAINE_ARCEAU", "modele": "Fontaine Arceau", "voie": "RUE SAINT ANTOINE", 
                     "commune": "PARIS 4EME ARRONDISSEMENT", "dispo": "OUI", 
                     "geo_point_2d": {"lon": 2.3670389176759645, "lat": 48.8535561857554}},
                    {"type_objet": "FONTAINE_WALLACE", "modele": "Wallace", "voie": "AVENUE VICTORIA", 
                     "commune": "PARIS 1ER ARRONDISSEMENT", "dispo": "OUI", 
                     "geo_point_2d": {"lon": 2.3501, "lat": 48.8580}},
                    {"type_objet": "FONTAINE_WALLACE", "modele": "Wallace", "voie": "BOULEVARD DE CLICHY", 
                     "commune": "PARIS 18EME ARRONDISSEMENT", "dispo": "OUI", 
                     "geo_point_2d": {"lon": 2.3301, "lat": 48.8830}},
                    {"type_objet": "FONTAINE_BOIS", "modele": "Wallace Bois", "voie": "AVENUE DAUMESNIL", 
                     "commune": "PARIS 12EME ARRONDISSEMENT", "dispo": "OUI", 
                     "geo_shape": {"type": "Feature", "geometry": {"coordinates": [2.3795, 48.8401], "type": "Point"}}}
                ];
            }
        }
        
        function addFountainsToMap(fountains) {
            document.getElementById('fountain-count').textContent = fountains.length;
            
            fountainMarkers = [];
            
            fountains.forEach(fountain => {
                let lat, lon;
                
                if (fountain.geo_point_2d && fountain.geo_point_2d.lat && fountain.geo_point_2d.lon) {
                    lat = fountain.geo_point_2d.lat;
                    lon = fountain.geo_point_2d.lon;
                } else if (fountain.geo_shape && fountain.geo_shape.geometry && 
                          fountain.geo_shape.geometry.coordinates && 
                          fountain.geo_shape.geometry.type === "Point") {
                    lon = fountain.geo_shape.geometry.coordinates[0];
                    lat = fountain.geo_shape.geometry.coordinates[1];
                }
                
                if (lat && lon) {
                    const marker = L.marker([lat, lon], {icon: fountainSimpleIcon}).addTo(map);
                    
                    let popupContent = `<b>${fountain.type_objet || 'Fontaine'}</b><br>`;
                    if (fountain.modele) popupContent += `Modèle: ${fountain.modele}<br>`;
                    
                    let adresse = "";
                    if (fountain.no_voirie_pair) adresse += fountain.no_voirie_pair + " ";
                    else if (fountain.no_voirie_impair) adresse += fountain.no_voirie_impair + " ";
                    if (fountain.voie) adresse += fountain.voie;
                    if (adresse) popupContent += `Adresse: ${adresse}<br>`;
                    
                    if (fountain.commune) popupContent += `Arrondissement: ${fountain.commune}<br>`;
                    if (fountain.dispo) popupContent += `Disponible: ${fountain.dispo === "OUI" ? "✅ Oui" : "❌ Non"}<br>`;
                    
                    popupContent += `<button class="route-button" onclick="calculateRoute(${lat}, ${lon})">
                        <i class="fas fa-route"></i> Itinéraire
                    </button>`;
                    
                    marker.bindPopup(popupContent);
                    
                    marker.fountainData = {
                        lat: lat,
                        lon: lon,
                        name: fountain.type_objet || 'Fontaine',
                        address: adresse,
                        district: fountain.commune
                    };
                    
                    marker.on('click', function(e) {
                        selectedFountain = e.target.fountainData;
                    });
                    
                    fountainMarkers.push(marker);
                }
            });
            
            updateFountainIcons();
        }
        
        function updateFountainIcons() {
            const zoomLevel = map.getZoom();
            
            fountainMarkers.forEach(marker => {
                if (zoomLevel >= 15) {
                    marker.setIcon(fountainDetailedIcon);
                } else {
                    marker.setIcon(fountainSimpleIcon);
                }
            });
            
            if (zoomLevel < 15 && fountainMarkers.length > 0) {
                showZoomNotification();
            }
        }

        function showZoomNotification() {
            const notification = document.getElementById('zoom-notification');
            notification.style.display = 'block';
            
            if (zoomNotificationTimeout) {
                clearTimeout(zoomNotificationTimeout);
            }
            
            zoomNotificationTimeout = setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
        
        function locateUser() {
            if (navigator.geolocation) {
                map.locate({setView: true, maxZoom: 16});
                
                map.on('locationfound', onLocationFound);
                
                map.on('locationerror', onLocationError);
                
                activatePositionTracking();
            } else {
                alert("La géolocalisation n'est pas prise en charge par votre navigateur.");
            }
        }
        
        function onLocationFound(e) {
            userPosition = {
                lat: e.latitude,
                lng: e.longitude
            };
            
            if (userMarker) {
                userMarker.setLatLng([e.latitude, e.longitude]);
            } else {
                userMarker = L.marker([e.latitude, e.longitude], {icon: userIcon}).addTo(map);
                userMarker.bindPopup("Vous êtes ici").openPopup();
            }
            
            const accuracyCircle = L.circle([e.latitude, e.longitude], {
                radius: e.accuracy / 2,
                fillColor: '#ff4757',
                fillOpacity: 0.15,
                color: '#ff4757',
                weight: 2
            }).addTo(map);
            
            setTimeout(() => {
                map.removeLayer(accuracyCircle);
            }, 2000);
        }
        
        function onLocationError(e) {
            alert(e.message || "Impossible de déterminer votre position.");
        }
        
        function activatePositionTracking() {
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
            }
            
            watchId = navigator.geolocation.watchPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    userPosition = { lat, lng };
                    
                    if (userMarker) {
                        userMarker.setLatLng([lat, lng]);
                    } else {
                        userMarker = L.marker([lat, lng], {icon: userIcon}).addTo(map);
                        userMarker.bindPopup("Vous êtes ici");
                    }
                    
                    if (routingControl && selectedFountain) {
                        updateRoute();
                    }
                },
                (error) => {
                    console.error("Erreur lors du suivi de position:", error);
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 10000,
                    timeout: 10000
                }
            );
        }
        
        function calculateRoute(lat, lon) {
            if (!userPosition) {
                alert("Votre position n'est pas encore disponible. Veuillez cliquer sur le bouton de localisation.");
                return;
            }
            
            if (routingControl) {
                map.removeControl(routingControl);
            }
            
            selectedFountain = {
                lat, lon
            };
            
            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(userPosition.lat, userPosition.lng),
                    L.latLng(lat, lon)
                ],
                routeWhileDragging: false,
                showAlternatives: false,
                fitSelectedRoutes: true,
                lineOptions: {
                    styles: [
                        {color: '#3498db', opacity: 0.8, weight: 6}
                    ]
                },
                router: L.Routing.osrmv1({
                    serviceUrl: 'https://router.project-osrm.org/route/v1',
                    profile: 'foot'
                }),
                createMarker: function() { return null; }
            }).addTo(map);
            
            document.getElementById('route-details').style.display = 'block';
            
            routingControl.on('routesfound', function(e) {
                const routes = e.routes;
                const route = routes[0];
                
                const distanceInMeters = route.summary.totalDistance;
                const durationInMinutes = Math.round(route.summary.totalTime / 60);
                
                document.getElementById('route-distance').textContent = distanceInMeters;
                document.getElementById('route-time').textContent = durationInMinutes;
            });
        }
        
        function cancelRoute() {
            if (routingControl) {
                map.removeControl(routingControl);
                routingControl = null;
                selectedFountain = null;
                document.getElementById('route-details').style.display = 'none';
            }
        }
        
        function updateRoute() {
            if (routingControl && userPosition && selectedFountain) {
                routingControl.setWaypoints([
                    L.latLng(userPosition.lat, userPosition.lng),
                    L.latLng(selectedFountain.lat, selectedFountain.lon)
                ]);
            }
        }
        
        map.on('zoomend', updateFountainIcons);
        
        document.getElementById('location-button').addEventListener('click', locateUser);
        
        document.getElementById('cancel-route-button').addEventListener('click', cancelRoute);
        
        window.calculateRoute = calculateRoute;
        window.cancelRoute = cancelRoute;
        
        async function init() {
            const fountainData = await loadFountainData();
            addFountainsToMap(fountainData);
        }
        
        init();
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchForm = document.getElementById('search-form');
        const useLocationCheckbox = document.getElementById('use-location');
        const distanceFilter = document.querySelector('.distance-filter');
        const distanceSlider = document.getElementById('max-distance');
        const distanceValue = document.getElementById('distance-value');
        const sortSelect = document.getElementById('sort-select');
        const resultsList = document.getElementById('results-list');
        const resultsCount = document.getElementById('results-count');
        const fountainTypesContainer = document.getElementById('fountain-types');
        const districtSelect = document.getElementById('district-select');
        const modelSelect = document.getElementById('model-select');
        const searchToggleButton = document.getElementById('search-toggle-button');
        const searchContainer = document.querySelector('.search-container');

        let userPosition = null;

        loadFountainTypes();
        loadDistricts();
        loadModels();

        useLocationCheckbox.addEventListener('change', function() {
            if (this.checked) {
                getLocation();
                distanceFilter.style.display = 'block';
                sortSelect.querySelector('option[value="distance"]').disabled = false;
            } else {
                distanceFilter.style.display = 'none';
                sortSelect.querySelector('option[value="distance"]').disabled = true;
                if (sortSelect.value === 'distance') sortSelect.value = 'type';
            }
        });

        distanceSlider.addEventListener('input', function() {
            distanceValue.textContent = this.value + ' m';
        });

        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            searchFountains();
        });

        searchForm.addEventListener('reset', function() {
            setTimeout(() => {
                resultsList.innerHTML = '';
                resultsCount.textContent = '(0)';
                useLocationCheckbox.checked = false;
                distanceFilter.style.display = 'none';
                sortSelect.querySelector('option[value="distance"]').disabled = true;
                document.querySelectorAll('#fountain-types input[type="checkbox"]').forEach(checkbox => checkbox.checked = false);
            }, 10);
        });

        searchToggleButton.addEventListener('click', function() {
            searchContainer.classList.toggle('active');
            if (searchContainer.classList.contains('active') && resultsList.children.length === 0) {
                searchFountains();
            }
        });

        closeSearchButton.addEventListener('click', function() {
            searchContainer.classList.remove('active');
        });

        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    position => {
                        userPosition = { lat: position.coords.latitude, lon: position.coords.longitude };
                    },
                    error => {
                        console.error("Erreur de géolocalisation:", error);
                        alert("Impossible d'obtenir votre position. La fonctionnalité de distance est désactivée.");
                        useLocationCheckbox.checked = false;
                        distanceFilter.style.display = 'none';
                        sortSelect.querySelector('option[value="distance"]').disabled = true;
                    }
                );
            } else {
                alert("La géolocalisation n'est pas prise en charge par votre navigateur.");
                useLocationCheckbox.checked = false;
            }
        }

        function loadFountainTypes() {
            fetch('search.php?action=getTypes')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fountainTypesContainer.innerHTML = data.data.map(type => `
                            <div class="checkbox-item">
                                <input type="checkbox" name="types[]" value="${type}" id="type-${type.toLowerCase().replace(/[^a-z0-9]/g, '-')}">
                                <label for="type-${type.toLowerCase().replace(/[^a-z0-9]/g, '-')}">${type}</label>
                            </div>
                        `).join('');
                    }
                })
                .catch(error => console.error('Erreur lors du chargement des types:', error));
        }

        function loadDistricts() {
            fetch('search.php?action=getDistricts')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        districtSelect.innerHTML = '<option value="">Tous les arrondissements</option>' + data.data.map(district => `<option value="${district}">${district}</option>`).join('');
                    }
                })
                .catch(error => console.error('Erreur lors du chargement des arrondissements:', error));
        }

        function loadModels() {
            fetch('search.php?action=getModels')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modelSelect.innerHTML = '<option value="">Tous les modèles</option>' + data.data.map(model => `<option value="${model}">${model}</option>`).join('');
                    }
                })
                .catch(error => console.error('Erreur lors du chargement des modèles:', error));
        }

        function searchFountains() {
            const formData = new FormData(searchForm);
            const searchParams = new URLSearchParams({
                action: 'getFountains',
                search: formData.get('search') || '',
                district: formData.get('district') || '',
                available: formData.get('available') || '',
                model: formData.get('model') || '',
                sort: formData.get('sort') || 'type',
                types: Array.from(document.querySelectorAll('#fountain-types input[type="checkbox"]:checked')).map(checkbox => checkbox.value).join(','),
                ...(useLocationCheckbox.checked && userPosition ? { userLat: userPosition.lat, userLon: userPosition.lon, maxDistance: distanceSlider.value } : {})
            });

            resultsList.innerHTML = '<div class="loading-results"><div class="spinner"></div><p>Recherche en cours...</p></div>';

            fetch(`search.php?${searchParams.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayResults(data.data);
                    } else {
                        resultsList.innerHTML = '<p class="error-message">Une erreur est survenue lors de la recherche.</p>';
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la recherche:', error);
                    resultsList.innerHTML = '<p class="error-message">Une erreur est survenue lors de la recherche.</p>';
                });
        }

        function displayResults(fountains) {
            resultsList.innerHTML = '';
            resultsCount.textContent = `(${fountains.length})`;

            if (fountains.length === 0) {
                resultsList.innerHTML = '<p class="no-results">Aucune fontaine trouvée correspondant à vos critères.</p>';
                return;
            }

            fountains.forEach(fountain => {
                let lat = null, lon = null;
                try {
                    if (fountain.geo_point_2d) {
                        lat = fountain.geo_point_2d.lat;
                        lon = fountain.geo_point_2d.lon;
                    } else if (fountain.geo_shape) {
                        const coords = JSON.parse(fountain.geo_shape).geometry.coordinates;
                        lon = coords[0];
                        lat = coords[1];
                    }
                } catch (e) {
                    console.warn('Coordonnées non disponibles pour cette fontaine');
                }

                const card = document.createElement('div');
                card.className = 'fountain-card';
                card.innerHTML = `
                    <div class="fountain-info">
                        <div class="fountain-header">
                            <h4 class="fountain-name">${fountain.type_objet || 'Fontaine'}</h4>
                            <span class="available-tag ${fountain.dispo === 'OUI' ? 'available-yes' : 'available-no'}">
                                ${fountain.dispo === 'OUI' ? 'Disponible' : 'Non disponible'}
                            </span>
                        </div>
                        <p class="fountain-address">${getAddress(fountain)}</p>
                        <p class="fountain-details">
                            ${fountain.modele ? `<span class="detail-item">Modèle: ${fountain.modele}</span>` : ''}
                            ${fountain.commune ? `<span class="detail-item">${fountain.commune}</span>` : ''}
                        </p>
                        ${fountain.distance !== null && fountain.distance !== undefined ? `<p class="fountain-distance">Distance: ${formatDistance(fountain.distance)}</p>` : ''}
                    </div>
                    <div class="fountain-actions">
                        ${lat && lon ? `
                            <button class="view-on-map-button" onclick="centerMapOn(${lat}, ${lon})">
                                <i class="fas fa-map-marker-alt"></i> Voir sur la carte
                            </button>
                            <button class="route-button" onclick="calculateRoute(${lat}, ${lon})">
                                <i class="fas fa-route"></i> Itinéraire
                            </button>
                        ` : ''}
                    </div>
                `;
                resultsList.appendChild(card);
            });
        }

        function getAddress(fountain) {
            let address = '';
            if (fountain.no_voirie_pair) address += fountain.no_voirie_pair + ' ';
            else if (fountain.no_voirie_impair) address += fountain.no_voirie_impair + ' ';
            if (fountain.voie) address += fountain.voie;
            return address || 'Adresse non disponible';
        }

        function formatDistance(distance) {
            return distance < 1000 ? Math.round(distance) + ' m' : (distance / 1000).toFixed(1) + ' km';
        }

        setTimeout(() => {
            searchFountains();
        }, 500);
    });

    function centerMapOn(lat, lon) {
        map.setView([lat, lon], 17);
        searchContainer.classList.remove('active');
    }

    const searchToggleButton = document.getElementById('search-toggle-button');
    const searchContainer = document.querySelector('.search-container');

    searchContainer.classList.remove('active');

    searchContainer.addEventListener('click', function(event) {
        event.stopPropagation();
    });

    document.addEventListener('click', function(event) {
        if (!searchContainer.contains(event.target) && event.target !== searchToggleButton && !searchToggleButton.contains(event.target)) {
            searchContainer.classList.remove('active');
        }
    });
</script>

</body>
</html>