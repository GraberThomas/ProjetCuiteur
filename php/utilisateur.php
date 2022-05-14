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

$db = gh_bd_connect();

/*------------------------------------------------------------------------------
- Get user's stats and info (current user if id is not set or invalid)
------------------------------------------------------------------------------*/
$id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['usID'];

if (isset($_GET['id']) && (! gh_est_entier(($_GET['id'])) || $_GET['id'] <= 0)){
    $id = $_SESSION['usID'];
}

$id = gh_bd_proteger_entree($db, $id);

$userStats = gh_sql_get_user_stats($db, $id);
$userData = gh_sql_get_user_info($db, $id);

// if user is not found, get current user's data
if (! $userStats){
    $userStats = gh_sql_get_user_stats($db, $_SESSION['usID']);
    $userData = gh_sql_get_user_info($db, $_SESSION['usID']);
}

/*------------------------------------------------------------------------------
- Generating the html code for the page
------------------------------------------------------------------------------*/
gh_aff_debut('Cuiteur | Profil de '. $userStats['usPseudo'], '../styles/cuiteur.css');

gh_aff_entete('Le profil de '. $userStats['usPseudo']);
gh_aff_infos(true);

gh_aff_user_stats($userStats);
gh_aff_user_info($userData);

gh_aff_pied();
gh_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// free resources
mysqli_close($db);

// ----------  Local functions ----------- //

    /**
     * Show user's info
     *
     * @param array $userData User's data
     */
    function gh_aff_user_info(array $userData){
        echo '<article id="userInfo">',
                '<table>',
                    '<tr>',
                        '<td>Date de naissance :</td>',
                        '<td>'. gh_amj_clair($userData['usDateNaissance']) .'</td>',
                    '</tr>',
                    '<tr>',
                        '<td>Date d\'inscription :</td>',
                        '<td>'. gh_amj_clair($userData['usDateInscription']) .'</td>',
                    '</tr>',
                    '<tr>',
                        '<td>Vile de résidence :</td>',
                        '<td>'. $userData['usVille'] .'</td>',
                    '</tr>',
                    '<tr>',
                        '<td>Mini-bio :</td>',
                        '<td>'. $userData['usBio'] .'</td>',
                    '</tr>',
                    '<tr>',
                        '<td>Site web :</td>',
                        '<td>'. $userData['usWeb'] .'</td>',
                    '</tr>',
                '</table>',
            '</article>';
    }