<?php 
session_start();
$id=$_SESSION['id'];
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
                $f = $row['5'];
                $g = $row['6'];
                $h = $row['7'];
                $i = $row['8'];
        
            
        $qt=0; $qt1=0; $pid=0; $id=0; $id=$_SESSION['id'];
	
	echo $a . '' .$b . ''. $c. '' . $d . ''. $e .''. $f .''. $g .''. $h.''.$i;
                
    // mysqli_query($con,"UPDATE `stockout` SET `user_id`='$id' WHERE `stockout_id`='$a'")or die(mysqli_error($con));

                $msg = true;
            } 
//}
            else
            {
                $count = "1";
            }
        }


        if(isset($msg))
        {
            $_SESSION['message'] = "Successfully Imported";
            header('Location: outbound_index.php');
            exit(0);
        }
        else
        {
            $_SESSION['message'] = "Data Already Exist";
            header('Location: outbound_index.php');
            exit(0);
        }
    }
    else
    {
        $_SESSION['message'] = "Invalid File";
        header('Location: outbound_index.php');
        exit(0);
    }
}

?>