<?php
$page = "";
if (!isset($_GET["page"]))
	$page = "index.php";
else {
	$page = $_GET["page"] . ".php";
}

highlight_file($page);
?>
