<?php

ob_start(); // start buffer
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

if(!gh_est_authentifie()){
    header('Location: ../index.php');
    exit;
}

$db = gh_bd_connect();

gh_aff_debut('Cuiteur | Suggestions', '../styles/cuiteur.css');

/*------------------------------------------------------------------------------
- Get suggestions for current user
------------------------------------------------------------------------------*/
$res = gh_sql_get_current_user_suggestions($db);

/*------------------------------------------------------------------------------
- Generating the html code for the page
------------------------------------------------------------------------------*/

gh_aff_entete("Suggestions");
gh_aff_infos(true, $db);

if (mysqli_num_rows($res) == 0){
    echo '<ul class="cardsList"><li id="no_blabla">Aucune suggestion disponible.</li></ul>';
}
else{
    gh_aff_user_stats_list($res, $db);
}

// libération des ressources
mysqli_free_result($res);
mysqli_close($db);

gh_aff_pied();
gh_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();
