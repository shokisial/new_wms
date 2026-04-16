<?php session_start();
if(empty($_SESSION['id'])):
header('Location:../index.php');
endif;

include('conn/dbcon.php');
$branch=$_SESSION['branch'];
$id=$_SESSION['id'];



	$name = $_POST['supplier_name'];
	$address = $_POST['supplier_address'];
	$phone= $_POST['phone'];
	$email = $_POST['email'];
	$contact = $_POST['contact'];
	$dept = $_POST['dept'];
	$emgphone = $_POST['emgphone'];
	$tax = $_POST['tax'];
	$days = $_POST['days'];
	$sid = $_POST['id'];
	
	/*$pic = $_FILES["image"]["name"];
			if ($pic=="")
			{	
				if ($_POST['image1']<>""){
					$pic=$_POST['image1'];
				}
				else
					$pic="default.gif";
			}
			else
			{
				$pic = $_FILES["image"]["name"];
				$type = $_FILES["image"]["type"];
				$size = $_FILES["image"]["size"];
				$temp = $_FILES["image"]["tmp_name"];
				$error = $_FILES["image"]["error"];
			
				if ($error > 0){
					die("Error uploading file! Code $error.");
					}
				else{
					if($size > 100000000000) //conditions for the file
						{
						die("Format is not allowed or file size is too big!");
						}
				else
				      {
					move_uploaded_file($temp, "../dist/uploads/".$pic);
				      }
					}
			
			}
		*/
		
		

		mysqli_query($con,"UPDATE `supplier` SET `supplier_name`='$name',`supplier_email`='$email',
		`supplier_contact`='$phone',`supplier_address`='$address',`contact_name`='$contact',
		`contact_dept`='$dept',`contact_emrg`='$emgphone',`contact_taxno`='$tax',`contact_pdate`='$days' WHERE supplier_id='$sid' and branch_id='$branch'")or die(mysqli_error($con));
	
	mysqli_query($con,"INSERT INTO `history_log`(`user_id`, `action`, `date`, `branch_id`) 
VALUES ('$id','update Customer Record $sid','$branch')")or die(mysqli_error($con));

	 echo "<script type='text/javascript'>alert('Successfully updated Supplier details!');</script>";
	 echo "<script>document.location='Supplier.php'</script>";  

	
?>
