<?php
    ob_start(); //démarre la bufferisation
    session_start();
    
    require_once 'bibli_generale.php';
    require_once 'bibli_cuiteur.php';

    if(!gh_est_authentifie()){
        header('Location: ../index.php');
        exit;
    }

    /*-----------------------------------------------------------------------------
    - Generate HTML page
    ------------------------------------------------------------------------------*/
    $nb = isset($_GET['numberCuit']) && gh_est_entier($_GET['numberCuit']) &&  $_GET['numberCuit'] > 0 ? $_GET['numberCuit'] : NUMBER_CUIT_DISPLAY;
    $db = gh_bd_connect();
    $usID = gh_bd_proteger_entree($db, $_SESSION['usID']);
    $sql = "SELECT  DISTINCT auteur.usID AS autID, auteur.usPseudo AS autPseudo, auteur.usNom AS autNom, auteur.usAvecPhoto AS autPhoto, 
                    blTexte, blDate, blHeure,
                    origin.usID AS oriID, origin.usPseudo AS oriPseudo, origin.usNom AS oriNom, origin.usAvecPhoto AS oriPhoto
            FROM (((users AS auteur
            INNER JOIN blablas ON blIDAuteur = usID)
            LEFT OUTER JOIN users AS origin ON origin.usID = blIDAutOrig)
            LEFT OUTER JOIN estabonne ON auteur.usID = eaIDAbonne)
            LEFT OUTER JOIN mentions ON blID = meIDBlabla
            WHERE   auteur.usID = $usID
            OR      eaIDUser = $usID
            OR      meIDUser = $usID
            ORDER BY blID DESC
            LIMIT $nb, $nb";
    $res = gh_bd_send_request($db, $sql);

    gh_aff_debut('Cuiteur', '../styles/cuiteur.css');
    gh_aff_entete();
    gh_aff_infos(true);
    echo '<ul>';
    if (mysqli_num_rows($res) == 0){
        echo '<li>Votre fil de blablas est vide</li>';
    }
    else{
        gh_aff_blablas($res);
        echo '<li class="plusBlablas">',
                '<a href="cuiteur.php?numberCuit=',$nb+NUMBER_CUIT_DISPLAY,'"><strong>Plus de blablas</strong></a>',
                '<img src="../images/speaker.png" width="75" height="82" alt="Image du speaker \'Plus de blablas\'">',
            '</li>';
    }
    echo '</ul>';
    gh_aff_pied();
    gh_aff_fin();
    ob_end_flush();

    // libération des ressources
    mysqli_free_result($res);
    mysqli_close($db);