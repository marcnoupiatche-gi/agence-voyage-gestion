<?php
require_once 'config.php';

$conn = getDBConnection();
$bus = $conn->query("SELECT * FROM bus WHERE statut IN ('disponible', 'en_service') ORDER BY numero_bus")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notre Flotte - <?php echo APP_NAME; ?></title>
    <style>
       * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
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
        
        /* Features Section */
        .features {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem; margin-bottom: 3rem;
        }
        .feature-card {
            background: white; padding: 2rem; border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center; transition: transform 0.3s;
        }
        .feature-card:hover { transform: translateY(-10px); }
        .feature-icon {
            font-size: 3rem; margin-bottom: 1rem;
            background: linear-gradient(135deg, #7ed957, #5cb85c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .feature-card h3 { color: #1a3d0f; margin-bottom: 0.5rem; }
        .feature-card p { color: #666; }
        
        /* Bus Grid */
        .bus-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 2rem;
        }
        
        .bus-card {
            background: white; border-radius: 20px; overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.4s; position: relative;
        }
        .bus-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(126, 217, 87, 0.3);
        }
        
        .bus-image {
            height: 200px; background: linear-gradient(135deg, #7ed957, #5cb85c);
            display: flex; align-items: center; justify-content: center;
            font-size: 6rem; position: relative; overflow: hidden;
        }
        .bus-image::before {
            content: ''; position: absolute; top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s infinite;
        }
        @keyframes shimmer {
            0%, 100% { transform: translate(-50%, -50%); }
            50% { transform: translate(0%, 0%); }
        }
        
        .bus-status {
            position: absolute; top: 1rem; right: 1rem;
            padding: 0.5rem 1rem; border-radius: 20px;
            font-weight: 600; font-size: 0.85rem;
            background: rgba(255, 255, 255, 0.95);
        }
        .status-disponible { color: #155724; }
        .status-en_service { color: #0c5460; }
        
        .bus-content {
            padding: 2rem;
        }
        .bus-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 1.5rem;
        }
        .bus-number {
            font-size: 1.5rem; font-weight: bold; color: #1a3d0f;
        }
        .bus-brand {
            color: #666; font-size: 1.1rem;
        }
        
        .specs-grid {
            display: grid; grid-template-columns: repeat(2, 1fr);
            gap: 1rem; margin: 1.5rem 0;
        }
        .spec-item {
            background: #f8fff8; padding: 1rem; border-radius: 10px;
            border-left: 3px solid #7ed957; text-align: center;
        }
        .spec-icon { font-size: 1.8rem; margin-bottom: 0.5rem; }
        .spec-label { color: #666; font-size: 0.85rem; }
        .spec-value { font-weight: 600; color: #1a3d0f; font-size: 1.1rem; }
        
        .amenities {
            margin-top: 1.5rem; padding-top: 1.5rem;
            border-top: 2px dashed #e0e0e0;
        }
        .amenities-title {
            font-weight: 600; color: #1a3d0f; margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        .amenities-list {
            display: flex; flex-wrap: wrap; gap: 0.75rem;
        }
        .amenity-badge {
            background: linear-gradient(135deg, #e8f5e9, #f8fff8);
            padding: 0.5rem 1rem; border-radius: 20px;
            font-size: 0.85rem; color: #2d5016;
            border: 1px solid #7ed957;
            display: flex; align-items: center; gap: 0.5rem;
        }
        
        /* Info Section */
        .info-section {
            background: linear-gradient(135deg, #f8fff8, #e8f5e9);
            padding: 3rem 2rem; border-radius: 20px;
            margin: 3rem 0; border: 2px solid #7ed957;
        }
        .info-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem; margin-top: 2rem;
        }
        .info-item {
            display: flex; align-items: flex-start; gap: 1rem;
        }
        .info-item-icon {
            font-size: 2rem; flex-shrink: 0;
        }
        .info-item h3 { color: #1a3d0f; margin-bottom: 0.5rem; }
        .info-item p { color: #666; }
        
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
        .footer-section a {
            color: #ecf0f1; text-decoration: none; transition: color 0.3s;
        }
        .footer-section a:hover { color: #7ed957; }
        .footer-bottom {
            text-align: center; padding-top: 2rem;
            border-top: 1px solid #34495e; color: #95a5a6;
        }
        
        @media (max-width: 768px) {
            .bus-grid { grid-template-columns: 1fr; }
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
        <h1>🚌 Notre Flotte de Bus</h1>
        <p>Des véhicules modernes et confortables pour votre voyage</p>
    </section>

    <div class="container">
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">❄️</div>
                <h3>Climatisation</h3>
                <p>Tous nos bus sont équipés de climatisation pour votre confort</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🛡️</div>
                <h3>Sécurité</h3>
                <p>Entretien régulier et contrôles techniques à jour</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💺</div>
                <h3>Confort</h3>
                <p>Sièges ergonomiques avec espace pour les jambes</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔋</div>
                <h3>USB & Wifi</h3>
                <p>Restez connectés pendant votre voyage</p>
            </div>
        </div>

        <h2 class="section-title">Nos Véhicules</h2>

        <div class="bus-grid">
            <?php foreach ($bus as $b): ?>
            <div class="bus-card">
                <div class="bus-image">
                    🚌
                    <div class="bus-status status-<?php echo $b['statut']; ?>">
                        <?php echo $b['statut'] === 'disponible' ? '✓ Disponible' : '🚦 En service'; ?>
                    </div>
                </div>
                
                <div class="bus-content">
                    <div class="bus-header">
                        <div>
                            <div class="bus-number"><?php echo htmlspecialchars($b['numero_bus']); ?></div>
                            <div class="bus-brand"><?php echo htmlspecialchars($b['marque']); ?></div>
                        </div>
                    </div>
                    
                    <div class="specs-grid">
                        <div class="spec-item">
                            <div class="spec-icon">👥</div>
                            <div class="spec-label">Capacité</div>
                            <div class="spec-value"><?php echo $b['capacite_passagers']; ?> places</div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-icon">📦</div>
                            <div class="spec-label">Bagages</div>
                            <div class="spec-value"><?php echo $b['capacite_colis']; ?> kg</div>
                        </div>
                    </div>
                    
                    <div class="amenities">
                        <div class="amenities-title">🎯 Équipements & Services</div>
                        <div class="amenities-list">
                            <span class="amenity-badge">❄️ Climatisation</span>
                            <span class="amenity-badge">📺 Écran TV</span>
                            <span class="amenity-badge">🔌 Prises USB</span>
                            <span class="amenity-badge">🌐 WiFi</span>
                            <span class="amenity-badge">💺 Sièges confort</span>
                            <span class="amenity-badge">🔊 Système audio</span>
                            <span class="amenity-badge">🚻 Toilettes</span>
                            <span class="amenity-badge">🧊 Distributeur d'eau</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="info-section">
            <h2 class="section-title" style="color: #1a3d0f;">Pourquoi Choisir Nos Bus ?</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-item-icon">🔧</div>
                    <div>
                        <h3>Entretien Régulier</h3>
                        <p>Tous nos véhicules sont régulièrement entretenus par des professionnels qualifiés</p>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-item-icon">✅</div>
                    <div>
                        <h3>Normes de Sécurité</h3>
                        <p>Respect strict des normes de sécurité et contrôles techniques à jour</p>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-item-icon">🌟</div>
                    <div>
                        <h3>Confort Premium</h3>
                        <p>Sièges ergonomiques, climatisation et espaces optimisés pour votre confort</p>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-item-icon">♻️</div>
                    <div>
                        <h3>Écologie</h3>
                        <p>Véhicules récents aux normes environnementales en vigueur</p>
                    </div>
                </div>
            </div>
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
                <ul style="list-style: none;">
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
</body>
</html>