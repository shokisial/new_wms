<?php session_start();
if(empty($_SESSION['id'])):
header('Location:../index.php');
endif;
if(empty($_SESSION['branch'])):
header('Location:../index.php');
endif;
$branch=$_SESSION['branch']; $uid=$_SESSION['id'];  
?>
<html>
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<style>
body {
    font-size: 0.5em;
    font-family: calibri;
    color: #212121;
}

th {
    background: #E6E6E6;
    
border-bottom: 1px solid #000000;
}

#table-container {
    width: 850px;
    margin: 25px auto;
}

table#tab {
    border-collapse: collapse;
  
  width: 100%;
}

table#tab th, table#tab td {
    border: 1px solid #E0E0E0;
    padding: 2px 8px;
    text-align: left;
    font-size: 0.5em;
}

.btn {
    
padding: 8px 4px 8px 1px;
}
#btnExport {
    padding: 5px 20px;
    background: #499a49;
    border: #499249 1px solid;
    color: #ffffff;
    font-size: 0.5em;
   
 cursor: pointer;
}
</style>
</head>

<head>
    <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Product LocationReport | <?php include('../dist/includes/title.php');?></title>
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
          
            page-break-inside:avoid;

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
<body>
<? include('conn/dbcon.php');
$pick_dat=$_POST['pick_dat']; 
$dt2 = date('Y/m/d', strtotime($pick_dat));
//$dt2=date($pick_dat,'Y/m/d');
echo 'Pick date =' .$dt2; 

$ord_qty=0; $pick_no=0;
$sql10=mysqli_query($con,"SELECT * FROM `location_temp` where dat='$dt2' and branch_id='$branch'")or die(mysqli_error());
	while($row10 = mysqli_fetch_array($sql10)) {  $pick_no=$row10['picking_no']; }  $pick_no=$pick_no+1;  echo  $pick_no;?>
<div >
        <table id="tab" >
            <thead>
                <tr>
                   

 <th>DC No</th>
 <th>SKU</th>
 <th>Batch</th>
 <th>Order QTY</th>

 <th>Location</th>

 
 <th>QTY to be Picked</th>
 <th>Carton Size</th>
 <th>Carton</th>
 <th>Loose </th>
 <th>Customer </th>
 <th>City </th>
 <th>Distributor </th>
 <th>Vehicle #</th>
 </tr>
            </thead>
            <tbody>
 


<?php  
//and dn_no='$dt2'
mysqli_query($con,"UPDATE `location_control` SET `out_qty`='0' WHERE branch_id='$branch' and out_qty > 0")or die(mysqli_error($con));

  $sp=0; $new_blc=0; $ord_qty=0; $sql1=  mysqli_query($con,"SELECT * FROM `stockout`  inner join supplier on supplier.supplier_id=stockout.sup_id where stockout.`final`='1' and stockout.stockout_qty ='0'  and `picking`='1' and stockout.branch_id='$branch' order by stockout.product_id ASC")or die(mysqli_error());
	while($row1 = mysqli_fetch_array($sql1))
{ $ord_qty=0; $ord_qty=$row1['stockout_dnqty']; $sp=$row1['sup_id']; $stid=0; $bt=0; $prd=0; 

$prs=0; $prs=$row1['product_id']; ?>

<?php $prs_name=0; $odno=0; $min_sheflife=0;
 $sql15=  mysqli_query($con,"SELECT * FROM `product` where prod_desc='$prs' and branch_id='$branch'")or die(mysqli_error());
	while($row15 = mysqli_fetch_array($sql15))
{ $prs_name=$row15['prod_name']; $vol=$row15['volume']; 
  $min_sheflife=$row15['min_shelflife']; } ?>

<tr>
<td><?php echo $row1['stockout_orderno']; $odno=$row1['stockout_orderno']; ?></td>
<td><?php echo $prs_name. ' - ' . $prs ;  $trk=0; ?></td>
<td><?php echo $row1['batch']; ?></td>
<td><?php echo $ord_qty; $ord_qty1=$ord_qty1+$ord_qty; ?></td>
</tr>
<?php $bt=$row1['batch']; $prd=$row1['product_id']; $stid=$row1['stockout_id'];  $trk=$row1['route_truckno']; ?>

<?php if($sp==='55') { $sid=0; $outblc1=0; $expr=0; $sql2=  mysqli_query($con,"SELECT * FROM `location_control` WHERE `batch_id`='$bt' and `prod_id`='$prd' and out_blc > 0 and location_control.branch_id='$branch' order by location_control.location_expiry ASC")or die(mysqli_error());
} else { ?>
<?php $sid=0; $outblc1=0; $expr=0; $sql2=  mysqli_query($con,"SELECT * FROM `location_control` WHERE `batch_id`='$bt' and `prod_id`='$prd' and out_blc > 0 and location_control.branch_id='$branch' order by location_control.out_blc ASC")or die(mysqli_error());
}
	while($row2 = mysqli_fetch_array($sql2))
{   
    
// $shlf=0;  $xpy=$row['location_expiry'] ; $shlf=$xpy-$dtr; if($shlf > $xpy)

if($ord_qty > 0 and $row2['out_blc'] > 0) { ?>
<tr>
    <td></td><td></td><td></td><td><?php $outblc=0; $ac_out=0; $sid=$row2['id']; ?></td>
<td><?php echo $row2['stock_location']; $location=$row2['stock_location']; 
        $expr=$row2['location_expiry'];  ?></td>

<td><?php if($row2['out_blc'] <= $ord_qty) { $outblc=$row2['out_blc'];  $outblc1=$outblc1+$outblc; $new_blc=$new_blc+$outblc; $expr=$row2['location_expiry'];  } 

elseif($ord_qty <= $row2['out_blc'] ) { $outblc= $ord_qty; $ac_out=$row2['out_blc']-$ord_qty; $outblc1=$outblc1+$outblc; $new_blc=$new_blc+$outblc; }
else {  echo '<tr>' . '<td>'. 'Rocord Not Found' . '</td>' .'</tr>'; }
echo $outblc; //$outblc=$outblc+$row2['out_qty']; ;?></td>

<td><?php echo $vol;?></td>
<?php
$first=0; $whole=0; $frac=0; $loose=0; 
  $first=$outblc/$vol;
$qt1=strtok($first, '.');

$whole = (int) $first;         //  5
$frac  = $first- (int) $first;  // .7
$loose = $frac*$vol;
$loose=round($loose);
?>

<td><?php echo $whole;?></td>
<td><?php echo $loose;?></td>

<td><?php echo $row1['supplier_name'];?></td>
<td><?php echo $row1['city']; $ord_qty=$ord_qty-$row2['out_blc'];  ?></td>
<td><?php echo $row1['dealer_code'];?></td>
<td><?php echo $trk;?></td>

<?php mysqli_query($con,"UPDATE `location_control` SET `out_qty`='$outblc',out_blc='$ac_out' WHERE id='$sid'")or die(mysqli_error($con));

mysqli_query($con,"UPDATE `stockout` SET stockout_qty='$outblc1',`picking`='0',stockout_dat='$pick_no',expiry='$expr' WHERE `stockout_id`='$stid'")or die(mysqli_error($con));

$st_qty=0; $cond=0;
	$query2=mysqli_query($con,"select * from location_temp where prod_id='$prd' and batch_id='$bt' and location='$location' and picking_no='0' and branch_id='$branch'")or die(mysqli_error($con));
		$count=mysqli_num_rows($query2);
        $cond=$bt.$location;
		if ($count>0)
		{ 	
		    while($row=mysqli_fetch_array($query2)){ $st_qty=$row['st_qty']; } $st_qty=$st_qty+$outblc;
		mysqli_query($con,"UPDATE `location_temp` SET `st_qty`='$st_qty', dat='$dt2',cond='$cond',ord_no='$odno' WHERE batch_id='$bt' and prod_id='$prd' and location='$location' and branch_id='$branch'")or die(mysqli_error($con));
		}
		else
		{	
		mysqli_query($con,"INSERT INTO `location_temp`(`batch_id`, `prod_id`, `location`, `st_qty`,dat,picking_user,picking_no,cond,branch_id,ord_no) VALUES ('$bt','$prd','$location','$outblc','$dt2','$uid','$pick_no','$cond','$branch','$odno')")or die(mysqli_error($con));
        }
		
?>
</tr>
<?php } }} ?>
</tr>

<tr>
<td></td><td>Total</td><td></td><td><?php echo $ord_qty1; ?></td> <td></td><td></td><td><?php echo $outblc1;?></td> <td><?php echo 'Out = '. $new_blc; ?></td>   
</tr>
</table>

<div style="break-after:page"></div>

<table id="tab">
<tr>
<td> <h5><b> Sovereign Warehouse Pick List Order No. <?php echo $orno; ?> Picking Summery</b></h5>
   </td>
<tr>
</table>
<hr>
<div align="right">
    
<form action="picking_summery.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="no"  value="<?php echo $pick_no; ?>">
<input type="hidden" name="dt"  value="<?php echo $dt2; ?>">
<button type="submit" name="cash" class="btn btn-primary mt-3" >Segregation List</button>
</form>
</div>

<?php //echo "<script>document.location='seg_list.php'</script>"; 
 
/*
<table id="tab">
            <thead>


  <tr>
<th> SNo </th>
<th> Code </th>
<th>SKU Name </th>
<th>Batch # </th>
<th> Location </th>
<th> Quantity</th>
<th> Carton </th>
<th> Loose</th>
</tr>

<?php /*$sno=1; $ds=0; $dstot=0; $whole2=0; $loose2=0; $t=0;$first12=0;

$sql1=mysqli_query($con,"SELECT * FROM `location_temp` INNER JOIN product on product.prod_desc=location_temp.prod_id  where picking_no='$pick_no' and location_temp.dat='$dt2' ORDER by `location` ASC")or die(mysqli_error());
	while($row1 = mysqli_fetch_array($sql1)) { ?>
<td> <?php echo $sno; ?> </td>
<td> <?php echo $row1['prod_id']; ?> </td>
<td> <?php echo $row1['prod_name']; ?> </td>
<td> <?php echo $row1['batch_id']; ?> </td>
<td> <?php echo $row1['location'];  $vol1=$row1['volume'];  ?> </td>
<td> <?php echo $row1['st_qty']; $first1=$row1['st_qty']; $first12=$first12+$row1['st_qty'];?> </td>
<?php 

$first1=$first1/$vol1;
$qt1=strtok($first1, '.');

$whole1 = (int) $first1;        
$frac1  = $first1- (int) $first1;  

$loose1 = $frac1*$vol1;
?>
<td><?php echo $whole1;  $whole2=$whole2+$whole1; ?></td>
<td><?php echo round($loose1);  $loose2=$loose2+$loose1; ?></td>

</tr>
<?php $sno = $sno+1;  } ?>
  

<tr>
<td></td><td></td><td>Total</td><td></td>    <td><?php echo $first12; ?></td>
<td><?php echo $whole2; ?></td><td><?php echo $loose2; ?></td>
</tr>
</table>

<?php 
/*
$test=0; $d_id=0; 
$sql2=  mysqli_query($con,"SELECT * FROM `location_control`  order by out_blc ASC")or die(mysqli_error());
	while($row2 = mysqli_fetch_array($sql2))
{ $test=$row2['out_qty']; $d_id=$row2['id'];
mysqli_query($con,"UPDATE `location_control` SET out_blc='$test' WHERE id='$d_id'")or die(mysqli_error($con));} 
echo 'Record Updated'; */ 
?>

</body>
</html>