<?php
include($_SERVER['DOCUMENT_ROOT'].'inc/dbs/ref_stats_config.php'); 
include('header.php');
include('../inc/functions.php');
?>

		<div class="row">
			<div class="col-md-12">
				<h2>Edit Count</h2>				
			</div>
		</div>
		
<? 
if (isset($_GET['id']) ) { 
	$id = (int) $_GET['id']; 
	if ($_SERVER['REQUEST_METHOD'] == "POST") {
		if ($_POST['count1'] == $_POST['count2'] && is_numeric($_POST['count1']) && is_numeric($_POST['count2'])) {

			// check if counts submitted are equal and valid
			if ($_POST['count1'] != $_POST['count2'] || !is_numeric($_POST['count1']) || !is_numeric($_POST['count2'])) {
				reporter("red", "Error: Counts do not match or are not numbers.  <a href='list.php'>Back to gate count management.</a>  ", " ");						
			}

			else {

				foreach($_POST AS $key => $value) { $_POST[$key] = mysqli_real_escape_string($link, $value); } 
				$sql = "UPDATE `$default_table_name` SET  `gate_number` =  '{$_POST['count1']}' WHERE `id` = '$id' ";			
				mysqli_query($link, $sql) or die(mysqli_error());

				// if coming from index.php, return
				if (isset($_REQUEST['origin']) && $_REQUEST['origin'] == 'index' ){
					header('Location: ../', true, 302);
				}

				// report success
				reporter("green", "<div class='row'><div class='col-md-6'>Gate count edited. <a href='list.php'>Back to gate count management.</a>", " ");
			}

		} 
		else {
			reporter("red","Could not edit gate count.  <a href='list.php'>Back to gate count management.</a>"," ");		
		}
	}
	else {	
		$row = mysqli_fetch_array ( mysqli_query($link, "SELECT * FROM `$default_table_name` WHERE `id` = '$id' ")); 

?>

		<div class="row">
			<div class="col-md-6">
				<form action='' method='POST' class="form" role="form">

					<!-- edit count -->
					<div class="form-group">
						<label class="alert_color" for="count1">Update Count (previous count has been automatically entered)</label>
						<input type="text" class="form-control" id="count1" name="count1" id="count1" value="<?php echo $row['gate_number']; ?>">
					</div>
					<div class="form-group">
						<!-- <label for="exampleInputEmail1">Enter Door Counts</label> -->
						<input type="text" class="form-control" name="count2" id="count2" value="<?php echo $row['gate_number']; ?>">
					</div>

					<!-- count id -->
					<input type="hidden" id="count_id" name="count_id" value="<?php echo $row['id']; ?>"></input>					

					<button type="submit" class="btn btn-default">Submit</button> 
				</form>
			</div>
		</div>

		<!-- footer -->
		<?php include('footer.php') ?>

	</div>

<? 
	} 
}
?> 












