<?php

ob_start(); // start buffer
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

if(!gh_est_authentifie()){
    header('Location: ../index.php');
    exit;
}

gh_aff_debut('Cuiteur | Abonnements', '../styles/cuiteur.css');

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
$sql = "SELECT eaIDAbonne as usID
        FROM users INNER JOIN estabonne ON usID=eaIDUser
        AND usID=$usID;";

$res = gh_bd_send_request($db, $sql);

/*------------------------------------------------------------------------------
- Generating the html code for the page
------------------------------------------------------------------------------*/

gh_aff_entete(gh_html_proteger_sortie("Les abonnements de {$userStats['usPseudo']}"));
gh_aff_infos(true);

if (mysqli_num_rows($res) == 0){
    gh_aff_user_stats($userStats);
    echo '<li id="no_blabla">', gh_html_proteger_sortie($userStats['usPseudo']), ' n\'a aucun abonnement.</li>';
}
else{
    gh_aff_user_stats_list($res, $db, $userStats);
}

// libération des ressources
mysqli_free_result($res);
mysqli_close($db);

gh_aff_pied();
gh_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();
