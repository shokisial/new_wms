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
                $d = $row['3'];
                
$qt=0; $qt1=0; $dt=date('d/m/Y');
$query=mysqli_query($con,"SELECT * FROM `product` WHERE `prod_id`='$a'")or die(mysqli_error());
while($row=mysqli_fetch_array($query)){ $qt=$row['prod_qty']; $qt1=$qt-$d; } 
                       
                
 $studentQuery = "INSERT INTO `cyclecount`(`cyclcountitem_id`, `cyclcount_qty`, `cyclcount_ava`, `cyclcount_balance`, `Remarks`, `cyclcount_dat`, `cyclcount_user`,'cyclcount_branch')
                                     VALUES ('$a','$qt','$d','$qt1', '','$dt','$id','$branch')";
                $result = mysqli_query($con, $studentQuery);
                
  
                $msg = true;
            } 
            else
            {
                $count = "1";
            }
        }


        if(isset($msg))
        {
            $_SESSION['message'] = "Successfully Imported";
            header('Location: ../index_stockrpt.php');
            exit(0);
        }
        else
        {
            $_SESSION['message'] = "LP Already Exist";
            header('index_stockrpt.php');
            exit(0);
        }
    }
    else
    {
        $_SESSION['message'] = "Invalid File";
        header('Location: index_stockrpt.php');
        exit(0);
    }
}

?>