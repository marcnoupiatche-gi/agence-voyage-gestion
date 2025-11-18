<?php
require_once 'config.php';

// Récupérer quelques itinéraires populaires
$conn = getDBConnection();
$stmt = $conn->query("SELECT * FROM itineraires LIMIT 4");
$itineraires = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Accueil</title>
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
        
        /* Services Section */
        .services {
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 2rem;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            color: #5cb85c;
            margin-bottom: 3rem;
        }
        
        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .service-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
        }
        
        .service-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #7ed957, #5cb85c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
        }
        
        .service-card h3 {
            color: #5cb85c;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        /* Itineraires populaires */
        .itineraires {
            background: #f8f9fa;
            padding: 4rem 2rem;
        }
        
        .itineraire-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .itineraire-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }
        
        .itineraire-route {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
        }
        
        .itineraire-details {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .itineraire-price {
            font-size: 1.5rem;
            color: #5cb85c;
            font-weight: bold;
        }
        
        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            padding: 3rem 2rem 1rem;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h3 {
            color: #7ed957;
            margin-bottom: 1rem;
        }
        
        .footer-section p, .footer-section ul {
            margin-bottom: 0.5rem;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section a {
            color: #ecf0f1;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-section a:hover {
            color: #7ed957;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #34495e;
            color: #95a5a6;
        }
    </style>
</head>
<body>
    <!-- Header -->
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

    <!-- Hero Section -->
    <section class="hero">
        <h1>Bienvenue chez <?php echo APP_NAME; ?></h1>
        <p>Votre partenaire de confiance pour vos voyages et envois de colis</p>
        <a href="client_reservation.php" class="btn">Réserver maintenant</a>
    </section>

    <!-- Services Section -->
    <section class="services">
        <h2 class="section-title">Nos Services</h2>
        <div class="service-grid">
            <div class="service-card">
                <div class="service-icon">🎫</div>
                <h3>Réservation de Voyages</h3>
                <p>Réservez vos billets en ligne facilement et voyagez en toute sécurité avec nos bus confortables.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">📦</div>
                <h3>Envoi de Colis</h3>
                <p>Expédiez vos colis rapidement et en toute sécurité vers toutes nos destinations.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">📍</div>
                <h3>Suivi en Temps Réel</h3>
                <p>Suivez l'état de vos colis en temps réel grâce à notre système de tracking avancé.</p>
            </div>
        </div>
    </section>

    <!-- Itinéraires Populaires -->
    <section class="itineraires">
        <h2 class="section-title">Nos Destinations</h2>
        <div class="itineraire-grid">
            <?php foreach ($itineraires as $itineraire): ?>
            <div class="itineraire-card">
                <div class="itineraire-route">
                    <?php echo htmlspecialchars($itineraire['ville_depart']); ?> → 
                    <?php echo htmlspecialchars($itineraire['ville_arrivee']); ?>
                </div>
                <div class="itineraire-details">
                    📏 <?php echo $itineraire['distance_km']; ?> km<br>
                    ⏱️ <?php echo $itineraire['duree_estimee']; ?>
                </div>
                <div class="itineraire-price">
                    <?php echo number_format($itineraire['prix_base'], 0, ',', ' '); ?> FCFA
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>À Propos</h3>
                <p><?php echo APP_NAME; ?> est votre agence de transport de confiance, offrant des services de qualité depuis plusieurs années.</p>
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
            <div class="footer-section">
                <h3>Horaires</h3>
                <p>Lundi - Samedi: 6h - 20h</p>
                <p>Dimanche: 8h - 18h</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 <?php echo APP_NAME; ?>. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html>