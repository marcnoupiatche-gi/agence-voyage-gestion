<?php
require_once 'config.php';

$conn = getDBConnection();

// Récupérer les itinéraires avec leurs informations
$itineraires = $conn->query("SELECT * FROM itineraires ORDER BY ville_depart, ville_arrivee")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Tickets & Horaires - <?php echo APP_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        
       /* Header */
        header {
            background: linear-gradient(135deg, #7ed957 0%, #5cb85c 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }
        
        nav a:hover {
            opacity: 0.8;
        }
        
         /* Hero Section */
        .hero {
            background: linear-gradient(rgba(126, 217, 87, 0.9), rgba(92, 184, 92, 0.9)),
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%235cb85c" width="1200" height="600"/></svg>');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 6rem 2rem;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
        }
        
        .btn {
            display: inline-block;
            padding: 1rem 2.5rem;
            background: white;
            color: #5cb85c;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        /* Main Content */
        .container {
            max-width: 1200px; margin: 3rem auto; padding: 0 2rem;
        }
        
        .section-title {
            text-align: center; font-size: 2rem; color: #1a3d0f;
            margin-bottom: 2rem; position: relative; padding-bottom: 1rem;
        }
        .section-title::after {
            content: ''; position: absolute; bottom: 0; left: 50%;
            transform: translateX(-50%); width: 80px; height: 4px;
            background: linear-gradient(90deg, #7ed957, #5cb85c);
            border-radius: 2px;
        }
        
        /* Filter Section */
        .filter-section {
            background: white; padding: 2rem; border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-bottom: 2rem;
        }
        .filter-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .filter-group label {
            display: block; margin-bottom: 0.5rem; color: #1a3d0f;
            font-weight: 600; font-size: 0.95rem;
        }
        .filter-group input, .filter-group select {
            width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0;
            border-radius: 10px; font-size: 1rem; transition: border-color 0.3s;
        }
        .filter-group input:focus, .filter-group select:focus {
            outline: none; border-color: #7ed957;
        }
        
        /* Tickets Grid */
        .tickets-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem; margin-top: 2rem;
        }
        
        .ticket-card {
            background: white; border-radius: 15px; overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s; position: relative;
            border: 2px solid transparent;
        }
        .ticket-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 30px rgba(126, 217, 87, 0.3);
            border-color: #7ed957;
        }
        
        .ticket-header {
            background: linear-gradient(135deg, #7ed957, #5cb85c);
            color: white; padding: 1.5rem; text-align: center;
            position: relative;
        }
        .ticket-header::after {
            content: ''; position: absolute; bottom: -10px; left: 0;
            width: 100%; height: 20px;
            background: radial-gradient(circle at 10px 0, transparent 10px, white 10px) repeat-x;
            background-size: 20px 20px;
        }
        .route {
            font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem;
            display: flex; align-items: center; justify-content: center;
            gap: 0.75rem;
        }
        .route-arrow {
            font-size: 1.3rem; animation: slideRight 1.5s infinite;
        }
        @keyframes slideRight {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(5px); }
        }
        
        .ticket-body {
            padding: 1.5rem;
        }
        .ticket-info {
            display: flex; flex-direction: column; gap: 1rem;
        }
        .info-row {
            display: flex; justify-content: space-between;
            padding: 0.75rem; background: #f8fff8;
            border-radius: 8px; border-left: 3px solid #7ed957;
        }
        .info-label {
            color: #666; font-size: 0.9rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .info-value {
            font-weight: 600; color: #1a3d0f;
        }
        
        .price-section {
            background: linear-gradient(135deg, #f8fff8, #e8f5e9);
            padding: 1.5rem; margin: 1rem -1.5rem -1.5rem;
            border-top: 2px dashed #7ed957;
        }
        .price-label {
            text-align: center; color: #666;
            font-size: 0.9rem; margin-bottom: 0.5rem;
        }
        .price {
            text-align: center; font-size: 2rem;
            font-weight: bold; color: #2d5016;
        }
        .price-subtext {
            text-align: center; color: #666;
            font-size: 0.85rem; margin-top: 0.5rem;
        }
        
        .btn-reserve {
            width: 100%; padding: 1rem; margin-top: 1rem;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white; border: none; border-radius: 10px;
            font-weight: bold; font-size: 1rem; cursor: pointer;
            transition: all 0.3s;
        }
        .btn-reserve:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }
        
        /* Info Banner */
        .info-banner {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border-left: 4px solid #ffa500; padding: 1.5rem;
            border-radius: 10px; margin: 2rem 0;
        }
        .info-banner h3 {
            color: #856404; margin-bottom: 0.5rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .info-banner ul {
            margin-left: 1.5rem; color: #856404;
        }
        .info-banner li { margin: 0.5rem 0; }
        
        /* Footer */
        footer {
            background: #2c3e50; color: white; padding: 3rem 2rem 1rem;
            margin-top: 4rem;
        }
        .footer-content {
            max-width: 1200px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem; margin-bottom: 2rem;
        }
        .footer-section h3 { color: #7ed957; margin-bottom: 1rem; }
        .footer-section p, .footer-section ul { margin-bottom: 0.5rem; }
        .footer-section ul { list-style: none; }
        .footer-section a {
            color: #ecf0f1; text-decoration: none; transition: color 0.3s;
        }
        .footer-section a:hover { color: #7ed957; }
        .footer-bottom {
            text-align: center; padding-top: 2rem;
            border-top: 1px solid #34495e; color: #95a5a6;
        }
        
        @media (max-width: 768px) {
            .tickets-grid { grid-template-columns: 1fr; }
            nav ul { gap: 1rem; }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="index.php" class="logo">
                <span>🚌</span>
                <span><?php echo APP_NAME; ?></span>
            </a>
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

    <section class="hero">
        <h1>🎫 Nos Tickets & Horaires</h1>
        <p>Découvrez toutes nos destinations et réservez votre voyage en toute simplicité</p>
    </section>

    <div class="container">
        <div class="filter-section">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>🏙️ Ville de départ</label>
                    <select id="departFilter" onchange="filterTickets()">
                        <option value="">Toutes les villes</option>
                        <?php
                        $villes_depart = array_unique(array_column($itineraires, 'ville_depart'));
                        foreach ($villes_depart as $ville):
                        ?>
                        <option value="<?php echo $ville; ?>"><?php echo $ville; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>🏙️ Ville d'arrivée</label>
                    <select id="arriveeFilter" onchange="filterTickets()">
                        <option value="">Toutes les villes</option>
                        <?php
                        $villes_arrivee = array_unique(array_column($itineraires, 'ville_arrivee'));
                        foreach ($villes_arrivee as $ville):
                        ?>
                        <option value="<?php echo $ville; ?>"><?php echo $ville; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>💰 Prix maximum (FCFA)</label>
                    <input type="number" id="prixFilter" placeholder="Ex: 10000" onchange="filterTickets()">
                </div>
            </div>
        </div>

        <div class="info-banner">
            <h3>ℹ️ Informations importantes</h3>
            <ul>
                <li>Les départs sont quotidiens de 6h à 18h</li>
                <li>Arrivez 30 minutes avant le départ</li>
                <li>Les enfants de moins de 3 ans voyagent gratuitement</li>
                <li>Réduction de 20% pour les groupes de 10 personnes et plus</li>
            </ul>
        </div>

        <h2 class="section-title">Nos Destinations</h2>

        <div class="tickets-grid" id="ticketsGrid">
            <?php foreach ($itineraires as $it): ?>
            <div class="ticket-card" 
                 data-depart="<?php echo $it['ville_depart']; ?>"
                 data-arrivee="<?php echo $it['ville_arrivee']; ?>"
                 data-prix="<?php echo $it['prix_base']; ?>">
                <div class="ticket-header">
                    <div class="route">
                        <span><?php echo htmlspecialchars($it['ville_depart']); ?></span>
                        <span class="route-arrow">→</span>
                        <span><?php echo htmlspecialchars($it['ville_arrivee']); ?></span>
                    </div>
                    <div style="font-size: 0.9rem; opacity: 0.9;">Trajet direct</div>
                </div>
                
                <div class="ticket-body">
                    <div class="ticket-info">
                        <div class="info-row">
                            <span class="info-label">
                                <span>📏</span>
                                <span>Distance</span>
                            </span>
                            <span class="info-value"><?php echo $it['distance_km']; ?> km</span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <span>⏱️</span>
                                <span>Durée estimée</span>
                            </span>
                            <span class="info-value"><?php echo $it['duree_estimee']; ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <span>🚌</span>
                                <span>Bus climatisé</span>
                            </span>
                            <span class="info-value" style="color: #28a745;">✓ Disponible</span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <span>📦</span>
                                <span>Bagages inclus</span>
                            </span>
                            <span class="info-value">20 kg</span>
                        </div>
                    </div>
                    
                    <div class="price-section">
                        <div class="price-label">Tarif par personne</div>
                        <div class="price"><?php echo number_format($it['prix_base'], 0, ',', ' '); ?> FCFA</div>
                        <div class="price-subtext">Taxes incluses</div>
                        <a href="client_reservation.php" class="btn-reserve">
                            🎫 Réserver maintenant
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
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
            <div class="footer-section">
                <h3>Liens Utiles</h3>
                <ul>
                    <li><a href="client_reservation.php">Réserver un voyage</a></li>
                    <li><a href="client_suivi.php">Suivre un colis</a></li>
                    <li><a href="login.php">Espace Admin</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 <?php echo APP_NAME; ?>. Tous droits réservés.</p>
        </div>
    </footer>

    <script>
        function filterTickets() {
            const departFilter = document.getElementById('departFilter').value.toLowerCase();
            const arriveeFilter = document.getElementById('arriveeFilter').value.toLowerCase();
            const prixFilter = document.getElementById('prixFilter').value;
            const cards = document.querySelectorAll('.ticket-card');
            
            cards.forEach(card => {
                const depart = card.dataset.depart.toLowerCase();
                const arrivee = card.dataset.arrivee.toLowerCase();
                const prix = parseFloat(card.dataset.prix);
                
                const matchDepart = !departFilter || depart.includes(departFilter);
                const matchArrivee = !arriveeFilter || arrivee.includes(arriveeFilter);
                const matchPrix = !prixFilter || prix <= parseFloat(prixFilter);
                
                if (matchDepart && matchArrivee && matchPrix) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>