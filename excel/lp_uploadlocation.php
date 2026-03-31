<?php 
session_start();
$id=$_SESSION['id'];
$branch=$_SESSION['branch'];
include('dbconfig.php');

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if(isset($_POST['save_out_data']))
{
    $fileName = $_FILES['import_file']['name'];
    $file_ext = pathinfo($fileName, PATHINFO_EXTENSION);

    $allowed_ext = ['xls','csv','xlsx'];

    if(in_array($file_ext, $allowed_ext))
    {
        $inputFileNamePath = $_FILES['import_file']['tmp_name'];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileNamePath);
        $data = $spreadsheet->getActiveSheet()->toArray();

        $count = "0"; 
        foreach($data as $row)
        { 
            if($count > 0)
            {
                $a = $row['0']; 
                $b = $row['1'];
                $c = $row['2'];
                $d = $row['3'];
                $e = $row['4'];
                // $f = $row['5'];
                // $g = $row['6'];
            //     $h = $row['7'];
            //   //  $i = $row['8'];
$vol=0; $first=0;          

// $query2=mysqli_query($con,"select * from stockin where stockin_id='$a' and branch_id='$branch'")or die(mysqli_error($con));
// 		$count=mysqli_num_rows($query2);

// 		if ($count>0)
// 		{
			
// $query3=mysqli_query($con,"select * from zone where zone_name='$d' and branch_id='$branch'")or die(mysqli_error($con));
// $count=mysqli_num_rows($query3);
    
// if ($count>0)
//     {

// $query1=mysqli_query($con,"SELECT * FROM `stockin` WHERE `stockin_id`='$a' ")or die(mysqli_error());
// while($row1=mysqli_fetch_array($query1)){ $vol=$row1['uom2'] ;  $qt=$row1['qty'];} 
//$vol1=$vol*$c;
//$vol=$row['volume'];  
 
// $query1=mysqli_query($con,"SELECT * FROM `product` WHERE `prod_desc`='$a' ")or die(mysqli_error());
// while($row1=mysqli_fetch_array($query1)){ $vol=$row1['volume'] ;  } 


// $first=$c/$vol;
// $qt1=strtok($first, '.');

// $whole = (int) $first;         //  5
// $frac  = $first- (int) $first;  // .7
// // echo "<br>";
// // echo $whole . "<br>";
// // echo $frac;

// $loose = $frac*$vol;
// //echo "<br>". $loose;


// $dts=0;
// date_default_timezone_set("Asia/Karachi");
// $dts = date("Y/m/d");


// $studentQuery = "INSERT INTO `location_control`(`st_id`, `batch_id`, `qty`, `pack`, `loose_lec`, `location`,user_id,location_expiry,out_blc,supplier_id,dat,prod_id)
//  VALUES ('$a','$b','$c','$whole','$loose','$d','$id','$e','$c','$f','$dts','$g')";

// mysqli_query($con,"UPDATE `stockin` SET `zone`='0',`expiry`='$e' WHERE `batch`='$b' and `stockin_id`='$a'")or die(mysqli_error($con));  

// mysqli_query($con,"UPDATE `batch_record` SET `record_expiry`='$e' WHERE `record_batch`='$b'")or die(mysqli_error($con));  

/*
$studentQuery = "INSERT INTO `product`(`prod_name`, `prod_desc`, `prod_qty`, `packet`, `branch_id`, `user_id`, `category`, `uom`, `weight`, `volume`, `sno`, `zone`, `batch_no`) VALUES ('$a','$b','$c','$d','1','1','0','0','0','$e','0','0','0')";

$studentQuery = "INSERT INTO `batch_record`(`recorditem_id`, `record_batch`, `record_package`, `record_unit`, `batch_loose`, `record_status`, `record_qty`, `leak`, `scrubed`, `expire`, `record_expiry`, `branch_id`, `supplier_id`)
                                VALUES ('$a','$b','$whole','$c','$loose','good','0','0','0','0','$d','3','$e')";
*/
$studentQuery = "INSERT INTO `locations`(`stock_location`, `conditions`, `type`, `exiting`, `unit`)
VALUES ('$b','$c','$d','$e','$a')";

 
  $result = mysqli_query($con, $studentQuery);
                
  
                $msg = true;
            } 
            else
            {
                $count = "1";
            }
   
     //       }}    
         }

/*
        if(isset($msg))
        {
            $_SESSION['message'] = "Successfully Imported";
            header('Location: lp_index.php');
            exit(0);
        }
        else
        {
            $_SESSION['message'] = "LP Already Exist";
            header('lp.php');
            exit(0);
        }
    }
    else
    {
        $_SESSION['message'] = "Invalid File";
        header('Location: lp_index.php');
        exit(0);
        */
    }
}

echo 'File Uload Sucessfull';
echo "<script>document.location='http://localhost/sovereign_wms/AdminLTE/pages/tables/index_location.php'</script>"; 
?>