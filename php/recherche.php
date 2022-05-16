<?php

ob_start(); // start output buffering
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

// if user is not authenticated, redirect to index.php
if (! gh_est_authentifie()){
    header('Location: ../index.php');
    exit;
}

/*------------------------------------------------------------------------------
- Do search if form is submitted
------------------------------------------------------------------------------*/
$row;
if(isset($_POST['recherche'])){
    $db = gh_bd_connect();
    $requete = "SELECT * FROM cuiteur WHERE usPseudo LIKE '%".$_POST['recherche']."%' OR usNom LIKE '%".$_POST['recherche']."%'";
    $request = gh_bd_proteger_entree($db, $request);
    $result = gh_bd_send_request($db, $requete);
}



/*------------------------------------------------------------------------------
- Generating the html code for the page
------------------------------------------------------------------------------*/

gh_aff_debut('Cuiteur | Recherche', '../styles/cuiteur.css');

gh_aff_entete('Rechercher des utilisateurs', true);
gh_aff_infos(true);

echo '<form  id="recherche" action="recherche.php" method="post">',
        '<table>',
            '<tr><td><input type=text name="recherche" size=30 maxlength=30></td>',
                '<td><input type=submit value="Rechercher"></td></tr>',
        '</table>',
    '</form>';
if(isset($_POST['recherche'])){
    echo '<ul>';
    echo '<h2 class="titleUnderline">Resultats de la recherche</h2>';
    while ($row = mysqli_fetch_assoc($result) != null) {
        $stat = gh_sql_get_user_stats($db, $row['usID']);;
    }
}
gh_aff_pied();
gh_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// free resources
mysqli_close($db);