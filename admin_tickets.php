<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$message = '';

// Fonction pour gérer l'ajout/modification d'un voyageur
function handleVoyageur($conn, $data) {
    // Vérifier si le voyageur existe déjà par téléphone
    $sql_check = "SELECT id_voyageur FROM voyageurs WHERE telephone = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([$data['telephone']]);
    $existing_id = $stmt_check->fetchColumn();

    if ($existing_id) {
        // Mise à jour
        $sql_update = "UPDATE voyageurs SET nom=?, prenom=?, email=?, adresse=? WHERE id_voyageur=?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->execute([$data['nom'], $data['prenom'], $data['email'], $data['adresse'], $existing_id]);
        return $existing_id;
    } else {
        // Création
        $sql_insert = "INSERT INTO voyageurs (nom, prenom, telephone, email, adresse) VALUES (?, ?, ?, ?, ?)";
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
            // 1. Gérer le voyageur
            $voyageur_data = [
                'nom' => $_POST['v_nom'],
                'prenom' => $_POST['v_prenom'],
                'telephone' => $_POST['v_telephone'],
                'email' => $_POST['v_email'],
                'adresse' => $_POST['v_adresse']
            ];
            $id_voyageur = handleVoyageur($conn, $voyageur_data);

            // 2. Gérer le ticket
            $data = [
                'id_voyageur' => $id_voyageur,
                'id_itineraire' => $_POST['id_itineraire'],
                'id_chauffeur' => $_POST['id_chauffeur'] ?: null,
                'id_bus' => $_POST['id_bus'] ?: null,
                'date_voyage' => $_POST['date_voyage'],
                'heure_depart' => $_POST['heure_depart'],
                'nombre_places' => $_POST['nombre_places'],
                'prix_total' => $_POST['prix_total'],
                'statut' => $_POST['statut']
            ];

            if ($action === 'create') {
                $data['numero_ticket'] = generateUniqueNumber('TKT'); // Utiliser la fonction de config.php
                $sql = "INSERT INTO tickets (numero_ticket, id_voyageur, id_itineraire, id_chauffeur, id_bus, date_voyage, heure_depart, nombre_places, prix_total, statut) 
                        VALUES (:numero_ticket, :id_voyageur, :id_itineraire, :id_chauffeur, :id_bus, :date_voyage, :heure_depart, :nombre_places, :prix_total, :statut)";
                $stmt = $conn->prepare($sql);
                $stmt->execute($data);
                $message = 'Ticket créé avec succès. N° Ticket: ' . $data['numero_ticket'];
            } elseif ($action === 'update') {
                $data['id_ticket'] = $_POST['id'];
                $sql = "UPDATE tickets SET id_voyageur=:id_voyageur, id_itineraire=:id_itineraire, id_chauffeur=:id_chauffeur, id_bus=:id_bus, 
                        date_voyage=:date_voyage, heure_depart=:heure_depart, nombre_places=:nombre_places, prix_total=:prix_total, statut=:statut 
                        WHERE id_ticket=:id_ticket";
                $stmt = $conn->prepare($sql);
                $stmt->execute($data);
                $message = 'Ticket mis à jour avec succès';
            }
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM tickets WHERE id_ticket=?");
            $stmt->execute([$_POST['id']]);
            $message = 'Ticket supprimé avec succès';
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
    }
}

// Récupération des tickets avec les détails des voyageurs et itinéraires
$tickets_list = $conn->query("
    SELECT 
        t.*, 
        v.nom as v_nom, v.prenom as v_prenom, v.telephone as v_tel, v.email as v_email, v.adresse as v_adresse,
        i.ville_depart, i.ville_arrivee, i.prix_base,
        c.prenom as ch_prenom, c.nom as ch_nom,
        b.numero_bus
    FROM tickets t 
    JOIN voyageurs v ON t.id_voyageur = v.id_voyageur 
    JOIN itineraires i ON t.id_itineraire = i.id_itineraire 
    LEFT JOIN chauffeurs c ON t.id_chauffeur = c.id_chauffeur
    LEFT JOIN bus b ON t.id_bus = b.id_bus
    ORDER BY t.date_reservation DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des données pour les formulaires
$itineraires = $conn->query("SELECT id_itineraire, ville_depart, ville_arrivee, prix_base FROM itineraires ORDER BY ville_depart")->fetchAll(PDO::FETCH_ASSOC);
$chauffeurs = $conn->query("SELECT id_chauffeur, nom, prenom FROM chauffeurs WHERE statut = 'actif'")->fetchAll(PDO::FETCH_ASSOC);
$bus_disponibles = $conn->query("SELECT id_bus, numero_bus, marque FROM bus WHERE statut != 'maintenance'")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Tickets - <?php echo APP_NAME; ?></title>
    <style>
       <?php include 'admin_style.css'; ?>
       
       /* Styles spécifiques à la page Tickets */
       .badge-reserve { background: linear-gradient(135deg, #ffa500, #ff8c00); color: white; } /* Orange */
       .badge-confirme { background: linear-gradient(135deg, #7ed957, #5cb85c); color: white; } /* Vert */
       .badge-annule { background: linear-gradient(135deg, #dc3545, #c82333); color: white; } /* Rouge */
       .badge-complete { background: linear-gradient(135deg, #6c757d, #5a6268); color: white; } /* Gris */
       
       .ticket-details {
           font-size: 0.9rem;
           color: #666;
           margin-top: 0.5rem;
       }
       .ticket-details strong {
           color: #333;
       }
       .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
       .form-grid-full { grid-column: 1 / 3; }
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
            <li><a href="admin_tickets.php" class="active"><span>🎫</span> Tickets</a></li>
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
            <h1>🎫 Gestion des Tickets</h1>
            <div>
                <a href="admin_dashboard.php" class="btn btn-back">← Retour</a>
                <button onclick="openCrudModal('create')" class="btn btn-primary">+ Nouveau Ticket</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'Erreur') !== false ? 'alert-danger' : 'alert-success'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>N° Ticket</th>
                        <th>Voyageur</th>
                        <th>Itinéraire</th>
                        <th>Date/Heure</th>
                        <th>Bus/Chauffeur</th>
                        <th>Prix Total</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets_list as $ticket): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($ticket['numero_ticket']); ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($ticket['v_prenom'] . ' ' . $ticket['v_nom']); ?><br>
                            <small style="color: #666;"><?php echo htmlspecialchars($ticket['v_tel']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($ticket['ville_depart'] . ' → ' . $ticket['ville_arrivee']); ?></td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($ticket['date_voyage'])); ?><br>
                            <small><?php echo substr($ticket['heure_depart'], 0, 5); ?></small>
                        </td>
                        <td>
                            Bus: <strong><?php echo htmlspecialchars($ticket['numero_bus'] ?? 'N/A'); ?></strong><br>
                            Chauffeur: <strong><?php echo htmlspecialchars($ticket['ch_prenom'] . ' ' . $ticket['ch_nom'] ?? 'N/A'); ?></strong>
                        </td>
                        <td>
                            <strong><?php echo number_format($ticket['prix_total'], 0, ',', ' '); ?> FCFA</strong><br>
                            <small><?php echo $ticket['nombre_places']; ?> place(s)</small>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $ticket['statut']; ?>">
                                <?php echo ucfirst($ticket['statut']); ?>
                            </span>
                        </td>
                        <td>
                            <button onclick='openCrudModal("update", <?php echo json_encode($ticket); ?>)' class="btn btn-warning btn-small">✏️ Modifier</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Confirmer la suppression du ticket <?php echo $ticket['numero_ticket']; ?> ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $ticket['id_ticket']; ?>">
                                <button type="submit" class="btn btn-danger btn-small">🗑️ Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal CRUD Ticket -->
    <div id="crudModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Nouveau Ticket</h2>
                <span class="close" onclick="closeModal('crudModal')">&times;</span>
            </div>
            <form method="POST" id="ticket-form">
                <input type="hidden" name="action" id="action" value="create">
                <input type="hidden" name="id" id="id">
                
                <div class="form-grid">
                    <div class="form-group form-grid-full" style="border-bottom: 2px solid #eee; margin-bottom: 1rem;">
                        <h3 style="color: #5cb85c;">Voyageur</h3>
                    </div>
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="v_nom" id="v_nom" required>
                    </div>
                    <div class="form-group">
                        <label>Prénom</label>
                        <input type="text" name="v_prenom" id="v_prenom" required>
                    </div>
                    <div class="form-group">
                        <label>Téléphone</label>
                        <input type="tel" name="v_telephone" id="v_telephone" required>
                    </div>
                    <div class="form-group">
                        <label>Email (Optionnel)</label>
                        <input type="email" name="v_email" id="v_email">
                    </div>
                    <div class="form-group form-grid-full">
                        <label>Adresse (Optionnel)</label>
                        <input type="text" name="v_adresse" id="v_adresse">
                    </div>

                    <div class="form-group form-grid-full" style="border-bottom: 2px solid #eee; margin: 1rem 0;">
                        <h3 style="color: #5cb85c;">Détails du Voyage</h3>
                    </div>
                    <div class="form-group">
                        <label>Itinéraire</label>
                        <select name="id_itineraire" id="id_itineraire" required onchange="updatePrixTotal()">
                            <option value="">Sélectionnez un itinéraire</option>
                            <?php foreach ($itineraires as $it): ?>
                            <option value="<?php echo $it['id_itineraire']; ?>" data-prix="<?php echo $it['prix_base']; ?>">
                                <?php echo htmlspecialchars($it['ville_depart'] . ' → ' . $it['ville_arrivee']); ?> (<?php echo number_format($it['prix_base'], 0, ',', ' '); ?> FCFA)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nombre de places</label>
                        <input type="number" name="nombre_places" id="nombre_places" required min="1" value="1" onchange="updatePrixTotal()">
                    </div>
                    <div class="form-group">
                        <label>Date de voyage</label>
                        <input type="date" name="date_voyage" id="date_voyage" required>
                    </div>
                    <div class="form-group">
                        <label>Heure de départ</label>
                        <input type="time" name="heure_depart" id="heure_depart" required>
                    </div>
                    <div class="form-group">
                        <label>Chauffeur (Optionnel)</label>
                        <select name="id_chauffeur" id="id_chauffeur">
                            <option value="">Non assigné</option>
                            <?php foreach ($chauffeurs as $ch): ?>
                            <option value="<?php echo $ch['id_chauffeur']; ?>">
                                <?php echo htmlspecialchars($ch['prenom'] . ' ' . $ch['nom']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Bus (Optionnel)</label>
                        <select name="id_bus" id="id_bus">
                            <option value="">Non assigné</option>
                            <?php foreach ($bus_disponibles as $b): ?>
                            <option value="<?php echo $b['id_bus']; ?>">
                                <?php echo htmlspecialchars($b['numero_bus'] . ' - ' . $b['marque']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Prix Total (FCFA)</label>
                        <input type="number" step="0.01" name="prix_total" id="prix_total" required readonly>
                    </div>
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="statut" id="statut" required>
                            <option value="reserve">Réservé</option>
                            <option value="confirme">Confirmé</option>
                            <option value="annule">Annulé</option>
                            <option value="complete">Terminé</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">💾 Enregistrer le Ticket</button>
            </form>
        </div>
    </div>

    <script>
        const itinerairesData = <?php echo json_encode($itineraires); ?>;
        
        function getPrixBase(id_itineraire) {
            const itineraire = itinerairesData.find(it => it.id_itineraire == id_itineraire);
            return itineraire ? parseFloat(itineraire.prix_base) : 0;
        }

        function updatePrixTotal() {
            const id_itineraire = document.getElementById('id_itineraire').value;
            const nombre_places = parseInt(document.getElementById('nombre_places').value) || 0;
            const prix_base = getPrixBase(id_itineraire);
            
            const prix_total = prix_base * nombre_places;
            document.getElementById('prix_total').value = prix_total.toFixed(2);
        }

        function openCrudModal(action, ticket = null) {
            const modal = document.getElementById('crudModal');
            const form = document.getElementById('ticket-form');
            
            document.getElementById('action').value = action;
            document.getElementById('modal-title').textContent = action === 'create' ? 'Nouveau Ticket' : 'Modifier Ticket';
            
            if (action === 'create') {
                form.reset();
                document.getElementById('prix_total').value = '';
            } else if (action === 'update' && ticket) {
                document.getElementById('id').value = ticket.id_ticket;
                
                // Voyageur
                document.getElementById('v_nom').value = ticket.v_nom;
                document.getElementById('v_prenom').value = ticket.v_prenom;
                document.getElementById('v_telephone').value = ticket.v_tel;
                document.getElementById('v_email').value = ticket.v_email;
                document.getElementById('v_adresse').value = ticket.v_adresse;
                
                // Ticket
                document.getElementById('id_itineraire').value = ticket.id_itineraire;
                document.getElementById('nombre_places').value = ticket.nombre_places;
                document.getElementById('date_voyage').value = ticket.date_voyage;
                document.getElementById('heure_depart').value = ticket.heure_depart.substring(0, 5);
                document.getElementById('id_chauffeur').value = ticket.id_chauffeur || '';
                document.getElementById('id_bus').value = ticket.id_bus || '';
                document.getElementById('prix_total').value = ticket.prix_total;
                document.getElementById('statut').value = ticket.statut;
            }
            
            modal.classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
    </script>
</body>
</html>
