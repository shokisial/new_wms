<?php
    $host = "localhost";
    $username = "sovereig";
    $password = ")x82C2TLMj[1eb";
    $database = "sovereig_inventory";

    // Create DB Connection
    $con = mysqli_connect($host, $username, $password, $database);

    // Check connection
    if (!$con) {
        die("Connection failed: " . mysqli_connect_error());
    }
 //   echo "Connected successfully";
?>