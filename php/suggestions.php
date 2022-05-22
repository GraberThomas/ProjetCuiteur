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
$sql = "((SELECT DISTINCT usID
          FROM users INNER JOIN estabonne ON usID=eaIDAbonne
          WHERE eaIDUser IN (SELECT eaIDAbonne FROM estabonne WHERE eaIDuser=$_SESSION[usID])
          AND usID!=$_SESSION[usID]
          AND usID NOT IN (SELECT eaIDAbonne
                           FROM estabonne
                           WHERE eaIDUSer=$_SESSION[usID]))
          UNION
          (SELECT * FROM (SELECT eaIDAbonne
                          FROM estabonne
                          WHERE eaIDAbonne != $_SESSION[usID]
                          AND eaIDAbonne NOT IN (SELECT eaIDAbonne FROM estabonne WHERE eaIDUser = $_SESSION[usID])
                          GROUP BY eaIDAbonne
                          ORDER BY COUNT(*) DESC
                          LIMIT " . NB_MOST_POPULAR_USERS . "
                         ) as sz
          ))
          ORDER BY RAND()
          LIMIT " . NB_SUGGESTIONS . ";";
$res = gh_bd_send_request($db, $sql);

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
