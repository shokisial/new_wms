<?php 
session_start();
$branch=$_SESSION['branch'];
include('conn/dbcon.php');

	$name = $_POST['supplier_name'];
	$address = $_POST['supplier_address'];
	$phone= $_POST['phone'];
	$email = $_POST['email'];
	$contact = $_POST['contact'];
	$dept = $_POST['dept'];
	$emgphone = $_POST['emgphone'];
	$tax = $_POST['tax'];
	$days = $_POST['days'];
	
	$query2=mysqli_query($con,"select * from supplier where supplier_name='$name'")or die(mysqli_error($con));
		$count=mysqli_num_rows($query2);

		if ($count>0)
		{
			echo "<script type='text/javascript'>alert('Customer already exist!');</script>";
			echo "<script>document.location='supplier.php'</script>";  
		}
		else
		{	

/*			$pic = $_FILES["image"]["name"];
			if ($pic=="")
			{
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
            $id=$_SESSION['id'];
            
			mysqli_query($con,"INSERT INTO `supplier`(`supplier_name`, `supplier_email`, `supplier_contact`, `supplier_address`,`contact_name`, `contact_dept`, `contact_emrg`, `contact_taxno`, `contact_pdate`, `user_id`, `branch_id`)
												VALUES('$name','$email','$phone','$address','$contact','$dept','$emgphone','$tax','$days','$id','$branch')")or die(mysqli_error($con));

mysqli_query($con,"INSERT INTO `history_log`(`user_id`, `action`, `branch_id`) 
VALUES ('$id','Add new Customer $name','$branch')")or die(mysqli_error($con));


			echo "<script type='text/javascript'>alert('Successfully added new Customer!');</script>";
					  echo "<script>document.location='supplier.php'</script>";  
		}
?>
	