<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Qui Nous Garde</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('style/pexel (2).jpg');
            background-size: cover;
        }
        header {
            background-color: #333;
            color: #fff;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .menu {
            position: fixed;
            top: 0;
            right: -250px; /* Caché par défaut */
            width: 250px;
            height: 100%;
            background-color: #333;
            transition: all 0.3s ease;
            z-index: 1000; /* Assurez-vous que le menu est au-dessus de tout */
            padding-top: 60px; /* Pour éviter de cacher le contenu lorsqu'il est ouvert */
        }
        .menu.open {
            right: 0; /* Afficher lorsque ouvert */
        }
        .menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .menu ul li {
            padding: 15px;
            border-bottom: 1px solid #555;
            transition: background-color 0.3s ease;
        }
        .menu ul li:hover {
            background-color: #555;
        }
        .menu ul li a {
            color: #fff;
            text-decoration: none;
        }
        .menu ul li label {
            color: #fff;
        }
        .menu ul li select {
            margin-top: 5px;
        }
        .menu-toggle {
            display: block;
            background-color: #333;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }
        section {
            padding: 20px;
        }
        footer {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 20px; /* augmenté la marge interne pour plus de contenu */
            /* Supprimez la propriété 'position: fixed;' */
            /* Ajoutez la propriété 'position: absolute;' */
            position: display;
            bottom: 0;
            width: 100%;
        }
        .testimonial {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #ccc;
}

.parent-comment {
    display: flex;
    align-items: center;
}

.parent-comment img {
    width: 80px; /* Ajustez la taille de l'image selon vos préférences */
    height: 80px; /* Ajustez la taille de l'image selon vos préférences */
    border-radius: 50%; /* Pour arrondir l'image */
    margin-right: 10px;
}

.parent-comment p {
    margin: 0;
}

.parent-comment strong {
    font-weight: bold;
}

    </style>
</head>
<body>
<header>
    <button class="menu-toggle" onclick="toggleMenu()">☰ Menu</button>
</header>
    <h1>Bienvenue sur kiNouGarde</h1>
<div class="menu" id="menu">
    <ul>
        <li><a href="#">Contact</a></li>
        <li>
            <label for="inscription">Inscription</label>
            <select id="inscription" onchange="location = this.value;">
                <option value="#" selected>S'inscrire</option>
                <option value="inscription-parents.php">Inscription Parents</option>
                <option value="inscription-nounou.php">Inscription Nounous</option>
            </select>
        </li>
        <li>
            <label for="connexion">Connexion</label>
            <select id="connexion" onchange="location = this.value;">
                <option value="#" selected>Se connecter</option>
                <option value="login.php">Connexion Parents</option>
                <option value="login.php">Connexion Nounous</option>
            </select>
        </li>
    </ul>
</div>
    
    <section class="banner">
        <p>Trouvez la meilleure garde d'enfants près de chez vous !</p>
        <a href="inscription-nounou.php" class="btn">Je suis une nounou</a>
        <a href="inscription-parents.php" class="btn">Je suis un parent</a>
    </section>
    
    <section class="services">
        <h2>Nos Services</h2>
        <p>kiNouGarde est votre solution pour trouver des nounous fiables et qualifiées pour prendre soin de vos enfants. Nous offrons :</p>
        <ul>
            <li>Un vaste réseau de nounous vérifiées et évaluées.</li>
            <li>Des outils de recherche avancés pour trouver la nounou parfaite en fonction de vos besoins.</li>
            <li>Des options de paiement sécurisées et flexibles.</li>
            <li>Un service client dédié pour répondre à toutes vos questions et préoccupations.</li>
        </ul>
    </section>
    
    <!-- Ajout des commentaires des clients -->
    <!-- Témoignages fictifs : la plateforme n'a jamais été mise en service.
         Ils illustrent la maquette, ils n'attestent de rien. -->
    <section class="testimonials">
        <h2>Témoignages <em>(exemples fictifs)</em></h2>
        <div class="testimonial">
        		<div class="parent-comment">
            <p>"J'ai trouvé une nounou incroyable grâce à kiNouGarde! Je recommande vivement cette plateforme." <br> <strong>Utilisateur : </strong>Marie <br> <strong>Evaluation : </strong>★★★★★</p>
        		</div>
        </div>
        <div class="testimonial">
        		<div class="parent-comment">
            <p>"Le service client de kiNouGarde est exceptionnel. Ils ont répondu à toutes mes questions rapidement et efficacement." <br> <strong>Utilisateur : </strong>Pierre <br> <strong>Evaluation : </strong>★★★★☆</p>
        		</div>
        </div>
        <div class="testimonial">
        		<div class="parent-comment">
            <p>"Grâce à kiNouGarde, j'ai trouvé une famille formidable à qui confier mes enfants. Merci pour cette belle expérience!" <br> <strong>Utilisateur : </strong>Sophie <br> <strong>Evaluation : </strong>★★★★★</p>
        		</div>
        </div>
        <div class="testimonial">
        		<div class="parent-comment">
            <p>"kiNouGarde m'a aidé à trouver une nounou compétente et de confiance pour mes enfants. Je suis très satisfait de ce service." <br> <strong>Utilisateur : </strong>Thomas <br> <strong>Evaluation : </strong>★★★★☆</p>
        		</div>
        </div>
        <div class="testimonial">
        		<div class="parent-comment">
            <p>"Je suis reconnaissante envers kiNouGarde pour m'avoir aidé à trouver une solution de garde d'enfants flexible et abordable." <br> <strong>Utilisateur : </strong>Julie <br> <strong>Evaluation : </strong>★★★</p>
        		</div>
        </div>
        <div class="testimonial">
        		<div class="parent-comment">
            <p>"Merci kiNouGarde pour votre professionnalisme et votre soutien tout au long du processus de recherche de nounou." <br> <strong>Utilisateur : </strong>Nicolas <br> <strong>Evaluation : </strong>★★★</p>
        		</div>
        </div>
        		
    </section>
    
    <footer>
        <p>kiNouGarde est la plateforme idéale pour trouver la garde d'enfants dont vous avez besoin. Rejoignez-nous dès aujourd'hui !</p>
        <p>&copy; 2024 kiNouGarde. Projet étudiant, à but non commercial.</p>
    </footer>

<script>
    function toggleMenu() {
        var menu = document.getElementById("menu");
        menu.classList.toggle("open");
    }
</script>
</body>
</html>
