<?php session_start();
if(empty($_SESSION['id'])):
header('Location:../index.php');
endif;
if(empty($_SESSION['branch'])):
header('Location:../index.php');
endif;
$branch=$_SESSION['branch']; $id=$_SESSION['id'];  

$gid=$_POST['gid']; echo $gid;
include('conn/dbcon.php');
mysqli_query($con,"DELETE FROM `temp_trans` WHERE `temp_trans_id`='$gid'")
	or die(mysqli_error());
	
echo "<script>document.location='inward_transaction.php'</script>";  
?>