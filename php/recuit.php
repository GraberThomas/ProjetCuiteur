<?php
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

if(!gh_est_authentifie()){
    header('Location: ../index.php');
    exit;
}
$db = gh_bd_connect();
$request = 'SELECT * FROM blablas WHERE blID ='.$_GET['idBlabla'];
$request = gh_bd_proteger_entree($db, $request);
$result = gh_bd_send_request($db, $request);
if(mysqli_num_rows($result) == 0){
    header('Location: ../index.php');
    exit;
}
$row = mysqli_fetch_assoc($result);
$request='INSERT INTO blablas (blTexte, blDate, blHeure, blIDAuteur, blIDAutOrig) VALUES ("'.$_POST['blTexte'].'", "'.date('Y-m-d').'", "'.date('H:i:s').'", '.$_SESSION['usID'].', '.$row['blIDAuteur'].')';

gh_bd_send_request($db, $request);

mysqli_free_result($result);
mysqli_close($db);

header('Location: ../index.php');
?>