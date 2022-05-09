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
- Check form submission
- If no error, retrieve user data from database and display it
------------------------------------------------------------------------------*/
$erPersonalInfo = isset($_POST['btnModifyPersonalInfo']) ? gh_traitement_infos_perso() : array();

if ($erPersonalInfo == array()) {
    $sqlUserData = 'SELECT usNom, usDateNaissance, usVille, usBio, usMail, usWeb, usPasse, usAvecPhoto
                    FROM users
                    WHERE usID = ' . $_SESSION['usID'];

    $userData = mysqli_fetch_assoc(em_bd_send_request($GLOBALS['db'], $sqlUserData));
}

/*------------------------------------------------------------------------------
- Generating the html code for the page
------------------------------------------------------------------------------*/

em_aff_debut('Cuiteur | Compte', '../styles/cuiteur.css');

em_aff_entete('Paramètres de mon compte', true);
em_aff_infos(true);

echo '<p>Cette page vous permet de modifier les informations relatives à votre compte.</p>',
     '<br>';

gh_aff_formulaire_infos_perso($erPersonalInfo);
gh_aff_formulaire_infos_compte_cuiteur(array());

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// free resources
mysqli_close($db);



// ----------  Local functions ----------- //

    /**
     * Show personal information form
     *
     * @param   array   $err    Array of errors to display
     * @global  array   $_POST
     */
    function gh_aff_formulaire_infos_perso(array $err): void {

        echo '<h2 class="titleForm">Informations personnelles</h2>';

        // If there are errors, display form with sent values
        // Else, retrieve the values from database and display form with them
        if (isset($_POST['btnModifyPersonalInfo'])) {
            $values = em_html_proteger_sortie($_POST);
        }
        else {
            $values = $GLOBALS['userData'];
        }

        if (count($err) > 0) {
            echo '<p class="error">Les erreurs suivantes ont été détectées :';
            foreach ($err as $v) {
                echo '<br> - ', $v;
            }
            echo '</p>';    
        }
        else if (isset($_POST['btnModifyPersonalInfo'])) {
            echo '<p class="success">La mise à jour des informations sur votre compte a bien été effectuée.</p>';    
        }

        // The date stored in the database is in sql format, we need to convert it for putting it into the date input field
        $values['usDateNaissance'] = gh_convert_date_to_input_format($values['usDateNaissance']);

        echo '<form method="post" action="compte.php">',
                '<table>';

        em_aff_ligne_input('Nom et prénom :', array('type' => 'text', 'name' => 'usNom', 'value' => $values['usNom'], 'required' => null));
        em_aff_ligne_input('Date de naissance :', array('type' => 'date', 'name' => 'usDateNaissance', 'value' => $values['usDateNaissance'], 'required' => null));
        em_aff_ligne_input('Ville :', array('type' => 'text', 'name' => 'usVille', 'value' => $values['usVille']));
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

    /**
 *  Handle the personal information form 
 *
 *      Step 1. Verify the data
 *                  -> return an array of errors if any
 *      Step 2. modify the data in the database
 *      Step 3. Show back the page with a success message
 *
 * Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage 
 * et donc entraînent l'appel de la fonction em_session_exit() sauf :
 * - les éventuelles suppressions des attributs required car l'attribut required est une nouveauté apparue dans la version HTML5 et 
 *   nous souhaitons que l'application fonctionne également correctement sur les vieux navigateurs qui ne supportent pas encore HTML5
 * - une éventuelle modification de l'input de type date en input de type text car c'est ce que font les navigateurs qui ne supportent 
 *   pas les input de type date
 *
 * @global array    $_POST
 *
 * @return array    associative array containing the errors if any
 */
function gh_traitement_infos_perso(): array {
    
    if( !em_parametres_controle('post', array('usNom', 'usDateNaissance', 'btnModifyPersonalInfo'), array('usVille', 'usBio'))) {
        em_session_exit();   
    }
    
    foreach($_POST as &$val){
        $val = trim($val);
    }
    
    $errors = array();
    
    // verify name
    if (mb_strlen($_POST['usNom'], 'UTF-8') > LMAX_NOMPRENOM){
        $errors[] = 'Le nom et le prénom ne peuvent pas dépasser ' . LMAX_NOMPRENOM . ' caractères.';
    }
    $noTags = strip_tags($_POST['usNom']);
    if ($noTags != $_POST['usNom']){
        $errors[] = 'Le nom et le prénom ne peuvent pas contenir de code HTML.';
    }
    else {
        if( !mb_ereg_match('^[[:alpha:]]([\' -]?[[:alpha:]]+)*$', $_POST['usNom'])){
            $errors[] = 'Le nom et le prénom contiennent des caractères non autorisés.';
        }
    }

    // verify date of birth
    if( !mb_ereg_match('^\d{4}(-\d{2}){2}$', $_POST['usDateNaissance'])){ // old navigator doesn't support date input
        $errors[] = 'la date de naissance doit être au format "AAAA-MM-JJ".'; 
    }
    else{
        list($annee, $mois, $jour) = explode('-', $_POST['usDateNaissance']);
        if (!checkdate($mois, $jour, $annee)) {
            $errors[] = 'La date de naissance n\'est pas valide.'; 
        }
        else if (mktime(0,0,0,$mois,$jour,$annee + AGE_MIN) > time()) {
            $errors[] = 'Vous devez avoir au moins '.AGE_MIN.' ans pour vous inscrire.'; 
        }
        else if (mktime(0,0,0,$mois,$jour,$annee + AGE_MAX + 1) < time()) {
            $errors[] = 'Vous devez avoir au plus '.AGE_MAX.' ans pour vous inscrire.'; 
        }
    }

    // return the errors array if any   
    if (count($errors) > 0) {  
        return $errors;    
    }
    // no error ==> modify user's data in the database
    $nom = em_bd_proteger_entree($GLOBALS['db'], $_POST['usNom']);
    $ville = em_bd_proteger_entree($GLOBALS['db'], $_POST['usVille']);
    $bio = em_bd_proteger_entree($GLOBALS['db'], $_POST['usBio']);

    $aaaammjj = $annee*10000  + $mois*100 + $jour;


    $sql = "UPDATE users
            SET usNom = '$nom',
            usDateNaissance = '$aaaammjj',
            usVille = '$ville',
            usBio = '$bio'
            WHERE usID = '$_SESSION[usID]'"; 
    
    em_bd_send_request($GLOBALS['db'], $sql);
    return array();
}

/**
     * Show Cuiteur account info form
     *
     * @param   array   $err    Array of errors to display
     * @global  array   $_POST
     */
    function gh_aff_formulaire_infos_compte_cuiteur(array $err): void {
        echo '<h2 class="titleForm">Informations sur votre compte Cuiteur</h2>';

        // If there are errors, display form with sent values
        // Else, retrieve the values from database and display form with them
        if (isset($_POST['btnModifyCuiteurAccountInfo'])) {
            $values = em_html_proteger_sortie($_POST);
        }
        else {
            $values = $GLOBALS['userData'];
        }

        if (count($err) > 0) {
            echo '<p class="error">Les erreurs suivantes ont été détectées :';
            foreach ($err as $v) {
                echo '<br> - ', $v;
            }
            echo '</p>';    
        }
        else if (isset($_POST['btnModifyCuiteurAccountInfo'])) {
            echo '<p class="success">La mise à jour des informations sur votre compte a bien été effectuée.</p>';
        }

        echo '<form method="post" action="compte.php">',
                '<table>';

        em_aff_ligne_input('Adresse email :', array('type' => 'email', 'name' => 'usMail', 'value' => $values['usMail'], 'required' => null));
        em_aff_ligne_input('Site web :', array('type' => 'text', 'name' => 'usWeb', 'value' => $values['usWeb']));
            echo '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnModifyCuiteurAccountInfo" value="Valider">',
                    '</td>',
                '</tr>',
            '</table>',
        '<form>';
    }