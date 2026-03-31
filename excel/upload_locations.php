<?php 
session_start();
$id=$_SESSION['id'];
$branch=$_SESSION['branch'];
include('dbconfig.php');
include('conn/dbcon.php');
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
               
$vol=0; $first=0;    $sid=0; $date = date('Y/m/d H:i:s'); $qtid2=0; $avb=0; 
$query=mysqli_query($con,"SELECT * FROM `stockin` WHERE `rec_dnno`='$a' and `prod_id`='$b' and `batch`='$d' and location < qty")or die(mysqli_error());
while($row=mysqli_fetch_array($query)){ $vol=$row['uom2'];  $sid=$row['shipper_id']; $qtid2=$row['stockin_id']; $expiry=$row['expiry'];  $avb=$row['location']; } 
//$vol1=$vol*$c;

if($vol > 0) {
$first=$f/$vol;
$qt1=strtok($first, '.');

$whole = (int) $first;         //  5
$frac  = $first- (int) $first;  // .7
// echo "<br>";
// echo $whole . "<br>";
// echo $frac;

$loose = $frac*$vol;
//echo "<br>". $loose;
}
if($sid>0){
 $studentQuery = "INSERT INTO `location_control`(`st_id`, `batch_id`, `prod_id`, `qty`, `pack`, `loose_lec`, `user_id`, `location_expiry`, `location_in`, `supplier_id`, `dat`, `out_blc`, `stock_location`, `branch_id`) 
 VALUES ('$qtid2','$d','$b','0','$whole','$loose','$uid','$expiry','0','$sid','$date','$f','$e','$branch')";
$ab=0; $ab=$avb+$f;

 mysqli_query($con,"UPDATE `stockin` SET location='$ab' WHERE `stockin_id`='$qtid2'") or die(mysqli_error($con));

    
 }


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
echo "<script>document.location='https://sovereign-wh.com/AdminLTE/pages/tables/final_location.php'</script>"; 
?>