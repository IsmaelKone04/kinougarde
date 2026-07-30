<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil — kiNouGarde</title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/accueil.css">
    <script src="assets/js/menu.js" defer></script>
</head>
<body>
<header>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu">☰ Menu</button>
</header>
<h1>Bienvenue sur kiNouGarde</h1>

<!-- Des liens, plus des <select onchange="location = this.value">. Les deux
     listes déroulantes d'origine n'étaient pas navigables au clavier, et celle
     de la connexion proposait deux entrées menant à la même page. -->
<nav class="menu" id="menu">
    <ul>
        <li><a href="inscription-parents.php">Inscription parent</a></li>
        <li><a href="inscription-nounou.php">Inscription nounou</a></li>
        <li><a href="login.php">Se connecter</a></li>
    </ul>
</nav>


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
</body>
</html>
