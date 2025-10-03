<?php
session_start();

// Charger la configuration
$configFile = __DIR__ . '/config.json';
if (!file_exists($configFile)) {
    die("❌ Fichier config.json introuvable. Configurez d'abord votre Mikrotik via index.php.");
}

$config = json_decode(file_get_contents($configFile), true);

// Vérifier que la config contient les infos nécessaires
if (empty($config['ip']) || empty($config['username']) || empty($config['password'])) {
    die("❌ Paramètres Mikrotik manquants dans config.json.");
}

$mikrotik_ip   = $config['ip'];
$mikrotik_user = $config['username'];
$mikrotik_pass = $config['password'];

// Vérifier que le splash existe
$splashFile = __DIR__ . '/splash_generated.html';
if (!file_exists($splashFile)) {
    die("❌ Aucun splash généré. Créez-le via customize.php avant d'uploader.");
}

// Connexion FTP
$conn_id = ftp_connect($mikrotik_ip);
if (!$conn_id) {
    die("❌ Impossible de se connecter à Mikrotik ($mikrotik_ip) en FTP.");
}

// Authentification
if (!ftp_login($conn_id, $mikrotik_user, $mikrotik_pass)) {
    ftp_close($conn_id);
    die("❌ Authentification FTP échouée. Vérifiez vos identifiants.");
}

// Mode passif
ftp_pasv($conn_id, true);

// Uploader en tant que hotspot/login.html
$remoteFile = "hotspot/login.html";
if (ftp_put($conn_id, $remoteFile, $splashFile, FTP_BINARY)) {
    echo "✅ Splash uploadé avec succès en tant que login.html dans /hotspot/";
} else {
    echo "❌ Erreur lors de l’upload du splash.";
}

// Fermer la connexion
ftp_close($conn_id);
?>
