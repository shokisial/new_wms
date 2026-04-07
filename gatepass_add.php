<?php session_start();
if(empty($_SESSION['id'])):
header('Location:../index.php');
endif;
if(empty($_SESSION['branch'])):
header('Location:../index.php');
endif;
$branch=$_SESSION['branch']; $id=$_SESSION['id'];  


$currentDateTime = date('Y/m/d H:i:s');
echo "Current date and time: " . $currentDateTime;

include('conn/dbcon.php');

	$grn_no1 = $_POST['grn_no'];
	
	if($grn_no1===''){
	    echo 'Please Select One P.T.N';
	    exit();
	    windows.goback();
	}
	else
	{
	$trns_name= $_POST['trns_name'];
	$cnic = $_POST['cnic'];
    $driver=$_POST['driver'];
	$vehicle_no = $_POST['vehicle_no'];
	$rptdate = $_POST['rptdate'];
	$indate = $_POST['indate'];
	$outdate = $_POST['outdate'];

    $veh_size=$_POST['vehicle_type'];
    $veh_other=$_POST['veh_other'];
    $mobile=$_POST['mobile'];
	$remarks=$_POST['remarks'];
	$bilty=$_POST['bilty'];
	$seal=$_POST['seal'];
	$pepsi_gpass=$_POST['pepsi_gpass'];
	$item_temp=$_POST['item_temp'];
	$veh_temp=$_POST['veh_temp'];
	$dock=$_POST['dock'];
    $id=$_SESSION['id']; 
	//$id=1;
    $shipper = $_POST['shipper'];   
    
    if($trns_name===''){echo 'Fill all Fields'; 
        exit();
	    windows.goback();
    } 
    else {
	mysqli_query($con,"INSERT INTO `gatepass`(`grn_no`, `trns_name`, `cnic`, `driver`, `vehicle_no`, rptdate , `user_id`,veh_size,mobile,remarks,branch_id,item_temp,veh_temp,bilty,veh_other,seal,dock)
     VALUES ('0','$trns_name','$cnic','$driver','$vehicle_no','$rptdate','$id','$veh_size','$mobile','$remarks','$branch','$item_temp','$veh_temp','$bilty','$veh_other','$seal','$dock')")or die(mysqli_error($con));


$gid=0;
   $query2=mysqli_query($con,"SELECT gatepass_id FROM `gatepass` ")or die(mysqli_error());
   while($row2=mysqli_fetch_array($query2)){ $gid=$row2['gatepass_id']; } echo  $gid;

   $grn_no="";  $grn_no2="";
   foreach($grn_no1 as $chk1)  
	  {  
	   $grn_no = $chk1;  
		
   
   //	echo 'Asno No = ' . $grn_no ." ";

mysqli_query($con,"UPDATE `stockin` SET `gatepass_id`='$gid' WHERE `rec_dnno`='$grn_no' and final='1' and branch_id='$branch'")or die(mysqli_error($con));
	  }
	  
	  $act=0; $act1="";  
   $query=mysqli_query($con,"SELECT * FROM `stockin` WHERE `final`='1' and `gatepass_id`='$gid' GROUP by `rec_dnno`")or die(mysqli_error());
	 
	 while($row=mysqli_fetch_array($query)){ $act=$row['rec_dnno']; $act1=$act; }
	  
	  
mysqli_query($con,"UPDATE `gatepass` SET `grn_no`='$act1' WHERE `gatepass_id`='$gid'")or die(mysqli_error($con));		
		  
   echo "<script type='text/javascript'>alert('Successfully added new Gate Pass! $gid');</script>";
   
   echo "<script>document.location='gatepass.php'</script>";  
	}

	$grn_no="";  $grn_no2="";
	foreach($grn_no1 as $chk1)  
	   {  
		$grn_no = $chk1;  
	     
	
	//	echo 'Asno No = ' . $grn_no ." ";

mysqli_query($con,"UPDATE `stockin` SET `gatepass_id`='$gid' WHERE `rec_dnno`='$grn_no'")or die(mysqli_error($con));
	   }
	   
	   $act=0; $act1="";  
	$query=mysqli_query($con,"SELECT * FROM `stockin` WHERE `final`='1' and `gatepass_id`='$gid' GROUP by `rec_dnno`")or die(mysqli_error());
	  
	  while($row=mysqli_fetch_array($query)){ $act=$row['rec_dnno']; $act1=$act; }
	   
	   
mysqli_query($con,"UPDATE `gatepass` SET `grn_no`='$act1' WHERE `gatepass_id`='$gid'")or die(mysqli_error($con));


$remarks="Inward Gate Pass # $gid Generated on  ";  
		mysqli_query($con,"INSERT INTO history_log(user_id,action,date,branch_id) VALUES('$id','$remarks','$currentDateTime','$branch')")or die(mysqli_error($con));

		   
	echo "<script type='text/javascript'>alert('Successfully added new Gate Pass! $gid');</script>";
	}	
	echo "<script>document.location='gatepass.php'</script>";  
	

?>