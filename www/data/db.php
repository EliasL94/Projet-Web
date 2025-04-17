<?php
$dbConfig = [
    'host' => 'mysql-elias.alwaysdata.net',
    'dbname' => 'elias_fontaines',
    'user' => 'elias',
    'password' => 'Elias.94(!)%',
];

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}",
        $dbConfig['user'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}
?>