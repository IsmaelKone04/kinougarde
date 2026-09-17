<?php
// Empêche la liste du contenu de ce dossier : sans ce fichier, un visiteur
// qui ouvre /uploads/ directement verrait la liste de toutes les photos de
// profil téléversées, y compris celles de nounous non affichées ailleurs.
http_response_code(403);
exit;
