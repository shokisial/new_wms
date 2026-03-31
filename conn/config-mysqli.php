<?Php
/////// Update your database login details here /////
$dbhost_name = "localhost"; // Your host name 
$database = "sovereig_wms";       // Your database name
$username = "sovereig";            // Your login userid 
$password = "sovereig_new_wms";            // Your password 
//////// End of database details of your server //////

//////// Do not Edit below /////////
$connection = mysqli_connect($host_name, $username, $password, $database);

if (!$connection) {
    echo "Error: Unable to connect to MySQL.<br>";
    echo "<br>Debugging errno: " . mysqli_connect_errno();
    echo "<br>Debugging error: " . mysqli_connect_error();
    exit;
}
?> 