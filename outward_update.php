<?php session_start();
if(empty($_SESSION['id'])):
header('Location:../index.php');
endif;
if(empty($_SESSION['branch'])):
header('Location:../index.php');
endif;
$branch=$_SESSION['branch']; $id=$_SESSION['id'];  

$tot=0;
include('conn/dbcon.php');
	    $id = $_POST['id'];
		$sno=$_POST['sno'];
	    $code =$_POST['code'];
        $batch = $_POST['batch'];
	    $qty =$_POST['qty'];
// 	$vol = $_POST['vol'];
//     $tot=$vol*$qty;	
	
	
	
	mysqli_query($con,"update temp_trans_out set qty='$qty',prod_id='$code',batch_out='$batch',serial_no='$sno' where temp_trans_id='$id'")or die(mysqli_error());
	
	
	echo "<script>document.location='outward_transaction.php'</script>";  

	
?>
