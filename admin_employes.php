<?php
require_once 'config.php';
requireLogin();

// Seul l'admin peut accéder à cette page
if (!isAdmin()) {
    header('Location: admin_dashboard.php');
    exit();
}

$conn = getDBConnection();
$message = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $stmt = $conn->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, telephone) VALUES (?, ?, ?, MD5(?), ?, ?)");
        $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['mot_de_passe'], $_POST['role'], $_POST['telephone']]);
        $message = 'Employé ajouté avec succès';
    } elseif ($action === 'update') {
        if (!empty($_POST['mot_de_passe'])) {
            $stmt = $conn->prepare("UPDATE utilisateurs SET nom=?, prenom=?, email=?, mot_de_passe=MD5(?), role=?, telephone=? WHERE id_utilisateur=?");
            $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['mot_de_passe'], $_POST['role'], $_POST['telephone'], $_POST['id']]);
        } else {
            $stmt = $conn->prepare("UPDATE utilisateurs SET nom=?, prenom=?, email=?, role=?, telephone=? WHERE id_utilisateur=?");
            $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['role'], $_POST['telephone'], $_POST['id']]);
        }
        $message = 'Employé modifié avec succès';
    } elseif ($action === 'delete') {
        // Ne pas supprimer son propre compte
        if ($_POST['id'] != $_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM utilisateurs WHERE id_utilisateur=?");
            $stmt->execute([$_POST['id']]);
            $message = 'Employé supprimé avec succès';
        } else {
            $message = 'Vous ne pouvez pas supprimer votre propre compte';
        }
    }
}

// Récupération des utilisateurs
$utilisateurs = $conn->query("SELECT * FROM utilisateurs ORDER BY date_creation DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Employés - <?php echo APP_NAME; ?></title>
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
        
        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404; border-left-color: #ffa500;
            padding: 1.2rem; margin-bottom: 1.5rem; border-radius: 8px;
            border-left: 4px solid #ffa500;
        }
        
        table {
            width: 100%; border-collapse: collapse;
        }
        th, td {
            padding: 1rem; text-align: left; border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #1a3d0f; font-weight: 600; font-size: 0.9rem;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        tr:hover { background: #f8fff8; }
        
        .badge {
            display: inline-block; padding: 0.4rem 0.9rem;
            border-radius: 20px; font-size: 0.85rem; font-weight: 600;
        }
        .badge-admin { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }
        .badge-employe_ticket { background: linear-gradient(135deg, #17a2b8, #138496); color: white; }
        .badge-chauffeur { background: linear-gradient(135deg, #7ed957, #5cb85c); color: white; }
        
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
        
        .password-hint {
            font-size: 0.85rem; color: #666; margin-top: 0.3rem;
            font-style: italic;
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
            <li><a href="admin_itineraires.php"><span>🗺️</span> Itinéraires</a></li>
            <li><a href="admin_employes.php" class="active"><span>👔</span> Employés</a></li>
            <li><a href="logout.php" style="color: #ff6b6b;"><span>🚪</span> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1>👔 Gestion des Employés</h1>
            <div>
                <a href="admin_dashboard.php" class="btn btn-back">← Retour</a>
                <button onclick="openModal('create')" class="btn btn-primary">+ Nouvel Employé</button>
            </div>
        </div>

        <div class="alert-warning">
            ⚠️ <strong>Zone sensible:</strong> Cette page permet de gérer les comptes d'accès au système. Soyez prudent lors de la modification ou suppression des comptes.
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
                        <th>Rôle</th>
                        <th>Date création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $u): ?>
                    <tr>
                        <td><?php echo $u['id_utilisateur']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?></strong>
                            <?php if ($u['id_utilisateur'] == $_SESSION['user_id']): ?>
                                <span style="color: #7ed957; font-size: 0.85rem;">(Vous)</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['telephone']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $u['role']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $u['role'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($u['date_creation'])); ?></td>
                        <td>
                            <button onclick='editEmploye(<?php echo json_encode($u); ?>)' class="btn btn-warning btn-small">✏️ Modifier</button>
                            <?php if ($u['id_utilisateur'] != $_SESSION['user_id']): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Confirmer la suppression de cet employé ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $u['id_utilisateur']; ?>">
                                <button type="submit" class="btn btn-danger btn-small">🗑️ Supprimer</button>
                            </form>
                            <?php endif; ?>
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
                <h2 id="modal-title">Nouvel Employé</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="employe-form">
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
                    <label>📧 Email</label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label>📞 Téléphone</label>
                    <input type="tel" name="telephone" id="telephone" required>
                </div>
                
                <div class="form-group">
                    <label>🔑 Mot de passe</label>
                    <input type="password" name="mot_de_passe" id="mot_de_passe">
                    <div class="password-hint" id="password-hint">Le mot de passe est obligatoire</div>
                </div>
                
                <div class="form-group">
                    <label>👔 Rôle</label>
                    <select name="role" id="role" required>
                        <option value="admin">Administrateur</option>
                        <option value="employe_ticket">Employé Tickets</option>
                        <option value="chauffeur">Chauffeur</option>
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
            document.getElementById('modal-title').textContent = action === 'create' ? 'Nouvel Employé' : 'Modifier Employé';
            const passwordField = document.getElementById('mot_de_passe');
            const passwordHint = document.getElementById('password-hint');
            
            if (action === 'create') {
                document.getElementById('employe-form').reset();
                passwordField.required = true;
                passwordHint.textContent = 'Le mot de passe est obligatoire';
            } else {
                passwordField.required = false;
                passwordHint.textContent = 'Laissez vide pour ne pas changer le mot de passe';
            }
        }
        
        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }
        
        function editEmploye(u) {
            openModal('update');
            document.getElementById('id').value = u.id_utilisateur;
            document.getElementById('nom').value = u.nom;
            document.getElementById('prenom').value = u.prenom;
            document.getElementById('email').value = u.email;
            document.getElementById('telephone').value = u.telephone || '';
            document.getElementById('role').value = u.role;
            document.getElementById('mot_de_passe').value = '';
        }
    </script>
</body>
</html>