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
            $stmt = $conn->prepare("INSERT INTO chauffeurs (nom, prenom, telephone, numero_permis, date_embauche, statut) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['telephone'], $_POST['numero_permis'], $_POST['date_embauche'], $_POST['statut']]);
            $message = 'Chauffeur ajouté avec succès';
        } elseif ($action === 'update') {
            $stmt = $conn->prepare("UPDATE chauffeurs SET nom=?, prenom=?, telephone=?, numero_permis=?, date_embauche=?, statut=? WHERE id_chauffeur=?");
            $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['telephone'], $_POST['numero_permis'], $_POST['date_embauche'], $_POST['statut'], $_POST['id']]);
            $message = 'Chauffeur modifié avec succès';
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM chauffeurs WHERE id_chauffeur=?");
            $stmt->execute([$_POST['id']]);
            $message = 'Chauffeur supprimé avec succès';
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
    }
}

// Récupération des chauffeurs
$chauffeurs_list = $conn->query("SELECT * FROM chauffeurs ORDER BY nom, prenom")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Chauffeurs - <?php echo APP_NAME; ?></title>
    <style>
       <?php include 'admin_style.css'; // Inclusion du style commun ?>
       
       /* Styles spécifiques à la page Chauffeurs */
       .badge-actif { background: linear-gradient(135deg, #7ed957, #5cb85c); color: white; }
       .badge-inactif { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }
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
            <li><a href="admin_chauffeurs.php" class="active"><span>🚗</span> Chauffeurs</a></li>
            <li><a href="admin_bus.php"><span>🚌</span> Bus</a></li>
            <li><a href="admin_itineraires.php"><span>🗺️</span> Itinéraires</a></li>
            <li><a href="admin_employes.php"><span>👔</span> Employés</a></li>
            <li><a href="logout.php" style="color: #ff6b6b;"><span>🚪</span> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1>🚗 Gestion des Chauffeurs</h1>
            <div>
                <a href="admin_dashboard.php" class="btn btn-back">← Retour</a>
                <button onclick="openModal('create')" class="btn btn-primary">+ Nouveau Chauffeur</button>
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
                        <th>Nom Complet</th>
                        <th>Téléphone</th>
                        <th>N° Permis</th>
                        <th>Date Embauche</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chauffeurs_list as $chauffeur): ?>
                    <tr>
                        <td><?php echo $chauffeur['id_chauffeur']; ?></td>
                        <td><strong><?php echo htmlspecialchars($chauffeur['prenom'] . ' ' . $chauffeur['nom']); ?></strong></td>
                        <td><?php echo htmlspecialchars($chauffeur['telephone']); ?></td>
                        <td><?php echo htmlspecialchars($chauffeur['numero_permis']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($chauffeur['date_embauche'])); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $chauffeur['statut']; ?>">
                                <?php echo ucfirst($chauffeur['statut']); ?>
                            </span>
                        </td>
                        <td>
                            <button onclick='editChauffeur(<?php echo json_encode($chauffeur); ?>)' class="btn btn-warning btn-small">✏️ Modifier</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Confirmer la suppression du chauffeur <?php echo $chauffeur['prenom']; ?> ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $chauffeur['id_chauffeur']; ?>">
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
                <h2 id="modal-title">Nouveau Chauffeur</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="chauffeur-form">
                <input type="hidden" name="action" id="action" value="create">
                <input type="hidden" name="id" id="id">
                
                <div class="form-group">
                    <label>👤 Nom</label>
                    <input type="text" name="nom" id="nom" required>
                </div>
                
                <div class="form-group">
                    <label>👤 Prénom</label>
                    <input type="text" name="prenom" id="prenom" required>
                </div>
                
                <div class="form-group">
                    <label>📞 Téléphone</label>
                    <input type="tel" name="telephone" id="telephone" required>
                </div>
                
                <div class="form-group">
                    <label>💳 Numéro Permis</label>
                    <input type="text" name="numero_permis" id="numero_permis" required>
                </div>
                
                <div class="form-group">
                    <label>📅 Date Embauche</label>
                    <input type="date" name="date_embauche" id="date_embauche" required>
                </div>
                
                <div class="form-group">
                    <label>🚦 Statut</label>
                    <select name="statut" id="statut" required>
                        <option value="actif">Actif</option>
                        <option value="inactif">Inactif</option>
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
            document.getElementById('modal-title').textContent = action === 'create' ? 'Nouveau Chauffeur' : 'Modifier Chauffeur';
            if (action === 'create') {
                document.getElementById('chauffeur-form').reset();
            }
        }
        
        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }
        
        function editChauffeur(chauffeur) {
            openModal('update');
            document.getElementById('id').value = chauffeur.id_chauffeur;
            document.getElementById('nom').value = chauffeur.nom;
            document.getElementById('prenom').value = chauffeur.prenom;
            document.getElementById('telephone').value = chauffeur.telephone;
            document.getElementById('numero_permis').value = chauffeur.numero_permis;
            document.getElementById('date_embauche').value = chauffeur.date_embauche;
            document.getElementById('statut').value = chauffeur.statut;
        }
    </script>
</body>
</html>
