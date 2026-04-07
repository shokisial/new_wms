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
        
        // $dno=0;
        // $query=mysqli_query($con,"SELECT stockout_dat, stockout_deliveryno FROM `stockout` ")or die(mysqli_error());
        // while($row=mysqli_fetch_array($query)){ $dno=$row['stockout_deliveryno'];   } 
        // echo 'Dn = '. $dno;
        // $dts=0;
        // date_default_timezone_set("Asia/Karachi");
        // $dts = date("Ymd");
        // if($dno===$dts){
        //     $dno=$dno+1;
        // }
        // else{
        //     $dno=$dts . 1;
            
        // }
        

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
        
                
   // echo 'Results = ' .'a='. $a .'b='. $b . 'c='.$c .'d='.$d.'e='.$e.'f='.$f.'g='.$g .'h='.$h.'i='. $i ."<br>";            
	
if ($h < 0) {
    $h = $h * -1;
}	

$h=str_replace(",","",$h);

$chk_asn=0;  
$query=mysqli_query($con,"SELECT * FROM `stockout` WHERE `stockout_orderno`='$a' and `gatepass_id` > '0' ")or die(mysqli_error());
while($row=mysqli_fetch_array($query)){ $chk_asn=$row['stockout_orderno'];  } 
if($chk_asn > 0) { echo "<script type='text/javascript'>alert('$chk_asn Already Exist');</script>";  }
else {
    
// $dcd=0;
// $query2=mysqli_query($con,"select * from stockout where  `gatepass_id`>'0' and stockout_orderno='$a' and branch_id='$branch'")or die(mysqli_error($con));
// 		$count=mysqli_num_rows($query2);

// 		if ($count>0)
// 		{
// 			echo "<script type='text/javascript'>alert('D.C No. already exist!');</script>";
// 		}
// 		else
// 		{	
$vol=0; $first=0;    $sid=0; $date = date('Y/m/d'); $wh=0; $wh_out=0;
$query=mysqli_query($con,"SELECT * FROM `product` WHERE `prod_desc`='$e' and branch_id='$branch'")or die(mysqli_error());
while($row=mysqli_fetch_array($query)){ $vol=$row['volume'];  
$sid=$row['supplier_id']; $wh=$row['weight']; } 
$totalblc=0; $wh_out=$wh*$h;
$query1=mysqli_query($con,"SELECT *,sum(`out_blc`) as totalblc FROM `location_control` WHERE `batch_id`='$g' and `prod_id`='$e' and branch_id='$branch' ")or die(mysqli_error());
while($row1=mysqli_fetch_array($query1)){ $totalblc=$row1['totalblc'];   } 



if($vol > 0) {
$first=$h/$vol;
$qt1=strtok($first, '.');

$whole = (int) $first;         //  5
$frac  = $first- (int) $first;  // .7
$loose = $frac*$vol;

if($branch==='1'){ $whole=0; $whole=$h/$vol; $whole=round($whole); $loose='0'; }



} $rem=0;
if($sid < 1) { $rem='SKU Not Found'; }  elseif($h > $totalblc ){ $rem='Not Enough Stock'; }

if($sid>0 and $totalblc >= $h and $h > 0){

 $studentQuery = "INSERT INTO `stockout`(stockout_orderno, product_id,batch, stockout_dnqty, branch_id, dealer_code,user_id,final,city,sup_id,uom3,qt1,stockout_loosedn,stockout_deliveryno,stockout_truckno,who,wh_out) 
    VALUES ('$a','$e','$g','$h','$branch','$c','$id','1','$d','$sid','$vol','$whole','$loose','$date','$i','$wh','$wh_out')";
                
                
}
else
{
    $studentQuery = "INSERT INTO `temp_trans_out`(serial_no,`prod_id`, `qty`, `branch_id`, `vol`, `qt1`, `batch_out`, `exp`,gdn,city,rem,supp,dist,route_truckno) 
                                VALUES ('$a','$e','$h','$branch','$vol','$h','$g','0','$dno','$d','$rem','$sid','$c','$i')"; 
}

$result = mysqli_query($con, $studentQuery);
                $msg = true;
            } 
            
   //     }
}
            else
            {
                $count = "1";
            }
        }


    //     if(isset($msg))
    //     {
    //         $_SESSION['message'] = "Successfully Imported";
    //         header('Location: ../outward_transaction.php');
    //         exit(0);
    //     }
    //     else
    //     {
    //         $_SESSION['message'] = "Data Already Exist";
    //         header('Location: outbound_index.php');
    //         exit(0);
    //     }
    // }
    // else
    // {
    //     $_SESSION['message'] = "Invalid File";
    //     header('Location: outbound_index.php');
    //     exit(0);
     }
}
echo 'File Uload Sucessfull';
echo "<script>document.location='https://sovereign-wh.com/new_wms/outward_transaction.php'</script>"; 
?>