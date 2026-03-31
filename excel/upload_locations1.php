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

 $studentQuery = "INSERT INTO `location_temp`(`batch_id`, `prod_id`, `location`, `st_qty`, `dat`, `picking_user`) 
 VALUES ('$a','$b','$c','$d','$e','$f')";

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