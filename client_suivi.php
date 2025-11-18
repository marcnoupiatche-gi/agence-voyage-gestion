<?php
require_once 'config.php';

$conn = getDBConnection();
$colis = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_suivi = $_POST['numero_suivi'] ?? '';
    
    if (!empty($numero_suivi)) {
        $stmt = $conn->prepare("
            SELECT c.*, 
                   e.nom as exp_nom, e.prenom as exp_prenom, e.telephone as exp_tel,
                   d.nom as dest_nom, d.prenom as dest_prenom, d.telephone as dest_tel, d.adresse as dest_adresse,
                   i.ville_depart, i.ville_arrivee,
                   ch.nom as chauf_nom, ch.prenom as chauf_prenom, ch.telephone as chauf_tel,
                   b.numero_bus
            FROM colis c
            LEFT JOIN expediteurs e ON c.id_expediteur = e.id_expediteur
            LEFT JOIN destinataires d ON c.id_destinataire = d.id_destinataire
            LEFT JOIN itineraires i ON c.id_itineraire = i.id_itineraire
            LEFT JOIN chauffeurs ch ON c.id_chauffeur = ch.id_chauffeur
            LEFT JOIN bus b ON c.id_bus = b.id_bus
            WHERE c.numero_suivi = ?
        ");
        $stmt->execute([$numero_suivi]);
        $colis = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$colis) {
            $error = 'Aucun colis trouvé avec ce numéro de suivi';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de Colis - <?php echo APP_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        
        header {
            background: linear-gradient(135deg, #7ed957 0%, #5cb85c 100%);
            color: white; padding: 1rem 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        nav {
            max-width: 1200px; margin: 0 auto; display: flex;
            justify-content: space-between; align-items: center; padding: 0 2rem;
        }
        .logo { font-size: 1.8rem; font-weight: bold; text-decoration: none; color: white; }
        nav ul { display: flex; list-style: none; gap: 2rem; }
        nav a { color: white; text-decoration: none; font-weight: 500; transition: opacity 0.3s; }
        nav a:hover { opacity: 0.8; }
        
        .container {
            max-width: 900px; margin: 3rem auto; padding: 0 2rem;
        }
        
        .page-title {
            text-align: center; color: #5cb85c; font-size: 2.5rem; margin-bottom: 2rem;
        }
        
        .search-card {
            background: white; padding: 2.5rem; border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-bottom: 2rem;
        }
        
        .search-form {
            display: flex; gap: 1rem; align-items: flex-end;
        }
        .search-form input {
            flex: 1; padding: 1rem; border: 2px solid #e0e0e0;
            border-radius: 10px; font-size: 1.1rem;
        }
        .search-form input:focus {
            outline: none; border-color: #5cb85c;
        }
        .search-form button {
            padding: 1rem 2rem; background: linear-gradient(135deg, #7ed957, #5cb85c);
            color: white; border: none; border-radius: 10px; font-weight: bold;
            cursor: pointer; transition: transform 0.3s;
        }
        .search-form button:hover {
            transform: translateY(-2px);
        }
        
        .error {
            background: #f8d7da; color: #721c24; padding: 1rem;
            border-radius: 10px; text-align: center; margin-bottom: 2rem;
        }
        
        .tracking-result {
            background: white; border-radius: 15px; overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .tracking-header {
            background: linear-gradient(135deg, #7ed957, #5cb85c);
            color: white; padding: 2rem; text-align: center;
        }
        .tracking-header h2 {
            font-size: 2rem; margin-bottom: 0.5rem;
        }
        
        .status-badge {
            display: inline-block; padding: 0.5rem 1.5rem;
            border-radius: 50px; font-weight: bold; font-size: 1.1rem;
            margin-top: 1rem;
        }
        .status-en_attente { background: #fff3cd; color: #856404; }
        .status-en_transit { background: #d1ecf1; color: #0c5460; }
        .status-livre { background: #d4edda; color: #155724; }
        .status-annule { background: #f8d7da; color: #721c24; }
        
        .tracking-content {
            padding: 2rem;
        }
        
        .info-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem; margin-bottom: 2rem;
        }
        
        .info-section {
            background: #f8f9fa; padding: 1.5rem; border-radius: 10px;
        }
        .info-section h3 {
            color: #5cb85c; margin-bottom: 1rem; font-size: 1.2rem;
        }
        .info-item {
            margin-bottom: 0.75rem;
        }
        .info-item strong {
            color: #666; display: block; font-size: 0.9rem;
        }
        .info-item span {
            color: #333; font-size: 1.1rem;
        }
        
        .timeline {
            position: relative; padding-left: 2rem;
        }
        .timeline::before {
            content: ''; position: absolute; left: 0.5rem; top: 0;
            width: 2px; height: 100%; background: #e0e0e0;
        }
        .timeline-item {
            position: relative; padding-bottom: 2rem;
        }
        .timeline-item::before {
            content: ''; position: absolute; left: -1.6rem; top: 0.3rem;
            width: 1rem; height: 1rem; border-radius: 50%;
            background: #5cb85c; border: 3px solid white;
            box-shadow: 0 0 0 2px #5cb85c;
        }
        .timeline-item.inactive::before {
            background: #e0e0e0; box-shadow: 0 0 0 2px #e0e0e0;
        }
        .timeline-item h4 {
            color: #333; margin-bottom: 0.25rem;
        }
        .timeline-item p {
            color: #666; font-size: 0.9rem;
        }
        
        footer {
            background: #2c3e50; color: white; padding: 3rem 2rem 1rem; margin-top: 4rem;
        }
        .footer-content {
            max-width: 1200px; margin: 0 auto; display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem;
        }
        .footer-section h3 { color: #7ed957; margin-bottom: 1rem; }
        .footer-bottom {
            text-align: center; padding-top: 2rem; border-top: 1px solid #34495e; color: #95a5a6;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="index.php" class="logo">🚌 <?php echo APP_NAME; ?></a>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="client_reservation.php">Réserver</a></li>
                <li><a href="client_ticket.php">Tickets</a></li>
                <li><a href="client_bus.php">Bus</a></li>
                <li><a href="client_suivi.php">Suivre un colis</a></li>
                <li><a href="login.php">Connexion Admin</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h1 class="page-title">Suivre votre colis</h1>

        <div class="search-card">
            <form method="POST" class="search-form">
                <input type="text" name="numero_suivi" placeholder="Entrez votre numéro de suivi (ex: CL-20250101234)" required>
                <button type="submit">🔍 Rechercher</button>
            </form>
        </div>

        <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($colis): ?>
        <div class="tracking-result">
            <div class="tracking-header">
                <h2>📦 Colis <?php echo htmlspecialchars($colis['numero_suivi']); ?></h2>
                <span class="status-badge status-<?php echo $colis['statut']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $colis['statut'])); ?>
                </span>
            </div>

            <div class="tracking-content">
                <div class="info-grid">
                    <div class="info-section">
                        <h3>📤 Expéditeur</h3>
                        <div class="info-item">
                            <strong>Nom</strong>
                            <span><?php echo htmlspecialchars($colis['exp_prenom'] . ' ' . $colis['exp_nom']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Téléphone</strong>
                            <span><?php echo htmlspecialchars($colis['exp_tel']); ?></span>
                        </div>
                    </div>

                    <div class="info-section">
                        <h3>📥 Destinataire</h3>
                        <div class="info-item">
                            <strong>Nom</strong>
                            <span><?php echo htmlspecialchars($colis['dest_prenom'] . ' ' . $colis['dest_nom']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Téléphone</strong>
                            <span><?php echo htmlspecialchars($colis['dest_tel']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Adresse</strong>
                            <span><?php echo htmlspecialchars($colis['dest_adresse']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-section">
                        <h3>📋 Détails du colis</h3>
                        <div class="info-item">
                            <strong>Trajet</strong>
                            <span><?php echo htmlspecialchars($colis['ville_depart'] . ' → ' . $colis['ville_arrivee']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Poids</strong>
                            <span><?php echo $colis['poids']; ?> kg</span>
                        </div>
                        <div class="info-item">
                            <strong>Prix</strong>
                            <span><?php echo number_format($colis['prix'], 0, ',', ' '); ?> FCFA</span>
                        </div>
                    </div>

                    <?php if ($colis['chauf_nom']): ?>
                    <div class="info-section">
                        <h3>🚗 Transport</h3>
                        <div class="info-item">
                            <strong>Chauffeur</strong>
                            <span><?php echo htmlspecialchars($colis['chauf_prenom'] . ' ' . $colis['chauf_nom']); ?></span>
                        </div>
                        <?php if ($colis['numero_bus']): ?>
                        <div class="info-item">
                            <strong>Bus</strong>
                            <span><?php echo htmlspecialchars($colis['numero_bus']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <strong>Contact</strong>
                            <span><?php echo htmlspecialchars($colis['chauf_tel']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="info-section">
                    <h3>🚚 Suivi du colis</h3>
                    <div class="timeline">
                        <div class="timeline-item">
                            <h4>Colis enregistré</h4>
                            <p><?php echo date('d/m/Y H:i', strtotime($colis['date_creation'])); ?></p>
                        </div>
                        <div class="timeline-item <?php echo in_array($colis['statut'], ['en_attente']) ? 'inactive' : ''; ?>">
                            <h4>En transit</h4>
                            <p>Le colis est en cours d'acheminement</p>
                        </div>
                        <div class="timeline-item <?php echo $colis['statut'] !== 'livre' ? 'inactive' : ''; ?>">
                            <h4>Livré</h4>
                            <p><?php echo $colis['date_livraison_prevue'] ? 'Livraison prévue le ' . date('d/m/Y', strtotime($colis['date_livraison_prevue'])) : 'À déterminer'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>À Propos</h3>
                <p><?php echo APP_NAME; ?> - Votre partenaire de confiance pour vos voyages et envois.</p>
            </div>
            <div class="footer-section">
                <h3>Contact</h3>
                <p>📞 <?php echo APP_PHONE; ?></p>
                <p>📧 <?php echo APP_EMAIL; ?></p>
                <p>📍 <?php echo APP_ADDRESS; ?></p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 <?php echo APP_NAME; ?>. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html>