<?php
session_start();

if (!isset($_SESSION['configured'])) {
    header('Location: index.php');
    exit;
}

// Inclure la librairie QRCode
require_once __DIR__ . '/libs/phpqrcode/qrlib.php';

// Chemin du fichier historique
$historyFile = __DIR__ . '/data/history.json';

// Charger l'historique
$history = [];
if (file_exists($historyFile)) {
    $history = json_decode(file_get_contents($historyFile), true) ?: [];
}

// Vider l’historique si demandé
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    file_put_contents($historyFile, json_encode([], JSON_PRETTY_PRINT));
    header("Location: history.php");
    exit;
}

// Export CSV
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="history.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['#', 'Utilisateur', 'Mot de passe', 'Profil', 'Durée', 'Prix (FCFA)', 'Date']);
    foreach ($history as $i => $ticket) {
        fputcsv($output, [
            $i + 1,
            $ticket['username'],
            $ticket['password'],
            $ticket['profile'],
            $ticket['duration'],
            $ticket['price'],
            $ticket['date']
        ]);
    }
    fclose($output);
    exit;
}

// Fonction pour générer un QRCode en base64
function generateQRCodeBase64($data) {
    ob_start();
    QRcode::png($data, null, QR_ECLEVEL_L, 4, 2);
    $imageData = ob_get_contents();
    ob_end_clean();
    return 'data:image/png;base64,' . base64_encode($imageData);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des Tickets - Peleforo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            padding: 25px;
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
        }
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 10px;
        }
        .btn {
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg,#667eea,#764ba2);
            color: #fff;
        }
        .btn-secondary {
            background: #ccc;
            color: #333;
        }
        .btn-danger {
            background: #e74c3c;
            color: #fff;
        }
        .btn:hover {
            opacity: 0.85;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 14px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #eee;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background: #667eea;
            color: white;
            text-transform: uppercase;
            font-size: 13px;
        }
        tr:nth-child(even) {
            background: #fafafa;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            font-size: 16px;
            color: #666;
        }
        .search-box {
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .search-input {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ddd;
            width: 250px;
        }
        img.qrcode {
            width: 80px;
            height: 80px;
        }
    </style>
    <script>
        function searchTable() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let rows = document.querySelectorAll("#historyTable tbody tr");
            rows.forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";
            });
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>📜 Historique des tickets générés</h1>

        <div class="toolbar">
            <div>
                <input type="text" id="searchInput" class="search-input" onkeyup="searchTable()" placeholder="🔍 Rechercher un ticket...">
            </div>
            <div>
                <a href="vouchers.php" class="btn btn-primary">🎟️ Générer de nouveaux tickets</a>
                <a href="history.php?action=export" class="btn btn-secondary">📂 Exporter CSV</a>
                <a href="history.php?action=clear" onclick="return confirm('Voulez-vous vraiment vider tout l’historique ?')" class="btn btn-danger">🗑️ Vider l’historique</a>
            </div>
        </div>

        <?php if (!empty($history)): ?>
        <table id="historyTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Utilisateur</th>
                    <th>Mot de passe</th>
                    <th>Profil</th>
                    <th>Durée</th>
                    <th>Prix (FCFA)</th>
                    <th>Date</th>
                    <th>QR Code</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $i => $ticket): ?>
                <tr>
                    <td><?php echo $i+1; ?></td>
                    <td><?php echo htmlspecialchars($ticket['username']); ?></td>
                    <td><?php echo htmlspecialchars($ticket['password']); ?></td>
                    <td><?php echo htmlspecialchars($ticket['profile']); ?></td>
                    <td><?php echo htmlspecialchars($ticket['duration']); ?></td>
                    <td><?php echo htmlspecialchars($ticket['price']); ?></td>
                    <td><?php echo htmlspecialchars($ticket['date']); ?></td>
                    <td>
                        <?php 
                            $qrData = "User: {$ticket['username']} | Pass: {$ticket['password']} | Profil: {$ticket['profile']} | Durée: {$ticket['duration']} | Prix: {$ticket['price']} FCFA";
                            $qrBase64 = generateQRCodeBase64($qrData);
                        ?>
                        <img src="<?php echo $qrBase64; ?>" class="qrcode" alt="QR Code">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="no-data">
                Aucun ticket enregistré pour le moment.<br><br>
                <a href="vouchers.php" class="btn btn-primary">➕ Générer vos premiers tickets</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
