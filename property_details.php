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

	try {
		if (isset($_GET["prop"]))
			$prop_id = $_GET["prop"];
		else $prop_id = 1;

		$query = "select * from property where prop_id = :prop";

		$stmt = $dbc->prepare($query);
		$stmt->bindParam(":prop", $prop_id);
		$stmt->execute();

		$r = $stmt->fetch();

		if (isset($_GET["action"]))
			$action = $_GET["action"];
		else $action = "update";

		$type_query = "select * from property_type order by type_name";
		$type_stmt = $dbc->prepare($type_query);

		$features = array();
		$feat_query = "select * from feature";
		$feat_stmt = $dbc->prepare($feat_query);
		$feat_stmt->execute();
		while ($feat_r = $feat_stmt->fetch()) {
			$features[$feat_r["feat_id"]] = $feat_r["feat_name"];
		}

		$curr_feat_query = "select * from property_feature where prop_id = " . $prop_id;
		$curr_feat_stmt = $dbc->prepare($curr_feat_query);
		$curr_feat_stmt->execute();

		$left_feat_query = "select * from feature where feat_id not in (select feat_id from property_feature where prop_id = " . $prop_id . ")";
		$left_feat_stmt = $dbc->prepare($left_feat_query);
		$left_feat_stmt->execute();

		switch ($action) {
		case "update":
			if (isset($_GET["status"])) {
				if ($_GET["status"] == "uploaded") {
					//echo "<pre>"; print_r($_FILES); print_r($_POST); echo "</pre>";
					$name = pathinfo($_FILES['userfile']['name'], PATHINFO_FILENAME);
					$ext = pathinfo($_FILES['userfile']['name'], PATHINFO_EXTENSION);
					$path = "property_imgs/";

					//echo "<pre>"; print_r($_FILES); echo "</pre>";
					//echo $_FILES["userfile"]["error"];
					if (isset($_FILES["userfile"]["tmp_name"])) {
						$upfile = $path . $_FILES["userfile"]["name"];
						if ($_FILES["userfile"]["type"] != "image/jpeg"
							&& $_FILES["userfile"]["type"] != "image/jpg"
							&& $_FILES["userfile"]["type"] != "image/png"
						) {
								echo $_FILES["userfile"]["type"];
						}
						else if (file_exists($upfile)) {
							$inc = '';
							while (file_exists($path . $name . $inc . '.' . $ext)) {
								$inc ++;
							}
							$upfile = $path . $name . $increment . '.' . $ext;
							move_uploaded_file($_FILES["userfile"]["tmp_name"], $name);
						}
						else if (!move_uploaded_file($_FILES["userfile"]["tmp_name"], $upfile)
						) {
							echo "Failed to move file";
						}
						else {
							$type = "." . pathinfo($_FILES['userfile']['name'], PATHINFO_EXTENSION);

							$ins_query = "insert into property_image values (prop_img_seq.nextVal, '" . $_FILES["userfile"]["name"] . "'," . $prop_id . ")";

							//echo $ins_query . "<br />";
							$ins_stmt = $dbc->prepare($ins_query);
							if ($ins_stmt->execute()) {
								// select currVal
								// update image name
								// rename()

								$cur_query = "select * from property_image where img_id = (select max(img_id) from property_image)";
								$cur_stmt = $dbc->prepare($cur_query);
								$cur_stmt->execute();
								$cur_res = $cur_stmt->fetch();

								$new_name = $cur_res["img_id"] . $type;
								if (rename($path . $_FILES["userfile"]["name"], $path . $new_name)) {
									$upd_query = "update property_image set img_path = '" . $new_name . "' where img_id = " . $cur_res["img_id"];
									$upd_stmt = $dbc->prepare($upd_query);
									$upd_stmt->execute();
								}
								echo "Upload successful!";
							}
							else {
								echo "Upload unsuccessful!";
							}
						}
					}
					else { echo "error"; }
				}
			}

			if (isset($_GET["delete"])) {
				$img_id = $_GET["delete"];

				$del_query = "delete from property_image where img_id = " . $img_id;
				$del_stmt = $dbc->prepare($del_query);
				$del_stmt->execute();

				$path = "property_imgs/";
				if (file_exists($path . $img_id . ".jpg"))
					unlink($path . $img_id . ".jpg");
				if (file_exists($path . $img_id . ".jpeg"))
					unlink($path . $img_id . ".jpeg");
				if (file_exists($path . $img_id . ".png"))
					unlink($path . $img_id . ".png");
			}
?>
			<div class="prop-imgs">
				<div class="imgs">
<?php
				$img_urls = array();
				$img_id = array();
				try {
					$img_query = "select * from property_image where prop_id = " . $prop_id;
					$img_stmt = $dbc->prepare($img_query);
					$img_stmt->execute();

					$i = 0;
					while ($img_r = $img_stmt->fetch()) {
						$img_id[$i] = $img_r["img_id"];
						$img_urls[$i++] = $img_r["img_path"];
					}
					//echo "<pre>"; print_r($img_urls); echo "</pre>";
					for ($j=0; $j<$i; $j++) { // write html into the page here
						img_thumb_disp($img_id[$j], $img_urls[$j], $prop_id);
					}
				}
				catch (Exception $e) {
					print_r($e->getMessage());
				}
?>
				</div>
				<div class="img-picker">
				<form method="POST" enctype="multipart/form-data" action="property_details.php?prop=<?php echo $prop_id;?>&action=update&status=uploaded">
					<input class="input-file" type="file" id="file" name="userfile" />
					<label for="file">Select Image</label><br />
					<input class="full-width" type="submit" value="Upload" />
				</form>
				</div>
			</div>
			<div class="prop-modify" class="search">

			<div id="warn-input" class="modal">
				<div class="modal-content">
					<p>Please fill in the required fields.</p>
					<button id="confirm">Okay</button>
				</div>
			</div>

			<form action="property_details.php?prop=<?php echo $prop_id;?>&action=confirmUpdate" method="POST" onSubmit="return verifyAddFields(this)">
				<div class="parent">
					<div class="child-left">
						<div>
							<label>Street</label><br />
							<input onfocus="resetBorder(this)" type="text" name="street" value="<?php echo $r["prop_street"]; ?>" />
						</div>
						<div>
							<label>Suburb</label><br />
							<input onfocus="resetBorder(this)" type="text" name="suburb" value="<?php echo $r["prop_suburb"]; ?>" /><br />
						</div>
						<div class="parent">
							<div class="child-left">
								<label>State</label><br />
								<input onfocus="resetBorder(this)" type="text" name="state" value="<?php echo $r["prop_state"]; ?>" /><br />
							</div>
							<div class="child-right">
								<label>Postcode</label><br />
								<input onfocus="resetBorder(this)" type="text" name="postcode" value="<?php echo $r["prop_pc"]; ?>" /><br />
							</div>
						</div>
						<div>
							<label>Type:</label><br />
							<select name="type" id="type">
		<?php
							if ($type_stmt->execute()) {
								while ($type_r = $type_stmt->fetch()) {
									if ($r["prop_type"] == $type_r["type_id"]) {
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
						<input type="text" name="description" value="<?php echo $r["prop_desc"]; ?>" /><br />
					</div>
				</div>

				<div class="features parent full-width">
					<label>Features</label><br />
					<div class="child-left">
						<p>Current features</p>
						<!-- loop and display from sql (property_feature table) -->
<?php
					while ($no_features = $curr_feat_stmt->fetch()) {
						// echo $features[$no_features["feat_id"]] . " " . $no_features["no_feat"] . "<br />";
						feature_disp($features[$no_features["feat_id"]], $no_features["no_feat"]);
					}
?>
					</div>
					<div class="child-right" style="border-left: 0.5px solid #CCCCCC;">
						<p>Add more features</>
						<!-- loop and display from feature that not in property_feature -->
<?php
					while ($left_features = $left_feat_stmt->fetch()) {
						feature_disp($left_features["feat_name"], "");
					}
?>
					</div>
				</div>
				<input class="full-width" type="submit" value="Update" />
			</form>
			</div>
<?php
			break;
		case "confirmUpdate":
			$type_r = NULL;
			if ($type_stmt->execute())
				while ($type_r = $type_stmt->fetch());

			$new_features = array ();
			if ($curr_feat_stmt->execute())
				while ($no_features = $curr_feat_stmt->fetch()) {
					$del_update = 1;

					if (!isset($_POST[$features[$no_features["feat_id"]]])) {
						$del_update = 0;
					}
					else {
						$feat_id = $no_features["feat_id"];
						$no_feat = $_POST[$features[$feat_id]];
					}

					if ($no_feat == 0) {
						$del_update = 0;
					}

					if ($del_update == 1) {
						$update_curr_feat_query = "update property_feature set no_feat = " . $no_feat
							. " where prop_id = " . $prop_id
							. "   and feat_id = " . $feat_id;
						$update_curr_feat_stmt = $dbc->prepare($update_curr_feat_query);
						$update_curr_feat_stmt->execute();

						$new_features[$features[$feat_id]] = $no_feat;
					}
					else if ($del_update == 0) {
						$del_curr_feat_query = "delete from property_feature where prop_id = " . $prop_id
							. " and feat_id = " . $feat_id;
						$del_curr_feat_stmt = $dbc->prepare($del_curr_feat_query);
						$del_curr_feat_stmt->execute();
					}
				}

			if ($left_feat_stmt->execute())
				while ($left_features = $left_feat_stmt->fetch()) {
					if ($_POST[$left_features["feat_name"]]) {
						$feat_id = $left_features["feat_id"];
						$no_feat = $_POST[$left_features["feat_name"]];

						$insert_feat_query = "insert into property_feature values ("
							. $prop_id . ", "
							. $feat_id . ", "
							. $no_feat . ")";
						$insert_feat_stmt = $dbc->prepare($insert_feat_query);
						$insert_feat_stmt->execute();

						$new_features[$features[$feat_id]] = $no_feat;
					}
				}

			$new_prop = array (
				"street" => $_POST["street"],
				"suburb" => $_POST["suburb"],
				"state" => $_POST["state"],
				"postcode" => $_POST["postcode"],
				"type_key" => $_POST["type"],
				"type_val" => $type_r[$_POST["type"]],
				"desc" => $_POST["description"]
			);

			$update_query = "update property set
				prop_street = '" . $new_prop["street"] . "',
				prop_suburb = '" . $new_prop["suburb"] . "',
				prop_state = '" . $new_prop["state"] . "',
				prop_pc = '" . $new_prop["postcode"] . "',
				prop_desc = '" . $new_prop["desc"] . "',
				prop_type = " . $new_prop["type_key"] . "
				where prop_id = " . $prop_id;

			$stmt = $dbc->prepare($update_query);
			if ($stmt->execute()) {
				echo "<p>Successfully updated property</p>";
				echo $new_prop["street"] . "<br />";
				echo $new_prop["suburb"] . "<br />";
				echo $new_prop["state"] . "<br />";
				echo $new_prop["postcode"] . "<br />";
				echo $new_prop["type_val"] . "<br />";
			}
			else {
				print_r($dbc->errorInfo());
			}

			foreach ($new_features as $feat => $no_feat) {
				echo $feat . ": " . $no_feat . "; ";
			}

			echo "<input type='button' value='Return to property list' OnClick='window.location=\"index.php?action=view\"' />";
			break;
		}
	}
	catch (Exception $e) {
		print_r($e->getMessage());
	}
?>
</div>
</body>
</html>
