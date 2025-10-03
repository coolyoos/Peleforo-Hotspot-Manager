<?php
session_start();
require_once 'routeros_api.php';

$configFile = 'config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : null;

// Déjà configuré
if ($config && isset($config['mikrotik_ip'])) {
    $_SESSION['configured'] = true;
    header("Location: dashboard.php");
    exit;
}

$error = null;
$success = null;

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mikrotik_ip   = trim($_POST['mikrotik_ip']);
    $mikrotik_port = trim($_POST['mikrotik_port']);
    $mikrotik_user = trim($_POST['mikrotik_user']);
    $mikrotik_pass = trim($_POST['mikrotik_pass']);

    if ($mikrotik_ip && $mikrotik_port && $mikrotik_user && $mikrotik_pass) {
        $api = new RouterOsAPI();
        if ($api->connect($mikrotik_ip, $mikrotik_port, $mikrotik_user, $mikrotik_pass)) {
            
            // Vérifier qu’il y a au moins un serveur hotspot
            $hotspots = $api->comm("/ip/hotspot/print");
            if (empty($hotspots)) {
                $error = "⚠️ Connexion Mikrotik OK mais aucun Hotspot trouvé. Activez d’abord le hotspot.";
            } else {
                // Sauvegarde de la configuration
                $newConfig = [
                    'mikrotik_ip'   => $mikrotik_ip,
                    'mikrotik_port' => $mikrotik_port,
                    'mikrotik_user' => $mikrotik_user,
                    'mikrotik_pass' => $mikrotik_pass
                ];
                file_put_contents($configFile, json_encode($newConfig, JSON_PRETTY_PRINT));
                $_SESSION['configured'] = true;
                $success = "✅ Connexion réussie et Hotspot détecté !";
                header("Refresh: 2; URL=dashboard.php");
            }
            $api->disconnect();
        } else {
            $error = "❌ Impossible de se connecter au Mikrotik. Vérifiez l’IP, le port, l’utilisateur et le mot de passe.";
        }
    } else {
        $error = "⚠️ Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Peleforo Hotspot Manager - Configuration</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }
        .card {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            width: 400px;
            text-align: center;
        }
        .card h1 { margin-bottom: 20px; font-size: 22px; color: #667eea; }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        .form-group input { width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; }
        .btn { margin-top: 10px; width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea, #764ba2); border: none; border-radius: 8px; color: #fff; font-weight: bold; font-size: 15px; cursor: pointer; }
        .btn:hover { background: linear-gradient(135deg, #5a67d8, #6b46c1); }
        .error, .success { padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 14px; }
        .error { background: #fee; color: #c0392b; }
        .success { background: #e6ffed; color: #2ecc71; }
    </style>
</head>
<body>
    <div class="card">
        <h1>⚙️ Configuration initiale</h1>
        <p>Entrez les paramètres de connexion à votre Mikrotik</p>

        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Adresse IP du Mikrotik</label>
                <input type="text" name="mikrotik_ip" placeholder="192.168.88.1" required>
            </div>
            <div class="form-group">
                <label>Port API</label>
                <input type="number" name="mikrotik_port" placeholder="8728" value="8728" required>
            </div>
            <div class="form-group">
                <label>Utilisateur</label>
                <input type="text" name="mikrotik_user" placeholder="admin" required>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="mikrotik_pass" placeholder="••••••" required>
            </div>
            <button type="submit" class="btn">🔗 Tester et Enregistrer</button>
        </form>
    </div>
</body>
</html>
