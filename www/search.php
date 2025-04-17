<?php

include_once 'data/db.php';

function getFountains($filters = []) {
    global $pdo;
    
    $conditions = [];
    $params = [];
    
    $sql = "SELECT * FROM fontaines_a_boire WHERE 1=1";
    
    if (!empty($filters['search'])) {
        $search = '%' . $filters['search'] . '%';
        $conditions[] = "(voie LIKE ? OR commune LIKE ? OR type_objet LIKE ? OR modele LIKE ? OR no_voirie_pair LIKE ? OR no_voirie_impair LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }
    
    if (!empty($filters['types']) && is_array($filters['types'])) {
        $typePlaceholders = implode(',', array_fill(0, count($filters['types']), '?'));
        $conditions[] = "type_objet IN ($typePlaceholders)";
        foreach ($filters['types'] as $type) {
            $params[] = $type;
        }
    }
    
    if (!empty($filters['district'])) {
        $conditions[] = "commune = ?";
        $params[] = $filters['district'];
    }
    
    if (isset($filters['available']) && $filters['available'] !== '') {
        $conditions[] = "dispo = ?";
        $params[] = $filters['available'] === '1' ? 'OUI' : 'NON';
    }
    
    if (!empty($filters['model'])) {
        $conditions[] = "modele = ?";
        $params[] = $filters['model'];
    }
    
    if (!empty($conditions)) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }
    
    switch($filters['sort'] ?? 'type') {
        case 'district':
            $sql .= " ORDER BY commune ASC, voie ASC";
            break;
        case 'model':
            $sql .= " ORDER BY modele ASC, type_objet ASC";
            break;
        case 'availability':
            $sql .= " ORDER BY dispo DESC, type_objet ASC";
            break;
        default:
            $sql .= " ORDER BY type_objet ASC, voie ASC";
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur SQL: " . $e->getMessage());
        return [];
    }
}

function getDistricts() {
    global $pdo;
    
    try {
        $sql = "SELECT DISTINCT commune FROM fontaines_a_boire WHERE commune IS NOT NULL ORDER BY commune";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Erreur SQL (getDistricts): " . $e->getMessage());
        return [];
    }
}

function getFountainTypes() {
    global $pdo;
    
    try {
        $sql = "SELECT DISTINCT type_objet FROM fontaines_a_boire WHERE type_objet IS NOT NULL ORDER BY type_objet";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Erreur SQL (getFountainTypes): " . $e->getMessage());
        return [];
    }
}

function getFountainModels() {
    global $pdo;
    
    try {
        $sql = "SELECT DISTINCT modele FROM fontaines_a_boire WHERE modele IS NOT NULL ORDER BY modele";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Erreur SQL (getFountainModels): " . $e->getMessage());
        return [];
    }
}

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000;
    
    $lat1Rad = deg2rad($lat1);
    $lon1Rad = deg2rad($lon1);
    $lat2Rad = deg2rad($lat2);
    $lon2Rad = deg2rad($lon2);
    
    $dLat = $lat2Rad - $lat1Rad;
    $dLon = $lon2Rad - $lon1Rad;
    
    $a = sin($dLat/2) * sin($dLat/2) + cos($lat1Rad) * cos($lat2Rad) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c; // distance en mètres
}

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'getFountains':
            try {
                $filters = [
                    'search' => $_GET['search'] ?? '',
                    'types' => isset($_GET['types']) ? explode(',', $_GET['types']) : [],
                    'district' => $_GET['district'] ?? '',
                    'available' => $_GET['available'] ?? '',
                    'sort' => $_GET['sort'] ?? 'type'
                ];
                
                $fountains = getFountains($filters);
                
                if (isset($_GET['userLat']) && isset($_GET['userLon'])) {
                    $userLat = floatval($_GET['userLat']);
                    $userLon = floatval($_GET['userLon']);
                    
                    foreach ($fountains as &$fountain) {
                        $fountainCoords = null;
                        
                        // Traitement des coordonnées depuis geo_point_2d
                        if (isset($fountain['geo_point_2d'])) {
                            if (is_string($fountain['geo_point_2d'])) {
                                try {
                                    $fountainCoords = json_decode($fountain['geo_point_2d'], true);
                                } catch (Exception $e) {
                                    error_log("Erreur JSON geo_point_2d: " . $e->getMessage());
                                }
                            } elseif (is_array($fountain['geo_point_2d'])) {
                                $fountainCoords = $fountain['geo_point_2d'];
                            }
                        }
                        // Traitement des coordonnées depuis geo_shape
                        elseif (isset($fountain['geo_shape'])) {
                            try {
                                if (is_string($fountain['geo_shape'])) {
                                    $geoShape = json_decode($fountain['geo_shape'], true);
                                } else {
                                    $geoShape = $fountain['geo_shape'];
                                }
                                
                                if ($geoShape && isset($geoShape['geometry']) && 
                                    isset($geoShape['geometry']['coordinates']) && 
                                    $geoShape['geometry']['type'] === "Point") {
                                    $fountainCoords = [
                                        'lon' => $geoShape['geometry']['coordinates'][0],
                                        'lat' => $geoShape['geometry']['coordinates'][1]
                                    ];
                                }
                            } catch (Exception $e) {
                                error_log("Erreur JSON geo_shape: " . $e->getMessage());
                            }
                        }
                        
                        if ($fountainCoords && isset($fountainCoords['lat']) && isset($fountainCoords['lon'])) {
                            $distance = calculateDistance(
                                $userLat, 
                                $userLon, 
                                $fountainCoords['lat'], 
                                $fountainCoords['lon']
                            );
                            
                            $fountain['distance'] = $distance;
                        } else {
                            $fountain['distance'] = null;
                        }
                    }
                    
                    if (isset($_GET['sort']) && $_GET['sort'] === 'distance') {
                        usort($fountains, function($a, $b) {
                            if ($a['distance'] === null) return 1;
                            if ($b['distance'] === null) return -1;
                            return $a['distance'] <=> $b['distance'];
                        });
                    }
                    
                    if (isset($_GET['maxDistance']) && $_GET['maxDistance'] > 0) {
                        $maxDistance = floatval($_GET['maxDistance']);
                        $fountains = array_filter($fountains, function($fountain) use ($maxDistance) {
                            return $fountain['distance'] === null || $fountain['distance'] <= $maxDistance;
                        });
                        $fountains = array_values($fountains);
                    }
                }
                
                echo json_encode(['success' => true, 'data' => array_values($fountains)]);
            } catch (Exception $e) {
                error_log("Erreur dans getFountains AJAX: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la recherche: ' . $e->getMessage()]);
            }
            break;
            
        case 'getDistricts':
            try {
                $districts = getDistricts();
                echo json_encode(['success' => true, 'data' => $districts]);
            } catch (Exception $e) {
                error_log("Erreur dans getDistricts AJAX: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des arrondissements']);
            }
            break;

        case 'getModels':
            try {
                $models = getFountainModels();
                echo json_encode(['success' => true, 'data' => $models]);
            } catch (Exception $e) {
                error_log("Erreur dans getModels AJAX: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des modèles de fontaines']);
            }
            break;
            
        case 'getTypes':
            try {
                $types = getFountainTypes();
                echo json_encode(['success' => true, 'data' => $types]);
            } catch (Exception $e) {
                error_log("Erreur dans getTypes AJAX: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des types de fontaines']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
    exit;
}
?>