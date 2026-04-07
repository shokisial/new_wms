<?php session_start();
if(empty($_SESSION['id'])):
header('Location:../index.php');
endif;
if(empty($_SESSION['branch'])):
header('Location:../index.php');
endif;
$branch=$_SESSION['branch']; $id=$_SESSION['id'];  

include('conn/dbcon.php');

	$grn_no1 = $_POST['grn_no'];
	
	$indate = $_POST['indate'];
	$outdate = $_POST['outdate'];
    $veh_size=$_POST['vehicle_type'];
    $mobile=$_POST['mobile'];
	$remarks=$_POST['remarks'];
    $gtno=$_POST['gtno'];
	
    $id=$_SESSION['id']; 
	//$id=1;
            
    
	
	

	//$chk="";  $grn5=""; $grn4="";
	//foreach($grn_no1 as $chk1)  
	 //  {  
	//	$grn_no = $chk1;  
	//	$grn4=$grn_no;
	///	$grn5=$grn5 . ',' . $grn4;
	
	

	
	//	echo 'Asno No = ' . $grn_no ." ";

//mysqli_query($con,"UPDATE `stockin` SET `gatepass_id`='$gid' WHERE `rec_dnno`='$grn_no'")or die(mysqli_error($con));
//$grn_no2=$grn2 . ',' . $grn_no;

//$query=mysqli_query($con,"SELECT * FROM `stockin` WHERE `final`='1' and `gatepass_id`='$gtno' GROUP by `rec_dnno`")or die(mysqli_error());
//$act=0;
//while($row=mysqli_fetch_array($query)){ $act=$row['rec_dnno']; $act1=$act1 .','. $act; }


mysqli_query($con,"UPDATE `gatepass` SET indate='$indate',outdate='$outdate' WHERE `gatepass_id`='$gtno'")or die(mysqli_error($con));		
	echo "<script type='text/javascript'>alert('Successfully Update Gate Pass! $gtno');</script>";
	
	echo "<script>document.location='gatepass.php'</script>";  
	
?>