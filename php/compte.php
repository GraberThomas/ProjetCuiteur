<?php

define('MSG_SUCCESS_MODIFY_PROFILE', 'La mise à jour des informations sur votre compte a bien été effectuée.');

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
- Get user's data
- Check form submission
------------------------------------------------------------------------------*/
$userData = gh_sql_get_user_info($db, $_SESSION['usID']);

$erPersonalInfo = isset($_POST['btnModifyPersonalInfo']) ? gh_traitement_infos_perso() : array();
$erCuiteurAccountInfo = isset($_POST['btnModifyCuiteurAccountInfo']) ? gh_traitement_infos_compte_cuiteur() : array();
$erCuiteurAccountSettings = isset($_POST['btnModifyCuiteurAccountSettings']) ? gh_traitement_parametres_compte_cuiteur() : array();

/*------------------------------------------------------------------------------
- Generating the html code for the page
------------------------------------------------------------------------------*/

gh_aff_debut('Cuiteur | Compte', '../styles/cuiteur.css');

gh_aff_entete('Paramètres de mon compte', true);
gh_aff_infos(true);

echo '<p>Cette page vous permet de modifier les informations relatives à votre compte.</p>',
     '<br>';

gh_aff_formulaire_infos_perso($erPersonalInfo);
gh_aff_formulaire_infos_compte_cuiteur($erCuiteurAccountInfo);
gh_aff_formulaire_parametres_compte_cuiteur($erCuiteurAccountSettings);

gh_aff_pied();
gh_aff_fin();

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
            $values = gh_html_proteger_sortie($_POST);
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
            echo '<p class="success">'.MSG_SUCCESS_MODIFY_PROFILE.'</p>';  
        }

        // The date stored in the database is in sql format, we need to convert it for putting it into the date input field
        if (!isset($_POST['btnModifyPersonalInfo'])) {
            $values['usDateNaissance'] = gh_convert_date_to_input_format($values['usDateNaissance']);
        }

        echo '<form method="post" action="compte.php">',
                '<table>';

        gh_aff_ligne_input('Nom et prénom :', array('type' => 'text', 'name' => 'usNom', 'value' => $values['usNom'], 'required' => null));
        gh_aff_ligne_input('Date de naissance :', array('type' => 'date', 'name' => 'usDateNaissance', 'value' => $values['usDateNaissance'], 'required' => null));
        gh_aff_ligne_input('Ville :', array('type' => 'text', 'name' => 'usVille', 'value' => $values['usVille']));
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
            '</form>';
    }

    /**
 *  Handle the personal information form 
 *
 *      Step 1. Verify the data
 *                  -> return an array of errors if any
 *      Step 2. modify the data in the database
 *      Step 3. Show back the page with a success message
 *
 *
 * @global array    $_POST
 *
 * @return array    associative array containing the errors if any
 */
function gh_traitement_infos_perso(): array {
    if( !gh_parametres_controle('post', array('usNom', 'usDateNaissance', 'btnModifyPersonalInfo'), array('usVille', 'usBio'))) {
        gh_session_exit();   
    }
    
    foreach($_POST as &$val){
        $val = trim($val);
    }
    
    $errors = array();
    
    // verify name
    gh_verif_nom($_POST['usNom'], $errors);

    // verify date of birth
    gh_verif_date_naissance($_POST['usDateNaissance'], $errors);

    // verify city
    if (!empty($_POST['usVille'])) {
        $noTags = strip_tags($_POST['usVille']);
        if ($noTags != $_POST['usVille']){
            $errors[] = 'Le nom de la ville ne peut contenir de balises HTML.';
        }
    }

    // verify bio
    if (!empty($_POST['usBio'])) {
        $noTags = strip_tags($_POST['usBio']);
        if ($noTags != $_POST['usBio']){
            $errors[] = 'La biographie ne peut contenir de balises HTML.';
        }
    }

    // return the errors array if any   
    if (count($errors) > 0) {  
        return $errors;    
    }
    // no error ==> modify user's data in the database
    $name = gh_bd_proteger_entree($GLOBALS['db'], $_POST['usNom']);
    $city = gh_bd_proteger_entree($GLOBALS['db'], $_POST['usVille']);
    $bio = gh_bd_proteger_entree($GLOBALS['db'], $_POST['usBio']);

    list($day, $month, $year) = explode('-', $_POST['usDateNaissance']);
    $yyyymmdd = $year*10000  + $month*100 + $day;


    $sql = "UPDATE users
            SET usNom = '$name',
            usDateNaissance = '$yyyymmdd',
            usVille = '$city',
            usBio = '$bio'
            WHERE usID = '$_SESSION[usID]'"; 
    
    gh_bd_send_request($GLOBALS['db'], $sql);
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
            $values = gh_html_proteger_sortie($_POST);
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
            echo '<p class="success">'.MSG_SUCCESS_MODIFY_PROFILE.'</p>';
        }

        echo '<form method="post" action="compte.php">',
                '<table>';

        gh_aff_ligne_input('Adresse email :', array('type' => 'email', 'name' => 'usMail', 'value' => $values['usMail'], 'required' => null));
        gh_aff_ligne_input('Site web :', array('type' => 'text', 'name' => 'usWeb', 'value' => $values['usWeb']));
            echo '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnModifyCuiteurAccountInfo" value="Valider">',
                    '</td>',
                '</tr>',
            '</table>',
        '</form>';
    }

        /**
 *  Handle the Cuiteur account info form
 *
 *      Step 1. Verify the data
 *                  -> return an array of errors if any
 *      Step 2. modify the data in the database
 *      Step 3. Show back the page with a success message
 *
 *
 * @global array    $_POST
 *
 * @return array    associative array containing the errors if any
 */
function gh_traitement_infos_compte_cuiteur(): array {
    
    if( !gh_parametres_controle('post', array('usMail', 'btnModifyCuiteurAccountInfo'), array('usWeb'))) {
        gh_session_exit();   
    }
    
    foreach($_POST as &$val){
        $val = trim($val);
    }
    
    $errors = array();
    
    // Verify email format
    gh_verif_email($_POST['usMail'], $errors);

    // Verify website format
    if (!empty($_POST['usWeb'] && !filter_var($_POST['usWeb'], FILTER_VALIDATE_URL))) {
        $errors[] = 'Le site web n\'est pas valide.';
    }

    // return the errors array if any   
    if (count($errors) > 0) {  
        return $errors;    
    }
    // no error ==> modify user's data in the database
    $email = gh_bd_proteger_entree($GLOBALS['db'], $_POST['usMail']);
    $web = gh_bd_proteger_entree($GLOBALS['db'], $_POST['usWeb']);

    $sql = "UPDATE users
            SET usMail = '$email',
            usWeb = '$web'
            WHERE usID = '$_SESSION[usID]'";
    
    gh_bd_send_request($GLOBALS['db'], $sql);
    return array();
}

/**
     * Show cuiteur account settings form
     *
     * @param   array   $err    Array of errors to display
     * @global  array   $_POST
     */
    function gh_aff_formulaire_parametres_compte_cuiteur(array $err): void {

        echo '<h2 class="titleForm">Paramètres de votre compte Cuiteur</h2>';

        // If there are errors, display form with sent values
        // Else, retrieve the values from database and display form with them
        if (isset($_POST['btnModifyCuiteurAccountSettings'])) {
            $values = gh_html_proteger_sortie($_POST);
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
        else if (isset($_POST['btnModifyCuiteurAccountSettings'])) {
            echo '<p class="success">'.MSG_SUCCESS_MODIFY_PROFILE.'</p>';  
        }
        if ((isset($_POST['btnModifyCuiteurAccountSettings']) && $_POST['usAvecPhoto'] == '1') || (!isset($_POST['btnModifyCuiteurAccountSettings']) && $GLOBALS['userData']['usAvecPhoto'] == '1')) {
            $photoProfilePath =  '../upload/' . $_SESSION['usID'] . '.jpg';
        }
        else {
            $photoProfilePath = '../images/anonyme.jpg';
        }

        echo '<form method="post" action="compte.php" enctype="multipart/form-data">',
                '<table>';

        gh_aff_ligne_input('Changer le mot de passe :', array('type' => 'password', 'name' => 'usPasse', 'value' => ''));
        gh_aff_ligne_input('Répétez le mot de passe :', array('type' => 'password', 'name' => 'usPasse2', 'value' => ''));
                echo '<tr>',
                        '<td>',
                            '<p>Votre photo actuelle :</p>',
                        '</td>',
                        '<td>',
                            '<img class="photoProfil" src="'.$photoProfilePath.'" alt="Photo de profil">',
                            '<p>Taille '.MAX_PHOTO_PROFILE_WEIGHT_KB.'ko maximum</p>',
                            '<p>Image JPG carrée (mini '.MIN_PHOTO_PROFILE_SIZE.'x'.MIN_PHOTO_PROFILE_SIZE.'px)</p>',
                            '<input type="file" name="usPhoto" accept="image/jpeg">',
                        '</td>',
                    '</tr>',
                    '<tr>',
                        '<td>',
                            '<label for="usAvecPhoto">Utiliser votre photo :</label>',
                        '</td>',
                        '<td>';
                            if ((isset($_POST['btnModifyCuiteurAccountSettings']) && $_POST['usAvecPhoto'] == '0') || (!isset($_POST['btnModifyCuiteurAccountSettings']) && $GLOBALS['userData']['usAvecPhoto'] == '0')) {
                                echo '<input type="radio" name="usAvecPhoto" value="0" id="usAvecPhoto" checked>';
                            }
                            else {
                                echo '<input type="radio" name="usAvecPhoto" value="0" id="usAvecPhoto">';
                            }
                            echo '<label for="usAvecPhoto">non</label>';

                            if ((isset($_POST['btnModifyCuiteurAccountSettings']) && $_POST['usAvecPhoto'] == '1') || (!isset($_POST['btnModifyCuiteurAccountSettings']) && $GLOBALS['userData']['usAvecPhoto'] == '1')) {
                                echo '<input type="radio" name="usAvecPhoto" value="1" id="usAvecPhoto" checked>';
                            }
                            else {
                                echo '<input type="radio" name="usAvecPhoto" value="1" id="usAvecPhoto">';
                            }
                            echo '<label for="usAvecPhoto">oui</label>',
                        '</td>',
                    '</tr>',
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnModifyCuiteurAccountSettings" value="Valider">',
                        '</td>',
                    '</tr>',
                '</table>',
            '</form>';
    }

       /**
 *  Handle cuiteur account settings form
 *
 *      Step 1. Verify the data
 *                  -> return an array of errors if any
 *      Step 2. modify the data in the database
 *      Step 3. Show back the page with a success message
 *
 *
 * @global array    $_POST
 *
 * @return array    associative array containing the errors if any
 */
function gh_traitement_parametres_compte_cuiteur(): array {
    if( !gh_parametres_controle('post', array('usAvecPhoto', 'btnModifyCuiteurAccountSettings'), array('usPasse', 'usPasse2', 'usPhoto'))) {
        gh_session_exit();   
    }
    
    foreach($_POST as &$val){
        $val = trim($val);
    }
    
    $errors = array();
    
    // verify the passwords if they are not empty
    if (mb_strlen($_POST['usPasse'], 'UTF-8') > 0 || mb_strlen($_POST['usPasse2'], 'UTF-8') > 0) {
        gh_verif_passe($_POST['usPasse'], $_POST['usPasse2'], $errors);
    }

    // verify photo if wanted
    if ($_POST['usAvecPhoto'] == '1') {
        if ($_FILES['usPhoto']['size'] == 0 && !file_exists('../upload/' . $_SESSION['usID'] . '.jpg')) {
            $errors[] = 'Veuillez sélectionner une photo';
        }
        else if ($_FILES['usPhoto']['size'] > 0) {
            $extension = pathinfo($_FILES['usPhoto']['name'], PATHINFO_EXTENSION);
            if ($extension !== 'jpg') {
                $errors[] = 'Le fichier doit être un fichier JPG.';
            }
            // check the size
            $size = getimagesize($_FILES['usPhoto']['tmp_name']);
            if ($size[0] < 50 || $size[1] < 50) {
                $errors[] = 'L\'image doit être au moins de '.MIN_PHOTO_PROFILE_SIZE. 'x'.MIN_PHOTO_PROFILE_SIZE.'px.';
            }
            // check the weight
            $maxSizeInBytes = MAX_PHOTO_PROFILE_WEIGHT_KB * 1024;
            if ($_FILES['usPhoto']['size'] > $maxSizeInBytes) {
                $errors[] = 'Le fichier doit être inférieur à ' . MAX_PHOTO_PROFILE_WEIGHT_KB . 'ko.';
            }
        }
    }

    // return the errors array if any   
    if (count($errors) > 0) {  
        return $errors;    
    }
    // no error ==> modify user's data in the database
    if ($_POST['usPasse'] !== '') {
        $passe = password_hash($_POST['usPasse'], PASSWORD_DEFAULT);
        $passe = gh_bd_proteger_entree($GLOBALS['db'], $passe);
    }

    $withPhoto = $_POST['usAvecPhoto'] == '1' ? '1' : '0';

    $sql = "UPDATE users
            SET usAvecPhoto = '$withPhoto'";
    
    if (isset($passe)) {
        $sql .= ", usPasse = '$passe'";
    }
    $sql .= " WHERE usID = '" . $_SESSION['usID'] . "'";
    
    gh_bd_send_request($GLOBALS['db'], $sql);

    // Upload the photo if wanted, and new one is uploaded
    if ($_POST['usAvecPhoto'] == '1' && $_FILES['usPhoto']['size'] > 0) {
        $photoProfilPath = '../upload/' . $_SESSION['usID'] . '.jpg';
        // delete the old photo if it exists
        if (file_exists($photoProfilPath)) {
            unlink($photoProfilPath);
        }

        // redim the photo to the correct size if needed
        $size = getimagesize($_FILES['usPhoto']['tmp_name']);
        if ($size[0] != MIN_PHOTO_PROFILE_SIZE || $size[1] != MIN_PHOTO_PROFILE_SIZE) {
            $image = imagecreatefromjpeg($_FILES['usPhoto']['tmp_name']);
            $image = imagescale($image, MIN_PHOTO_PROFILE_SIZE, MIN_PHOTO_PROFILE_SIZE);
            imagejpeg($image, $photoProfilPath);
        }
        
        move_uploaded_file($_FILES['usPhoto']['tmp_name'], $photoProfilPath);
    }
    return array();
}