<?php
$language_selected = "eng"; //Possible values: eng, ger

if ($language_selected == "eng") {
include 'wp-content/language/eng.php'; //Loads the english language-file
}
else if ($language_selected == "ger") {
include 'wp-content/language/ger.php'; //Loads the german language-file
}
else {
include 'wp-content/language/eng.php'; // Fallback to eng if $language_selected has a not existing value
}
?>