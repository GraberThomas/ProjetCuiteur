<?php

/*********************************************************
 *        Bibliothèque de fonctions spécifiques          *
 *               à l'application Cuiteur                 *
 *********************************************************/

 // Force l'affichage des erreurs
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting( E_ALL );

// Définit le fuseau horaire par défaut à utiliser. Disponible depuis PHP 5.1
date_default_timezone_set('Europe/Paris');

//définition de l'encodage des caractères pour les expressions rationnelles multi-octets
mb_regex_encoding ('UTF-8');

define('IS_DEV', true);//true en phase de développement, false en phase de production

 // Paramètres pour accéder à la base de données
define('BD_SERVER', 'localhost');
define('BD_NAME', 'cuiteur_bd');
define('BD_USER', 'cuiteur_userl');
define('BD_PASS', 'cuiteur_passl');
/*define('BD_NAME', 'merlet_cuiteur');
define('BD_USER', 'merlet_u');
define('BD_PASS', 'merlet_p');*/


// paramètres de l'application
define('LMIN_PSEUDO', 4);
define('LMAX_PSEUDO', 30); //longueur du champ dans la base de données
define('LMAX_EMAIL', 80); //longueur du champ dans la base de données
define('LMAX_NOMPRENOM', 60); //longueur du champ dans la base de données


define('LMIN_PASSWORD', 4);
define('LMAX_PASSWORD', 20);

define('AGE_MIN', 18);
define('AGE_MAX', 120);

define("NUMBER_CUIT_DISPLAY",4);
define('MAX_PHOTO_PROFILE_WEIGHT_KB', 20); // in kB
define('MIN_PHOTO_PROFILE_SIZE', 50); // in px


//_______________________________________________________________
/**
 * Génération et affichage de l'entete des pages
 *
 * @param ?string    $titre  Titre de l'entete (si null, affichage de l'entete de cuiteur.php avec le formulaire)
 * @param bool       $with_buttons   If true, show buttons (deconnection, profile modification, ...) => default true
 */
function gh_aff_entete(?string $titre = null, bool $connected=true):void{
    echo '<div id="bcContenu">';
    if($connected === true){
        echo    '<header id="header_connected">';
    }else{
        echo '<header id="header_disconnected">';
    }
    if($connected === true){
        echo '<a href="./deconnexion.php" title="Se déconnecter de cuiteur"></a>',
            '<a href="../index.php" title="Ma page d\'accueil"></a>',
            '<a href="./recherche.php" title="Rechercher des personnes à suivre"></a>',
            '<a href="./compte.php" title="Modifier mes informations personnelles"></a>';
    }
    if ($titre === null){
        echo    '<form action="../index.php" method="POST">',
                    '<textarea name="txtMessage"></textarea>',
                    '<input type="submit" name="btnPublier" value="" title="Publier mon message">',
                '</form>';
    }
    else{
        echo    '<h1>', $titre, '</h1>';
    }
    echo    '</header>';    
}

//_______________________________________________________________
/**
 * Génération et affichage du bloc d'informations utilisateur
 *
 * @param bool    $connecte  true si l'utilisateur courant s'est authentifié, false sinon
 */
function gh_aff_infos(bool $connecte = true):void{
    echo '<aside>';
    if ($connecte){
        echo
            '<h3>Utilisateur</h3>',
            '<ul>',
                '<li>',
                    '<img class="photoProfil" src="../images/pdac.jpg" alt="photo de l\'utilisateur">',
                    '<a href="../index.php" title="Voir mes infos">pdac</a> Pierre Dac',
                '</li>',
                '<li><a href="../index.php" title="Voir la liste de mes messages">100 blablas</a></li>',
                '<li><a href="../index.php" title="Voir les personnes que je suis">123 abonnements</a></li>',
                '<li><a href="../index.php" title="Voir les personnes qui me suivent">34 abonnés</a></li>',                 
            '</ul>',
            '<h3>Tendances</h3>',
            '<ul>',
                '<li>#<a href="../index.php" title="Voir les blablas contenant ce tag">info</a></li>',
                '<li>#<a href="../index.php" title="Voir les blablas contenant ce tag">lol</a></li>',
                '<li>#<a href="../index.php" title="Voir les blablas contenant ce tag">imbécile</a></li>',
                '<li>#<a href="../index.php" title="Voir les blablas contenant ce tag">fairelafete</a></li>',
                '<li><a href="../index.php">Toutes les tendances</a><li>',
            '</ul>',
            '<h3>Suggestions</h3>',             
            '<ul>',
                '<li>',
                    '<img class="photoProfil" src="../images/yoda.jpg" alt="photo de l\'utilisateur">',
                    '<a href="../index.php" title="Voir mes infos">yoda</a> Yoda',
                '</li>',       
                '<li>',
                    '<img class="photoProfil" src="../images/paulo.jpg" alt="photo de l\'utilisateur">',
                    '<a href="../index.php" title="Voir mes infos">paulo</a> Jean-Paul Sartre',
                '</li>',
                '<li><a href="../index.php">Plus de suggestions</a></li>',
            '</ul>';
    }
    echo '</aside>',
         '<main>';   
}

//_______________________________________________________________
/**
 * Génération et affichage du pied de page
 *
 */
function gh_aff_pied(): void{
    echo    '</main>',
            '<footer>',
                '<a href="../index.php">A propos</a>',
                '<a href="../index.php">Publicité</a>',
                '<a href="../index.php">Patati</a>',
                '<a href="../index.php">Aide</a>',
                '<a href="../">Patata</a>',
                '<a href="../index.php">Stages</a>',
                '<a href="../index.php">Emplois</a>',
                '<a href="../index.php">Confidentialité</a>',
            '</footer>',
    '</div>';
}

//_______________________________________________________________
/**
* Affichages des résultats des SELECT des blablas.
*
* La fonction gére la boucle de lecture des résultats et les
* encapsule dans du code HTML envoyé au navigateur 
*
* @param mysqli_result  $r       Objet permettant l'accès aux résultats de la requête SELECT
*/
function gh_aff_blablas(mysqli_result $r): void {
    while ($t = mysqli_fetch_assoc($r)) {
        if ($t['oriID'] === null){
            $id_orig = $t['autID'];
            $pseudo_orig = $t['autPseudo'];
            $photo = $t['autPhoto'];
            $nom_orig = $t['autNom'];
        }
        else{
            $id_orig = $t['oriID'];
            $pseudo_orig = $t['oriPseudo'];
            $photo = $t['oriPhoto'];
            $nom_orig = $t['oriNom'];
        }
        echo    '<li>', 
                    '<img src="../', ($photo == 1 ? "upload/$id_orig.jpg" : 'images/anonyme.jpg'), 
                    '" class="imgAuteur" alt="photo de l\'auteur">',
                    gh_html_a('utilisateur.php', '<strong>'.gh_html_proteger_sortie($pseudo_orig).'</strong>','id', $id_orig, 'Voir mes infos'), 
                    ' ', gh_html_proteger_sortie($nom_orig),
                    ($t['oriID'] !== null ? ', recuité par '
                                            .gh_html_a( 'utilisateur.php','<strong>'.gh_html_proteger_sortie($t['autPseudo']).'</strong>',
                                                        'id', $t['autID'], 'Voir mes infos') : ''),
                    '<br>',
                    gh_html_proteger_sortie($t['blTexte']),
                    '<p class="finMessage">',
                    gh_amj_clair($t['blDate']), ' à ', gh_heure_clair($t['blHeure']),
                    '<a href="../index.php">Répondre</a> <a href="../index.php">Recuiter</a></p>',
                '</li>';
    }
}

//_______________________________________________________________
/**
* Détermine si l'utilisateur est authentifié
*
* @global array    $_SESSION 
* @return bool     true si l'utilisateur est authentifié, false sinon
*/
function gh_est_authentifie(): bool {
    return  isset($_SESSION['usID']);
}

//_______________________________________________________________
/**
 * Termine une session et effectue une redirection vers la page transmise en paramètre
 *
 * Elle utilise :
 *   -   la fonction session_destroy() qui détruit la session existante
 *   -   la fonction session_unset() qui efface toutes les variables de session
 * Elle supprime également le cookie de session
 *
 * Cette fonction est appelée quand l'utilisateur se déconnecte "normalement" et quand une 
 * tentative de piratage est détectée. On pourrait améliorer l'application en différenciant ces
 * 2 situations. Et en cas de tentative de piratage, on pourrait faire des traitements pour 
 * stocker par exemple l'adresse IP, etc.
 * 
 * @param string    URL de la page vers laquelle l'utilisateur est redirigé
 */
function gh_session_exit(string $page = '../index.php'):void {
    session_destroy();
    session_unset();
    $cookieParams = session_get_cookie_params();
    setcookie(session_name(), 
            '', 
            time() - 86400,
            $cookieParams['path'], 
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    header("Location: $page");
    exit();
}
//____________________Form fields verification___________________
//_______________________________________________________________
/**
 * Verify last name and first name
 *
 * - Check if it's not empty 
 * - Check if it's not too long
 * - Check if it doesn't contain HTML tags
 * - Check if it doesn't contain forbidden characters
 * 
 * @param string    $name        Last name
 * @param array    $errors       Array of errors
 */
function gh_verif_nom(string $name, array &$errors): void {
    if (empty($name)) {
        $errors[] = 'Le nom et le prénom doivent être renseignés.'; 
    }
    else {
        if (mb_strlen($name, 'UTF-8') > LMAX_NOMPRENOM){
            $errors[] = 'Le nom et le prénom ne peuvent pas dépasser ' . LMAX_NOMPRENOM . ' caractères.';
        }
        $noTags = strip_tags($name);
        if ($noTags != $name){
            $errors[] = 'Le nom et le prénom ne peuvent pas contenir de code HTML.';
        }
        else {
            if( !mb_ereg_match('^[[:alpha:]]([\' -]?[[:alpha:]]+)*$', $name)){
                $errors[] = 'Le nom et le prénom contiennent des caractères non autorisés.';
            }
        }
    }
}
//_______________________________________________________________
/**
 * Verify birth date
 *
 * - Check if it's not empty 
 * - Check if it's in the correct format (YYYY-MM-DD)
 * - Check if it's valid
 * - Check if user is older than AGE_MIN years and younger than AGE_MAX
 * 
 * @param string   $birthDate    Birth date
 * @param array    $errors       Array of errors
 */
function gh_verif_date_naissance(string $birthDate, array &$errors): void {
    if (empty($birthDate)){
        $errors[] = 'La date de naissance doit être renseignée.'; 
    }
    else{
        if( !mb_ereg_match('^\d{4}(-\d{2}){2}$', $birthDate)){ //vieux navigateur qui ne supporte pas le type date ?
            $errors[] = 'la date de naissance doit être au format "AAAA-MM-JJ".'; 
        }
        else{
            list($year, $month, $day) = explode('-', $birthDate);
            if (!checkdate($month, $day, $year)) {
                $errors[] = 'La date de naissance n\'est pas valide.'; 
            }
            else if (mktime(0,0,0,$month,$day,$year + AGE_MIN) > time()) {
                $errors[] = 'Vous devez avoir au moins '.AGE_MIN.' ans pour vous inscrire.'; 
            }
            else if (mktime(0,0,0,$month,$day,$year + AGE_MAX + 1) < time()) {
                $errors[] = 'Vous devez avoir au plus '.AGE_MAX.' ans pour vous inscrire.'; 
            }
        }
    }
}
//_______________________________________________________________
/**
 * Verify email
 *
 * - Check if it's not empty 
 * - Check if it's not too long
 * - Check if it's valid (syntax)
 * 
 * @param string   $email    Email address
 * @param array    $errors   Array of errors
 */
function gh_verif_email(string $email, array &$errors): void {
    if (empty($email)){
        $errors[] = 'L\'adresse mail ne doit pas être vide.'; 
    }
    else {
        if (mb_strlen($email, 'UTF-8') > LMAX_EMAIL){
            $errors[] = 'L\'adresse mail ne peut pas dépasser '.LMAX_EMAIL.' caractères.';
        }
        if(! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'adresse mail n\'est pas valide.';
        }
    }
}
//_______________________________________________________________
/**
 * Verify passwords
 *
 * - Check if they're equal
 * - Check if they're not too short or too long
 * 
 * @param string   $passe1   Password
 * @param string   $passe2   Password confirmation
 * @param array    $errors   Array of errors
 */
function gh_verif_passe(string $passe1, string $passe2, array &$errors): void {
    if ($passe1 !== $passe2) {
        $errors[] = 'Les mots de passe doivent être identiques.';
    }
    $nb = mb_strlen($passe1, 'UTF-8');
    if ($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
        $errors[] = 'Le mot de passe doit être constitué de '. LMIN_PASSWORD . ' à ' . LMAX_PASSWORD . ' caractères.';
    }
}
//_______________________________________________________________
/**
 * Get stats about a user (usPseudo, usNom, nbBlablas, nbMentions, nbAbonnes, nbAbonnements)
 * 
 * @param mysqli   $mysqli    MySQLi object
 * @param int      $id        User's id
 * @return array              User's info
 */
function gh_sql_get_user_stats(mysqli $db, int $id): array {
    $sql = "SELECT usPseudo, usNom
            FROM users
            WHERE usId = $id
            UNION ALL
            SELECT COUNT(*), NULL
            FROM blablas
            WHERE blIDAuteur = $id
            UNION ALL
            SELECT COUNT(*), NULL
            FROM mentions
            WHERE meIDUser = $id
            UNION ALL
            SELECT COUNT(*), NULL
            FROM estabonne
            WHERE eaIDUser = $id
            UNION ALL
            SELECT COUNT(*), NULL
            FROM estabonne
            WHERE eaIDAbonne = $id";

    $results = gh_bd_send_request($db, $sql);
    $row = mysqli_fetch_array($results);
    $data = array(
        'usPseudo' => $row[0],
        'usNom' => $row[1]
    );
    $row = mysqli_fetch_array($results);
    $data['nbBlablas'] = $row[0];
    $row = mysqli_fetch_array($results);
    $data['nbMentions'] = $row[0];
    $row = mysqli_fetch_array($results);
    $data['nbAbonnes'] = $row[0];
    $row = mysqli_fetch_array($results);
    $data['nbAbonnements'] = $row[0];
    return $data;
}
