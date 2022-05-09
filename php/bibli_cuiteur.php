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

define('MAX_PHOTO_PROFILE_WEIGHT_KB', 50); // in kB
define('MIN_PHOTO_PROFILE_SIZE', 50); // in px


//_______________________________________________________________
/**
 * Génération et affichage de l'entete des pages
 *
 * @param ?string    $titre  Titre de l'entete (si null, affichage de l'entete de cuiteur.php avec le formulaire)
 * @param bool       $with_buttons   If true, show buttons (deconnection, profile modification, ...) => default true
 */
function em_aff_entete(?string $titre = null, bool $connected=true):void{
    echo '<div id="bcContenu">';
    if($connected === true){
        echo    '<header id="header_connected">';
    }else{
        echo '<header id="header_disconnected">';
    }
    if($connected === true){
        echo '<a href="../index.php" title="Se déconnecter de cuiteur"></a>',
            '<a href="../index.php" title="Ma page d\'accueil"></a>',
            '<a href="../index.php" title="Rechercher des personnes à suivre"></a>',
            '<a href="../index.php" title="Modifier mes informations personnelles"></a>';
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
function em_aff_infos(bool $connecte = true):void{
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
function em_aff_pied(): void{
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
function em_aff_blablas(mysqli_result $r): void {
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
                    em_html_a('utilisateur.php', '<strong>'.em_html_proteger_sortie($pseudo_orig).'</strong>','id', $id_orig, 'Voir mes infos'), 
                    ' ', em_html_proteger_sortie($nom_orig),
                    ($t['oriID'] !== null ? ', recuité par '
                                            .em_html_a( 'utilisateur.php','<strong>'.em_html_proteger_sortie($t['autPseudo']).'</strong>',
                                                        'id', $t['autID'], 'Voir mes infos') : ''),
                    '<br>',
                    em_html_proteger_sortie($t['blTexte']),
                    '<p class="finMessage">',
                    em_amj_clair($t['blDate']), ' à ', em_heure_clair($t['blHeure']),
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
function em_est_authentifie(): bool {
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
function em_session_exit(string $page = '../index.php'):void {
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

?>
