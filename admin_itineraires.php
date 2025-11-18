<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$message = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $stmt = $conn->prepare("INSERT INTO itineraires (ville_depart, ville_arrivee, distance_km, duree_estimee, prix_base) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['ville_depart'], $_POST['ville_arrivee'], $_POST['distance_km'], $_POST['duree_estimee'], $_POST['prix_base']]);
        $message = 'Itinéraire ajouté avec succès';
    } elseif ($action === 'update') {
        $stmt = $conn->prepare("UPDATE itineraires SET ville_depart=?, ville_arrivee=?, distance_km=?, duree_estimee=?, prix_base=? WHERE id_itineraire=?");
        $stmt->execute([$_POST['ville_depart'], $_POST['ville_arrivee'], $_POST['distance_km'], $_POST['duree_estimee'], $_POST['prix_base'], $_POST['id']]);
        $message = 'Itinéraire modifié avec succès';
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM itineraires WHERE id_itineraire=?");
        $stmt->execute([$_POST['id']]);
        $message = 'Itinéraire supprimé avec succès';
    }
}

// Récupération des itinéraires
$itineraires = $conn->query("SELECT * FROM itineraires ORDER BY ville_depart, ville_arrivee")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Itinéraires - <?php echo APP_NAME; ?></title>
    <style>
       * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        
        .sidebar {
            position: fixed; left: 0; top: 0; width: 260px; height: 100vh;
            background: linear-gradient(180deg, #7ed957 0%, #5cb85c 100%);
            color: white; padding: 2rem 0; overflow-y: auto;
        }
        .logo-sidebar { text-align: center; padding: 0 1rem 2rem; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .logo-sidebar h2 { font-size: 1.5rem; margin-top: 0.5rem; }
        .menu { list-style: none; padding: 1rem 0; }
        .menu li a {
            display: block; padding: 1rem 1.5rem; color: white;
            text-decoration: none; transition: background 0.3s;
        }
        .menu li a:hover { background: rgba(255,255,255,0.2); }
        
        .main-content { margin-left: 260px; padding: 2rem; }
        
        .top-bar {
            background: white; padding: 1.5rem; border-radius: 10px;
            margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex; justify-content: space-between; align-items: center;
        }
        .top-bar h1 { color: #333; }
        
        .btn {
            padding: 0.75rem 1.5rem; border: none; border-radius: 5px;
            cursor: pointer; font-weight: 500; text-decoration: none;
            display: inline-block; transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background: #5cb85c; color: white; }
        .btn-danger { background: #ff4444; color: white; }
        .btn-warning { background: #ffa500; color: white; }
        .btn-back { background: #6c757d; color: white; }
        
        .card {
            background: white; border-radius: 10px; padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem;
        }
        
        .message {
            padding: 1rem; border-radius: 5px; margin-bottom: 1rem;
            background: #d4edda; color: #155724;
        }
        

        .itineraires-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }
        
        .itineraire-card {
            background: linear-gradient(135deg, #ffffff, #f8fff8);
            border: 2px solid #e0e0e0; border-radius: 12px; padding: 1.5rem;
            transition: all 0.3s; position: relative; overflow: hidden;
        }
        .itineraire-card::before {
            content: ''; position: absolute; top: 0; left: 0;
            width: 100%; height: 4px;
            background: linear-gradient(90deg, #7ed957, #5cb85c);
        }
        .itineraire-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(126, 217, 87, 0.2);
            border-color: #7ed957;
        }
        
        .route {
            font-size: 1.4rem; font-weight: bold; color: #1a3d0f;
            margin-bottom: 1rem; display: flex; align-items: center;
            gap: 0.5rem;
        }
        .route-arrow {
            color: #7ed957; font-size: 1.2rem;
        }
        
        .detail-row {
            display: flex; justify-content: space-between;
            padding: 0.6rem 0; border-bottom: 1px dashed #e0e0e0;
        }
        .detail-row:last-of-type { border-bottom: none; }
        .detail-label {
            color: #666; font-size: 0.9rem;
        }
        .detail-value {
            font-weight: 600; color: #1a3d0f;
        }
        
        .price-tag {
            background: linear-gradient(135deg, #7ed957, #5cb85c);
            color: white; font-size: 1.5rem; font-weight: bold;
            padding: 0.75rem; border-radius: 8px;
            text-align: center; margin: 1rem 0;
        }
        
        .card-actions {
            display: flex; gap: 0.5rem; margin-top: 1rem;
            padding-top: 1rem; border-top: 2px solid #e0e0e0;
        }
        
        .modal {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.6);
            z-index: 1000; align-items: center; justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white; padding: 2rem; border-radius: 12px;
            max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .modal-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem;
            border-bottom: 2px solid #e0e0e0;
        }
        .modal-header h2 { color: #1a3d0f; }
        .close { font-size: 2rem; cursor: pointer; color: #999; line-height: 1; }
        .close:hover { color: #dc3545; }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block; margin-bottom: 0.5rem; color: #1a3d0f;
            font-weight: 600; font-size: 0.95rem;
        }
        input, select {
            width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0;
            border-radius: 8px; font-size: 1rem; transition: border-color 0.3s;
        }
        input:focus, select:focus {
            outline: none; border-color: #7ed957;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo-sidebar">
            <div style="font-size: 3rem;">🚌</div>
            <h2><?php echo APP_NAME; ?></h2>
        </div>
        <ul class="menu">
            <li><a href="admin_dashboard.php"><span>📊</span> Dashboard</a></li>
            <li><a href="admin_voyageurs.php"><span>👥</span> Voyageurs</a></li>
            <li><a href="admin_tickets.php"><span>🎫</span> Tickets</a></li>
            <li><a href="admin_colis.php"><span>📦</span> Colis</a></li>
            <li><a href="admin_chauffeurs.php"><span>🚗</span> Chauffeurs</a></li>
            <li><a href="admin_bus.php"><span>🚌</span> Bus</a></li>
            <li><a href="admin_itineraires.php" class="active"><span>🗺️</span> Itinéraires</a></li>
            <li><a href="admin_employes.php"><span>👔</span> Employés</a></li>
            <li><a href="logout.php" style="color: #ff6b6b;"><span>🚪</span> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1>🗺️ Gestion des Itinéraires</h1>
            <div>
                <a href="admin_dashboard.php" class="btn btn-back">← Retour</a>
                <button onclick="openModal('create')" class="btn btn-primary">+ Nouvel Itinéraire</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="itineraires-grid">
            <?php foreach ($itineraires as $it): ?>
            <div class="itineraire-card">
                <div class="route">
                    <span><?php echo htmlspecialchars($it['ville_depart']); ?></span>
                    <span class="route-arrow">→</span>
                    <span><?php echo htmlspecialchars($it['ville_arrivee']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">📏 Distance</span>
                    <span class="detail-value"><?php echo $it['distance_km']; ?> km</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">⏱️ Durée</span>
                    <span class="detail-value"><?php echo $it['duree_estimee']; ?></span>
                </div>
                
                <div class="price-tag">
                    <?php echo number_format($it['prix_base'], 0, ',', ' '); ?> FCFA
                </div>
                
                <div class="card-actions">
                    <button onclick='editItineraire(<?php echo json_encode($it); ?>)' class="btn btn-warning btn-small" style="flex: 1;">✏️ Modifier</button>
                    <form method="POST" style="flex: 1;" onsubmit="return confirm('Confirmer la suppression ?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $it['id_itineraire']; ?>">
                        <button type="submit" class="btn btn-danger btn-small" style="width: 100%;">🗑️ Supprimer</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Nouvel Itinéraire</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="itineraire-form">
                <input type="hidden" name="action" id="action" value="create">
                <input type="hidden" name="id" id="id">
                
                <div class="form-group">
                    <label>🏙️ Ville de départ</label>
                    <input type="text" name="ville_depart" id="ville_depart" required placeholder="Ex: Yaoundé">
                </div>
                
                <div class="form-group">
                    <label>🏙️ Ville d'arrivée</label>
                    <input type="text" name="ville_arrivee" id="ville_arrivee" required placeholder="Ex: Douala">
                </div>
                
                <div class="form-group">
                    <label>📏 Distance (km)</label>
                    <input type="number" step="0.01" name="distance_km" id="distance_km" required placeholder="Ex: 250">
                </div>
                
                <div class="form-group">
                    <label>⏱️ Durée estimée (HH:MM:SS)</label>
                    <input type="time" name="duree_estimee" id="duree_estimee" step="1" required>
                </div>
                
                <div class="form-group">
                    <label>💰 Prix de base (FCFA)</label>
                    <input type="number" step="0.01" name="prix_base" id="prix_base" required placeholder="Ex: 5000">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Enregistrer</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(action) {
            document.getElementById('modal').classList.add('active');
            document.getElementById('action').value = action;
            document.getElementById('modal-title').textContent = action === 'create' ? 'Nouvel Itinéraire' : 'Modifier Itinéraire';
            if (action === 'create') {
                document.getElementById('itineraire-form').reset();
            }
        }
        
        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }
        
        function editItineraire(it) {
            openModal('update');
            document.getElementById('id').value = it.id_itineraire;
            document.getElementById('ville_depart').value = it.ville_depart;
            document.getElementById('ville_arrivee').value = it.ville_arrivee;
            document.getElementById('distance_km').value = it.distance_km;
            document.getElementById('duree_estimee').value = it.duree_estimee;
            document.getElementById('prix_base').value = it.prix_base;
        }
    </script>
</body>
</html>