<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/style.css" />
	<script src="js/script.js"></script>
</head>
<body>

<?php

include 'includes.php';
check_session();

// street, suburb, postcode, state
$search = array(
	"street" => "",
	"suburb" => "",
	"postcode" => "",
	"state" => "",
	"type" => 4
);
$dbc = new PDO('oci:dbname=FIT2076', 's26244608', 'monash00');
$dbc->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

$type_query = "select * from property_type order by type_name";
$type_stmt = $dbc->prepare($type_query);
?>

<div class="header">
<?php menu();
?>
</div>

<div class="content">
<?php
if (!isset($_GET["action"])) {
	$_GET["action"] = "view";
}
if (isset($_GET["action"])) {
	switch ($_GET["action"]) {
	case "view":
?>
		<div class="search">
			<form action="index.php" method="post">
			<!-- search fields for $search_items -->
			<div class="parent filter">
				<div class="child-left">
					<div>
						<label>Street</label><br />
						<input type="text" name="street" />
					</div>
					<div>
						<label>Suburb</label><br />
						<input type="text" name="suburb" /><br />
					</div>
				</div>
				<div class="child-right">
					<div class="parent">
						<div class="child-left">
							<label>State</label><br />
							<input type="text" name="state" /><br />
						</div>
						<div class="child-right">
							<label>Postcode</label><br />
							<input type="text" name="postcode" /><br />
						</div>
					</div>
					<div>
						<label>Type:</label><br />
						<select name="type" id="type">
<?php
						if ($type_stmt->execute()) {
							while ($type_r = $type_stmt->fetch()) {
?>
								<option value="<?php echo $type_r["type_id"]; ?>"><?php echo $type_r["type_name"]; ?></option>
<?php
							}
	?>
							<option value="4" selected="selected">All</option>
<?php
						}
?>
						</select>
					</div>
				</div>
			<input class="search-submit" type="submit" value="Search" />
			</form>
			</div>
		</div>
		<div style="overflow: hidden; padding: 20px;" width="400px">
			<a class="float-right" href="new_property.php?action=new">Add Property</a>
		</div>
<?php
		try {
			if (isset($_POST["street"]))
				$search["street"] = $_POST["street"];
			if (isset($_POST["suburb"]))
				$search["suburb"] = $_POST["suburb"];
			if (isset($_POST["state"]))
				$search["state"] = $_POST["state"];
			if (isset($_POST["postcode"]))
				$search["postcode"] = $_POST["postcode"];
			if (isset($_POST["type"]))
				$search["type"] = $_POST["type"];

			$query = "select * from property
				where prop_street like '%:street%'
				and prop_suburb like '%:suburb%'
				and prop_state like '%:state%'
				and prop_pc like '%:postcode%'";
			$test_query = "select * from property
				where prop_street like '%". $search["street"] ."%'
				and prop_suburb like '%". $search["suburb"] ."%'
				and prop_state like '%". $search["state"] ."%'
				and prop_pc like '%". $search["postcode"] ."%'";
			if ($search["type"] < 4)
				$test_query = $test_query . "and prop_type = " . $search["type"];

			$stmt = $dbc->prepare($test_query);

			//echo $test_query . "<br />";
			//echo "<pre>"; print_r($search); echo "</pre>";

	//		$stmt->bindParam(":street", $search["street"]);
	//		$stmt->bindParam(":suburb", $search["suburb"]);
	//		$stmt->bindParam(":state", $search["state"]);
	//		$stmt->bindParam(":postcode", $search["postcode"]);

			$stmt->execute();
			//$stmt->debugDumpParams();
?>
		<div class="prop-display">
<?php
			while ($r = $stmt->fetch()) {
				display_prop_thumb($r["prop_id"]);
			}
?>
		</div>
		<div style="overlay: hidden;">
			<button class="display-code"><a href="display.php?page=index">Property</a></button>
		</div>
<?php
		}
		catch (Exception $e) {
			die($e->getMessage());
		}
		break;
	case "delete":
		if (isset($_GET["prop"])) {
			echo "Confirm deleting this property? <br />";
			// get $_GET
			$prop_id = $_GET["prop"];

			// make 2 buttons
?>
			<button onClick="removeProperty(<?php echo $prop_id; ?>)">Confirm</button>
			<button onClick="returnProperty()">Cancel</button>
<?php
		}
		break;
	case "confirmDelete":
		echo "Successfully deleted property. <br />";
		// delete sql
		// delete imgs in the directory
		// make 1 button go back to index.php?action=view

		$prop_id = $_GET["prop"];

		$img_query = "select * from property_image where prop_id = " . $prop_id;
		$img_stmt = $dbc->prepare($img_query);
		$img_stmt->execute();

		$path = "property_imgs/";
		while ($img = $img_stmt->fetch()) {
			if (file_exists($path . $img["img_path"]))
				unlink($path . $img["img_path"]);
		}

		$del_query = "delete from property where prop_id = " . $prop_id;
		$del_stmt = $dbc->prepare($del_query);
		$del_stmt->execute();
?>
		<button onClick="returnProperty()">Return to property page</button>

<?php
		break;
	}
}
?>
</div>
</body>
</html>
