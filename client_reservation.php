<?php
require_once 'config.php';

$conn = getDBConnection();
$message = '';
$success = false;

// Récupération des itinéraires
$itineraires = $conn->query("SELECT * FROM itineraires ORDER BY ville_depart, ville_arrivee")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        // Créer ou trouver le voyageur
        $stmt = $conn->prepare("SELECT id_voyageur FROM voyageurs WHERE telephone = ?");
        $stmt->execute([$_POST['telephone']]);
        $voyageur = $stmt->fetch();
        
        if ($voyageur) {
            $id_voyageur = $voyageur['id_voyageur'];
        } else {
            $stmt = $conn->prepare("INSERT INTO voyageurs (nom, prenom, email, telephone, adresse) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['telephone'], $_POST['adresse']]);
            $id_voyageur = $conn->lastInsertId();
        }
        
        // Créer le ticket
        $numero_ticket = generateUniqueNumber('TK-');
        $prix_total = $_POST['prix'] * $_POST['nombre_places'];
        
        $stmt = $conn->prepare("INSERT INTO tickets (numero_ticket, id_voyageur, id_itineraire, date_voyage, heure_depart, nombre_places, prix_total, statut) VALUES (?, ?, ?, ?, ?, ?, ?, 'reserve')");
        $stmt->execute([$numero_ticket, $id_voyageur, $_POST['id_itineraire'], $_POST['date_voyage'], $_POST['heure_depart'], $_POST['nombre_places'], $prix_total]);
        
        $conn->commit();
        $success = true;
        $message = "Réservation effectuée avec succès ! Votre numéro de ticket est : <strong>$numero_ticket</strong>";
    } catch (Exception $e) {
        $conn->rollBack();
        $message = "Erreur lors de la réservation : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réserver un voyage - <?php echo APP_NAME; ?></title>
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
            max-width: 800px; margin: 3rem auto; padding: 0 2rem;
        }
        
        .page-title {
            text-align: center; color: #5cb85c; font-size: 2.5rem; margin-bottom: 2rem;
        }
        
        .card {
            background: white; padding: 2.5rem; border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .message {
            padding: 1rem; border-radius: 10px; margin-bottom: 2rem; text-align: center;
        }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        
        .form-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem; margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block; margin-bottom: 0.5rem; color: #333; font-weight: 500;
        }
        input, select, textarea {
            width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0;
            border-radius: 8px; font-size: 1rem; transition: border-color 0.3s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none; border-color: #5cb85c;
        }
        
        .price-display {
            background: #f8f9fa; padding: 1rem; border-radius: 8px;
            text-align: center; margin: 1.5rem 0;
        }
        .price-display .label { color: #666; font-size: 0.9rem; }
        .price-display .price {
            font-size: 2rem; color: #5cb85c; font-weight: bold;
        }
        
        .btn {
            padding: 1rem 2.5rem; border: none; border-radius: 50px;
            cursor: pointer; font-weight: bold; font-size: 1.1rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #7ed957, #5cb85c);
            color: white; width: 100%;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(92, 184, 92, 0.4);
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
        <h1 class="page-title">Réserver un voyage</h1>

        <?php if ($message): ?>
        <div class="message <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" id="reservation-form">
                <h2 style="color: #5cb85c; margin-bottom: 1.5rem;">Informations personnelles</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom *</label>
                        <input type="text" name="nom" required>
                    </div>
                    <div class="form-group">
                        <label>Prénom *</label>
                        <input type="text" name="prenom" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>Téléphone *</label>
                        <input type="tel" name="telephone" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Adresse</label>
                    <textarea name="adresse" rows="2"></textarea>
                </div>

                <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e0e0e0;">

                <h2 style="color: #5cb85c; margin-bottom: 1.5rem;">Détails du voyage</h2>

                <div class="form-group">
                    <label>Itinéraire *</label>
                    <select name="id_itineraire" id="itineraire" required onchange="updatePrice()">
                        <option value="">Sélectionnez un itinéraire</option>
                        <?php foreach ($itineraires as $it): ?>
                        <option value="<?php echo $it['id_itineraire']; ?>" data-prix="<?php echo $it['prix_base']; ?>">
                            <?php echo htmlspecialchars($it['ville_depart'] . ' → ' . $it['ville_arrivee']); ?>
                            - <?php echo number_format($it['prix_base'], 0, ',', ' '); ?> FCFA
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date du voyage *</label>
                        <input type="date" name="date_voyage" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Heure de départ *</label>
                        <input type="time" name="heure_depart" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nombre de places *</label>
                    <input type="number" name="nombre_places" id="nombre_places" min="1" max="10" value="1" required onchange="updatePrice()">
                </div>

                <input type="hidden" name="prix" id="prix" value="0">

                <div class="price-display">
                    <div class="label">Prix total</div>
                    <div class="price" id="prix-display">0 FCFA</div>
                </div>

                <button type="submit" class="btn btn-primary">Confirmer la réservation</button>
            </form>
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
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 <?php echo APP_NAME; ?>. Tous droits réservés.</p>
        </div>
    </footer>

    <script>
        function updatePrice() {
            const itineraire = document.getElementById('itineraire');
            const nbPlaces = document.getElementById('nombre_places').value;
            const selectedOption = itineraire.options[itineraire.selectedIndex];
            const prixUnitaire = selectedOption.getAttribute('data-prix') || 0;
            const prixTotal = prixUnitaire * nbPlaces;
            
            document.getElementById('prix').value = prixUnitaire;
            document.getElementById('prix-display').textContent = prixTotal.toLocaleString('fr-FR') + ' FCFA';
        }
    </script>
</body>
</html>