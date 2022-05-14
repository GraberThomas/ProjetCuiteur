<?php
    ob_start(); //démarre la bufferisation
    session_start();
    
    require_once 'bibli_generale.php';
    require_once 'bibli_cuiteur.php';

    if(!gh_est_authentifie()){
        header('Location: ../index.php');
        exit;
    }

    $db = gh_bd_connect();

    /*--------------------------------------------------------------------------------------------
    - Handle new message publication (if any)
    ---------------------------------------------------------------------------------------------*/
    $er = isset($_POST['btnPublish']) ? gh_handle_message_publication($db) : array();

    /*--------------------------------------------------------------------------------------------
    - Send query to get user's feed and another to get total number of posts to show in the feed
    ---------------------------------------------------------------------------------------------*/
    $nbToDisplay = isset($_GET['numberCuit']) && gh_est_entier($_GET['numberCuit']) &&  $_GET['numberCuit'] > 0 ? $_GET['numberCuit'] : NUMBER_CUIT_DISPLAY;
    $nbToDisplay = (int) gh_bd_proteger_entree($db, $nbToDisplay);
    $usID = gh_bd_proteger_entree($db, $_SESSION['usID']);
    $sqlBlablas = "SELECT DISTINCT auteur.usID AS autID, auteur.usPseudo AS autPseudo, auteur.usNom AS autNom, auteur.usAvecPhoto AS autPhoto, 
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
                    ORDER BY blID DESC";

    $nbRows = (int) mysqli_num_rows(gh_bd_send_request($db, $sqlBlablas));

    $res = gh_bd_send_request($db, $sqlBlablas);


    /*-----------------------------------------------------------------------------
    - Generate HTML page
    ------------------------------------------------------------------------------*/
    gh_aff_debut('Cuiteur', '../styles/cuiteur.css');
    if(count($er) > 0){
        gh_aff_entete(null, true, $_POST['txtMessage']);
    }else{
        gh_aff_entete(null, true);
    }
    gh_aff_infos(true);
    echo '<ul>';

    if (count($er) > 0) {
        echo '<p class="error" id="error_input_cuit">Les erreurs suivantes ont été détectées :';
        foreach ($er as $v) {
            echo '<br> - ', $v;
        }
        echo '</p><br>';    
    }
    
    if ($nbRows == 0){
        echo '<li>Votre fil de blablas est vide</li>';
    }
    else{
        gh_aff_blablas($db, $res, $nbToDisplay);
        echo '<li class="plusBlablas">';
            if ($nbRows > $nbToDisplay){
                    echo '<a href="cuiteur.php?numberCuit=',$nbToDisplay+NUMBER_CUIT_DISPLAY,'"><strong>Plus de blablas</strong></a>',
                        '<img src="../images/speaker.png" width="75" height="82" alt="Image du speaker \'Plus de blablas\'">';
            }
        echo '</li>';
    }
    echo '</ul>';
    gh_aff_pied();
    gh_aff_fin();
    ob_end_flush();

    // libération des ressources
    mysqli_free_result($res);
    mysqli_close($db);


    // ----------  Local functions ----------- //

    /**
     * Handle new message publication
     *
     * @param  mysqli $db database connection
     * @global $_POST array containing form data
     * @return array containing error messages
     */
    function gh_handle_message_publication(mysqli $db): array {
        $message = gh_bd_proteger_entree($db, $_POST['txtMessage']);

        $er = array();
        if (empty($message)){
            $er[] = 'Vous n\'avez pas écrit de message';
            return $er;
        }

        // check for mentions
        $mentions = array();
        $regex = '/@([a-zA-Z0-9_]+)/';
        preg_match_all($regex, $message, $mentions);
        $mentions = array_unique($mentions[1]);

        foreach ($mentions as $i) {
            $sqlRequest = "SELECT * FROM users WHERE usPseudo='".gh_bd_proteger_entree($db, $i). "'";
            $R = gh_bd_send_request($db, $sqlRequest);
            if(mysqli_num_rows($R) == 0){
                $er[] = 'Le pseudo '.$i.' n\'existe pas';
            }
        }
        // check for tags
        $tags = array();
        $regex = '/#([a-zA-Z0-9_]+)/';
        preg_match_all($regex, $message, $tags);
        $tags = array_unique($tags[1]);

        if (count($er) > 0){
            return $er;
        }

        // get current date and time
        $date = date('Ymd');
        $heure = date('H:i:s');

        // insert message
        $sqlInsertMessage = "INSERT
                             INTO blablas
                             VALUES (NULL, '$_SESSION[usID]', '$date', '$heure', '$message', NULL)";
        gh_bd_send_request($db, $sqlInsertMessage);

        // get message ID   
        $messageId = (int) mysqli_insert_id($db);

        // insert mentions
        foreach ($mentions as $mention){
            $sqlGetUserId = "SELECT usID FROM users WHERE usPseudo = '$mention'";
            $res = gh_bd_send_request($db, $sqlGetUserId);
            $userId = (int) mysqli_fetch_assoc($res)['usID'];

            $sqlInsertMention = "INSERT
                                 INTO mentions
                                 VALUES ($userId, $messageId)";
            gh_bd_send_request($db, $sqlInsertMention);
        }

        // insert tags
        foreach ($tags as $tag){
            $sqlInsertTag = "INSERT
                             INTO tags
                             VALUES ('$tag', $messageId)";
            gh_bd_send_request($db, $sqlInsertTag);
        }

        return $er;
    }