<?php

ob_start(); //démarre la bufferisation

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

$bd = gh_bd_connect();

$sql = 'SELECT  auteur.usID AS autID, auteur.usPseudo AS autPseudo, auteur.usNom AS autNom, auteur.usAvecPhoto AS autPhoto, 
                blTexte, blDate, blHeure,
                origin.usID AS oriID, origin.usPseudo AS oriPseudo, origin.usNom AS oriNom, origin.usAvecPhoto AS oriPhoto
        FROM (users AS auteur
        INNER JOIN blablas ON blIDAuteur = usID)
        LEFT OUTER JOIN users AS origin ON origin.usID = blIDAutOrig
        WHERE auteur.usID = 2
        ORDER BY blID DESC';

$res = gh_bd_send_request($bd, $sql);

gh_aff_debut('Cuiteur | Blablas', '../styles/cuiteur.css');

// Récupération des données et encapsulation dans du code HTML envoyé au navigateur
$i = 0;
while ($t = mysqli_fetch_assoc($res)) {
    if ($i == 0){
        gh_aff_entete(gh_html_proteger_sortie("Les blablas de {$t['autPseudo']}"));
        gh_aff_infos();
        echo '<ul>';
    }
    if ($t['oriID'] === null){
        $id_orig = $t['autID'];
        $pseudo_orig = $t['autPseudo'];
        $photo = $t['autPhoto'];
        $nom_orig = $t['autNom'];
    }
    else{
        $id_orig = $t['oriID'];
        $pseudo_orig = $t['oriPseudo'];
        $photo = $t['oriPhoto'];
        $nom_orig = $t['oriNom'];
    }
    echo    '<li>', 
                '<img src="../', ($photo == 1 ? "upload/$id_orig.jpg" : 'images/anonyme.jpg'), 
                '" class="imgAuteur" alt="photo de l\'auteur">',
                gh_html_a('utilisateur.php','<strong>'.gh_html_proteger_sortie($pseudo_orig).'</strong>','id', $id_orig, 'Voir mes infos'), 
                ' ', gh_html_proteger_sortie($nom_orig),
                ($t['oriID'] !== null ? ', recuité par '
                                        .gh_html_a( 'utilisateur.php','<strong>'.gh_html_proteger_sortie($t['autPseudo']).'</strong>',
                                                    'id', $t['autID'], 'Voir mes infos') : ''),
                '<br>',
                gh_html_proteger_sortie($t['blTexte']),
                '<p class="finMessage">',
                gh_amj_clair($t['blDate']), ' à ', gh_heure_clair($t['blHeure']),
                '<a href="../index.php">Répondre</a> <a href="../index.php">Recuiter</a></p>',
            '</li>';
    ++$i;
}

echo '</ul>';

// libération des ressources
mysqli_free_result($res);
mysqli_close($bd);

gh_aff_pied();
gh_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();



?>
