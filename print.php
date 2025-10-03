<?php
session_start();

if (!isset($_SESSION['configured'])) {
    header('Location: index.php');
    exit;
}

$vouchers = [];
if (isset($_GET['vouchers'])) {
    $vouchers = json_decode(base64_decode($_GET['vouchers']), true);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimer les tickets - Peleforo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .no-print {
            max-width: 800px;
            margin: 0 auto 30px;
        }
        
        .toolbar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .tickets-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .ticket {
            background: white;
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            page-break-after: always;
            position: relative;
        }
        
        .ticket-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px dashed #e0e0e0;
            margin-bottom: 20px;
        }
        
        .ticket-logo {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .ticket-title {
            color: #667eea;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .ticket-subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .ticket-body {
            padding: 20px 0;
        }
        
        .credential-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .credential-label {
            color: #666;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .credential-value {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            color: #333;
            background: white;
            padding: 8px 15px;
            border-radius: 5px;
            letter-spacing: 2px;
        }
        
        .ticket-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .info-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-label {
            color: #999;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #333;
            font-size: 16px;
            font-weight: bold;
        }
        
        .ticket-footer {
            text-align: center;
            padding-top: 20px;
            border-top: 2px dashed #e0e0e0;
            margin-top: 20px;
        }
        
        .qr-code {
            width: 120px;
            height: 120px;
            margin: 0 auto 15px;
            background: #f0f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #999;
        }
        
        .ticket-footer p {
            color: #666;
            font-size: 12px;
            margin: 5px 0;
        }
        
        .ticket-number {
            position: absolute;
            top: 10px;
            right: 15px;
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            .ticket {
                border: 2px dashed #000;
                margin-bottom: 0;
                box-shadow: none;
            }
            
            .tickets-container {
                max-width: 100%;
            }
        }
        
        @page {
            size: A4;
            margin: 10mm;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <div class="toolbar">
            <div>
                <h2 style="color: #333; margin-bottom: 5px;">🎟️ Tickets prêts à imprimer</h2>
                <p style="color: #666; font-size: 14px;"><?php echo count($vouchers); ?> ticket(s) généré(s)</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimer</button>
                <a href="vouchers.php" class="btn btn-secondary">← Retour</a>
            </div>
        </div>
    </div>
    
    <div class="tickets-container">
        <?php foreach ($vouchers as $index => $voucher): ?>
        <div class="ticket">
            <div class="ticket-number">#<?php echo str_pad($index + 1, 3, '0', STR_PAD_LEFT); ?></div>
            
            <div class="ticket-header">
                <div class="ticket-logo">🌐</div>
                <div class="ticket-title">Peleforo Hotspot</div>
                <div class="ticket-subtitle">Code d'accès WiFi</div>
            </div>
            
            <div class="ticket-body">
                <div class="credential-row">
                    <div>
                        <div class="credential-label">Utilisateur</div>
                    </div>
                    <div class="credential-value"><?php echo htmlspecialchars($voucher['username']); ?></div>
                </div>
                
                <div class="credential-row">
                    <div>
                        <div class="credential-label">Mot de passe</div>
                    </div>
                    <div class="credential-value"><?php echo htmlspecialchars($voucher['password']); ?></div>
                </div>
                
                <div class="ticket-info">
                    <div class="info-item">
                        <div class="info-label">Profil</div>
                        <div class="info-value"><?php echo htmlspecialchars($voucher['profile']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Durée</div>
                        <div class="info-value"><?php echo htmlspecialchars($voucher['duration']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Prix</div>
                        <div class="info-value"><?php echo htmlspecialchars($voucher['price']); ?> FCFA</div>
                    </div>
                </div>
            </div>
            
            <div class="ticket-footer">
                <div class="qr-code">
                    QR Code<br>(optionnel)
                </div>
                <p><strong>Instructions:</strong></p>
                <p>1. Connectez-vous au réseau WiFi</p>
                <p>2. Entrez le code utilisateur et le mot de passe</p>
                <p>3. Profitez de votre connexion Internet!</p>
                <p style="margin-top: 10px; font-size: 11px; color: #999;">
                    Généré le <?php echo date('d/m/Y à H:i'); ?>
                </p>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($vouchers)): ?>
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 10px;">
            <div style="font-size: 64px; margin-bottom: 20px;">📋</div>
            <h2 style="color: #333; margin-bottom: 10px;">Aucun ticket à imprimer</h2>
            <p style="color: #666; margin-bottom: 20px;">Générez d'abord des vouchers pour créer des tickets</p>
            <a href="vouchers.php" class="btn btn-primary">Générer des vouchers</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>