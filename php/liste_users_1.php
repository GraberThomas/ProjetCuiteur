<?php
/** 1ère version : liste des utilisateurs */

ob_start(); //démarre la bufferisation

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

$bd = gh_bd_connect();

$sql = 'SELECT *
        FROM users
        ORDER BY usID';

$res = gh_bd_send_request($bd, $sql);

gh_aff_debut('Cuiteur | Utilisateurs');

echo '<h1>', 'Liste des utilisateurs de Cuiteur', '</h1>';

// Récupération des données et encapsulation dans du code HTML envoyé au navigateur 
while ($t = mysqli_fetch_assoc($res)) {
    echo '<h2> Utilisateur ', $t['usID'], '</h2>',
        '<ul>',
            '<li>Pseudo : ', gh_html_proteger_sortie($t['usPseudo']), '</li>',
            '<li>Nom : ', gh_html_proteger_sortie($t['usNom']), '</li>',
            '<li>Inscription : ', $t['usDateInscription'], '</li>',         // pas nécessaire de protéger les entiers
            '<li>Ville : ', gh_html_proteger_sortie($t['usVille']), '</li>',
            '<li>Web : ', gh_html_proteger_sortie($t['usWeb']), '</li>',
            '<li>Mail : ', gh_html_proteger_sortie($t['usMail']), '</li>',
            '<li>Naissance : ', $t['usDateNaissance'], '</li>',
            '<li>Bio : ', gh_html_proteger_sortie($t['usBio']), '</li>',
        '</ul>';
}

// libération des ressources
mysqli_free_result($res);
mysqli_close($bd);

gh_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();



?>
