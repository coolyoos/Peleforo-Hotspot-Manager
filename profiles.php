<?php
// profiles.php - Gestion complète des profils utilisateur Mikrotik

require_once 'routeros_api.php';

// Configuration MikroTik
$routerIP   = "192.168.88.1";
$routerUser = "admin";
$routerPass = "admin";
$routerPort = 8728;

$API = new RouterosAPI();
$API->debug = false;

$profiles = [];
$editProfile = null;

if ($API->connect($routerIP, $routerUser, $routerPass, $routerPort)) {

    // Ajouter un profil
    if (isset($_POST['add'])) {
        $name = $_POST['name'];
        $rateLimit = $_POST['rate_limit'];
        $sessionTimeout = $_POST['session_timeout'];
        $price = $_POST['price'];

        $API->comm("/ip/hotspot/user/profile/add", [
            "name" => $name,
            "rate-limit" => $rateLimit,
            "session-timeout" => $sessionTimeout,
            "comment" => "Tarif: $price FCFA"
        ]);
    }

    // Charger un profil pour modification
    if (isset($_GET['edit'])) {
        $id = $_GET['edit'];
        $res = $API->comm("/ip/hotspot/user/profile/print", [
            ".proplist" => ".id,name,rate-limit,session-timeout,comment",
            ".id" => $id
        ]);
        if (!empty($res)) {
            $editProfile = $res[0];
        }
    }

    // Modifier un profil existant
    if (isset($_POST['update'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $rateLimit = $_POST['rate_limit'];
        $sessionTimeout = $_POST['session_timeout'];
        $price = $_POST['price'];

        $API->comm("/ip/hotspot/user/profile/set", [
            ".id" => $id,
            "name" => $name,
            "rate-limit" => $rateLimit,
            "session-timeout" => $sessionTimeout,
            "comment" => "Tarif: $price FCFA"
        ]);
    }

    // Supprimer un profil
    if (isset($_GET['delete'])) {
        $id = $_GET['delete'];
        $API->comm("/ip/hotspot/user/profile/remove", [
            ".id" => $id
        ]);
    }

    // Lister tous les profils
    $profiles = $API->comm("/ip/hotspot/user/profile/print");

    $API->disconnect();
} else {
    die("❌ Impossible de se connecter au Mikrotik.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Profils Utilisateur</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; }
        h1 { color: #333; }
        form { margin-bottom: 20px; padding: 15px; background: #fff; border-radius: 5px; box-shadow: 0 0 5px #ccc; }
        label { display: inline-block; width: 160px; font-weight: bold; }
        input { padding: 6px; width: 250px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; background: #fff; box-shadow: 0 0 5px #ccc; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .btn { padding: 5px 10px; text-decoration: none; border-radius: 4px; margin-right: 5px; }
        .btn-danger { background: #e74c3c; color: #fff; }
        .btn-primary { background: #3498db; color: #fff; }
        .btn-edit { background: #f39c12; color: #fff; }
    </style>
</head>
<body>
    <h1>📊 Gestion des Profils Hotspot</h1>

    <?php if ($editProfile): ?>
        <!-- Formulaire modification -->
        <form method="post">
            <h2>✏️ Modifier un profil</h2>
            <input type="hidden" name="id" value="<?= htmlspecialchars($editProfile['.id']) ?>">

            <label>Nom du profil:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($editProfile['name']) ?>" required><br><br>

            <label>Débit (rate-limit):</label>
            <input type="text" name="rate_limit" value="<?= $editProfile['rate-limit'] ?? '' ?>" placeholder="1M/512k"><br><br>

            <label>Durée max (session-timeout):</label>
            <input type="text" name="session_timeout" value="<?= $editProfile['session-timeout'] ?? '' ?>" placeholder="1h, 30m"><br><br>

            <label>Prix (FCFA):</label>
            <input type="number" name="price" value="<?= preg_replace('/\D/', '', $editProfile['comment'] ?? '0') ?>"><br><br>

            <button type="submit" name="update" class="btn btn-primary">Mettre à jour</button>
            <a href="profiles.php" class="btn">Annuler</a>
        </form>
    <?php else: ?>
        <!-- Formulaire ajout -->
        <form method="post">
            <h2>➕ Ajouter un profil</h2>
            <label>Nom du profil:</label>
            <input type="text" name="name" required><br><br>

            <label>Débit (rate-limit):</label>
            <input type="text" name="rate_limit" placeholder="1M/512k"><br><br>

            <label>Durée max (session-timeout):</label>
            <input type="text" name="session_timeout" placeholder="1h, 30m"><br><br>

            <label>Prix (FCFA):</label>
            <input type="number" name="price"><br><br>

            <button type="submit" name="add" class="btn btn-primary">Ajouter</button>
        </form>
    <?php endif; ?>

    <!-- Liste des profils -->
    <h2>📋 Profils existants</h2>
    <table>
        <tr>
            <th>Nom</th>
            <th>Débit</th>
            <th>Durée</th>
            <th>Commentaire</th>
            <th>Action</th>
        </tr>
        <?php if (!empty($profiles)): ?>
            <?php foreach ($profiles as $profile): ?>
                <tr>
                    <td><?= htmlspecialchars($profile['name']) ?></td>
                    <td><?= $profile['rate-limit'] ?? '-' ?></td>
                    <td><?= $profile['session-timeout'] ?? '-' ?></td>
                    <td><?= $profile['comment'] ?? '' ?></td>
                    <td>
                        <a class="btn btn-edit" href="?edit=<?= urlencode($profile['.id']) ?>">Modifier</a>
                        <a class="btn btn-danger" href="?delete=<?= urlencode($profile['.id']) ?>" onclick="return confirm('Supprimer ce profil ?')">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">Aucun profil trouvé.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>
