<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/style.css" />
	<script src="js/script.js"></script>
</head>
<body>

<?php
include('includes.php');
//echo $_GET["prop"];

check_session();

?>
<div class="header">
<?php menu(); ?>
</div>

<div class="content">
<?php
	$dbc = new PDO('oci:dbname=FIT2076', 's26244608', 'monash00');
	$dbc->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

	$property = array();
	$prop_query = "select * from property";
	$prop_stmt = $dbc->prepare($prop_query);

	if (isset($_GET["action"])) {
		if ($_GET["action"] == "updatePrice") {
			$prop_stmt->execute();
			while ($p = $prop_stmt->fetch()) {
				$prop_id = $p["prop_id"];
				$price = $_POST[$prop_id];

				$update_query = "update property
					set price = " . $price . "
					where prop_id = " . $prop_id;
				//echo $update_query . "<br />";

				$update_stmt = $dbc->prepare($update_query);
				$update_stmt->execute();
			}
			echo "Price Updated" . "<br />";
		}
	}

	$prop_stmt->execute();
	while ($p = $prop_stmt->fetch()) {
		$property[] = $p;
	}
?>
	<form method="POST" action="multiple_properties.php?action=updatePrice">

<?php
	foreach ($property as $key => $p) {
		property_thumb_mult($p);
	}
?>
	<input type="submit" value="Update" />
	</form>
	<div style="overlay: hidden;">
		<button class="display-code"><a href="display.php?page=multiple_properties">Multiple Property</a></button>
	</div>
</div>
</body>
</html>
