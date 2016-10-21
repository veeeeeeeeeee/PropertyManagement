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

	if (isset($_GET["action"])) {
		switch ($_GET["action"]) {
		case "add":
			if (isset($_POST["feat"]) && $_POST["feat"] != "") {
				$feat_name = $_POST["feat"];
				$add_query = "insert into feature values (feat_seq.nextVal, '" . $feat_name . "')";
				$add_stmt = $dbc->prepare($add_query);
				$add_stmt->execute();
			}
			break;
		case "delete":
			$feat_id = $_GET["delFeature"];
			$del_query = "delete from feature where feat_id = " . $feat_id;
			$del_stmt = $dbc->prepare($del_query);
			$del_stmt->execute();

			break;
		}
	}

// get features from dB
	$free_feat = array();
	$occd_feat = array();

// features with no property attached
	$free_feat_query = "select * from feature where feat_id not in (select feat_id from property_feature)";
	$free_feat_stmt = $dbc->prepare($free_feat_query);
	if ($free_feat_stmt->execute()) {
		while ($feat_r = $free_feat_stmt->fetch()) {
			$free_feat[$feat_r["feat_id"]] = $feat_r["feat_name"];
		}
	}

// features with property attached
	$occd_feat_query = "select * from feature where feat_id in (select feat_id from property_feature)";
	$occd_feat_stmt = $dbc->prepare($occd_feat_query);
	if ($occd_feat_stmt->execute()) {
		while ($feat_r = $occd_feat_stmt->fetch()) {
			$occd_feat[$feat_r["feat_id"]] = $feat_r["feat_name"];
		}
	}

	/*
	echo "<pre>";
		print_r($free_feat);
		print_r($occd_feat);
	echo "</pre>";
	 */

// form to add more features
?>

<div class="add-feat">
	<p>Add More Features</p>
	<form action="features.php?action=add" method="POST">
	<div class="parent">
		<div class="child-left">
			<label>Feature Description</label><br />
			<input type="text" name="feat" value="" /><br />
		</div>
		<div class="child-right">
			<input class="full-width" type="submit" value="Add" />
		</div>
	</div>
	</form>
</div>

<?php
// current features
//
?>
<div class="feat-list">
	<p>Current Features List</p>
<?php
	//debug_array($free_feat);
	foreach ($free_feat as $feat_id => $feat_name) {
		free_feat_disp($feat_id, $feat_name);
	}

	foreach ($occd_feat as $feat_id => $feat_name) {
		occd_feat_disp($feat_id, $feat_name);
	}
?>
</div>
<div style="overlay: hidden;">
	<button class="display-code"><a href="display.php?page=features">Feature</a></button>
</div>
</div>
</body>
</html>
