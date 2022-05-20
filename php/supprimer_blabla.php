<?php
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

if(!gh_est_authentifie()){
    header('Location: ../index.php');
    exit;
}
if(!isset($_GET['idBlabla']) || !isset($_GET['from'])){
    header('Location: ../index.php');
    exit;
}
$db = gh_bd_connect();
$request = 'SELECT * FROM blablas WHERE blID ='.$_GET['idBlabla'];
$request = gh_bd_proteger_entree($db, $request);
$result = gh_bd_send_request($db, $request);
if(mysqli_num_rows($result) == 0){
    mysqli_free_result($result);
    mysqli_close($db);
    header('Location: ../index.php');
    exit;
}
$row = mysqli_fetch_assoc($result);
if($row['blIDAuteur'] != $_SESSION['usID']){
    header('Location: ../index.php');
    exit;
}
mysqli_free_result($result);
$request = 'DELETE FROM mentions WHERE meIDBlabla ='.$_GET['idBlabla'];
$request = gh_bd_proteger_entree($db, $request);
$result = gh_bd_send_request($db, $request);

$request = 'DELETE FROM tags WHERE taIDBlabla ='.$_GET['idBlabla'];
$request = gh_bd_proteger_entree($db, $request);
$result = gh_bd_send_request($db, $request);

$request='DELETE FROM blablas WHERE blID ='.$_GET['idBlabla'];
$request = gh_bd_proteger_entree($db, $request);
gh_bd_send_request($db, $request);

mysqli_close($db);

header('Location: '.$_GET['from']);
?>