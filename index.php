<?php
    ob_start(); //démarre la bufferisation
    session_start();
    
    require_once './php/bibli_generale.php';
    require_once './php/bibli_cuiteur.php';

    // If user is authentificated, we redirect him to Cuiteur.php
    if (gh_est_authentifie()){
        header('Location: ./php/cuiteur.php, TRUE, 308');
        exit;
    }

    $er = isset($_POST['btnConnexion']) ? gh_valider_connexion() : true;

    /*-----------------------------------------------------------------------------
    - Generate HTML page
    ------------------------------------------------------------------------------*/
    gh_aff_debut('Cuiteur | Connexion', './styles/cuiteur.css');
    gh_aff_entete('Connectez-vous', false);
    gh_aff_infos(false);
    gh_aff_formulaire($er);

    echo '<p>Pas encore de compte ? <a href="./php/inscription.php">Inscrivez-vous</a> sans tarder!<br>',
            'Vous hésitez à vous inscrire ? Laissez-vous séduire par une <a href="./html/presentation.html">présentation</a> des possibilités de Cuiteur.</p>';
    gh_aff_pied();
    gh_aff_fin();

    ob_end_flush();

    // ----------  Local functions ----------- //

    /**
     * Show content of the page (Registration form)
     *
     * @param   array   $first_try    If the user has already tried to connect, show an error message
     * @global  array   $_POST
     */
    function gh_aff_formulaire(bool $first_try): void {
        echo '<p>Pour vous connecter, il faut vous authentifier:</p>',
            '<form method="post" action="index.php">';
        if(!$first_try){
            echo '<p class="error"> Le pseudo et/ou le mot de passe est incorrect.</p>';
        }
        echo        '<table>';

        gh_aff_ligne_input( 'Pseudo :', array('type' => 'text', 'name' => 'pseudo', 'value' => '', 'required' => null));
        gh_aff_ligne_input('Mot de passe :', array('type' => 'password', 'name' => 'passe', 'value' => '', 'required' => null));

        echo 
            '<tr>',
                '<td colspan="2">',
                    '<input type="submit" name="btnConnexion" value="Se connecter">',
                '</td>',
            '</tr>',
        '</table>',
        '</form>';
    }

    /**
     *  Manage the connection 
     *
     *      Step 1.  Verify the user's informations
     *                  -> return errors if any
     *      Step 2.  Open the Session and redirect the user to Cuiteur.php
     *
     * @global array    $_POST
     *
     * @return bool    Returns false if errors are found
     */
    function gh_valider_connexion(): bool {
        if( !gh_parametres_controle('post', array('pseudo', 'passe', 'btnConnexion'))) {
            gh_session_exit();   
        }

        foreach($_POST as &$val){
            $val = trim($val);
        }
        
        // Pseudo verification
        $l = mb_strlen($_POST['pseudo'], 'UTF-8');
        if ($l < LMIN_PSEUDO || $l > LMAX_PSEUDO){
            return false;
        }
        else if( !mb_ereg_match('^[[:alnum:]]{'.LMIN_PSEUDO.','.LMAX_PSEUDO.'}$', $_POST['pseudo'])){
            return false;
        }

        $nb = mb_strlen($_POST['passe'], 'UTF-8');
        if ($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
            return false;
        }
        
        $db = gh_bd_connect();
        $pseudo = gh_bd_proteger_entree($db, $_POST['pseudo']);
        $sql = "SELECT * FROM users WHERE usPseudo = '$pseudo'";
        $result = gh_bd_send_request($db, $sql);
        if(mysqli_num_rows($result) == 0){
            return false;
        }
        
        $row = mysqli_fetch_assoc($result);
        $pass_hash = $row['usPasse'];

        // Password verification
        if(!password_verify($_POST['passe'], $pass_hash)){
            return false;
        }
        $_SESSION['usID'] = $row['usID'];
        mysqli_close($db);
        header('Location: ./php/cuiteur.php');
        exit();
    }
