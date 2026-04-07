<?php session_start();
if(empty($_SESSION['id'])):
header('Location:../index.php');
endif;
if(empty($_SESSION['branch'])):
header('Location:../index.php');
endif;
$branch=$_SESSION['branch']; $id=$_SESSION['id'];  



include('conn/dbcon.php');
	$qtid2 = $_POST['id'];
	$doc_no =$_POST['doc_no'];
	$rec =$_POST['rec'];
	$mfg=$_POST['mfg'];
	$expiry =$_POST['expiry'];
	$cond =$_POST['cond'];
	$cond_qty =$_POST['condqty'];
	$access=$_POST['access'];
	
	if($cond_qty > 0){$cond='product Damaged' ; }
	//expiry=date_format($expiry,'Y/m/d');

//	echo 'id='.$qtid2 . 'doc='.$doc_no . 'recvied='.$rec . 'Expiry='.$expiry . 
//	'condition='.$cond . 'cond_qty='.$condqty;

	$qty=0; $asn_qty=0; $dmg=0; $blc=0; 
	
	$query=mysqli_query($con,"SELECT * FROM `stockin` where stockin_id='$qtid2' and final='1'")or die(mysqli_error());
		while($row=mysqli_fetch_array($query)){ $asn_qty=$row['asn_qty']; 
		$qty=$row['qty']; $dmg=$row['cond_qty']; $uom2=$row['uom2'];
		$packavb=$row['pack'];$looseavb=$row['loose']; $pid=$row['prod_id']; 
		$batch=$row['batch'];	$supplier=$row['shipper_id']; 	}
	$rec1=$rec+$qty+$access; 
	$dmg1=$dmg+$cond_qty; 
	$blc=$rec1-$asn_qty;
	
	$wh=0; $whr=0; $sp_id=0; $min_shelf=0; 
	$query0=mysqli_query($con,"SELECT * FROM `product` where prod_desc='$pid'")or die(mysqli_error());
		while($row0=mysqli_fetch_array($query0)){ $wh=$row0['weight']; 
		$sp_id=$row0['supplier_id']; $min_shelf=$row0['min_shelflife']; }
	
	$expiry = $_POST['expiry']; $min_shelf=$min_shelf-1;

    $today = new DateTime('today');
    $expiry_date = new DateTime($expiry);

$days = (int)$today->diff($expiry_date)->format('%r%a');

// Increase first
$min_shelf = (int)$min_shelf;

// Now check
if ($days <= $min_shelf) {
    echo "<script>
            alert('Expiry not OK. Only $days days remaining! Minimum required: $min_shelf days.');
            window.history.back();
          </script>";
    exit();
}


		
	$whr=$rec1*$wh;
	
	$first=0; 
        $first=$rec1/$uom2;
        $qt1=strtok($first, '.');
        
        $whole = (int) $first;         //  5
        $frac  = $first- (int) $first;  // .7

        $loose = $frac*$uom2;
        
        if($branch==='1'){ $whole=0; $whole=$rec1/$uom2; $whole=round($whole); $loose='0'; }


    if($sp_id==='55'){ $qtrr=0; $qtrr1=0;
      $query6=mysqli_query($con,"SELECT * FROM `rectbl_detail` where rec_id='$qtid2' and exp_dt='$expiry'")or die(mysqli_error());
		while($row6=mysqli_fetch_array($query6)){  $qtrr=$row6['rec_qty'];
		    $qtrr1=$qtrr+$rec;
		}
	
	if ($qtrr>0)
		{
		    
	mysqli_query($con,"UPDATE `rectbl_detail` SET `rec_qty`='$qtrr1' WHERE `rec_id`='$qtid2'") or die(mysqli_error($con));	    
		}
		else
		{ 
 mysqli_query($con,"INSERT INTO `rectbl_detail`(`rec_id`, `exp_dt`, `rec_qty`,mf_dt)
 VALUES ('$qtid2','$expiry','$rec','$mfg') ")or die(mysqli_error($con));
		}
 
} 
 
 
$rmn_pk=$packavb-$whole;  
      $loosblc=$looseavb-$loose;
	$date = date('Y/m/d');
	$tim  = date('H:i:s');
        $uid=$id; 
    
    
    // echo 'uid = '. $uid . 'qty = '. $rec1 . 'asn_blc =' . $blc . 'cond = '. $cond . 'Cond_qty = ' . $dmg1 . 'date = '. $date .'Exp= '. $expiry;  
      
      
        mysqli_query($con,"UPDATE `stockin` SET `rec_userid`='$uid',`qty`='$rec1',`asn_balance`='$blc', grn_no='$grno',cond='$cond',cond_qty='$dmg1',rec_date='$date',rec_tim='$tim',mfg='$mfg',expiry='$expiry',pack_rec='$whole',pack_remain='$rmn_pk',loose_rec='$loose',loose_blc='$loosblc',dt='$date',weightp='$wh',weight_rec='$whr' WHERE `stockin_id`='$qtid2'") or die(mysqli_error($con));
        
//       $rmn_pk=$packavb-$whole;  
//       $loosblc=$looseavb-$loose;
// 	$date = date('Y/m/d H:i:s');
//         $uid=$id; 
    
    
//     // echo 'uid = '. $uid . 'qty = '. $rec1 . 'asn_blc =' . $blc . 'cond = '. $cond . 'Cond_qty = ' . $dmg1 . 'date = '. $date .'Exp= '. $expiry;  
      
//         mysqli_query($con,"UPDATE `stockin` SET `rec_userid`='$uid',`qty`='$rec1',`asn_balance`='$blc', grn_no='$grno',cond='$cond',cond_qty='$dmg1',rec_date='$date',mfg='$mfg',expiry='$expiry',pack_rec='$whole',pack_remain='$rmn_pk',loose_rec='$loose',loose_blc='$loosblc' WHERE `stockin_id`='$qtid2'") or die(mysqli_error($con));

$_SESSION['sub1'] = $doc_no;

$record_package=0; $record_unit=0; $record_dmg=0;$record_loose=0; $record_batch1=0;
$query5=mysqli_query($con,"SELECT * FROM `batch_record` WHERE `recorditem_id`='$pid' and record_batch='$batch' and branch_id='$branch' and supplier_id='$supplier'")or die(mysqli_error());
       while($row5=mysqli_fetch_array($query5)){ $record_package=$row5['record_package']; $record_unit=$row5['record_unit']; $record_dmg=$row5['record_qty']; $record_loose=$row5['batch_loose'];
            $record_batch1=$row5['record_batch'];  $record_sitem=$row5['recorditem_id'];
          //  $record_src=$row5['expire'];
           
   
       $record_package1=$record_package1+$whole;  $record_unit1=$record_unit+$rec; $record_loose1=$record_loose+$loose;
       $rec_dmg=$record_dmg+$cond_qty;
       }
        
    
 if($record_unit>0 ){
   mysqli_query($con,"UPDATE `batch_record` SET `record_package`='$record_package1',record_unit='$record_unit1',record_qty='$rec_dmg',record_status='$cond',batch_loose='$record_loose1' WHERE `recorditem_id`='$pid' and record_batch='$batch'")or die(mysqli_error($con));  
 }
   
else {
    
//if($cond==='Good'){$cond='0';}
 mysqli_query($con,"INSERT INTO `batch_record`(`recorditem_id`, `record_package`, `record_unit`,record_batch,record_qty,record_status,record_expiry,batch_loose,branch_id,supplier_id) 
 VALUES ('$pid','$whole','$rec','$batch','$cond_qty','$cond','0','$record_loose','$branch','$supplier') ")or die(mysqli_error($con));
} 
 
	
// 	echo "<script type='text/javascript'>alert('Successfully updated Transporter details!');</script>";
 	echo "<script>document.location='final_barcode_asn1.php'</script>";  

	
?>
