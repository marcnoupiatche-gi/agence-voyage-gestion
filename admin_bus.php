<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$message = '';

// Traitement des actions CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO bus (numero_bus, marque, capacite_passagers, capacite_colis, statut) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['numero_bus'], $_POST['marque'], $_POST['capacite_passagers'], $_POST['capacite_colis'], $_POST['statut']]);
            $message = 'Bus ajouté avec succès';
        } elseif ($action === 'update') {
            $stmt = $conn->prepare("UPDATE bus SET numero_bus=?, marque=?, capacite_passagers=?, capacite_colis=?, statut=? WHERE id_bus=?");
            $stmt->execute([$_POST['numero_bus'], $_POST['marque'], $_POST['capacite_passagers'], $_POST['capacite_colis'], $_POST['statut'], $_POST['id']]);
            $message = 'Bus modifié avec succès';
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM bus WHERE id_bus=?");
            $stmt->execute([$_POST['id']]);
            $message = 'Bus supprimé avec succès';
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
    }
}

// Récupération des bus
$bus_list = $conn->query("SELECT * FROM bus ORDER BY numero_bus")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Bus - <?php echo APP_NAME; ?></title>
    <style>
       <?php include 'admin_style.css'; // Inclusion du style commun ?>
       
       /* Styles spécifiques à la page Bus */
       .badge-disponible { background: linear-gradient(135deg, #7ed957, #5cb85c); color: white; }
       .badge-en_service { background: linear-gradient(135deg, #ffa500, #ff8c00); color: white; }
       .badge-maintenance { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }
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
            <li><a href="admin_bus.php" class="active"><span>🚌</span> Bus</a></li>
            <li><a href="admin_itineraires.php"><span>🗺️</span> Itinéraires</a></li>
            <li><a href="admin_employes.php"><span>👔</span> Employés</a></li>
            <li><a href="logout.php" style="color: #ff6b6b;"><span>🚪</span> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1>🚌 Gestion des Bus</h1>
            <div>
                <a href="admin_dashboard.php" class="btn btn-back">← Retour</a>
                <button onclick="openModal('create')" class="btn btn-primary">+ Nouveau Bus</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'Erreur') !== false ? 'alert-danger' : 'alert-success'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Numéro Bus</th>
                        <th>Marque</th>
                        <th>Capacité Passagers</th>
                        <th>Capacité Colis (kg)</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bus_list as $bus): ?>
                    <tr>
                        <td><?php echo $bus['id_bus']; ?></td>
                        <td><strong><?php echo htmlspecialchars($bus['numero_bus']); ?></strong></td>
                        <td><?php echo htmlspecialchars($bus['marque']); ?></td>
                        <td><?php echo $bus['capacite_passagers']; ?></td>
                        <td><?php echo number_format($bus['capacite_colis'], 2, ',', ' '); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $bus['statut']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $bus['statut'])); ?>
                            </span>
                        </td>
                        <td>
                            <button onclick='editBus(<?php echo json_encode($bus); ?>)' class="btn btn-warning btn-small">✏️ Modifier</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Confirmer la suppression du bus <?php echo $bus['numero_bus']; ?> ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $bus['id_bus']; ?>">
                                <button type="submit" class="btn btn-danger btn-small">🗑️ Supprimer</button>
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
                <h2 id="modal-title">Nouveau Bus</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="bus-form">
                <input type="hidden" name="action" id="action" value="create">
                <input type="hidden" name="id" id="id">
                
                <div class="form-group">
                    <label>🔢 Numéro Bus</label>
                    <input type="text" name="numero_bus" id="numero_bus" required placeholder="Ex: TE-004">
                </div>
                
                <div class="form-group">
                    <label>🏭 Marque</label>
                    <input type="text" name="marque" id="marque" required placeholder="Ex: Scania">
                </div>
                
                <div class="form-group">
                    <label>👥 Capacité Passagers</label>
                    <input type="number" name="capacite_passagers" id="capacite_passagers" required min="1">
                </div>
                
                <div class="form-group">
                    <label>📦 Capacité Colis (kg)</label>
                    <input type="number" step="0.01" name="capacite_colis" id="capacite_colis" required min="0">
                </div>
                
                <div class="form-group">
                    <label>🚦 Statut</label>
                    <select name="statut" id="statut" required>
                        <option value="disponible">Disponible</option>
                        <option value="en_service">En Service</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Enregistrer</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(action) {
            document.getElementById('modal').classList.add('active');
            document.getElementById('action').value = action;
            document.getElementById('modal-title').textContent = action === 'create' ? 'Nouveau Bus' : 'Modifier Bus';
            if (action === 'create') {
                document.getElementById('bus-form').reset();
            }
        }
        
        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }
        
        function editBus(bus) {
            openModal('update');
            document.getElementById('id').value = bus.id_bus;
            document.getElementById('numero_bus').value = bus.numero_bus;
            document.getElementById('marque').value = bus.marque;
            document.getElementById('capacite_passagers').value = bus.capacite_passagers;
            document.getElementById('capacite_colis').value = bus.capacite_colis;
            document.getElementById('statut').value = bus.statut;
        }
    </script>
</body>
</html>
