<?php session_start();
if(empty($_SESSION['id'])):
header('Location:../index.php');
endif;
if(empty($_SESSION['branch'])):
header('Location:../index.php');
endif;
$branch=$_SESSION['branch']; $id=$_SESSION['id'];  

include('conn/dbcon.php');

	
	date_default_timezone_set("Asia/Karachi"); 
	$date = date("Y/m/d");
	$cid=$_REQUEST['cid'];
	
// 	$rec_dnno=$_POST['rec_dnno'];
// 	$rec_dnno10=$_POST['rec_dnno1'];
// 	$cons_name=$_POST['cons_name'];
	//$truckno=$_POST['truckno'];
		
/*
	$query2=mysqli_query($con,"SELECT * FROM `stockout` WHERE `stockout_orderno`='$rec_dnno' and branch_id='$branch'")or die(mysqli_error($con));
		$count=mysqli_num_rows($query2);

		if ($count>0)
		{
			echo "<script type='text/javascript'>alert('Delivery Number $rec_dnno already exist!');</script>";
			echo "<script>document.location='outward_transaction.php'</script>";  
		}
		else
		{	
		*/

	$sales_id=mysqli_insert_id($con);
	$_SESSION['sid']=$sales_id;
	$query=mysqli_query($con,"select * from temp_trans_out")or die(mysqli_error($con));
		while ($row=mysqli_fetch_array($query))
		{
		    $rid=$row['temp_trans_id'];
		    $sno=$row['serial_no'];
			$pid=$row['prod_id'];	
 			$qty=$row['qty'];
		    $city=$row['city'];
			$batch=$row['batch_out'];
		    $cons_name=$row['vol'];
		    $gdn=$row['gdn'];
		    $out_dt=$row['exp'];
		    $out_dist=$row['dist'];
		    
	$sid=0; $vol=0;
	$query5=mysqli_query($con,"SELECT * FROM `product` WHERE `prod_desc`='$pid'")or die(mysqli_error($con));
		while ($row5=mysqli_fetch_array($query5))
		{ $sid=$row5['supplier_id'];   $vol=$row5['volume']; }
	echo $sid . $vol;
	if($vol > 0) {
$first=$qty/$vol;
$qt1=strtok($first, '.');

$whole = (int) $first;         //  5
$frac  = $first- (int) $first;  // .7
// echo "<br>";
// echo $whole . "<br>";
// echo $frac;

$loose = $frac*$vol;
//echo "<br>". $loose;

$totalblc=0;
$query1=mysqli_query($con,"SELECT *,sum(`out_blc`) as totalblc FROM `location_control` WHERE `batch_id`='$batch' and `prod_id`='$pid'")or die(mysqli_error());
while($row1=mysqli_fetch_array($query1)){ $totalblc=$row1['totalblc'];   } 

if($totalblc >= $qty){	    
			mysqli_query($con,"INSERT INTO `stockout`(`stockout_orderno`, `product_id`, `stockout_dnqty`, `branch_id`, `dealer_code`, `user_id`, `final`,vol,batch,qt1,stockout_loosedn,city,sup_id)
			                            VALUES('$sno','$pid','$qty','$branch', '$out_dist','$id','1','$vol','$batch','$whole','$loose','$city','$sid')")or die(mysqli_error($con));
			 mysqli_query($con,"DELETE FROM temp_trans_out where temp_trans_id='$rid'")	or die(mysqli_error($con));
                    }		
}}
		
		
		
		echo "<script>document.location='outward_transaction.php'</script>";  	
		
		
//	}	
?>