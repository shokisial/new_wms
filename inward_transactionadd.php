<?php session_start();
if(empty($_SESSION['id'])):
header('Location:../index.php');
endif;
if(empty($_SESSION['branch'])):
header('Location:../index.php');
endif;
$branch=$_SESSION['branch']; $id=$_SESSION['id'];  

include('conn/dbcon.php');

	$cid = $_POST['cid'];
	$name = $_POST['prod_name'];
	$qty = $_POST['qty'];
	$sno = $_POST['sno'];
	$batch = $_POST['batch'];
	$po_no = $_POST['asn'];
    $sup = $_POST['sup'];
	
	$date=date_create("$expiry");
    $exp1= date_format($date,"d/m/Y");

	$query=mysqli_query($con,"SELECT * FROM `product` WHERE `prod_desc`='$name' and branch_id='$branch'")or die(mysqli_error());
			$grand=0; $prod_id=0;
		while($row=mysqli_fetch_array($query)){ $grand=$row['volume'];  }
	
// 		//$po_no=0;
// 		$query1=mysqli_query($con,"SELECT * FROM `stockin` WHERE branch_id='$branch'")or die(mysqli_error());
// 		while($row1=mysqli_fetch_array($query1)){ $po_no=$row1['stockin_id'];  }
	//$qty1 = $qty*$grand;		
			
	/*	
		$query1=mysqli_query($con,"select * from temp_trans where prod_id='$name' ")or die(mysqli_error());
		$count=mysqli_num_rows($query1);
		
		//$total=$price*$qty;
		
		if ($count>0){
	//		mysqli_query($con,"update temp_trans set qty=qty+'$qty1' where //prod_id='$name' ")or die(mysqli_error());
	
		}
		else{
		
		*/
			mysqli_query($con,"INSERT INTO temp_trans(prod_id,uom1,qt1,qty,branch_id,batch,po_no,supplier_id) VALUES('$name','$grand','$qty','$qty','$branch','$batch','$po_no','$sup')")or die(mysqli_error($con));
	//	}

	
		echo "<script>document.location='inward_transaction.php'</script>";  
	
?>