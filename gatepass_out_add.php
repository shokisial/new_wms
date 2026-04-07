<?php session_start();
if(empty($_SESSION['id'])):
header('Location:../index.php');
endif;
if(empty($_SESSION['branch'])):
header('Location:../index.php');
endif;
$branch=$_SESSION['branch']; $id=$_SESSION['id'];  

include('conn/dbcon.php');

   if(isset($_POST['optionlist'])){ 
  
   // $grn_no = $_POST['grn_no'];
	$trns_name= $_POST['trns_name'];
	$vehicle_no = $_POST['vehicle_no'];
	$seal_no = $_POST['seal_no'];
	$veh_size=$_POST['vehicle_type'];
	$veh_other=$_POST['veh_other'];
	$driver=$_POST['driver'];
    $cnic = $_POST['cnic'];
    $mobile=$_POST['mobile'];
    $bilty=$_POST['bilty'];
	$indate = $_POST['indate'];
	$outdate = $_POST['outdate'];
//	$gt_no=$_POST['gt_no'];
    $id=$_SESSION['id'];
    $gpsno=$_POST['gpsno'];
    $veh_temp=$_POST['veh_temp'];
    $item_temp=$_POST['item_temp'];
    $dock=$_POST['dock'];
    
  mysqli_query($con,"INSERT INTO `gatepass_out`(`trns_name`, `cnic`, `driver`, `vehicle_no`, rptdate, `user_id`,veh_size,mobile,bilty,seal_no,indate,outdate,dn_no,branch_id,veh_other,out_seq,veh_temp,item_temp,dock_out)
     VALUES ('$trns_name','$cnic','$driver','$vehicle_no','0','$id','$veh_size','$mobile','$bilty','$seal_no','$indate','$outdate','$grn_no','$branch','$veh_other','$gpsno','$veh_temp','$item_temp','$dock')")or die(mysqli_error($con));
    
  $gid=0;
	$query2=mysqli_query($con,"SELECT gatepass_id FROM `gatepass_out` ")or die(mysqli_error());
	while($row2=mysqli_fetch_array($query2)){ $gid=$row2['gatepass_id']; } echo  $gid;
   
    $grn_no=0;
    
    foreach($_POST["grn_no"] as $rec=> $value){
    $grn_no = $_POST["grn_no"][$rec];
    $dt1=date("Y/m/d"); $dt2=date("Y/m/d");
  mysqli_query($con,"UPDATE `stockout` SET gatepass_id='$gid',dn_no='$dt2',final='0',route_truckno='$vehicle_no' WHERE `stockout_orderno`='$grn_no'") or die(mysqli_error($con));	 } }
  
   

	
            
    // if($gt_no===0){
    //     echo 'Invalid Gate Pass No!';
    //     history.back();
    // }

	 
    

//	mysqli_query($con,"UPDATE `stockout` SET `gatepass_id`='$gid',final='0' , route_truckno='$vehicle_no' WHERE `stockout_deliveryno`='$grn_no'")or die(mysqli_error($con));

$qtr=0; $wh_qty=0; $ls_qty=0; $first=0; $whole=0; $loose=0; $whole_blc=0; $loose_blc=0; $stid=0;
	$query3=mysqli_query($con,"SELECT * FROM `stockout` WHERE `gatepass_id`='$gid'")or die(mysqli_error());
	while($row3=mysqli_fetch_array($query3)){ $stid=$row3['stockout_id']; $qtr=$row3['stockout_qty']; $wh_qty=$row3['qt1']; $ls_qty=$row3['stockout_loosedn']; $vol=$row3['uom3'];

	if($qtr > 0) {
$first=$qtr/$vol;
$qt1=strtok($first, '.');

$whole = (int) $first;         //  5
$frac  = $first- (int) $first;  // .7
$loose = $frac*$vol;
}    

$whole_blc=$wh_qty-$whole; $loose_blc=$ls_qty-$loose;
 mysqli_query($con,"UPDATE `stockout` SET `pack_deliver`='$whole',`pack_balance`='$whole_blc',`stockout_loosedel`='$loose',`stockout_looseblc`='$loose_blc' where `stockout_id`='$stid'")or die(mysqli_error($con));
	} 



	echo "<script type='text/javascript'>alert('Successfully added new Gate Pass! $gid');</script>";
	echo "<script>document.location='gatepass_out.php'</script>";  
		
?>