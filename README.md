# kiNouGarde — plateforme de mise en relation parents / nounous

Application web permettant à des parents de trouver une garde d'enfants : profils de
nounous consultables, fiches enfants (âge, allergies, besoins spécifiques), messagerie
interne entre les deux parties, et suivi des contrats.

> PHP 8 · MySQL · HTML/CSS · JavaScript — sans framework

---

## 📌 Contexte

Projet d'école réalisé en **avril 2024**, en PHP « nu » : pas de framework, pas de
Composer, pas de moteur de templates. L'objectif du module était de comprendre ce que
Laravel ou Symfony font à votre place — routage, requêtes préparées, sessions,
hachage des mots de passe — en l'écrivant soi-même.

Le dépôt a été **repris et corrigé en juillet 2026** avant publication. La section
[Ce que la relecture a révélé](#-ce-que-la-relecture-a-révélé) détaille ce qui n'allait
pas ; c'est probablement la partie la plus utile de ce README.

## 🚀 Démarrage

Prérequis : **PHP 8.0+**, **MySQL 5.7+** (XAMPP, WAMP ou Laragon font l'affaire).

```bash
git clone https://github.com/IsmaelKone04/kinougarde.git
cd kinougarde

cp .env.example .env      # puis renseigner DB_USER / DB_PASS
```

Créer la base et charger les données de démonstration :

```bash
mysql -u root -p -e "CREATE DATABASE kinougarde CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p kinougarde < sql/schema.sql
mysql -u root -p kinougarde < sql/seed.sql
```

Lancer le serveur intégré de PHP :

```bash
php -S localhost:8000
```

Puis ouvrir <http://localhost:8000/home.php>.

### Comptes de démonstration

Mot de passe des cinq comptes : **`Demo1234!`**

| Adresse | Rôle |
|---------|------|
| `aicha.parent@example.com` | Parent (2 enfants) |
| `serge.parent@example.com` | Parent (1 enfant) |
| `adjoua.nounou@example.com` | Nounou |
| `fatou.nounou@example.com` | Nounou |
| `moussa.nounou@example.com` | Nounou |

Ces comptes sont **entièrement fictifs**. Le dump d'origine contenait de vrais comptes
de test — adresses personnelles, numéros de téléphone, mots de passe en clair — il n'a
pas été republié.

## 🧭 Parcours

| Page | Rôle |
|------|------|
| `home.php` | Page d'accueil publique |
| `inscription-parents.php` · `inscription-nounou.php` | Création de compte |
| `login.php` · `logout.php` | Authentification |
| `dashboard_parent.php` | Informations du parent et fiches de ses enfants |
| `dashboard_nounou.php` | Contrats, montant dû, messages reçus |
| `mon-enfant.php` · `modifier-enfant.php` | Gestion des fiches enfants |
| `liste_nounous.php` · `profil-nounou.php` | Recherche de nounou |
| `messages.php?avec=<id>` | Conversation entre deux comptes |

## 🧱 Structure

```
config.php     # connexion PDO, lecture du .env
auth.php       # session, contrôle d'accès, échappement, jeton CSRF
functions.php  # requêtes métier (profils, enfants, contrats, messages)
sql/
  schema.sql   # structure des tables
  seed.sql     # jeu de démonstration fictif
*.php          # les pages
*.css          # une feuille de style par page
```

## 🔍 Ce que la relecture a révélé

Le projet rendu à l'école *paraissait* fonctionner. Il ne fonctionnait pas, et il était
dangereux. Les défauts, du plus grave au plus anecdotique :

### 1. N'importe quel parent pouvait lire et modifier la fiche d'un autre enfant

`modifier-enfant.php` chargeait et mettait à jour `enfants WHERE id = $_GET['id']`
**sans vérifier que l'enfant appartenait au parent connecté**. Incrémenter le chiffre
dans l'URL donnait accès au nom, à l'âge, aux allergies et aux besoins spécifiques de
l'enfant d'une autre famille. Sur une plateforme de garde d'enfants, c'est la faille
qui aurait coulé le projet.

Le contrôle passe maintenant par une seule fonction, et le `parent_id` est répété
jusque dans la clause `WHERE` de l'`UPDATE`.

### 2. La moitié des mots de passe n'était pas hachée

L'inscription des parents appelait `password_hash()`. Celle des nounous insérait
`$_POST['mot_de_passe']` tel quel. Et `login.php` s'en accommodait :

```php
// nounous
if ($mot_de_passe === $row_nounou['mot_de_passe']) { … }
// parents
if (password_verify($mot_de_passe, $row_parent['mot_de_passe'])) { … }
```

Une seule vérification désormais, `password_verify()`, pour tout le monde — avec
`password_needs_rehash()` pour faire évoluer le coût sans casser les comptes existants.

### 3. Deux injections SQL

`profil-nounou.php` concaténait `$_GET['id']` dans la requête, **sans exiger de
connexion** : la table `nounous` — colonne mot de passe comprise — était lisible par
un `UNION SELECT`. `repondre_message.php` faisait de même avec `$_POST`.

Le reste du projet utilisait pourtant des requêtes préparées : ce sont deux fichiers
écrits à la hâte qui ont introduit le trou. C'est justement pour ça que la règle doit
être absolue plutôt qu'appliquée au cas par cas.

### 4. `htmlspecialchars()` pris pour une protection contre les injections SQL

```php
// Fonction pour protéger contre les injections SQL
function secure_input($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}
```

Le commentaire est faux : `htmlspecialchars()` échappe le HTML, à l'affichage. Contre
SQL, ce sont les requêtes préparées. Les deux protections existent, elles ne se
remplacent pas — et je les confondais.

Aucune donnée n'était échappée à l'affichage, d'ailleurs : un message contenant
`<script>` s'exécutait chez son destinataire.

### 5. La messagerie ne pouvait pas fonctionner

Trois causes cumulées :

- **trois clés de session différentes** selon les fichiers — `$_SESSION['email']`,
  `$_SESSION['id']`, `$_SESSION['user_id']` — dont deux n'étaient jamais écrites ;
- **deux tables** pour la même chose, `messages` et `messsages` (trois s), avec des
  colonnes différentes (`message` / `content`) ;
- l'appel AJAX pointait vers `message.php`, un fichier inexistant.

### 6. Des liens vers des fichiers qui n'existent pas

`logout.php` renvoyait vers `login_parents.php`, `modifier-enfant.php` vers
`mon-enfants.php` (au pluriel), plusieurs menus vers `home.html` ou `messages.html`
en `.html`. Se déconnecter menait à une page 404.

### 7. Les identifiants MySQL recopiés dans dix fichiers

`root` / mot de passe vide, réécrits à l'identique dans chaque page. Ils sont
désormais lus une seule fois, depuis un `.env` ignoré par git.

### 8. `echo` avant `header('Location: …')`

Plusieurs pages affichaient « Inscription réussie ! » **avant** de rediriger. En PHP,
tout affichage envoie les en-têtes : la redirection qui suit est ignorée et
un avertissement s'affiche.

## 🛡️ Ce qui a été ajouté

- **Jeton CSRF** sur tous les formulaires. Sans lui, un formulaire hébergé ailleurs
  peut poster ici en réutilisant le cookie de session du visiteur.
- **`session_regenerate_id()`** à la connexion, contre la fixation de session.
- Cookies de session en `HttpOnly` et `SameSite=Lax`.
- **Message d'erreur unique** à la connexion : distinguer « aucun compte » de « mot de
  passe incorrect » revient à confirmer, adresse par adresse, qui est inscrit.
- **Transactions** à l'inscription : compte et profil sont créés ensemble ou pas du tout.
- Contrainte d'**unicité sur l'e-mail**, absente du schéma d'origine.

## ⚠️ Limites connues

- **Jamais mis en service.** Aucun utilisateur réel, aucune donnée réelle.
- **Pas de framework, donc pas de routeur** : les URL sont des noms de fichiers.
- **Pas de tests automatisés.**
- **Pas de téléversement de photo** : le champ `photo_profil` existe, le formulaire non.
- **Pas de réinitialisation de mot de passe**, pas de vérification d'e-mail.
- **Aucune vérification d'identité des nounous**, ce qu'exigerait une vraie plateforme
  de garde d'enfants.
- La messagerie recharge la page à chaque envoi (le sondage AJAX d'origine a été retiré
  avec le code qu'il appelait).

## 📄 Licence

[MIT](LICENSE)

## 👤 Auteur

**Cheick Ismaël Koné** — [@IsmaelKone04](https://github.com/IsmaelKone04)
