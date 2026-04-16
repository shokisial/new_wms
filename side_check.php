<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }

$branch = $_SESSION['branch'];
$id     = $_SESSION['id'];
$name   = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';

$ssd = isset($_POST['optionlist']) ? $_POST['optionlist'] : '';
$user_group = $_SESSION['user_group']; 

include('conn/dbcon.php');
?>
<?php if($user_group==='1') { include('side_main.php'); } 
if($user_group==='0') { include('side_operator.php'); }

?>
