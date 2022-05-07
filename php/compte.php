<?php

ob_start(); // start output buffering
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

// if user is not authenticated, redirect to index.php
if (! em_est_authentifie()){
    header('Location: ../index.php');
    exit;
}

$db = em_bd_connect();

/*------------------------------------------------------------------------------
- Retrieve the user data from the database
------------------------------------------------------------------------------*/
$sqlUserData = 'SELECT usNom, usDateNaissance, usVille, usBio, usMail, usWeb, usPasse, usAvecPhoto
                FROM users
                WHERE usID = ' . $_SESSION['usID'];

$userData = em_bd_send_request($db, $sqlUserData);

/*------------------------------------------------------------------------------
- Generating the html code for the page
------------------------------------------------------------------------------*/

em_aff_debut('Cuiteur | Compte', '../styles/cuiteur.css');

em_aff_entete('Paramètres de mon compte', true);
em_aff_infos(true);

echo '<p>Cette page vous permet de modifier les informations relatives à votre compte.</p>',
     '<br>',
     '<h2 class="titleForm">Informations personnelles</h2>',

gh_aff_formulaire_compte(array());

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();



// ----------  Local functions ----------- //

    /**
     * Show personal information form
     *
     * @param   array   $err    Array of errors to display
     * @global  array   $_POST
     */
    function gh_aff_formulaire_compte(array $err): void {
        // If there are errors, display form with sent values
        // Else, retrieve the values from database and display form with them
        if (isset($_POST['btnModifyPersonalInfo'])) {
            $values = em_html_proteger_sortie($_POST);
        }
        else {
            $values = mysqli_fetch_assoc($GLOBALS['userData']);
        }

        echo '<form method="post" action="compte.php">',
                '<table>';

        em_aff_ligne_input('Nom et prénom :', array('type' => 'text', 'name' => 'usNom', 'value' => $values['usNom'], 'required' => null));
        em_aff_ligne_input('Date de naissance :', array('type' => 'date', 'name' => 'usDateNaissance', 'value' => $values['usDateNaissance'], 'required' => null));
        em_aff_ligne_input('Ville :', array('type' => 'text', 'name' => 'usVille', 'value' => $values['usVille'], 'required' => null));
                echo '<tr>',
                        '<td>',
                            '<label for="usBio">Mini-bio :</label>',
                        '</td>',
                        '<td>',
                            '<textarea name="usBio" id="usBio" cols="30" rows="15">', $values['usBio'], '</textarea>',
                        '</td>',
                    '</tr>',
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnModifyPersonalInfo" value="Valider">',
                        '</td>',
                    '</tr>',
                '</table>',
            '<form>';
    }