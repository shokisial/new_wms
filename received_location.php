<?php session_start();
if(empty($_SESSION['id'])):
header('Location:../index.php');
endif;
if(empty($_SESSION['branch'])):
header('Location:../index.php');
endif;
$branch=$_SESSION['branch']; $uid=$_SESSION['id'];  



include('conn/dbcon.php');
	$qtid2 = $_POST['id'];
	$doc_no =$_POST['doc_no'];
	$rec =$_POST['rec'];
	$loc =$_POST['location'];
	
	$location0=0; $query0=mysqli_query($con,"SELECT * FROM `locations` WHERE `stock_location`='$loc' and unit='$branch'")or die(mysqli_error());
	while($row0=mysqli_fetch_array($query0)){ $location0=$row0['location']; }
		
 	if($location0===0 or $location0 === '') { echo 'Location Not Found, Scan Location' ; 
	    
 	echo "<a href=\"javascript:history.go(-1)\">GO BACK</a>";    
 	}
	echo 'id='.$qtid2 . 'doc='.$doc_no . 'recvied='.$rec . 'Expiry='.$expiry . 
	'condition='.$cond . 'cond_qty='.$condqty; $pid='0'; $batch='0';
	$qty=0; $asn_qty=0; $dmg=0; $blc=0; $rc_qt=0; $rc_loc=0; $rc_tot=0; $mfg=0;
	//INNER JOIN product on product.prod_desc=stockin.prod_id
	$query=mysqli_query($con,"SELECT * FROM `stockin` where stockin.stockin_id='$qtid2' and stockin.rec_date >'0' ")or die(mysqli_error());
		while($row=mysqli_fetch_array($query)){ $qty=$row['qty']; 
    $pid=$row['prod_id']; $batch=$row['batch'];	$supplier=$row['shipper_id'];
    $location=$row['location']; $uom2=$row['uom2']; $expiry=$row['expiry']; $rc_qt=$row['qty']; $rc_loc=$row['location']; $rc_loc=$rc_loc+$rec;
		    $rc_tot=$rc_qt-$rc_loc; $mfg=$row['mfg'];
		}
    
    
    if ($rc_tot<'0' )
		{
		    $_SESSION['sub1'] = $doc_no;
			echo "<script type='text/javascript'>alert('Recieved QTY Less Than Location!');</script>";
			echo "<script>document.location='final_location_ok.php'</script>";  
			
		}
		else
		{	
		    
	$rec1=$rec+$location; 
	
	$first=0; 
        $first=$rec/$uom2;
        $qt1=strtok($first, '.');
        
        $whole = (int) $first;         //  5
        $frac  = $first- (int) $first;  // .7

        $loose = $frac*$uom2;
      $rmn_pk=$packavb-$whole;  
      $loosblc=$looseavb-$loose;
   // $whole=$rc_qt/$uom2;
	$date = date('Y/m/d H:i:s');
	$date1 = date('Y/m/d');
        $uid=$id; 
    
    echo 'uid = '. $uid . 'qty = '. $rec1 . 'asn_blc =' . $blc . 'cond = '. $cond . 'Cond_qty = ' . $dmg1 . 'date = '. $date .'Exp= '. $expiry . $rec1 . 'PID= ' . $pid . 'Batch = ' . $batch;  
   //   if($pid>'0' and $batch>'0'){ 
        mysqli_query($con,"UPDATE `stockin` SET location='$rec1' WHERE `stockin_id`='$qtid2'") or die(mysqli_error($con));

$_SESSION['sub1'] = $doc_no; $bl=0;

if($loc==='RMFD'){ $bl=$rec; $rec=0; }
 mysqli_query($con,"INSERT INTO `location_control`(`st_id`, `batch_id`, `prod_id`, `qty`, `pack`, `loose_lec`, `user_id`, `location_expiry`, `location_in`, `supplier_id`, `dat`, `out_blc`, `stock_location`, `branch_id`,rec_no,loc_dat,loc_qty,loc_userid,mfg_dat,block) 
 VALUES ('$qtid2','$batch','$pid','0','$whole','$loose','$uid','$expiry','0','$supplier','$date','$rec','$loc','$branch','$doc_no','$date1','$rec','$uid','$mfg','$bl') ")or die(mysqli_error($con));
  //   }
	
// 	echo "<script type='text/javascript'>alert('Successfully updated Transporter details!');</script>";
 	echo "<script>document.location='final_location_ok.php'</script>";  
}
	
?>
