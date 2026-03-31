<?php 
session_start();
$id=$_SESSION['id'];
$branch=$_SESSION['branch'];
include('dbconfig.php');
mysqli_query($con,"DELETE FROM `product_error` WHERE `branch_id`='$branch'")or die(mysqli_error());
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
                 $i = $row['8'];

                // $j = $row['9']; 
                // $k = $row['10'];
                // $l = $row['11'];
                // $m = $row['12'];
                // $n = $row['13'];
                // $o = $row['14'];
                // $p = $row['15'];
                // $q = $row['16'];
                // $r = $row['17'];
                date_default_timezone_set("Asia/Karachi");
                $sid=0;   $date = date('Y/m/d');       

 $studentQuery = "INSERT INTO `product`(`prod_name`, `prod_desc`, `branch_id`, `user_id`, `uom`, `volume`, `supplier_id`, `shelf_lifewh`,storage_condition,weight,sno)
  VALUES ('$a','$b','1','$id','$d','$c','$e','$f','$g','$h','$i')";

// $studentQuery = "UPDATE `product` SET `prod_desc`='$a' WHERE `prod_desc`='$b' and branch_id='1'";

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
echo "<script>document.location='product.php'</script>"; 
?>