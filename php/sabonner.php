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

if(!isset($_POST['btnValiderAbonnement'])){
    header('Location: ../index.php');
    exit;
}

$db = gh_bd_connect();

foreach ($_POST as $key => $value) {
    if (str_starts_with($key, 'abonnement_')) {
        $id = gh_bd_proteger_entree($db, $value);
        $date = date('Ymd');
        $request = "INSERT INTO estabonne VALUES ($_SESSION[usID], $id, $date)";
        gh_bd_send_request($db, $request);
    }else if(str_starts_with($key, 'desabonnement_')){
        $id = gh_bd_proteger_entree($db, $value);
        $request = "DELETE FROM estabonne WHERE eaIDUser = $_SESSION[usID] AND eaIDAbonne = $id";
        gh_bd_send_request($db, $request);
    }
}
header('Location: ../index.php');

?>