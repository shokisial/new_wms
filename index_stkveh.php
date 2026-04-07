<?php session_start();
// if(empty($_SESSION['id']) or $_SESSION['id']!='11'):
// header('Location:../index.php');
// endif;
// if(empty($_SESSION['branch'])):
// header('Location:../index.php');
// endif;
$branch=$_SESSION['branch']; $id=$_SESSION['id'];  
?>
<htm>
  <head>

          </head>
  <form action="" method="POST" enctype="multipart/form-data">
<input type="date" name="start" >
<input type="date" name="end">
<button type="submit"  class="btn btn-primary">Select</button>   
</form>

<?php
$start=$_POST['start']; $start = date("Y/m/d", strtotime($start)); //echo $start;
$end=$_POST['end'];     $end = date("Y/m/d", strtotime($end)); // echo $end;

?>
 <form action="" method="POST" enctype="multipart/form-data">
                
								<select id="optionlist" name="optionlist"  autocomplete="off" required>
                      <option value="">Select Vehicle</option>
          
                  <?php include('conn/dbcon.php');
//SELECT * FROM `location_control` INNER JOIN product on product.prod_desc=location_control.prod_id where rec_no > 0 and location_control.loc_dat >='$start' and location_control.loc_dat <= '$end'  group by rec_no
 $query=mysqli_query($con,"SELECT * FROM `stockin` WHERE `date` >='$start' and `date` <= '$end' and branch_id='$branch' group by rec_dnno")or die(mysqli_error());
		while($row=mysqli_fetch_array($query)){ ?>
                  
                  <option value="<?php echo $row['rec_dnno']; ?>"><?php echo $row['rec_dnno']; ?></option>
                <?php } ?>
                      </select>
                   
     <button type="submit" name="choice" class="btn btn-primary">Select</button>   
                               

      <br>
</form>

<?php
if(isset($_POST['choice'])){
date_default_timezone_set("Asia/Karachi"); 
	$date = date("Y-m-d");
$orno=$_POST['optionlist']; 
$stno=0; include 'DBController.php'; ?>

<h5><b> Sovereing Warehouse Location List  as on <?php echo '  ' . $orno;?></b></h5>
                  
				  
                            
    <div >
        <table id="tab">
            <thead>
                <tr>  
                      <th>Code</th> 
                      <th>S.K.U</th> 
                      <th>Batch</th> 
                      <th>MFG</th>
                      <th>Expiry</th>
				      <th>Quantity</th>
				      <th>Balance QTY</th>
                      <th>Location</th>    
  
           
     </tr>
            </thead>

<?php $s=1; $pid=0; $pname=0; $batch=0; $expiry=0; $qtt=0;
$query1=mysqli_query($con,"SELECT * FROM `stockin` INNER JOIN product on product.prod_desc=stockin.prod_id   WHERE `rec_dnno`='$orno' and stockin.branch_id='$branch'")or die(mysqli_error());
		while($row1=mysqli_fetch_array($query1)){ $stno=$row1['stockin_id']; 
		$pid=$row1['prod_desc']; $pname=$row1['prod_name']; $batch=$row1['batch']; $expiry=$row1['expiry'];
	        $qtt=$row1['qty'];

$sno=1; 
//$qt=0; $qts=0; $qtb=0; $qt1=0;

$db_handle = new DBController();
$productResult = $db_handle->runQuery("SELECT * FROM `location_control` WHERE `st_id`='$stno' ");
//SELECT * FROM `location_control` INNER JOIN product on product.prod_desc=location_control.prod_id where rec_no='$orno'
//SELECT * FROM `stockout` INNER JOIN product on product.prod_id=stockout.product_id INNER JOIN stockin on stockin.batch=stockout.batch where stockout.`final`='1' and stockout.stockout_orderno='$orno' and stockout.stockout_qty ='0' group by stockout.product_id

if (isset($_POST["export"])) {
  
  $filename = "Export_excel.xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
   
 $isPrintHeader = false;
   $sno=0;  if (! empty($productResult)) {
        foreach ($productResult as $row) {
            if (! $isPrintHeader) {
               
 echo implode("\t", array_keys($row)) . "\n";
                $isPrintHeader = true;
            }
            echo implode("\t", array_values($row)) . "\n";
        }
    }

    exit();
}
?>
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<style>
body {
    font-size: 0.95em;
    font-family: arial;
    color: #212121;
}

th {
    background: #E6E6E6;
    
border-bottom: 1px solid #000000;
}

#table-container {
    width: 850px;
    margin: 50px auto;
}

table#tab {
    border-collapse: collapse;
  
  width: 100%;
}

table#tab th, table#tab td {
    border: 1px solid #E0E0E0;
    padding: 8px 15px;
    text-align: left;
    font-size: 0.95em;
}

.btn {
    
padding: 8px 4px 8px 1px;
}
#btnExport {
    padding: 10px 40px;
    background: #499a49;
    border: #499249 1px solid;
    color: #ffffff;
    font-size: 0.9em;
   
 cursor: pointer;
}
</style>
</head>

<head>
    <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Product Inventory Report | <?php include('../dist/includes/title.php');?></title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.5 -->
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../plugins/datatables/dataTables.bootstrap.css">
    <link rel="stylesheet" href="../dist/css/AdminLTE.min.css">
    <!-- AdminLTE Skins. Choose a skin from the css/skins
         folder instead of downloading all of them to reduce the load. -->
    <link rel="stylesheet" href="../dist/css/skins/_all-skins.min.css">
    <style type="text/css">
      h5,h6{
        text-align:center;
      }
		

      @media print {
          .btn-print {
            display:none !important;
		  }
		  .main-footer	{
			display:none !important;
		  }
		  .box.box-primary {
			  border-top:none !important;
		  }
		  
          
      }
    </style>
 </head>
</head>
<body onload="window.print()">
    
    <h5><b><?php //echo $row['branch_name'];?></b> </h5>  
                  		  
    <div >
            
            
            <tbody>
 
            <?php 
            if (! empty($productResult)) {
                foreach ($productResult as $key => $value) {
                    ?>
                 
                     <tr>
   <td><?php echo $pid; ?></td> <td><?php echo $pname; ?></td> <td><?php echo $batch; ?></td> 
   <td><?php echo $productResult[$key]["mfg_dat"];?></td>
   <td><?php echo $expiry; ?></td> 
  <td><?php echo $productResult[$key]["loc_qty"] ; 
  $qt=$qt+$productResult[$key]["loc_qty"] ;?></td>  
  <td><?php echo $productResult[$key]['out_blc'] ; 
  $qt1=$qt1+$productResult[$key]["out_blc"] ; ?></td>
  <td><?php echo $productResult[$key]["stock_location"];?></td>  
 </tr>
             <?php
      $sno=$sno+1;          }
            }  } 
            ?>
<tr> <td></td><td></td><td><b> Total Packages </b></td> <td></td><td></td>   <td> <b><?php echo $qt; ?> </b></td> <td> <b><?php echo $qt1; ?> </b></td> <td></td>   </tr>               
            
      </tbody>
        </table>
      </tbody>
      <br><br>
        </table>
<?php } ?>
        
    </div>
</body>

</html>