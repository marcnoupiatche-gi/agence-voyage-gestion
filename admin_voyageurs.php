<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$message = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $stmt = $conn->prepare("INSERT INTO voyageurs (nom, prenom, email, telephone, adresse) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['telephone'], $_POST['adresse']]);
        $message = 'Voyageur ajouté avec succès';
    } elseif ($action === 'update') {
        $stmt = $conn->prepare("UPDATE voyageurs SET nom=?, prenom=?, email=?, telephone=?, adresse=? WHERE id_voyageur=?");
        $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['telephone'], $_POST['adresse'], $_POST['id']]);
        $message = 'Voyageur modifié avec succès';
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM voyageurs WHERE id_voyageur=?");
        $stmt->execute([$_POST['id']]);
        $message = 'Voyageur supprimé avec succès';
    }
}

// Récupération des voyageurs
$voyageurs = $conn->query("SELECT * FROM voyageurs ORDER BY date_creation DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Voyageurs - <?php echo APP_NAME; ?></title>
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
        
        table {
            width: 100%; border-collapse: collapse;
        }
        th, td {
            padding: 1rem; text-align: left; border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fa; color: #333; font-weight: 600;
        }
        tr:hover { background: #f8f9fa; }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block; margin-bottom: 0.5rem; color: #333; font-weight: 500;
        }
        input, textarea {
            width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0;
            border-radius: 5px; font-size: 1rem;
        }
        input:focus, textarea:focus {
            outline: none; border-color: #5cb85c;
        }
        
        .modal {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.5);
            z-index: 1000; align-items: center; justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white; padding: 2rem; border-radius: 10px;
            max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;
        }
        .modal-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 1.5rem;
        }
        .close { font-size: 2rem; cursor: pointer; color: #999; }
        .close:hover { color: #333; }
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
            <li><a href="admin_voyageurs.php" style="background: rgba(255,255,255,0.2);"><span>👥</span> Voyageurs</a></li>
            <li><a href="admin_tickets.php"><span>🎫</span> Tickets</a></li>
            <li><a href="admin_colis.php"><span>📦</span> Colis</a></li>
            <li><a href="admin_chauffeurs.php"><span>🚗</span> Chauffeurs</a></li>
            <li><a href="admin_bus.php"><span>🚌</span> Bus</a></li>
            <li><a href="admin_itineraires.php"><span>🗺️</span> Itinéraires</a></li>
            <li><a href="admin_employes.php"><span>👔</span> Employés</a></li>
            <li><a href="logout.php" style="color: #ff6b6b;"><span>🚪</span> Déconnexion</a></li>

        </ul>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1>Gestion des Voyageurs</h1>
            <div>
                <a href="admin_dashboard.php" class="btn btn-back">← Retour</a>
                <button onclick="openModal('create')" class="btn btn-primary">+ Nouveau Voyageur</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom Complet</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Adresse</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($voyageurs as $v): ?>
                    <tr>
                        <td><?php echo $v['id_voyageur']; ?></td>
                        <td><?php echo htmlspecialchars($v['prenom'] . ' ' . $v['nom']); ?></td>
                        <td><?php echo htmlspecialchars($v['email']); ?></td>
                        <td><?php echo htmlspecialchars($v['telephone']); ?></td>
                        <td><?php echo htmlspecialchars($v['adresse']); ?></td>
                        <td>
                            <button onclick='editVoyageur(<?php echo json_encode($v); ?>)' class="btn btn-warning" style="padding: 0.5rem 1rem; margin-right: 0.5rem;">Modifier</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Confirmer la suppression ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $v['id_voyageur']; ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem;">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Nouveau Voyageur</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="voyageur-form">
                <input type="hidden" name="action" id="action" value="create">
                <input type="hidden" name="id" id="id">
                
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="nom" id="nom" required>
                </div>
                
                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="prenom" id="prenom" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="email">
                </div>
                
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="tel" name="telephone" id="telephone" required>
                </div>
                
                <div class="form-group">
                    <label>Adresse</label>
                    <textarea name="adresse" id="adresse" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(action) {
            document.getElementById('modal').classList.add('active');
            document.getElementById('action').value = action;
            document.getElementById('modal-title').textContent = action === 'create' ? 'Nouveau Voyageur' : 'Modifier Voyageur';
            if (action === 'create') {
                document.getElementById('voyageur-form').reset();
            }
        }
        
        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }
        
        function editVoyageur(v) {
            openModal('update');
            document.getElementById('id').value = v.id_voyageur;
            document.getElementById('nom').value = v.nom;
            document.getElementById('prenom').value = v.prenom;
            document.getElementById('email').value = v.email || '';
            document.getElementById('telephone').value = v.telephone;
            document.getElementById('adresse').value = v.adresse || '';
        }
    </script>
</body>
</html>