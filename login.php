<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE email = ? AND mot_de_passe = MD5(?)");
        $stmt->execute([$email, $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id_utilisateur'];
            $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['role'] = $user['role'];
            header('Location: admin_dashboard.php');
            exit();
        } else {
            $error = 'Email ou mot de passe incorrect';
        }
    } else {
        $error = 'Veuillez remplir tous les champs';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?php echo APP_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #7ed957 0%, #5cb85c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .logo h1 {
            color: #5cb85c;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .logo p {
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #5cb85c;
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #7ed957, #5cb85c);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(92, 184, 92, 0.4);
        }
        
        .error {
            background: #ff4444;
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: #5cb85c;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .info-box {
            background: #e8f5e9;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 2rem;
            border-left: 4px solid #5cb85c;
        }
        
        .info-box h4 {
            color: #5cb85c;
            margin-bottom: 0.5rem;
        }
        
        .info-box p {
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">🚌</div>
            <h1><?php echo APP_NAME; ?></h1>
            <p>Espace Administration</p>
        </div>
        
        <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Se connecter</button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Retour à l'accueil</a>
        </div>
        
        <div class="info-box">
            <h4>Informations de test</h4>
            <p><strong>Email:</strong> admin@touristique-express.com<br>
            <strong>Mot de passe:</strong> admin123</p>
        </div>
    </div>
</body>
</html>