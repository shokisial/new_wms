<?php 
session_start();
$id=$_SESSION['id'];
$branch=$_SESSION['branch'];
include('dbconfig.php');

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if(isset($_POST['save_lp_data']))
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
                $f = $row['5'];
                $g = $row['6'];
                $h = $row['7'];
              //  $i = $row['8'];
$vol=0; $first=0;          
$query=mysqli_query($con,"SELECT * FROM `product` WHERE `prod_desc`='$a'")or die(mysqli_error());
while($row=mysqli_fetch_array($query)){ $vol=$row['volume'];   } 
$vol1=$vol*$a;

 
$first=$f/$vol;
$qt1=strtok($first, '.');

$whole = (int) $first;         //  5
$frac  = $first- (int) $first;  // .7
// echo "<br>";
// echo $whole . "<br>";
// echo $frac;

$loose = $frac*$vol;
//echo "<br>". $loose;

// $query1=mysqli_query($con,"SELECT * FROM `supplier` WHERE `supplier_name`='$i'")or die(mysqli_error());
// while($row1=mysqli_fetch_array($query1)){ $sid=$row1['supplier_id'] ;  } 

//$studentQuery = "INSERT INTO `location_control`(`batch_id`, `prod_id`, `qty`, `pack`, `loose_lec`, `location`, `user_id`, `location_expiry`, `out_blc`, `supplier_id`)
//VALUES ('$c','$a','$f','$whole','$loose','$d','$id', '$g', '$f','$e')";

/*
$studentQuery = "INSERT INTO `product`(`prod_name`, `prod_desc`, `prod_qty`, `packet`, `branch_id`, `user_id`, `category`, `uom`, `weight`, `volume`, `sno`, `zone`, `batch_no`) VALUES ('$a','$b','$c','$d','1','1','0','0','0','$e','0','0','0')";
*/

$studentQuery = "INSERT INTO `batch_record`(`recorditem_id`, `record_batch`, `record_package`, `record_unit`, `batch_loose`, `record_status`, `record_qty`, `leak`, `scrubed`, `expire`, `record_expiry`, `branch_id`, `supplier_id`)
VALUES ('$a','$b','$whole','$f','$loose','good','0','0','0','0','$e','3','$d')";

 
  $result = mysqli_query($con, $studentQuery);
                
  
                $msg = true;
            } 
            else
            {
                $count = "1";
            }
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
echo "<script>document.location='https://sovereign-wh.com/AdminLTE/pages/tables/category.php'</script>"; 
?>