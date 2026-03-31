<?php 
session_start();
$id=$_SESSION['id'];
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
                
//  $vol=0; $pid=0;
//  $query=mysqli_query($con,"SELECT * FROM `product` WHERE `prod_desc`='$b'")or die(mysqli_error());
// while($row=mysqli_fetch_array($query)){ $pid=$row['prod_id'] ;
// $vol=$row['volume'];   } 

// $first=0; 
//         $first=$f/$vol;
//         $qt1=strtok($first, '.');
        
//         $whole = (int) $first;         //  5
//         $frac  = $first- (int) $first;  // .7

//         $loose = $frac*$vol;
//     //   $rmn_pk=$packavb-$whole;  
//     //   $loosblc=$looseavb-$loose;

// $whole=round($whole); $loose=round($loose);
// $studentQuery = "INSERT INTO `location_control`(`batch_id`, `prod_id`, `location_expiry`, `out_blc`, `supplier_id`, `stock_location`, `branch_id`,mfg_dat,pack,loose_lec) 
// VALUES ('$a','$b','$e','$f','$d','$c','1','$g','$whole','$loose')";

//  $studentQuery = "INSERT INTO `stockin`(`rec_dnno`, `prod_id`, `qty`, `date`, `branch_id`, `user_id`, `asn_qty`, `batch`, `expiry`,`location`, `mfg`)VALUES ('20241031-Open','$b','$f','2024/10/30','1','1','$f','$a','$e','$c','$g')";


//  $studentQuery = "UPDATE `location_control` SET `pack`='$b' WHERE `prod_id`='$a' and mfg_dat='$e' and branch_id='1'";


/*
$query=mysqli_query($con,"SELECT * FROM `product` WHERE `prod_name`='$e'")or die(mysqli_error());
while($row=mysqli_fetch_array($query)){ $pid=$row['prod_id'] ;
$vol=$row['volume'];   } 
$vol1=$vol*$c;
                         


$studentQuery = "INSERT INTO `stockin`(`rec_dnno`, `prod_id`, `pack`, `date`,`user_id`, `asn_qty`, `uom2`,final,batch) 
VALUES ('$a','$pid','$c','$b','$id', '$vol1','$vol','1','$d')";

/*

$studentQuery = "INSERT INTO `batch_record`(`recorditem_id`, `record_batch`, `record_package`, `record_unit`, `record_status`, `record_qty`) VALUES ('$a','$b','$c','$d','$e','0')";
*/
 
 $studentQuery = "INSERT INTO `zone`(`zone_name`, `branch_id`, `user_id`) VALUES ('$a','3','1')";
 
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

//echo "<script>document.location='http://localhost/sovereign_wms/AdminLTE/pages/tables/product.php'</script>"; 
?>