<?php
session_start();

$configFile = 'portal_custom.json';

// Charger configuration existante si elle existe
$data = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];

// Valeurs par défaut si certaines clés n'existent pas
if (!isset($data['portal_name'])) $data['portal_name'] = '';
if (!isset($data['welcome_message'])) $data['welcome_message'] = '';
if (!isset($data['footer_text'])) $data['footer_text'] = '';
if (!isset($data['manager_phone'])) $data['manager_phone'] = '';
if (!isset($data['theme'])) $data['theme'] = 'default';
if (!isset($data['tariffs'])) $data['tariffs'] = [];
if (!isset($data['payments'])) $data['payments'] = [];

$success = null;

// Sauvegarde du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['portal_name'] = $_POST['portal_name'] ?? '';
    $data['welcome_message'] = $_POST['welcome_message'] ?? '';
    $data['footer_text'] = $_POST['footer_text'] ?? '';
    $data['manager_phone'] = $_POST['manager_phone'] ?? '';
    $data['theme'] = $_POST['theme'] ?? 'default';

    // Tarifs
    $tariffs = [];
    if (!empty($_POST['tariff_name'])) {
        foreach ($_POST['tariff_name'] as $i => $name) {
            if (!empty($name)) {
                $tariffs[] = [
                    'name' => $name,
                    'speed' => $_POST['tariff_speed'][$i] ?? '',
                    'price' => $_POST['tariff_price'][$i] ?? ''
                ];
            }
        }
    }
    $data['tariffs'] = $tariffs;

    // Paiements
    $payments = [];
    if (!empty($_POST['payment_name'])) {
        foreach ($_POST['payment_name'] as $i => $pname) {
            if (!empty($pname)) {
                $logo = $_POST['payment_logo'][$i] ?? '';
                // Gestion upload
                if (isset($_FILES['payment_logo_file']['tmp_name'][$i]) && is_uploaded_file($_FILES['payment_logo_file']['tmp_name'][$i])) {
                    $uploadDir = 'uploads/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $filename = time() . '_' . basename($_FILES['payment_logo_file']['name'][$i]);
                    $dest = $uploadDir . $filename;
                    move_uploaded_file($_FILES['payment_logo_file']['tmp_name'][$i], $dest);
                    $logo = $dest;
                }
                $payments[] = [
                    'name' => $pname,
                    'logo' => $logo
                ];
            }
        }
    }
    $data['payments'] = $payments;

    // Sauvegarde JSON
    file_put_contents($configFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $success = "✅ Configuration mise à jour avec succès.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>🎨 Personnalisation du portail</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f6fa; margin: 0; padding: 0; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; font-size: 20px; }
        .container { max-width: 1000px; margin: 20px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        input[type="text"], input[type="number"], textarea, select {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;
        }
        textarea { height: 80px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th, table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        table th { background: #f8f9fa; }
        .btn { padding: 12px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-primary { background: #667eea; color: white; }
        .btn-secondary { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .success { background: #d4f4dd; padding: 10px; border-radius: 6px; margin-bottom: 20px; color: #27ae60; }
        .payment-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        .payment-row input[type="text"] { flex: 1; }
    </style>
</head>
<body>
    <div class="navbar">⚙️ Personnalisation du portail</div>
    <div class="container">
        <h1>🎨 Paramètres</h1>

        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">

            <div class="form-group">
                <label>Nom du portail</label>
                <input type="text" name="portal_name" value="<?= htmlspecialchars($data['portal_name']) ?>">
            </div>

            <div class="form-group">
                <label>Message de bienvenue</label>
                <textarea name="welcome_message"><?= htmlspecialchars($data['welcome_message']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Texte pied de page</label>
                <input type="text" name="footer_text" value="<?= htmlspecialchars($data['footer_text']) ?>">
            </div>

            <div class="form-group">
                <label>Numéro du gérant</label>
                <input type="text" name="manager_phone" value="<?= htmlspecialchars($data['manager_phone']) ?>">
            </div>

            <div class="form-group">
                <label>Thème</label>
                <select name="theme">
                    <option value="default" <?= $data['theme'] === 'default' ? 'selected' : '' ?>>Par défaut</option>
                    <option value="blue" <?= $data['theme'] === 'blue' ? 'selected' : '' ?>>Bleu</option>
                    <option value="dark" <?= $data['theme'] === 'dark' ? 'selected' : '' ?>>Sombre</option>
                </select>
            </div>

            <h3>💰 Tarifs</h3>
            <table>
                <tr>
                    <th>Nom</th>
                    <th>Débit</th>
                    <th>Prix</th>
                </tr>
                <?php if (!empty($data['tariffs'])): ?>
                    <?php foreach ($data['tariffs'] as $t): ?>
                        <tr>
                            <td><input type="text" name="tariff_name[]" value="<?= htmlspecialchars($t['name']) ?>"></td>
                            <td><input type="text" name="tariff_speed[]" value="<?= htmlspecialchars($t['speed']) ?>"></td>
                            <td><input type="number" name="tariff_price[]" value="<?= htmlspecialchars($t['price']) ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr>
                    <td><input type="text" name="tariff_name[]" placeholder="Ex: 1 Heure"></td>
                    <td><input type="text" name="tariff_speed[]" placeholder="Ex: 2 Mbps"></td>
                    <td><input type="number" name="tariff_price[]" placeholder="500"></td>
                </tr>
            </table>

            <h3>💳 Modes de paiement</h3>
            <div id="payments">
                <?php if (!empty($data['payments'])): ?>
                    <?php foreach ($data['payments'] as $i => $p): ?>
                        <div class="payment-row">
                            <input type="text" name="payment_name[]" value="<?= htmlspecialchars($p['name']) ?>" placeholder="Nom moyen de paiement">
                            <input type="text" name="payment_logo[]" value="<?= htmlspecialchars($p['logo']) ?>" placeholder="Chemin logo">
                            <input type="file" name="payment_logo_file[<?= $i ?>]">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="payment-row">
                    <input type="text" name="payment_name[]" placeholder="Nom moyen de paiement">
                    <input type="text" name="payment_logo[]" placeholder="Chemin logo ou uploader">
                    <input type="file" name="payment_logo_file[]">
                </div>
            </div>

            <br>
            <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
        </form>
    </div>
</body>
</html>
