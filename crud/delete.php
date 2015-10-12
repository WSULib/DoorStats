<?php
include($_SERVER['DOCUMENT_ROOT'].'inc/dbs/ref_stats_config.php'); 
include('header.php');
include('../inc/functions.php');
?>



<?php
$id = (int) $_GET['id']; 
mysqli_query($link, "DELETE FROM `ref_stats` WHERE `id` = '$id' ") ;
$result = (mysqli_affected_rows($link))? True : False;

if ($result == True){
	header('Location: ' . $_SERVER['HTTP_REFERER']);
}
else{
	?>
	<div class="row">
		<div class="col-md-10">
			<h2>Transaction Removal</h2>
			<p>There was a problem.</p>
			<a href='list.php'>Back To Listing</a>
		</div>
	</div>

	<!-- footer -->
	<?php include('footer.php') ?>
	
	<?php
}
?>

	</div>

</body>
</html>
