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

/*------------------------------------------------------------------------------
- Generating the html code for the page
------------------------------------------------------------------------------*/
gh_aff_debut('Cuiteur | Tendances', '../styles/cuiteur.css');

if(count($_GET) != 1 || ! isset($_GET['hashtag'])){
    gh_aff_entete('');
    gh_aff_infos(true, $db);

    gh_aff_top_tags_today($db);
    gh_aff_top_tags_week($db);
    gh_aff_top_tags_month($db);
    gh_aff_top_tags_year($db);
}else{
    $hashtag = gh_html_proteger_sortie($_GET['hashtag']);

    gh_aff_entete($hashtag);
    gh_aff_infos(true, $db);
    gh_aff_selected_tendances($db, $hashtag);
}

// libération des ressources
mysqli_close($db);

gh_aff_pied();
gh_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();


// ----------  Local functions ----------- //
    /**
     * Show today's top tags
     *
     * @param  mysqli $db database connection
     */
    function gh_aff_top_tags_today($db) {
        $date = date('Ymd');
        $sql = 'SELECT taID, COUNT(*) AS nbTags
                FROM tags INNER JOIN blablas on taIDBlabla=blID
                WHERE blDate='.gh_bd_proteger_entree($db, $date).'
                GROUP BY taID
                ORDER BY COUNT(*) DESC, taID ASC
                LIMIT '. NB_TENDS_TO_DISPLAY.';';
        $res = gh_bd_send_request($db, $sql);

        echo '<h2 class="title_tendances">Top ', NB_TENDS_TO_DISPLAY, ' du jour</h2>';
        if (mysqli_num_rows($res) == 0){
            echo '<p>Aucune tendance ...</p>';
        }
        else {
            gh_aff_liste_tendances($res);
        }

        mysqli_free_result($res);
    }

    /**
     * Show week's top tags
     *
     * @param  mysqli $db database connection
     */
    function gh_aff_top_tags_week($db) {
        $date = date('Ymd',time()+( 1 - date('w'))*24*3600); // get monday of current week
        
        $sql = 'SELECT taID, COUNT(*) AS nbTags
                FROM tags INNER JOIN blablas on taIDBlabla=blID
                WHERE blDate>='.gh_bd_proteger_entree($db, $date).'
                GROUP BY taID
                ORDER BY COUNT(*) DESC, taID ASC
                LIMIT '. NB_TENDS_TO_DISPLAY.';';
        $res = gh_bd_send_request($db, $sql);

        echo '<h2 class="title_tendances">Top ', NB_TENDS_TO_DISPLAY, ' de la semaine</h2>';
        if (mysqli_num_rows($res) == 0){
            echo '<p>Aucune tendance ...</p>';
        }
        else {
            gh_aff_liste_tendances($res);
        }

        mysqli_free_result($res);
    }

    /**
     * Show month's top tags
     *
     * @param  mysqli $db database connection
     */
    function gh_aff_top_tags_month($db) {
        $date = date('Ym01'); // get first day of current month
        $sql = 'SELECT taID, COUNT(*) AS nbTags
                FROM tags INNER JOIN blablas on taIDBlabla=blID
                WHERE blDate>='.gh_bd_proteger_entree($db, $date).'
                GROUP BY taID
                ORDER BY COUNT(*) DESC, taID ASC
                LIMIT '. NB_TENDS_TO_DISPLAY.';';
        $res = gh_bd_send_request($db, $sql);

        echo '<h2 class="title_tendances">Top ', NB_TENDS_TO_DISPLAY, ' du mois</h2>';
        if (mysqli_num_rows($res) == 0){
            echo '<p>Aucune tendance ...</p>';
        }
        else {
            gh_aff_liste_tendances($res);
        }

        mysqli_free_result($res);
    }
    /**
     * Show year's top tags
     *
     * @param  mysqli $db database connection
     */
    function gh_aff_top_tags_year($db) {
        $date = date('Y0101'); // get first day of current month
        $sql = 'SELECT taID, COUNT(*) AS nbTags
                FROM tags INNER JOIN blablas on taIDBlabla=blID
                WHERE blDate>='.gh_bd_proteger_entree($db, $date).'
                GROUP BY taID
                ORDER BY COUNT(*) DESC, taID ASC
                LIMIT '. NB_TENDS_TO_DISPLAY.';';
        $res = gh_bd_send_request($db, $sql);
        echo '<div id=tend_year>',
                '<h2 class="title_tendances">Top ', NB_TENDS_TO_DISPLAY, ' de l\'année</h2>';
        if (mysqli_num_rows($res) == 0){
            echo '<p>Aucune tendance ...</p>';
        }
        else {
            gh_aff_liste_tendances($res);
        }
        echo '</div>';
        mysqli_free_result($res);
    }

    /**
     * Show the selected tendances
     *
     * @param  mysqli $db database connection
     * @param  string $hashtag hashtag to display
     */
    function gh_aff_selected_tendances(mysqli $db, String $hashtag){
        $sql = "SELECT  auteur.usID AS autID, auteur.usPseudo AS autPseudo, auteur.usNom AS autNom, auteur.usAvecPhoto AS autPhoto, 
                        blID, blTexte, blDate, blHeure,
                        origin.usID AS oriID, origin.usPseudo AS oriPseudo, origin.usNom AS oriNom, origin.usAvecPhoto AS oriPhoto
                FROM ((users AS auteur INNER JOIN blablas ON blIDAuteur = usID)
                LEFT OUTER JOIN users AS origin ON origin.usID = blIDAutOrig)
                INNER JOIN tags ON blID=taIDBlabla
                WHERE taID='".gh_bd_proteger_entree($db,$hashtag)."';";
        $res = gh_bd_send_request($db, $sql);
        if(mysqli_num_rows($res) == 0){
            echo '<p>Aucune tendance pour ce hashtag.</p>';
        }
        else {
            echo '<ul class="cardsList">';
            gh_aff_blablas($db, $res);
            echo '</ul>';
        }
        mysqli_free_result($res);
    }
