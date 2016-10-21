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

	// handles add/edit/delete

	$new_client = array();
	if (isset($_GET["action"])) {

		switch ($_GET["action"]) {
		case "add":
			$fname = $_POST["fname"];
			$lname = $_POST["lname"];
			$email = $_POST["email"];
			$mobile = $_POST["mobile"];
			$street = $_POST["street"];
			$suburb = $_POST["suburb"];
			$state = $_POST["state"];
			$pc = $_POST["postcode"];

			$add_query = "insert into client values (
				client_seq.nextVal, '"
				. $fname . "',
				'" . $lname . "',
				'" . $email . "',
				'" . $mobile . "',
				'" . $street . "',
				'". $suburb . "',
				'". $state . "',
				'". $pc . "'
				)";

			$add_stmt = $dbc->prepare($add_query);
			$add_stmt->execute();

			break;
		case "edit":
			$fname = $_POST["fname"];
			$lname = $_POST["lname"];
			$email = $_POST["email"];
			$mobile = $_POST["mobile"];
			$street = $_POST["street"];
			$suburb = $_POST["suburb"];
			$state = $_POST["state"];
			$pc = $_POST["postcode"];

			$id = $_GET["edit"];
			$update_query = "update client set
				client_fname = '" . $fname . "',
				client_lname = '" . $lname . "',
				client_email = '" . $email . "',
				client_mobile = '" . $mobile . "',
				client_street = '" . $street . "',
				client_suburb = '" . $suburb . "',
				client_state = '" . $state . "',
				client_pc = '" . $pc . "'
				where client_id = " . $id;
			//echo $update_query . "<br />";
			$update_stmt = $dbc->prepare($update_query);
			$update_stmt->execute();

			break;
		case "delete":
			$id = $_GET["delete"];
			$delete_query = "delete from client where client_id = " . $id;
			//echo $delete_query . "<br />";
			$delete_stmt = $dbc->prepare($delete_query);
			$delete_stmt->execute();

			break;
		}
	}

	$client = array();
	$client_query = "select * from client";
	$client_stmt = $dbc->prepare($client_query);
	$client_stmt->execute();
	while ($client_r = $client_stmt->fetch()) {
		$client[] = $client_r;
	}
?>

<div class="float-right"><a id="gen-pdf">Create PDF report</a></div>
<button width="400px" onClick="toggleAddClient()">Add new Client</button>

// pdf


<div class="add-client" style="display:none;">
<form method="post" action="clients.php?action=add">
	<div class="parent full-width">
		<div class="child-left">
			<div>
				<label>First Name</label><br />
				<input type="text" name="fname" value="" />
			</div>
			<div>
				<label>Last Name</label><br />
				<input type="text" name="lname" value="" />
			</div>
			<div>
				<label>Email</label><br />
				<input type="text" name="email" value="" />
			</div>
			<div>
				<label>Mobile Phone</label><br />
				<input type="text" name="mobile" value="" />
			</div>
		</div>
		<div class="child-right">
			<div>
				<label>Street</label><br />
				<input type="text" name="street" value="" />
			</div>
			<div>
				<label>Suburb</label><br />
				<input type="text" name="suburb" value="" />
			</div>
			<div>
				<label>State</label><br />
				<input type="text" name="state" value="" />
			</div>
			<div>
				<label>Postcode</label><br />
				<input type="text" name="postcode" value="" />
			</div>
		</div>
	</div>
	<input class="full-width" type="submit" value="Submit" />
</form>
</div>

<div id="confirm-delete" class="modal">
	<div class="modal-content">
		<p>Are you sure you want to delete this client?</p>
		<div class="parent">
			<div class="child-left">
				<button id="yes">Yes</button>
			</div>
			<div class="child-right">
				<button id="no">No</button>
			</div>
		</div>
	</div>
</div>

<div class="client-list">
<?php
	for ($i=0; $i<count($client); $i++) {
		client_disp($client[$i]);
	}
?>
</div>
<div style="overlay: hidden;">
	<button class="display-code"><a href="display.php?page=clients">Client</a></button>
</div>

</div>
</body>
</html>
