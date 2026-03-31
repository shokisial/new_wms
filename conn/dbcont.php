<?php
$servername = "localhost";
$username = "sovereig";
$password = ")x82C2TLMj[1eb";
$dbname = "sovereig_new_wms";
//mysql and db connection

$con = new mysqli($servername, $username, $password, $dbname);

if ($con->connect_error) {  //error check
    die("Connection failed: " . $con->connect_error);
}
else
{

}

?>