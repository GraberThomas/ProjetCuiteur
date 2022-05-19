<?php

ob_start(); // start buffer
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

gh_aff_debut('Cuiteur | Mentions', '../styles/cuiteur.css');

// Les valeurs contenues dans $_POST et $_GET sont de type 'string'.
// Donc, si cette page est appelée avec l'URL blabla_4.php?id=4, la valeur de $_GET['id'] sera de type string
// => is_int($_GET['id']) renverra false
// => Il faut utiliser is_numeric() pour déterminer si la valeur de $_GET['id'] est une chaine numérique
// C'est ce qui est fait dans la fonction gh_est_entier()
if (count($_GET) > 2 || ! isset($_GET['id']) || ! gh_est_entier(($_GET['id'])) || $_GET['id'] <= 0){
    $usID = $_SESSION['usID'];
}
else {
    $usID = (int)$_GET['id'];
}

$db = gh_bd_connect();

$userStats = gh_sql_get_user_stats($db, $usID);
if (empty($userStats)){ // user not found, redirect to index
    header('Location: ../index.php');
}

/*------------------------------------------------------------------------------
- Get blablas of user $id
------------------------------------------------------------------------------*/
$nbToDisplay = isset($_GET['numberCuit']) && gh_est_entier($_GET['numberCuit']) &&  $_GET['numberCuit'] > 0 ? $_GET['numberCuit'] : NUMBER_CUIT_DISPLAY;
$nbToDisplay = (int) gh_bd_proteger_entree($db, $nbToDisplay);

$sql = "SELECT auteur.usID as autID, auteur.usNom as autNom, auteur.usPseudo as autPseudo, auteur.usAvecPhoto as autPhoto, 
                blDate, blTexte, blHeure, blID, blIDAutOrig as oriID, origin.usNom as oriNom, origin.usPseudo as oriPseudo, origin.usAvecPhoto as oriPhoto
        FROM (((users AS auteur LEFT OUTER JOIN blablas ON auteur.usID = blIDAuteur) 
        LEFT OUTER JOIN users AS origin ON blIDAutOrig = origin.usID) 
        INNER JOIN mentions ON blID = meIDBlabla)
        WHERE meIDUser = $usID
        ORDER BY blID DESC";

$res = gh_bd_send_request($db, $sql);

$nbRows = (int) mysqli_num_rows($res);

/*------------------------------------------------------------------------------
- Generating the html code for the page
------------------------------------------------------------------------------*/

gh_aff_entete(gh_html_proteger_sortie("Les mentions de {$userStats['usPseudo']}"));
gh_aff_infos(true);
gh_aff_user_stats($userStats);
echo '<article id="userInfo">
        <ul class="cardsList">';

if ($nbRows == 0){
    echo '<li id="no_blabla">', gh_html_proteger_sortie($userStats['usPseudo']), ' n\'a jamais été mentionné.</li>';
}
else{
    gh_aff_blablas($db, $res, $nbToDisplay);
    echo '<li class="plusBlablas">';
        if ($nbRows > $nbToDisplay){
            echo '<a href="blablas.php?id='.$usID.'&numberCuit=',$nbToDisplay+NUMBER_CUIT_DISPLAY,'"><strong>Plus de blablas</strong></a>',
                 '<img src="../images/speaker.png" width="75" height="82" alt="Image du speaker \'Plus de blablas\'">';
        }
    echo '</li>';
}

echo    '</ul>',
    '</article>';

// libération des ressources
mysqli_free_result($res);
mysqli_close($db);

gh_aff_pied();
gh_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();