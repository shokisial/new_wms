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
	$date = date("d/m/Y");
	//$cid=$_REQUEST['cid'];
	
	$rec_dnno=$_POST['rec_dnno'];
	//$truckno=$_POST['truckno'];
	

	// $query2=mysqli_query($con,"select * from stockin where rec_dnno='$rec_dnno' and branch_id='$branch'")or die(mysqli_error($con));
	// 	$count=mysqli_num_rows($query2);

	/*	if ($count>0)
		{
			echo "<script type='text/javascript'>alert('P.T.N $rec_dnno already exist!');</script>";
			echo "<script>document.location='inward_transaction.php'</script>";  
		}
		else
		{	*/

	$sales_id=mysqli_insert_id($con);
	$_SESSION['sid']=$sales_id;
	$query=mysqli_query($con,"select * from temp_trans where branch_id='$branch'")or die(mysqli_error($con));
		while ($row=mysqli_fetch_array($query))
		{
			$pid=$row['prod_id'];	
			$uom1=$row['uom1'];	
 			$f=$row['qty'];
			$batch=$row['batch'];
			$po_no=$row['po_no'];
			$rec_id=$row['temp_trans_id'];
			$expiry=0;
		
			$vol=0; $first=0;    $sid=0; $date = date('Y/m/d'); 
			$query1=mysqli_query($con,"SELECT * FROM `product` WHERE `prod_desc`='$pid'")or die(mysqli_error());
			while($row1=mysqli_fetch_array($query1)){ $vol=$row1['volume'];  $sid=$row1['supplier_id']; } 
			//$vol1=$vol*$c;
			
			if($vol > 0) {
			$first=$f/$vol;
			$qt1=strtok($first, '.');
			
			$whole = (int) $first;         //  5
			$frac  = $first- (int) $first;  // .7
			// echo "<br>";
			// echo $whole . "<br>";
			// echo $frac;
			
			$loose = $frac*$vol;
			//echo "<br>". $loose;
			
			$trns=0; $truck=0;
			$query2=mysqli_query($con,"SELECT * FROM `stockin` WHERE `rec_dnno`='$po_no'")or die(mysqli_error());
			while($row2=mysqli_fetch_array($query2)){ $trns=$row2['transporter'];  $truck=$row2['truck_no']; } 
			

			$studentQuery = mysqli_query($con,"INSERT INTO `stockin`(`rec_dnno`, `prod_id`, `pack`, `date`,`user_id`, `asn_qty`, `uom2`,final,batch,truck_no,transporter,loose,branch_id,shipper_id) 
			VALUES ('$po_no','$pid','$whole','$date','$id', '$f','$vol','1','$batch','$truck','$trns','$loose','$branch','$sid')") or die(mysqli_error($con));;
			
			//mysqli_query($con,"INSERT INTO `stockin`(`rec_dnno`, `prod_id`, `asn_qty`, `date`, `branch_id`, `final`, `whcode`, `rec_point`, `remarks`,serial_no,user_id,batch,expiry,uom2,pack) VALUES('$rec_dnno','$pid','$qty','$date','$branch', '1','Store 14 E','S-14','0','$sno','$id','$batch','$expiry','$uom1','$qt1')")or die(mysqli_error($con));
			
		//	mysqli_query($con,"UPDATE product SET prod_qty=prod_qty+'$qty' where prod_id='$pid' and branch_id='$branch'") or die(mysqli_error($con)); 
		
		
		$result=mysqli_query($con,"DELETE FROM temp_trans where branch_id='$branch' and temp_trans_id='$rec_id'")	or die(mysqli_error($con));
	}
	else
	{
		echo '$pid Not Found Product';

	}

	}
		
		echo "<script>document.location='inward_transaction.php'</script>";  	
//	}
		
	
?>