<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/style.css" />
	<script src="js/script.js"></script>
</head>
<body>

<?php
include 'includes.php';

$dbc = new PDO('oci:dbname=FIT2076', 's26244608', 'monash00');
$dbc->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

check_session();
?>

<div class="header">
<?php menu(); ?>
</div>
<?php
	//echo "<pre>"; print_r($img_urls); echo "</pre>";

	// handle upload, move to property_imgs/, rename, put into img_urls, img_ids
	if (isset($_GET["status"])) {
		if ($_GET["status"] == "uploaded") {
			$name = pathinfo($_FILES['userfile']['name'], PATHINFO_FILENAME);
			$ext = pathinfo($_FILES['userfile']['name'], PATHINFO_EXTENSION);
			$path = "tmp_imgs/";

			$inc = ''; $inc ++;
			if (isset($_FILES["userfile"]["tmp_name"])) {
				$upfile = $path . $inc . '.' . $ext;

				if ($ext != "jpeg"
					&& $ext != "jpg"
					&& $ext != "png"
				) {
					//echo $ext;
				}
				else if (file_exists($upfile)) { // name change - new convention 1.jpg 2.png 3.jpg etc
					//echo "rename\n";
					while (file_exists($path . $inc . '.' . $ext)) {
						$inc ++;
					}
					$upfile = $path . $inc . '.' . $ext;
					//echo $upfile;
					move_uploaded_file($_FILES["userfile"]["tmp_name"], $upfile);
				}
				else if (!move_uploaded_file($_FILES["userfile"]["tmp_name"], $upfile)) {
					echo "Failed to move file.";
				}
				else {
					// echo "uploaded";
					// echo "<pre>"; print_r($img_urls); echo "</pre>";
				}
			}
		}
	}

	$img_urls = array(); // load tmp_imgs/ into this array
	$tmp_img_dir = "tmp_imgs";
	$dh = opendir($tmp_img_dir);
	while (($filename = readdir($dh)) !== false) {
		if ($filename != "." && $filename != "..")
			$img_urls[] = $filename;
	}

	if (isset($_GET["delete"])) { // unset, splice
		$img_ind = $_GET["delete"];
		$path = "tmp_imgs/";
		if (file_exists($path . $img_urls[$img_ind])) {
			unlink($path . $img_urls[$img_ind]);
			array_splice($img_urls, $img_ind, 1);
		}
	}
?>

<div class="content">
<?php
if (isset($_GET["action"])) {
	$type_query = "select * from property_type order by type_name";
	$type_stmt = $dbc->prepare($type_query);

	$features = array();
	$feat_query = "select * from feature";
	$feat_stmt = $dbc->prepare($feat_query);
	$feat_stmt->execute();
	while ($feat_r = $feat_stmt->fetch()) {
		$features[$feat_r["feat_id"]] = $feat_r["feat_name"];
	}

	switch($_GET["action"]) {
	case "new":
?>
		<div class="prop-imgs">
			<div class="imgs">
			<!--populate images-->
<?php
		// unchanged
				foreach ($img_urls as $i => $img_path) {
					img_thumb_edit($i, $img_urls[$i]);
				}
?>
			</div>
			<div class="img-picker">
			<!--file upload form-->
			<form method="POST" enctype="multipart/form-data" action="new_property.php?action=new&status=uploaded">
				<input class="input-file" type="file" id="file" name="userfile" />
				<label for="file">Select Image</label><br />
				<input class="full-width" type="submit" value="Upload" />
			</form>
			</div>
		</div>
		<div class="prop-modify" class="search">
		<!--form property details-->
		<div id="warn-input" class="modal">
			<div class="modal-content">
				<p>Please fill in the required fields.</p>
				<button id="confirm">Okay</button>
			</div>
		</div>

		<form method="POST" action="new_property.php?action=submit" onSubmit="return verifyAddFields(this)">
			<div class="parent">
				<div class="child-left">
					<div>
						<label>Street</label><br />
						<input onfocus="resetBorder(this)" class="add-required" type="text" name="street" />
					</div>
					<div>
						<label>Suburb</label><br />
						<input onfocus="resetBorder(this)" class="add-required" type="text" name="suburb" />
					</div>
					<div class="parent">
						<div class="child-left">
							<label>State</label><br />
							<input onfocus="resetBorder(this)" class="add-required" type="text" name="state" /><br />
						</div>
						<div class="child-right">
							<label>Postcode</label><br />
							<input onfocus="resetBorder(this)" class="add-required" type="text" name="postcode" /><br />
						</div>
					</div>
					<div>
						<label>Type:</label><br />
						<select name="type" id="type">
		<?php
						if ($type_stmt->execute()) {
							while ($type_r = $type_stmt->fetch()) {
								if ($type_r["type_id"] == 1) {
									$selected = 'selected="selected"';
								}
								else {
									$selected = "";
								}
		?>
								<option value="<?php echo $type_r["type_id"]; ?>" <?php echo $selected; ?> ><?php echo $type_r["type_name"]; ?></option>
		<?php
							}
						}
		?>
						</select>
					</div>
				</div>
				<div class="child-right" class="desc-field">
					<label>Description</label><br />
					<input type="text" name="description" /><br />
				</div>
			</div>
			<!-- TODO: add features -->
			<div class="features full-width">
				<label>Features</label><br />
				<p>Add features</p>
<?php
				foreach ($features as $feat_id => $feat_name) {
					feature_disp($feat_name, "");
				}
?>
			</div>
			<input type="submit" value="Submit" />
		</form>
<?php
		break;
	case "submit":
		// upon submit:
		//	grab forms data
		//	craft insert property stmt
		//	

		$type_r = NULL;
		if ($type_stmt->execute())
			while ($type_r = $type_stmt->fetch());

		$new_prop = array (
			"street" => $_POST["street"],
			"suburb" => $_POST["suburb"],
			"state" => $_POST["state"],
			"postcode" => $_POST["postcode"],
			"type_key" => $_POST["type"],
			"type_val" => $type_r[$_POST["type"]],
			"desc" => $_POST["description"]
		);

		$ins_query = "insert into property values (
			prop_seq.nextVal,
			'" . $new_prop["street"] . "',
			'" . $new_prop["suburb"] . "',
			'" . $new_prop["state"] . "',
			'" . $new_prop["postcode"] . "',
			" . $new_prop["type_key"] . ",
			'" . $new_prop["desc"] . "', 0
		)";

		$new_features = array ();
		$ins_stmt = $dbc->prepare($ins_query);
		//echo $ins_query;
		if ($ins_stmt->execute()) {
			foreach ($features as $feat_id => $feat_name) {
				$insert = 1;
				if (!isset($_POST[$feat_name])) {
					$insert = 0;
				}
				else {
					$no_feat = $_POST[$feat_name];
				}

				if ($no_feat == 0) {
					$insert = 0;
				}

				if ($insert == 1) {
					$insert_feat_query = "insert into property_feature values (
						prop_seq.currVal, "
						. $feat_id . ", "
						. $no_feat . ")";

					$insert_feat_stmt = $dbc->prepare($insert_feat_query);
					$insert_feat_stmt->execute();

					$new_features[$feat_name] = $no_feat;
				}
			}

			echo "<p>Successfully inserted property</p>";
			echo $new_prop["street"] . "<br />";
			echo $new_prop["suburb"] . "<br />";
			echo $new_prop["state"] . "<br />";
			echo $new_prop["postcode"] . "<br />";
			echo $new_prop["type_val"] . "<br />";
		}

		foreach ($new_features as $feat => $no_feat) {
			echo $feat . ": " . $no_feat . "; ";
		}
		echo "<br />";

		$added_query = "select * from property where prop_id = (select max(prop_id) from property)";
		$added_stmt = $dbc->prepare($added_query);
		$added_stmt->execute();
		$added_r = $added_stmt->fetch();

		$prop_id = $added_r["prop_id"];

		//	insert all imgs path into dB (property_imgs/)
		//	select from dB, change name & move from tmp_imgs/ to property_imgs/

		//echo "Images uploaded" . "<br />";
?>
		<div class="prop-imgs">
<?php
		foreach ($img_urls as $i => $img_path) {
			$tmp_path = "tmp_imgs/";
			$tar_path = "property_imgs/";

			$split = explode(".", $img_path);
			$ext = end($split);

			$ins_query = "insert into property_image values (
				prop_img_seq.nextVal,
				prop_img_seq.currVal || '" . "." . $ext . "', " .
				$prop_id .
			")";

			$ins_stmt = $dbc->prepare($ins_query);
			$ins_stmt->execute();

			$cur_query = "select * from property_image where img_id = (select max(img_id) from property_image)";
			$cur_stmt = $dbc->prepare($cur_query);
			$cur_stmt->execute();
			$cur_res = $cur_stmt->fetch();

			$new_name = $cur_res["img_id"] . "." . $ext;
			if (rename($tmp_path . $img_path, $tar_path . $new_name)) {
?>
				<div class="img-item">
					<img src="<?php echo $tar_path . $new_name; ?>" alt="" height="100px" />
				</div>
<?php
			}
		}
?>
		</div>
<?php
		echo "<input type='button' value='Return to property list' OnClick='window.location=\"index.php?action=view\"' />";
		break;
	}
}
?>
	</div>
	<div style="overlay: hidden;">
		<button class="display-code"><a href="display.php?page=new_property">Add Property</a></button>
	</div>
</div>

</body>
</html>
