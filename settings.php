<?php
session_start();

if (!isset($_SESSION['configured'])) {
    header('Location: index.php');
    exit;
}

$configFile = 'config.json';
$config = json_decode(file_get_contents($configFile), true);
$updated = false;
$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        $config = [
            'mikrotik_ip' => $_POST['mikrotik_ip'],
            'mikrotik_port' => $_POST['mikrotik_port'],
            'mikrotik_user' => $_POST['mikrotik_user'],
            'mikrotik_pass' => $_POST['mikrotik_pass'],
            'hotspot_name' => $_POST['hotspot_name']
        ];
        
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
        
        $_SESSION['mikrotik_ip'] = $config['mikrotik_ip'];
        $_SESSION['mikrotik_port'] = $config['mikrotik_port'];
        $_SESSION['mikrotik_user'] = $config['mikrotik_user'];
        $_SESSION['hotspot_name'] = $config['hotspot_name'];
        
        $updated = true;
    }
    
    if (isset($_POST['test_connection'])) {
        // Simulation de test de connexion
        // Dans une vraie implémentation, on testerait la connexion API ici
        $testResult = [
            'success' => true,
            'message' => 'Connexion réussie au Mikrotik!',
            'details' => [
                'IP' => $_POST['mikrotik_ip'],
                'Port' => $_POST['mikrotik_port'],
                'Hotspot' => $_POST['hotspot_name']
            ]
        ];
    }
    
    if (isset($_POST['reset'])) {
        session_destroy();
        unlink($configFile);
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Peleforo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }
        
        .back-btn {
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .page-header p {
            color: #666;
            font-size: 14px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 15px;
        }
        
        .btn {
            padding: 14px 24px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 100%;
        }
        
        .btn-secondary {
            background: #3498db;
            color: white;
            width: 100%;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .button-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }
        
        .success-message {
            background: #d4f4dd;
            color: #27ae60;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .test-result {
            background: #d9e8fc;
            border-left: 4px solid #3498db;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .test-result h3 {
            color: #2980b9;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .test-result ul {
            list-style: none;
            margin-top: 10px;
        }
        
        .test-result li {
            color: #555;
            padding: 5px 0;
            font-size: 14px;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .warning-box p {
            color: #856404;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        
        .info-label {
            color: #999;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="dashboard.php" class="navbar-brand">🌐 Peleforo</a>
        <a href="dashboard.php" class="back-btn">← Retour</a>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h1>⚙️ Paramètres</h1>
            <p>Gérez la configuration de connexion à votre Mikrotik</p>
        </div>
        
        <?php if ($updated): ?>
        <div class="success-message">
            ✓ Configuration mise à jour avec succès !
        </div>
        <?php endif; ?>
        
        <?php if ($testResult && $testResult['success']): ?>
        <div class="test-result">
            <h3>✓ <?php echo $testResult['message']; ?></h3>
            <ul>
                <?php foreach ($testResult['details'] as $key => $value): ?>
                <li><strong><?php echo $key; ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>🔌 Configuration Mikrotik</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Adresse IP Mikrotik</label>
                    <input type="text" name="mikrotik_ip" value="<?php echo htmlspecialchars($config['mikrotik_ip']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom d'utilisateur</label>
                        <input type="text" name="mikrotik_user" value="<?php echo htmlspecialchars($config['mikrotik_user']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Port API</label>
                        <input type="text" name="mikrotik_port" value="<?php echo htmlspecialchars($config['mikrotik_port']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="mikrotik_pass" value="<?php echo htmlspecialchars($config['mikrotik_pass']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Nom du serveur Hotspot</label>
                    <input type="text" name="hotspot_name" value="<?php echo htmlspecialchars($config['hotspot_name']); ?>" required>
                </div>
                
                <button type="submit" name="update" class="btn btn-primary">
                    💾 Enregistrer les modifications
                </button>
                
                <div class="button-group">
                    <button type="submit" name="test_connection" class="btn btn-secondary">
                        🔍 Tester la connexion
                    </button>
                    <button type="submit" name="reset" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir réinitialiser ? Toutes les configurations seront perdues.')">
                        🔄 Réinitialiser
                    </button>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2>📊 Informations système</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Version PHP</div>
                    <div class="info-value"><?php echo phpversion(); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Application</div>
                    <div class="info-value">Peleforo v1.0</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Statut API</div>
                    <div class="info-value" style="color: #27ae60;">● Configuré</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date d'installation</div>
                    <div class="info-value"><?php echo date('d/m/Y'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="warning-box">
            <h3>⚠️ Instructions importantes</h3>
            <p><strong>Pour activer l'API Mikrotik, connectez-vous via Winbox ou Terminal et exécutez :</strong></p>
            <p style="background: white; padding: 10px; border-radius: 5px; margin-top: 10px; font-family: 'Courier New', monospace; color: #333;">
                /ip service enable api<br>
                /ip service set api port=8728
            </p>
            <p style="margin-top: 15px;">Assurez-vous également que votre pare-feu autorise les connexions sur le port 8728.</p>
        </div>
    </div>
</body>
</html>