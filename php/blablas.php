<?php

ob_start(); // start buffer
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

if(!gh_est_authentifie()){
    header('Location: ../index.php');
    exit;
}

gh_aff_debut('Cuiteur | Blablas', '../styles/cuiteur.css');

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

$sql = "SELECT  auteur.usID AS autID, auteur.usPseudo AS autPseudo, auteur.usNom AS autNom, auteur.usAvecPhoto AS autPhoto, 
                blID, blTexte, blDate, blHeure,
                origin.usID AS oriID, origin.usPseudo AS oriPseudo, origin.usNom AS oriNom, origin.usAvecPhoto AS oriPhoto
        FROM (users AS auteur
        INNER JOIN blablas ON blIDAuteur = usID)
        LEFT OUTER JOIN users AS origin ON origin.usID = blIDAutOrig
        WHERE auteur.usID = $usID
        ORDER BY blID DESC";

$res = gh_bd_send_request($db, $sql);

$nbRows = (int) mysqli_num_rows($res);

/*------------------------------------------------------------------------------
- Generating the html code for the page
------------------------------------------------------------------------------*/

gh_aff_entete("Les blablas de {$userStats['usPseudo']}");
gh_aff_infos(true);

echo  '<ul class="cardsList">',
        '<li class="noBackground">';
gh_aff_user_stats($userStats);
echo '</li>';

if ($nbRows == 0){
    echo '<li id="no_blabla">', $userStats['usPseudo'], ' n\'a pas posté(e) de blabla.</li>';
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

echo    '</ul>';

// libération des ressources
mysqli_free_result($res);
mysqli_close($db);

gh_aff_pied();
gh_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();
