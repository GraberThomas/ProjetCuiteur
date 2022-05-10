<?php

// ATTENTION : dans ce script, les chaînes saisies sont envoyées au navigateur sans être protégées

require_once 'bibli_generale.php';


gh_aff_debut('Cuiteur | Inscription');

echo '<hr>';

foreach($_POST as $cle => $val){
    echo $cle , ':', $val, '<br>';
}

echo '<hr>';

echo '<pre>';
var_dump($_POST);
echo '</pre>';

echo '<hr>';

echo '<pre>', print_r($_POST, true), '</pre>';


gh_aff_fin();

ob_end_flush();

?>
