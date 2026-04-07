           


<?php
include 'DBController.php';
$db_handle = new DBController();
$productResult = $db_handle->runQuery("SELECT * FROM `outbound_fileformat`");

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

<h1>
              <a class="btn btn-lg btn-warning" href="outward_transaction.php">Back</a>
              
            </h1>
<body>
    <div >
        <table id="tab">
            <thead>
                <tr>
                   
 <th width="10%">DC No</th>
 <th width="20%">Customer Code</th>
 <th width="20%">Customer</th>
 <th width="10%">City</th>
 <th width="20%">Material Code</th>
 <th width="20%">Material Description</th>
<th width="10%">Batch</th>
<th width="10%">Quantity</th>
<th width="10%">Vehicle</th>


    
 	        
     </tr>
            </thead>
            <tbody>
 
            <?php
            if (! empty($productResult)) {
                foreach ($productResult as $key => $value) {
                    ?>
                 
                     <tr>
 <td><?php echo $productResult[$key]["dcno"]; ?> </td>    
 <td><?php echo $productResult[$key]["prod_id"]; ?></td> 
  
 <td><?php echo $productResult[$key]["distributer"]; ?></td>  
 <td><?php echo $productResult[$key]["city"]; ?></td>
 <td><?php echo $productResult[$key]["prod_id"]; ?></td> 
 <td><?php echo 'Multibionta Inj. for Inf. 5sx10ml'; ?></td> 
 <td><?php echo $productResult[$key]["batch"]; ?></td>  
 <td><?php echo $productResult[$key]["quantity"]; ?></td>  
 <td><?php echo $productResult[$key]["vehicle"]; ?></td>
   
 
  
</tr>
             <?php
                }
            }
            ?>
            
            
      </tbody>
        </table>
      </tbody>
        </table>

        <div class="btn">
            <form action="" method="post">
                <button type="submit" id="btnExport"
                    name='export' value="Export to Excel"
                    class="btn btn-info">Excel File</button>
            </form>
        </div>
    </div>
</body>
</html>