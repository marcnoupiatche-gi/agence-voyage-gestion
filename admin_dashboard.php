<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();

// Statistiques
$stats = [
    'tickets' => $conn->query("SELECT COUNT(*) as count FROM tickets")->fetch()['count'],
    'colis' => $conn->query("SELECT COUNT(*) as count FROM colis")->fetch()['count'],
    'voyageurs' => $conn->query("SELECT COUNT(*) as count FROM voyageurs")->fetch()['count'],
    'revenus_tickets' => $conn->query("SELECT SUM(prix_total) as total FROM tickets WHERE statut != 'annule'")->fetch()['total'] ?? 0,
    'revenus_colis' => $conn->query("SELECT SUM(prix) as total FROM colis WHERE statut != 'annule'")->fetch()['total'] ?? 0
];

// Récents tickets
$recentTickets = $conn->query("
    SELECT t.*, v.nom, v.prenom, i.ville_depart, i.ville_arrivee 
    FROM tickets t 
    JOIN voyageurs v ON t.id_voyageur = v.id_voyageur 
    JOIN itineraires i ON t.id_itineraire = i.id_itineraire 
    ORDER BY t.date_reservation DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Récents colis
$recentColis = $conn->query("
    SELECT c.*, e.nom as exp_nom, e.prenom as exp_prenom, d.nom as dest_nom, d.prenom as dest_prenom 
    FROM colis c 
    JOIN expediteurs e ON c.id_expediteur = e.id_expediteur 
    JOIN destinataires d ON c.id_destinataire = d.id_destinataire 
    ORDER BY c.date_creation DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #7ed957 0%, #5cb85c 100%);
            color: white;
            padding: 2rem 0;
            overflow-y: auto;
        }
        
        .logo-sidebar {
            text-align: center;
            padding: 0 1rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .logo-sidebar h2 {
            font-size: 1.5rem;
            margin-top: 0.5rem;
        }
        
        .menu {
            list-style: none;
            padding: 1rem 0;
        }
        
        .menu li a {
            display: block;
            padding: 1rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .menu li a:hover, .menu li a.active {
            background: rgba(255,255,255,0.2);
        }
        
        .menu li a span {
            margin-right: 0.5rem;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 2rem;
        }
        
        /* Top Bar */
        .top-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar h1 {
            color: #333;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: #5cb85c;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .btn-logout {
            padding: 0.5rem 1.5rem;
            background: #ff4444;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-card .icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #7ed957, #5cb85c);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            font-weight: normal;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }
        
        /* Recent Activity */
        .activity-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
        }
        
        .activity-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .activity-card h2 {
            color: #5cb85c;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }
        
        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item strong {
            color: #333;
        }
        
        .activity-item small {
            color: #999;
            display: block;
            margin-top: 0.5rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo-sidebar">
            <div style="font-size: 3rem;">🚌</div>
            <h2><?php echo APP_NAME; ?></h2>
        </div>
        <ul class="menu">
            <li><a href="admin_dashboard.php" class="active"><span>📊</span> Dashboard</a></li>
            <li><a href="admin_voyageurs.php"><span>👥</span> Voyageurs</a></li>
            <li><a href="admin_tickets.php"><span>🎫</span> Tickets</a></li>
            <li><a href="admin_colis.php"><span>📦</span> Colis</a></li>
            <li><a href="admin_chauffeurs.php"><span>🚗</span> Chauffeurs</a></li>
            <li><a href="admin_bus.php"><span>🚌</span> Bus</a></li>
            <li><a href="admin_itineraires.php"><span>🗺️</span> Itinéraires</a></li>
            <li><a href="admin_employes.php"><span>👔</span> Employés</a></li>
            <li><a href="logout.php" style="color: #ff6b6b;"><span>🚪</span> Déconnexion</a></li>

        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1>Tableau de Bord</h1>
            <div class="user-info">
                <div class="user-avatar"><?php echo substr($_SESSION['user_name'], 0, 1); ?></div>
                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">🎫</div>
                <h3>Total Tickets</h3>
                <div class="value"><?php echo number_format($stats['tickets']); ?></div>
            </div>
            <div class="stat-card">
                <div class="icon">📦</div>
                <h3>Total Colis</h3>
                <div class="value"><?php echo number_format($stats['colis']); ?></div>
            </div>
            <div class="stat-card">
                <div class="icon">👥</div>
                <h3>Voyageurs</h3>
                <div class="value"><?php echo number_format($stats['voyageurs']); ?></div>
            </div>
            <div class="stat-card">
                <div class="icon">💰</div>
                <h3>Revenus Totaux</h3>
                <div class="value"><?php echo number_format($stats['revenus_tickets'] + $stats['revenus_colis'], 0, ',', ' '); ?> FCFA</div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="activity-section">
            <div class="activity-card">
                <h2>Derniers Tickets</h2>
                <?php foreach ($recentTickets as $ticket): ?>
                <div class="activity-item">
                    <strong><?php echo htmlspecialchars($ticket['prenom'] . ' ' . $ticket['nom']); ?></strong><br>
                    <?php echo htmlspecialchars($ticket['ville_depart'] . ' → ' . $ticket['ville_arrivee']); ?>
                    <span class="badge badge-<?php 
                        echo $ticket['statut'] === 'confirme' ? 'success' : 
                            ($ticket['statut'] === 'reserve' ? 'warning' : 'info'); 
                    ?>">
                        <?php echo ucfirst($ticket['statut']); ?>
                    </span>
                    <small>N° <?php echo $ticket['numero_ticket']; ?> - <?php echo date('d/m/Y', strtotime($ticket['date_reservation'])); ?></small>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="activity-card">
                <h2>Derniers Colis</h2>
                <?php foreach ($recentColis as $coli): ?>
                <div class="activity-item">
                    <strong><?php echo htmlspecialchars($coli['exp_prenom'] . ' ' . $coli['exp_nom']); ?></strong>
                    → <?php echo htmlspecialchars($coli['dest_prenom'] . ' ' . $coli['dest_nom']); ?><br>
                    <span class="badge badge-<?php 
                        echo $coli['statut'] === 'livre' ? 'success' : 
                            ($coli['statut'] === 'en_transit' ? 'warning' : 'info'); 
                    ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $coli['statut'])); ?>
                    </span>
                    <small>N° <?php echo $coli['numero_suivi']; ?> - <?php echo number_format($coli['prix'], 0, ',', ' '); ?> FCFA</small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</body>
</html>