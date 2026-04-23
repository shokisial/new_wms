<?php session_start();
if(empty($_SESSION['id'])):
header('Location:../index.php');
endif;
if(empty($_SESSION['branch'])):
header('Location:../index.php');
endif;
$branch=$_SESSION['branch']; $uid=$_SESSION['id'];  
date_default_timezone_set('Asia/Karachi');

$date = date("Y-m-d H:i:s");
include('conn/dbcon.php');
	$id = $_POST['id'];
	$name =$_POST['desc'];
	$batch = $_POST['batch'];
	$expiry = $_POST['expiry'];
	$asn_qty = $_POST['asn_qty'];
	$qty = $_POST['qty'];	
	$asn_balance = $_POST['asn_balance'];
	$gpass=$_POST['gpass']; 
	$veh=$_POST['veh'];
	$rec=$_POST['rec'];
	
	$supl=0;
	$query=mysqli_query($con,"SELECT * FROM product where prod_desc='$name'")or die(mysqli_error());
		while($row=mysqli_fetch_array($query)){ $supl=$row['supplier_id']; }
                      
	$ccode=0; $btlocation=0; $btcode=0; $btexp=0; $btblc=0; $btlocation=0; $btblock=0;
	$query=mysqli_query($con,"SELECT * FROM `stockin` where stockin_id='$id'")or die(mysqli_error());
		while($row=mysqli_fetch_array($query)){
		    $ccode=$row['prod_id'];  $btcode=$row['batch']; 
		    $btexp=$row['expiry']; $btblc=$row['asn_balance'];
		     $btasn_qty=$row['asn_qty']; $btqty=$row['qty']; 
		     $btasnold=$row['rec_dnno']; }
		    
		    mysqli_query($con,"INSERT INTO `adjustment`(`ad_id`, `ad_code`, `ad_batch`, `ad_exp`, `asn_qty`, `rec_qty`, `rec_diff`,dats,user,asn)
	VALUES ('$id','$ccode','$btcode','$btexp','$btasn_qty','$btqty','$btblc','$date','$uid','$btasnold')")or die(mysqli_error($con));

	mysqli_query($con,"UPDATE `stockin` SET `prod_id`='$name',`qty`='$qty',`asn_qty`='$asn_qty',`asn_balance`='$asn_balance',`batch`='$batch',`expiry`='$expiry',truck_no='$veh',rec_dnno='$rec' WHERE `stockin_id`='$id'")or die(mysqli_error($con));
	$remarks="$uid changed Receiving Record";  
	
		mysqli_query($con,"UPDATE `gatepass` SET `vehicle_no`='$veh' WHERE `gatepass_id`='$gpass'")or die(mysqli_error($con));
		
	mysqli_query($con,"INSERT INTO history_log(user_id,action,date,branch_id) VALUES('$uid','$remarks','$date','$branch')")or die(mysqli_error($con));

//	echo "<script type='text/javascript'>alert('Successfully updated Stock details!');</script>";
	echo "<script>document.location='dealer_pre.php'</script>";  

	
?>
