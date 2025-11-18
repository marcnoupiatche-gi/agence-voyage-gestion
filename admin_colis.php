<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$message = '';

// Fonction pour gérer l'ajout/modification d'un expéditeur ou destinataire
function handlePersonne($conn, $table, $id_key, $data) {
    // Vérifier si la personne existe déjà par téléphone (ou email si fourni)
    $sql_check = "SELECT {$id_key} FROM {$table} WHERE telephone = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([$data['telephone']]);
    $existing_id = $stmt_check->fetchColumn();

    if ($existing_id) {
        // Mise à jour
        $sql_update = "UPDATE {$table} SET nom=?, prenom=?, email=?, adresse=? WHERE {$id_key}=?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->execute([$data['nom'], $data['prenom'], $data['email'], $data['adresse'], $existing_id]);
        return $existing_id;
    } else {
        // Création
        $sql_insert = "INSERT INTO {$table} (nom, prenom, telephone, email, adresse) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->execute([$data['nom'], $data['prenom'], $data['telephone'], $data['email'], $data['adresse']]);
        return $conn->lastInsertId();
    }
}

// Traitement des actions CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create' || $action === 'update') {
            // 1. Gérer l'expéditeur
            $expediteur_data = [
                'nom' => $_POST['exp_nom'],
                'prenom' => $_POST['exp_prenom'],
                'telephone' => $_POST['exp_telephone'],
                'email' => $_POST['exp_email'],
                'adresse' => $_POST['exp_adresse']
            ];
            $id_expediteur = handlePersonne($conn, 'expediteurs', 'id_expediteur', $expediteur_data);

            // 2. Gérer le destinataire
            $destinataire_data = [
                'nom' => $_POST['dest_nom'],
                'prenom' => $_POST['dest_prenom'],
                'telephone' => $_POST['dest_telephone'],
                'email' => $_POST['dest_email'],
                'adresse' => $_POST['dest_adresse']
            ];
            $id_destinataire = handlePersonne($conn, 'destinataires', 'id_destinataire', $destinataire_data);

            // 3. Gérer le colis
            $data = [
                'numero_suivi' => $_POST['numero_suivi'],
                'id_expediteur' => $id_expediteur,
                'id_destinataire' => $id_destinataire,
                'id_itineraire' => $_POST['id_itineraire'],
                'description' => $_POST['description'],
                'poids' => $_POST['poids'],
                'prix' => $_POST['prix'],
                'date_expedition' => $_POST['date_expedition'],
                'statut' => $_POST['statut']
            ];

            if ($action === 'create') {
                $data['numero_suivi'] = generateUniqueNumber('COL'); // Utiliser la fonction de config.php
                $sql = "INSERT INTO colis (numero_suivi, id_expediteur, id_destinataire, id_itineraire, description, poids, prix, date_expedition, statut) 
                        VALUES (:numero_suivi, :id_expediteur, :id_destinataire, :id_itineraire, :description, :poids, :prix, :date_expedition, :statut)";
                $stmt = $conn->prepare($sql);
                $stmt->execute($data);
                $message = 'Colis enregistré avec succès. N° Suivi: ' . $data['numero_suivi'];
            } elseif ($action === 'update') {
                $data['id_colis'] = $_POST['id'];
                $sql = "UPDATE colis SET numero_suivi=:numero_suivi, id_expediteur=:id_expediteur, id_destinataire=:id_destinataire, id_itineraire=:id_itineraire, 
                        description=:description, poids=:poids, prix=:prix, date_expedition=:date_expedition, statut=:statut WHERE id_colis=:id_colis";
                $stmt = $conn->prepare($sql);
                $stmt->execute($data);
                $message = 'Colis mis à jour avec succès';
            }
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM colis WHERE id_colis=?");
            $stmt->execute([$_POST['id']]);
            $message = 'Colis supprimé avec succès';
        } elseif ($action === 'update_statut') {
            $stmt = $conn->prepare("UPDATE colis SET statut=? WHERE id_colis=?");
            $stmt->execute([$_POST['statut'], $_POST['id']]);
            $message = 'Statut du colis mis à jour avec succès';
        } elseif ($action === 'assign') {
            $stmt = $conn->prepare("UPDATE colis SET id_chauffeur=?, id_bus=? WHERE id_colis=?");
            $stmt->execute([$_POST['id_chauffeur'], $_POST['id_bus'], $_POST['id']]);
            $message = 'Chauffeur et bus assignés avec succès';
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
    }
}

// Récupération des données pour l'affichage et les formulaires
$colis_list = $conn->query("
    SELECT c.*, 
           e.nom as exp_nom, e.prenom as exp_prenom, e.telephone as exp_tel, e.email as exp_email, e.adresse as exp_adresse,
           d.nom as dest_nom, d.prenom as dest_prenom, d.telephone as dest_tel, d.email as dest_email, d.adresse as dest_adresse,
           i.ville_depart, i.ville_arrivee,
           ch.prenom as chauf_prenom, ch.nom as chauf_nom,
           b.numero_bus
    FROM colis c
    JOIN expediteurs e ON c.id_expediteur = e.id_expediteur
    JOIN destinataires d ON c.id_destinataire = d.id_destinataire
    JOIN itineraires i ON c.id_itineraire = i.id_itineraire
    LEFT JOIN chauffeurs ch ON c.id_chauffeur = ch.id_chauffeur
    LEFT JOIN bus b ON c.id_bus = b.id_bus
    ORDER BY c.date_creation DESC
")->fetchAll(PDO::FETCH_ASSOC);

$itineraires = $conn->query("SELECT id_itineraire, ville_depart, ville_arrivee FROM itineraires ORDER BY ville_depart")->fetchAll(PDO::FETCH_ASSOC);
$chauffeurs = $conn->query("SELECT id_chauffeur, nom, prenom FROM chauffeurs WHERE statut = 'actif'")->fetchAll(PDO::FETCH_ASSOC);
$bus_disponibles = $conn->query("SELECT id_bus, numero_bus, marque FROM bus WHERE statut != 'maintenance'")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Colis - <?php echo APP_NAME; ?></title>
    <style>
       <?php include 'admin_style.css'; ?>
       
       /* Styles spécifiques à la page Colis */
       .badge-en_attente { background: linear-gradient(135deg, #ffa500, #ff8c00); color: white; } /* Orange */
       .badge-en_transit { background: linear-gradient(135deg, #17a2b8, #138496); color: white; } /* Bleu */
       .badge-livre { background: linear-gradient(135deg, #7ed957, #5cb85c); color: white; } /* Vert */
       .badge-annule { background: linear-gradient(135deg, #dc3545, #c82333); color: white; } /* Rouge */
       
       .filter-bar { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
       .filter-bar input, .filter-bar select { flex: 1; padding: 0.75rem; border: 1px solid #ccc; border-radius: 5px; }
       
       .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
       .detail-item { padding: 0.5rem; border-bottom: 1px dashed #eee; }
       .detail-label { font-size: 0.85rem; color: #666; }
       .detail-value { font-weight: 600; color: #333; }
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
            <li><a href="admin_colis.php" class="active"><span>📦</span> Colis</a></li>
            <li><a href="admin_chauffeurs.php"><span>🚗</span> Chauffeurs</a></li>
            <li><a href="admin_bus.php"><span>🚌</span> Bus</a></li>
            <li><a href="admin_itineraires.php"><span>🗺️</span> Itinéraires</a></li>
            <li><a href="admin_employes.php"><span>👔</span> Employés</a></li>
            <li><a href="logout.php" style="color: #ff6b6b;"><span>🚪</span> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1>📦 Gestion des Colis</h1>
            <div>
                <a href="admin_dashboard.php" class="btn btn-back">← Retour</a>
                <button onclick="openModal('create')" class="btn btn-primary">+ Nouveau Colis</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'Erreur') !== false ? 'alert-danger' : 'alert-success'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="filter-bar">
                <input type="text" id="search" placeholder="🔍 Rechercher par numéro, expéditeur..." onkeyup="filterTable()">
                <select id="statusFilter" onchange="filterTable()">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente">En attente</option>
                    <option value="en_transit">En transit</option>
                    <option value="livre">Livré</option>
                    <option value="annule">Annulé</option>
                </select>
            </div>

            <table id="colisTable">
                <thead>
                    <tr>
                        <th>N° Suivi</th>
                        <th>Expéditeur</th>
                        <th>Destinataire</th>
                        <th>Trajet</th>
                        <th>Poids/Prix</th>
                        <th>Statut</th>
                        <th>Transport</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($colis_list as $c): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($c['numero_suivi']); ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($c['exp_prenom'] . ' ' . $c['exp_nom']); ?><br>
                            <small style="color: #666;"><?php echo htmlspecialchars($c['exp_tel']); ?></small>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($c['dest_prenom'] . ' ' . $c['dest_nom']); ?><br>
                            <small style="color: #666;"><?php echo htmlspecialchars($c['dest_tel']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($c['ville_depart'] . ' → ' . $c['ville_arrivee']); ?></td>
                        <td>
                            <?php echo $c['poids']; ?> kg<br>
                            <strong><?php echo number_format($c['prix'], 0, ',', ' '); ?> FCFA</strong>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $c['statut']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $c['statut'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($c['chauf_nom']): ?>
                                <?php echo htmlspecialchars($c['chauf_prenom'] . ' ' . $c['chauf_nom']); ?><br>
                                <small style="color: #666;"><?php echo htmlspecialchars($c['numero_bus']); ?></small>
                            <?php else: ?>
                                <span style="color: #dc3545;">Non assigné</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button onclick='viewColis(<?php echo json_encode($c); ?>)' class="btn btn-primary btn-small">Voir</button>
                            <button onclick='openModal("update", <?php echo json_encode($c); ?>)' class="btn btn-warning btn-small">Modifier</button>
                            <button onclick='assignTransport(<?php echo json_encode($c); ?>)' class="btn btn-warning btn-small">Assigner</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Confirmer la suppression du colis <?php echo $c['numero_suivi']; ?> ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $c['id_colis']; ?>">
                                <button type="submit" class="btn btn-danger btn-small">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal CRUD Colis -->
    <div id="crudModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Nouveau Colis</h2>
                <span class="close" onclick="closeModal('crudModal')">&times;</span>
            </div>
            <form method="POST" id="colis-form">
                <input type="hidden" name="action" id="action" value="create">
                <input type="hidden" name="id" id="id">
                <input type="hidden" name="numero_suivi" id="numero_suivi"> <!-- Pour l'update -->

                <div class="detail-grid">
                    <div style="grid-column: 1 / 3; border-bottom: 2px solid #eee; margin-bottom: 1rem;">
                        <h3 style="color: #5cb85c;">Expéditeur</h3>
                    </div>
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="exp_nom" id="exp_nom" required>
                    </div>
                    <div class="form-group">
                        <label>Prénom</label>
                        <input type="text" name="exp_prenom" id="exp_prenom" required>
                    </div>
                    <div class="form-group">
                        <label>Téléphone</label>
                        <input type="tel" name="exp_telephone" id="exp_telephone" required>
                    </div>
                    <div class="form-group">
                        <label>Email (Optionnel)</label>
                        <input type="email" name="exp_email" id="exp_email">
                    </div>
                    <div class="form-group" style="grid-column: 1 / 3;">
                        <label>Adresse</label>
                        <input type="text" name="exp_adresse" id="exp_adresse" required>
                    </div>

                    <div style="grid-column: 1 / 3; border-bottom: 2px solid #eee; margin: 1rem 0;">
                        <h3 style="color: #5cb85c;">Destinataire</h3>
                    </div>
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="dest_nom" id="dest_nom" required>
                    </div>
                    <div class="form-group">
                        <label>Prénom</label>
                        <input type="text" name="dest_prenom" id="dest_prenom" required>
                    </div>
                    <div class="form-group">
                        <label>Téléphone</label>
                        <input type="tel" name="dest_telephone" id="dest_telephone" required>
                    </div>
                    <div class="form-group">
                        <label>Email (Optionnel)</label>
                        <input type="email" name="dest_email" id="dest_email">
                    </div>
                    <div class="form-group" style="grid-column: 1 / 3;">
                        <label>Adresse</label>
                        <input type="text" name="dest_adresse" id="dest_adresse" required>
                    </div>

                    <div style="grid-column: 1 / 3; border-bottom: 2px solid #eee; margin: 1rem 0;">
                        <h3 style="color: #5cb85c;">Détails du Colis</h3>
                    </div>
                    <div class="form-group">
                        <label>Itinéraire</label>
                        <select name="id_itineraire" id="id_itineraire" required>
                            <option value="">Sélectionnez un itinéraire</option>
                            <?php foreach ($itineraires as $it): ?>
                            <option value="<?php echo $it['id_itineraire']; ?>">
                                <?php echo htmlspecialchars($it['ville_depart'] . ' → ' . $it['ville_arrivee']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Poids (kg)</label>
                        <input type="number" step="0.01" name="poids" id="poids" required min="0.1">
                    </div>
                    <div class="form-group">
                        <label>Prix (FCFA)</label>
                        <input type="number" step="0.01" name="prix" id="prix" required min="100">
                    </div>
                    <div class="form-group">
                        <label>Date d'expédition</label>
                        <input type="date" name="date_expedition" id="date_expedition" required>
                    </div>
                    <div class="form-group" style="grid-column: 1 / 3;">
                        <label>Description</label>
                        <textarea name="description" id="description" rows="3"></textarea>
                    </div>
                    <div class="form-group" style="grid-column: 1 / 3;">
                        <label>Statut</label>
                        <select name="statut" id="statut" required>
                            <option value="en_attente">En attente</option>
                            <option value="en_transit">En transit</option>
                            <option value="livre">Livré</option>
                            <option value="annule">Annulé</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">💾 Enregistrer le Colis</button>
            </form>
        </div>
    </div>

    <!-- Modal Assignation -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Assigner Transport</h2>
                <span class="close" onclick="closeModal('assignModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="assign">
                <input type="hidden" name="id" id="assign_id">
                
                <div class="form-group">
                    <label>🚗 Chauffeur</label>
                    <select name="id_chauffeur" id="assign_chauffeur" required>
                        <option value="">Sélectionnez un chauffeur</option>
                        <?php foreach ($chauffeurs as $ch): ?>
                        <option value="<?php echo $ch['id_chauffeur']; ?>">
                            <?php echo htmlspecialchars($ch['prenom'] . ' ' . $ch['nom']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>🚌 Bus</label>
                    <select name="id_bus" id="assign_bus" required>
                        <option value="">Sélectionnez un bus</option>
                        <?php foreach ($bus_disponibles as $b): ?>
                        <option value="<?php echo $b['id_bus']; ?>">
                            <?php echo htmlspecialchars($b['numero_bus'] . ' - ' . $b['marque']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Assigner</button>
            </form>
        </div>
    </div>

    <!-- Modal Détails (pour le statut) -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Détails du Colis</h2>
                <span class="close" onclick="closeModal('detailModal')">&times;</span>
            </div>
            <div id="colisDetails"></div>
            <div style="margin-top: 1.5rem;">
                <form method="POST" style="display: inline-block; width: 100%;">
                    <input type="hidden" name="action" value="update_statut">
                    <input type="hidden" name="id" id="detail_id">
                    <div class="form-group">
                        <label>Changer le statut :</label>
                        <select name="statut" id="detail_statut">
                            <option value="en_attente">En attente</option>
                            <option value="en_transit">En transit</option>
                            <option value="livre">Livré</option>
                            <option value="annule">Annulé</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Mettre à jour le statut</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal(action, colis = null) {
            const modal = document.getElementById('crudModal');
            const form = document.getElementById('colis-form');
            
            document.getElementById('action').value = action;
            document.getElementById('modal-title').textContent = action === 'create' ? 'Nouveau Colis' : 'Modifier Colis';
            
            if (action === 'create') {
                form.reset();
                document.getElementById('numero_suivi').value = '';
            } else if (action === 'update' && colis) {
                document.getElementById('id').value = colis.id_colis;
                document.getElementById('numero_suivi').value = colis.numero_suivi;
                
                // Expéditeur
                document.getElementById('exp_nom').value = colis.exp_nom;
                document.getElementById('exp_prenom').value = colis.exp_prenom;
                document.getElementById('exp_telephone').value = colis.exp_tel;
                document.getElementById('exp_email').value = colis.exp_email;
                document.getElementById('exp_adresse').value = colis.exp_adresse;
                
                // Destinataire
                document.getElementById('dest_nom').value = colis.dest_nom;
                document.getElementById('dest_prenom').value = colis.dest_prenom;
                document.getElementById('dest_telephone').value = colis.dest_tel;
                document.getElementById('dest_email').value = colis.dest_email;
                document.getElementById('dest_adresse').value = colis.dest_adresse;
                
                // Colis
                document.getElementById('id_itineraire').value = colis.id_itineraire;
                document.getElementById('poids').value = colis.poids;
                document.getElementById('prix').value = colis.prix;
                document.getElementById('date_expedition').value = colis.date_expedition;
                document.getElementById('description').value = colis.description;
                document.getElementById('statut').value = colis.statut;
            }
            
            modal.classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function viewColis(colis) {
            const html = `
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">N° Suivi</div>
                        <div class="detail-value">${colis.numero_suivi}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Statut</div>
                        <div class="detail-value"><span class="badge badge-${colis.statut}">${colis.statut.replace('_', ' ')}</span></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Expéditeur</div>
                        <div class="detail-value">${colis.exp_prenom} ${colis.exp_nom}</div>
                        <small style="color: #666;">${colis.exp_tel}</small>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Destinataire</div>
                        <div class="detail-value">${colis.dest_prenom} ${colis.dest_nom}</div>
                        <small style="color: #666;">${colis.dest_tel}</small>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Trajet</div>
                        <div class="detail-value">${colis.ville_depart} → ${colis.ville_arrivee}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Poids/Prix</div>
                        <div class="detail-value">${colis.poids} kg / ${parseInt(colis.prix).toLocaleString('fr-FR')} FCFA</div>
                    </div>
                    <div class="detail-item" style="grid-column: 1 / 3;">
                        <div class="detail-label">Description</div>
                        <div class="detail-value">${colis.description || 'N/A'}</div>
                    </div>
                </div>
            `;
            document.getElementById('colisDetails').innerHTML = html;
            document.getElementById('detail_id').value = colis.id_colis;
            document.getElementById('detail_statut').value = colis.statut;
            document.getElementById('detailModal').classList.add('active');
        }
        
        function assignTransport(colis) {
            document.getElementById('assign_id').value = colis.id_colis;
            document.getElementById('assign_chauffeur').value = colis.id_chauffeur || '';
            document.getElementById('assign_bus').value = colis.id_bus || '';
            document.getElementById('assignModal').classList.add('active');
        }
        
        function filterTable() {
            const search = document.getElementById('search').value.toLowerCase();
            const status = document.getElementById('statusFilter').value.toLowerCase();
            const table = document.getElementById('colisTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                const statusCell = row.cells[5].textContent.toLowerCase();
                
                const matchSearch = text.includes(search);
                const matchStatus = !status || statusCell.includes(status);
                
                row.style.display = (matchSearch && matchStatus) ? '' : 'none';
            }
        }
    </script>
</body>
</html>
