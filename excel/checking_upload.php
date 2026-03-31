<?php 
session_start();
$id=$_SESSION['id'];
$branch=$_SESSION['branch'];
include('dbconfig.php');
mysqli_query($con,"UPDATE `count_racktbl` SET `final`='0' WHERE `final`='1' and branch_id='$branch'")or die(mysqli_error($con));

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
                
 date_default_timezone_set("Asia/Karachi");             
 $dat = date('Y/m/d H:i:s');

 $studentQuery = "INSERT INTO `count_racktbl`(`rack`, `dat`, `final`, `branch_id`, `user_id`) VALUES ('$a','$dat','1','$branch','$id')";
 


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

$remarks="Upload Location Checking on ";  
		mysqli_query($con,"INSERT INTO history_log(user_id,action,date,branch_id) VALUES('$id','$remarks','$date','$branch')")or die(mysqli_error($con));

mysqli_query($con,"UPDATE `location_control` SET `count`='0' WHERE `count`='1' and branch_id='$branch'")or die(mysqli_error($con));


echo 'File Uload Sucessfull';
echo "<script>document.location='https://sovereign-wh.com/AdminLTE/pages/tables/stockcount_int.php'</script>"; 
?>