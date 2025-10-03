<?php
session_start();
require_once 'routeros_api.php';

if (!isset($_SESSION['configured'])) {
    header('Location: index.php');
    exit;
}

// Charger la configuration
$configFile = 'config.json';
$config = json_decode(file_get_contents($configFile), true);

// Fonction de génération de voucher
function generateVoucher($length = 8) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $voucher = '';
    for ($i = 0; $i < $length; $i++) {
        $voucher .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $voucher;
}

$generatedVouchers = [];
$success = false;
$error = null;
$profiles = [];

// Connexion à Mikrotik pour récupérer les profils
$api = new RouterOsAPI();
if ($api->connect($config['mikrotik_ip'], $config['mikrotik_port'], $config['mikrotik_user'], $config['mikrotik_pass'])) {
    $profiles = $api->getHotspotProfiles();
    $api->disconnect();
} else {
    $error = "⚠️ Impossible de récupérer les profils : " . $api->getLastError();
}

// Traitement de création de profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_profile'])) {
    $profileName = $_POST['profile_name'];
    $rateLimit = '';
    $sessionTimeout = '';

    // Construction du rate limit
    if (!empty($_POST['upload_limit']) && !empty($_POST['download_limit'])) {
        $uploadUnit = $_POST['upload_unit'];
        $downloadUnit = $_POST['download_unit'];
        $rateLimit = $_POST['upload_limit'] . $uploadUnit . '/' . $_POST['download_limit'] . $downloadUnit;
    }

    // Construction du session timeout
    if (!empty($_POST['time_value'])) {
        $timeValue = (int)$_POST['time_value'];
        $timeUnit = $_POST['time_unit'];

        switch ($timeUnit) {
            case 'minutes':
                $sessionTimeout = sprintf('%02d:%02d:00', 0, $timeValue);
                break;
            case 'hours':
                $sessionTimeout = sprintf('%02d:00:00', $timeValue);
                break;
            case 'days':
                $sessionTimeout = ($timeValue * 24) . 'h';
                break;
            case 'weeks':
                $sessionTimeout = ($timeValue * 7) . 'd';
                break;
            case 'months':
                $sessionTimeout = ($timeValue * 30) . 'd';
                break;
        }
    }

    $sharedUsers = (int)$_POST['shared_users'];

    // Créer le profil dans Mikrotik
    $api = new RouterOsAPI();
    if ($api->connect($config['mikrotik_ip'], $config['mikrotik_port'], $config['mikrotik_user'], $config['mikrotik_pass'])) {
        if ($api->addHotspotProfile($profileName, $rateLimit, $sessionTimeout, $sharedUsers)) {
            $success = true;
            $error = "✅ Profil '$profileName' créé avec succès !";
            // Recharger les profils
            $profiles = $api->getHotspotProfiles();
        } else {
            $error = "❌ Erreur lors de la création du profil : " . $api->getLastError();
        }
        $api->disconnect();
    } else {
        $error = "❌ Impossible de se connecter au Mikrotik : " . $api->getLastError();
    }
}

// Traitement de génération de vouchers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $quantity = (int)$_POST['quantity'];
    $profile = $_POST['profile'];
    $duration = $_POST['duration'];
    $price = $_POST['price'];

    // Se connecter à Mikrotik
    $api = new RouterOsAPI();
    if ($api->connect($config['mikrotik_ip'], $config['mikrotik_port'], $config['mikrotik_user'], $config['mikrotik_pass'])) {

        $successCount = 0;
        $errorCount = 0;

        for ($i = 0; $i < $quantity; $i++) {
            $username = generateVoucher();
            $password = generateVoucher(6);

            // Créer l'utilisateur dans Mikrotik
            $comment = "Peleforo - " . date('Y-m-d H:i:s') . " - " . $price . " FCFA";

            if ($api->addHotspotUser($username, $password, $profile, $comment)) {
                $generatedVouchers[] = [
                    'username' => $username,
                    'password' => $password,
                    'profile'  => $profile,
                    'duration' => $duration,
                    'price'    => $price,
                    'created'  => date('Y-m-d H:i:s'),
                    'status'   => 'success'
                ];
                $successCount++;
            } else {
                $generatedVouchers[] = [
                    'username' => $username,
                    'password' => $password,
                    'profile'  => $profile,
                    'duration' => $duration,
                    'price'    => $price,
                    'created'  => date('Y-m-d H:i:s'),
                    'status'   => 'error',
                    'error'    => $api->getLastError()
                ];
                $errorCount++;
            }
        }

        $api->disconnect();

        // Sauvegarder dans l'historique (seulement les succès)
        $historyFile = 'vouchers_history.json';
        $history = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];
        $successVouchers = array_filter($generatedVouchers, function($v) {
            return $v['status'] === 'success';
        });
        $history = array_merge($history, $successVouchers);
        file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT));

        $success = true;

        if ($errorCount > 0) {
            $error = "⚠️ $successCount voucher(s) créé(s) avec succès, $errorCount échec(s)";
        }

    } else {
        $error = "❌ Impossible de se connecter au Mikrotik : " . $api->getLastError();
    }
}
?>
