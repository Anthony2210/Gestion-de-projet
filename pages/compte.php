<?php
require '../bd/bd.php'; // Connexion à la base de données
include '../includes/header.php'; // Assure-toi que `session_start()` est au tout début de header.php

// Gestion de la connexion
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Préparation de la requête pour vérifier les informations de connexion
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Vérifie si l'utilisateur existe et si le mot de passe est correct
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: ../index.php"); // Redirige vers la page index.php
        exit;
    } else {
        $login_error = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}
// Gestion de l'inscription
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Vérifie que les mots de passe correspondent
    if ($password === $confirm_password) {
        // Vérifie si le nom d'utilisateur est déjà pris
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $register_error = "Ce nom d'utilisateur est déjà pris.";
        } else {
            // Hashage du mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Photo de profil par défaut
            $profile_picture = 'user.png';

            // Insertion du nouvel utilisateur dans la base de données
            $stmt = $conn->prepare("INSERT INTO users (username, password, profile_picture) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $profile_picture);

            if ($stmt->execute()) {
                $register_success = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
            } else {
                $register_error = "Une erreur s'est produite lors de la création du compte.";
            }
        }
    } else {
        $register_error = "Les mots de passe ne correspondent pas.";
    }
}

// Vérifie si l'utilisateur est connecté pour afficher l'historique des recherches
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Récupère l'historique des recherches de l'utilisateur
    $stmt = $conn->prepare("SELECT search_query, search_date FROM search_history WHERE user_id = ? ORDER BY search_date DESC LIMIT 10");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $search_history = $result->fetch_all(MYSQLI_ASSOC);
}
// Effacer l'historique des recherches
if (isset($_POST['clear_history']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("DELETE FROM search_history WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    // Rafraîchir la page pour mettre à jour l'affichage
    header("Location: compte.php");
    exit;
}

// Récupérer les informations de l'utilisateur pour afficher la date d'inscription
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    // Récupérer les détails de l'utilisateur
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user = $user_result->fetch_assoc();

    // Récupérer l'historique des recherches
    $stmt = $conn->prepare("SELECT search_query, search_date FROM search_history WHERE user_id = ? ORDER BY search_date DESC LIMIT 10");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $history_result = $stmt->get_result();
    $search_history = $history_result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Compte - PureOxy</title>
    <!-- Lien Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../styles/style.css">
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/includes.css">

</head>
<body>

<div class="compte-container">
    <h1>L’espace Compte</h1>

    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Tableau de bord de l'utilisateur -->
        <div class="dashboard">

            <!-- Carte de Profil -->
            <div class="profile-card">
                <div class="profile-avatar">
                    <img src="../images/user.png" alt="Photo de profil">
                </div>
                <div class="profile-info">
                    <h2><?= htmlspecialchars($_SESSION['username']) ?></h2>
                    <!-- Afficher la date d'inscription si disponible -->
                    <p>Membre depuis <?= isset($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : 'Date inconnue' ?></p>
                </div>
            </div>

            <!-- Section Historique des Recherches -->
            <div class="history-section">
                <h3><i class="fas fa-history"></i> Historique des dernières recherches</h3>
                <?php if (!empty($search_history)): ?>
                    <ul class="history-list">
                        <?php foreach ($search_history as $search): ?>
                            <li>
                                <span class="search-query">
                                    <i class="fas fa-search"></i> <?= htmlspecialchars($search['search_query']) ?>
                                </span>
                                <span class="search-date"><?= date('d/m/Y H:i', strtotime($search['search_date'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <!-- Bouton pour effacer l'historique -->
                    <form method="post" class="clear-history-form">
                        <button type="submit" name="clear_history" class="clear-history-button"><i class="fas fa-trash-alt"></i> Effacer l'historique</button>
                    </form>
                <?php else: ?>
                    <p>Vous n'avez pas encore effectué de recherches.</p>
                <?php endif; ?>
            </div>

            <!-- Bouton de déconnexion -->
            <a href="deconnecter.php" class="logout-button"><i class="fas fa-sign-out-alt"></i> Se déconnecter</a>
        </div>
    <?php else: ?>
        <!-- Conteneur des onglets -->
        <div class="tabs">
            <button class="tab-link active" onclick="openTab(event, 'connexion')">
                <i class="fas fa-sign-in-alt"></i> Connexion
            </button>
            <button class="tab-link" onclick="openTab(event, 'inscription')">
                <i class="fas fa-user-plus"></i> Inscription
            </button>
        </div>

        <!-- Formulaire de connexion -->
        <div id="connexion" class="tab-content active">
            <form class="compte-form" method="POST">
                <h2>Connexion à votre compte</h2>
                <?php if (isset($login_error)): ?>
                    <p class="error-message"><?= $login_error ?></p>
                <?php endif; ?>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Nom d'utilisateur" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Mot de passe" required>
                </div>
                <button type="submit" name="login">Se connecter</button>
            </form>
        </div>

        <!-- Formulaire d'inscription -->
        <div id="inscription" class="tab-content">
            <form class="compte-form" method="POST">
                <h2>Création d'un nouveau compte</h2>
                <?php if (isset($register_success)): ?>
                    <p class="success-message"><?= $register_success ?></p>
                <?php elseif (isset($register_error)): ?>
                    <p class="error-message"><?= $register_error ?></p>
                <?php endif; ?>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Nom d'utilisateur" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Mot de passe" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" placeholder="Confirmez le mot de passe" required>
                </div>
                <button type="submit" name="register">S'inscrire</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
<!-- Script pour les onglets -->
<script>
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;

        // Récupérer tous les éléments avec la classe "tab-content" et les cacher
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
            tabcontent[i].classList.remove("active");
        }

        // Récupérer tous les éléments avec la classe "tab-link" et enlever la classe "active"
        tablinks = document.getElementsByClassName("tab-link");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }

        // Afficher le contenu de l'onglet courant et ajouter la classe "active" au bouton qui a été cliqué
        document.getElementById(tabName).style.display = "block";
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }

    // Par défaut, afficher l'onglet de connexion
    document.getElementById("connexion").style.display = "block";
</script>

</body>
</html>

