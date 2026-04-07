<?php

$token = "12345";

if (!isset($_GET['key']) || $_GET['key'] !== $token) {
    die("Access Denied");
}

// Force SSH key usage
putenv('HOME=/home3/sovereig');
putenv('GIT_SSH_COMMAND=ssh -i /home3/sovereig/.ssh/id_ed25519');

$output = [];

exec('cd /home3/sovereig/public_html/new_wms && git add . 2>&1', $output);
exec('cd /home3/sovereig/public_html/new_wms && git commit -m "Auto update '.date("Y-m-d H:i:s").'" 2>&1', $output);
exec('cd /home3/sovereig/public_html/new_wms && git push origin main 2>&1', $output);

echo "<pre>";
print_r($output);
echo "</pre>";  

?>