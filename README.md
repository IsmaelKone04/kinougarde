# kiNouGarde — plateforme de mise en relation parents / nounous

Application web permettant à des parents de trouver une garde d'enfants : profils de
nounous consultables, fiches enfants (âge, allergies, besoins spécifiques), messagerie
interne entre les deux parties, et suivi des contrats.

> PHP 8 · MySQL · HTML/CSS · JavaScript — sans framework

---

## 📌 Contexte

Projet d'école réalisé en **avril 2024**, en PHP « nu » : pas de framework, pas de
moteur de templates. L'objectif du module était de comprendre ce que Laravel ou
Symfony font à votre place — routage, requêtes préparées, sessions, hachage des mots
de passe — en l'écrivant soi-même. L'application elle-même n'a toujours aucune
dépendance ; Composer n'intervient que côté développement, pour les tests
automatisés (voir [🧪 Tests](#-tests)).

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

Lancer le serveur intégré de PHP. **`-t public`** est important : il désigne
`public/` comme racine web, de sorte que `src/`, `sql/` et le `.env` restent
hors de portée d'une URL.

```bash
php -S localhost:8000 -t public
```

Puis ouvrir <http://localhost:8000/>.

### Avec Docker

Alternative sans installer PHP/MySQL en local :

```bash
docker compose up --build
```

Charge automatiquement le schéma **et** les données de démonstration au
premier démarrage (volume MySQL vide), puis sert l'application sur
<http://127.0.0.1:8010/>. `docker compose down -v` repart de zéro.

## 🧪 Tests

Tests PHPUnit sur les fonctions les plus sensibles : contrôle d'accès aux fiches
enfants (régression sur la faille IDOR corrigée en juillet 2026), anti-bruteforce,
réinitialisation de mot de passe, validation des téléversements, avatars.

Nécessite une base **séparée** de celle de développement, jamais réutilisée : les
tests vident leurs tables avant chaque exécution.

```bash
composer install

mysql -u root -p -e "CREATE DATABASE kinougarde_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p kinougarde_test < sql/schema.sql

composer test
```

Exécutés automatiquement à chaque push par GitHub Actions
(`.github/workflows/tests.yml`), contre un vrai MySQL de service — pas de
mock de la base.

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
| `index.php` | Page d'accueil publique |
| `inscription-parents.php` · `inscription-nounou.php` | Création de compte |
| `login.php` · `logout.php` | Authentification |
| `dashboard-parent.php` | Informations du parent et fiches de ses enfants |
| `dashboard-nounou.php` | Contrats, montant dû, messages reçus |
| `mon-enfant.php` · `modifier-enfant.php` | Gestion des fiches enfants |
| `liste-nounous.php` · `profil-nounou.php` | Recherche de nounou |
| `messages.php?avec=<id>` | Conversation entre deux comptes |

## 🧱 Structure

```
public/                    # racine web — le seul dossier exposé par le serveur
  index.php                # accueil
  login.php  logout.php
  inscription-parents.php  inscription-nounou.php
  dashboard-parent.php     dashboard-nounou.php
  mon-enfant.php           modifier-enfant.php
  liste-nounous.php        profil-nounou.php
  messages.php
  assets/
    css/                   # base.css + une feuille par page
    js/                    # menu.js, liste-nounous.js
src/                       # hors racine web
  bootstrap.php            # amorçage : charge les trois fichiers ci-dessous
  config.php               # connexion PDO, lecture du .env
  auth.php                 # session, contrôle d'accès, échappement, jeton CSRF
  functions.php            # requêtes métier (profils, enfants, contrats, messages)
sql/
  schema.sql               # structure des tables
  seed.sql                 # jeu de démonstration fictif
.env                       # identifiants MySQL — ignoré par git
```

Tout partait de la racine : les pages, les feuilles de style, la connexion à la
base et le dump SQL, dans un seul dossier de trente fichiers. Le découpage
`public/` · `src/` n'est pas qu'une affaire de rangement : avec `-t public`,
une URL ne peut plus désigner `config.php`, `.env` ni `sql/`. Si le serveur
cessait un jour d'interpréter le PHP — mauvaise configuration, module désactivé
— `config.php` serait renvoyé en texte brut, mot de passe de la base compris.
Hors racine web, il n'a pas d'URL du tout.

Chaque page commence donc par une seule ligne :

```php
require_once __DIR__ . '/../src/bootstrap.php';
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

### 9. Le bouton ☰ du tableau de bord nounou n'ouvrait rien

La page affichait **deux menus identiques** : un `<nav>` visible et un
`<div class="menu">` que sa feuille de style posait en `display: none`. Le
bouton basculait bien la classe `.open`, mais aucune règle ne repassait
l'élément en `block` : il restait masqué. Les deux menus n'en font plus qu'un.

Dans le même esprit, la fonction `toggleMenu()` était recopiée à l'identique
dans trois pages, appelée par un `onclick` en attribut. Elle vit maintenant
dans `assets/js/menu.js`, écrite une fois — et referme le menu avec Échap.

### 10. Des feuilles de style qui décrivaient une page inexistante

`message.css` stylait `#chat-box`, `.message .sender`, `.message .date` : le
balisage de l'interface AJAX qui n'a jamais été terminée. Aucun de ces
sélecteurs ne correspondait à ce que la page produisait réellement.

Symétriquement, quatre feuilles chargeaient un fond `url('style/pexel (2).jpg')`
— une image absente du dépôt. Et la classe `erreur`, émise par six pages, n'était
définie nulle part : les messages d'erreur s'affichaient en texte ordinaire.

## 🛡️ Ce qui a été ajouté

- **Jeton CSRF** sur tous les formulaires. Sans lui, un formulaire hébergé ailleurs
  peut poster ici en réutilisant le cookie de session du visiteur.
- **`session_regenerate_id()`** à la connexion, contre la fixation de session.
- Cookies de session en `HttpOnly` et `SameSite=Lax`.
- **Message d'erreur unique** à la connexion : distinguer « aucun compte » de « mot de
  passe incorrect » revient à confirmer, adresse par adresse, qui est inscrit.
- **Transactions** à l'inscription : compte et profil sont créés ensemble ou pas du tout.
- Contrainte d'**unicité sur l'e-mail**, absente du schéma d'origine.
- **Séparation `public/` · `src/`** : le code sensible n'est plus atteignable
  par une URL (voir [Structure](#-structure)).

## 🎨 Refonte de l'interface et durcissement (septembre 2026)

Le projet fonctionnait mais l'interface d'origine mélangeait douze feuilles CSS
sans système commun (couleurs et polices différentes d'une page à l'autre),
n'affichait jamais la photo d'une nounou (le champ existait, aucun formulaire
ne permettait de la téléverser) et la connexion n'avait aucune limite de
tentatives. Repris avec un vrai audit sécurité + UI/UX :

- **Système de design commun** (`assets/css/base.css`) : palette, typographie,
  boutons, cartes, avatars (photo réelle ou initiales colorées en repli).
- **Sidebar** de navigation sur toutes les pages connectées (tiroir en mobile,
  fixe en desktop), remplaçant un menu hamburger incohérent d'une page à l'autre.
- **Téléversement de photo de profil** pour les nounous, avec validation réelle
  du type d'image (`getimagesize()`, pas la seule extension déclarée) et nom de
  fichier régénéré aléatoirement.
- **Anti-bruteforce sur la connexion** : blocage après 5 échecs / 15 min par
  e-mail (table `tentatives_connexion`).
- **Réinitialisation de mot de passe** par jeton à usage unique, valable 30 min,
  empreinte SHA-256 stockée plutôt que le jeton lui-même. Aucun serveur SMTP
  configuré sur ce projet : le lien est affiché à l'écran plutôt qu'envoyé par
  e-mail (voir `mot-de-passe-oublie.php`) — en production, il faudrait le
  remplacer par un envoi réel.
- **Bornage serveur** de tous les champs texte (longueur) et numériques
  (nombre d'enfants, tarif) : le HTML seul ne protégeait pas contre un POST
  direct hors limites.
- **En-têtes de sécurité** (`X-Frame-Options`, `X-Content-Type-Options`,
  `Referrer-Policy`, `Strict-Transport-Security` si servi en HTTPS) et dossier
  `uploads/` protégé contre la liste de son contenu.

### Vrai flux de contrat, avis et messagerie restreinte

- **Cycle de vie complet d'un contrat** : `en_attente` → `acceptee`/`refusee`
  (choix de la nounou) → `terminee` (choix du parent), au lieu de lignes
  figées uniquement présentes dans les données de démonstration.
- **Avis et notes** (`avis`) : un avis par contrat, possible uniquement une
  fois le contrat `terminee` et jamais deux fois — note moyenne affichée sur
  la liste et la fiche de chaque nounou.
- **Recherche et filtres** (ville, service, tarif maximum) sur la liste des
  nounous, en GET pour rester partageable.
- **Modification de son propre profil** pour une nounou (ville, service,
  horaires, tarif, photo).
- **Messagerie restreinte** aux échanges parent ↔ nounou (un parent ne
  pouvait plus écrire à un autre parent).
- **Badge de messages non lus** dans la sidebar : ce projet n'a aucun serveur
  SMTP configuré (voir ci-dessus), donc pas de notification par e-mail
  possible à la réception d'un message ; ce badge en tient lieu.

### Mode sombre, pagination, export et badge « vérifiée »

- **Mode sombre** : suit la préférence système (`prefers-color-scheme`) ou un
  choix explicite via le bouton de la sidebar, mémorisé dans `localStorage`.
- **Pagination** de la liste des nounous (9 par page).
- **Export CSV** de ses propres contrats, pour un parent comme pour une
  nounou (`export-contrats.php`).
- **Badge « Vérifiée »** (`nounous.verifiee`) affiché sur la liste et la
  fiche d'une nounou. Il n'existe pas encore de back-office admin pour
  l'activer : c'est aujourd'hui une bascule manuelle en base de données, pas
  un vrai processus de vérification d'identité.

## ⚠️ Limites connues

- **Jamais mis en service.** Aucun utilisateur réel, aucune donnée réelle.
- **Pas de framework, donc pas de routeur** : les URL sont des noms de fichiers.
- **Réinitialisation de mot de passe sans e-mail réel** (voir ci-dessus) : correct
  pour une démo locale, à corriger avant toute mise en production.
- **Pas de vraie vérification d'identité des nounous** : le badge « Vérifiée »
  existe (voir ci-dessus) mais se bascule à la main en base, faute d'un
  back-office admin — ce qu'exigerait une vraie plateforme de garde d'enfants.
- La messagerie recharge la page à chaque envoi (le sondage AJAX d'origine a été retiré
  avec le code qu'il appelait).

## 📄 Licence

[MIT](LICENSE)

## 👤 Auteur

**Cheick Ismaël Koné** — [@IsmaelKone04](https://github.com/IsmaelKone04)
