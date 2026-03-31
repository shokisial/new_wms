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
 
$e=str_replace(",","",$e);

$chk_asn=0;  
$query=mysqli_query($con,"SELECT * FROM `stockin` WHERE `rec_dnno`='$a' and `gatepass_id` > '0' ")or die(mysqli_error());
while($row=mysqli_fetch_array($query)){ $chk_asn=$row['rec_dnno'];  } 
if($chk_asn > 0) { echo "<script type='text/javascript'>alert('$chk_asn Already Exist');</script>";  }
else {
$vol=0; $first=0;    $sid=0; $date = date('Y/m/d'); 
$query=mysqli_query($con,"SELECT * FROM `product` WHERE `prod_desc`='$b' and branch_id='$branch'")or die(mysqli_error());
while($row=mysqli_fetch_array($query)){ $vol=$row['volume'];  $sid=$row['supplier_id']; } 
//$vol1=$vol*$c;

if($vol > 0 ) {
$first=$e/$vol;
$qt1=strtok($first, '.');

$whole = (int) $first;         //  5
$frac  = $first- (int) $first;  // .7
// echo "<br>";
// echo $whole . "<br>";
// echo $frac;

$loose = $frac*$vol;
//echo "<br>". $loose;

if($branch==='1'){ $whole=0; $whole=$e/$vol; $whole=round($whole); $loose='0'; }

}


if($sid>0 and $e > 0){
 $studentQuery = "INSERT INTO `stockin`(`rec_dnno`, `prod_id`, `pack`, `date`,`user_id`, `asn_qty`, `uom2`,final,batch,truck_no,transporter,loose,branch_id,shipper_id) 
 VALUES ('$a','$b','$whole','$date','$id', '$e','$vol','1','$d','$f','$g','$loose','$branch','$sid')";
 }
else
 {

     $studentQuery ="INSERT INTO `temp_trans`(`supplier_id`, `prod_id`, `uom1`, `qt1`, `loose`, `qty`, `branch_id`, `serial_no`, `batch`, `po_no`) 
     VALUES ('$sid','$b','$vol','$whole','$loose','$e','$branch','Check Item Code / QTY','$d','$a')";

 }

$result = mysqli_query($con, $studentQuery);
                $msg = true;
            } }
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

$remarks="Upload ASN on ";  
		mysqli_query($con,"INSERT INTO history_log(user_id,action,date,branch_id) VALUES('$id','$remarks','$date','$branch')")or die(mysqli_error($con));


echo 'File Uload Sucessfull';
echo "<script>document.location='https://sovereign-wh.com/AdminLTE/pages/tables/inward_transaction.php'</script>"; 
?>