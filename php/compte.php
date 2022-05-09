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
$erCuiteurAccountInfo = isset($_POST['btnModifyCuiteurAccountInfo']) ? gh_traitement_infos_compte_cuiteur() : array();
$erCuiteurAccountSettings = isset($_POST['btnModifyCuiteurAccountSettings']) ? gh_traitement_parametres_compte_cuiteur() : array();

$sqlUserData = 'SELECT usNom, usDateNaissance, usVille, usBio, usMail, usWeb, usPasse, usAvecPhoto
                FROM users
                WHERE usID = ' . $_SESSION['usID'];

$userData = mysqli_fetch_assoc(em_bd_send_request($GLOBALS['db'], $sqlUserData));

/*------------------------------------------------------------------------------
- Generating the html code for the page
------------------------------------------------------------------------------*/

em_aff_debut('Cuiteur | Compte', '../styles/cuiteur.css');

em_aff_entete('Paramètres de mon compte', true);
em_aff_infos(true);

echo '<p>Cette page vous permet de modifier les informations relatives à votre compte.</p>',
     '<br>';

gh_aff_formulaire_infos_perso($erPersonalInfo);
gh_aff_formulaire_infos_compte_cuiteur($erCuiteurAccountInfo);
gh_aff_formulaire_parametres_compte_cuiteur($erCuiteurAccountSettings);

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
        if (!isset($_POST['btnModifyPersonalInfo'])) {
            $values['usDateNaissance'] = gh_convert_date_to_input_format($values['usDateNaissance']);
        }

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
    
    if( !em_parametres_controle('post', array('usMail', 'btnModifyCuiteurAccountInfo'), array('usWeb'))) {
        em_session_exit();   
    }
    
    foreach($_POST as &$val){
        $val = trim($val);
    }
    
    $errors = array();
    
    // Verify email format
    if (mb_strlen($_POST['usMail'], 'UTF-8') > LMAX_EMAIL){
        $errors[] = 'L\'adresse mail ne peut pas dépasser '.LMAX_EMAIL.' caractères.';
    }
    if(! filter_var($_POST['usMail'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'adresse mail n\'est pas valide.';
    }

    // Verify website format
    if (mb_strlen($_POST['usWeb'], 'UTF-8') > 0 && !filter_var($_POST['usWeb'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Le site web n\'est pas valide.';
    }

    // return the errors array if any   
    if (count($errors) > 0) {  
        return $errors;    
    }
    // no error ==> modify user's data in the database
    $email = em_bd_proteger_entree($GLOBALS['db'], $_POST['usMail']);
    $web = em_bd_proteger_entree($GLOBALS['db'], $_POST['usWeb']);

    $sql = "UPDATE users
            SET usMail = '$email',
            usWeb = '$web'
            WHERE usID = '$_SESSION[usID]'";
    
    em_bd_send_request($GLOBALS['db'], $sql);
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
        else if (isset($_POST['btnModifyCuiteurAccountSettings'])) {
            echo '<p class="success">La mise à jour des informations sur votre compte a bien été effectuée.</p>';    
        }

        $photoProfilPath = $GLOBALS['userData']['usAvecPhoto'] == '1' ? '../upload/' . $_SESSION['usID'] . '.jpg' : '../images/anonyme.jpg';

        echo '<form method="post" action="compte.php" enctype="multipart/form-data">',
                '<table>';

        em_aff_ligne_input('Changer le mot de passe :', array('type' => 'password', 'name' => 'usPasse', 'value' => ''));
        em_aff_ligne_input('Répétez le mot de passe :', array('type' => 'password', 'name' => 'usPasse2', 'value' => ''));
                echo '<tr>',
                        '<td>',
                            '<p>Votre photo actuelle :</p>',
                        '</td>',
                        '<td>',
                            '<img class="photoProfil" src="'.$photoProfilPath.'" alt="Photo de profil">',
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
                            if ($GLOBALS['userData']['usAvecPhoto'] == '0' || (isset($_POST['btnModifyCuiteurAccountSettings']) && $_POST['usAvecPhoto'] == '0')) {
                                echo '<input type="radio" name="usAvecPhoto" value="0" id="usAvecPhoto" checked>';
                            }
                            else {
                                echo '<input type="radio" name="usAvecPhoto" value="0" id="usAvecPhoto">';
                            }
                            echo '<label for="usAvecPhoto">non</label>';

                            if ($GLOBALS['userData']['usAvecPhoto'] == '1' || (isset($_POST['btnModifyCuiteurAccountSettings']) && $_POST['usAvecPhoto'] == '1')) {
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
    if( !em_parametres_controle('post', array('usAvecPhoto', 'btnModifyCuiteurAccountSettings'), array('usPasse', 'usPasse2', 'usPhoto'))) {
        em_session_exit();   
    }
    
    foreach($_POST as &$val){
        $val = trim($val);
    }
    
    $errors = array();
    
    // verify the passwords if they are not empty
    if (mb_strlen($_POST['usPasse'], 'UTF-8') > 0 || mb_strlen($_POST['usPasse2'], 'UTF-8') > 0) {
        if ($_POST['usPasse'] !== $_POST['usPasse2']) {
            $errors[] = 'Les mots de passe doivent être identiques.';
        }
        $nb = mb_strlen($_POST['usPasse'], 'UTF-8');
        if ($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
            $errors[] = 'Le mot de passe doit être constitué de '. LMIN_PASSWORD . ' à ' . LMAX_PASSWORD . ' caractères.';
        }
    }

    // verify photo if wanted
    if ($_POST['usAvecPhoto'] == '1') {
        if ($_FILES['usPhoto']['size'] == 0) {
            $errors[] = 'Vous devez sélectionner une photo.';
        }
        else {
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
        $passe = em_bd_proteger_entree($bd, $passe);
    }

    $avecPhoto = $_POST['usAvecPhoto'] == '1' ? '1' : '0';
    $photo = $_FILES['usPhoto']['name'];

    $sql = "UPDATE users
            SET usAvecPhoto = '$avecPhoto'";
    
    if (isset($passe)) {
        $sql .= ", usPasse = '$passe'";
    }
    $sql .= " WHERE usID = '" . $_SESSION['usID'] . "'";
    
    em_bd_send_request($GLOBALS['db'], $sql);

    // Upload the photo if wanted
    if ($_POST['usAvecPhoto'] == '1') {
        $photoProfilPath = '../upload/' . $_SESSION['usID'] . '.jpg';
        // delete the old photo if it exists
        if (file_exists($photoProfilPath)) {
            unlink($photoProfilPath);
        }
        move_uploaded_file($_FILES['usPhoto']['tmp_name'], $photoProfilPath);
    }
    return array();
}