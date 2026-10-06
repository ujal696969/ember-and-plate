<?php

echo "<h2>PHP SSL Test</h2>";

echo "<b>PHP Version:</b> ";
echo phpversion();

echo "<br><br>";

echo "<b>Loaded php.ini:</b><br>";
echo php_ini_loaded_file();

echo "<br><br>";

echo "<b>openssl.cafile:</b><br>";
echo ini_get('openssl.cafile');

echo "<br><br>";

echo "<b>curl.cainfo:</b><br>";
echo ini_get('curl.cainfo');

echo "<br><br>";

echo "<b>CA File Exists:</b> ";

$ca = 'C:\\xampp\\php\\extras\\ssl\\cacert.pem';

var_dump(file_exists($ca));

echo "<br><br>";

echo "<b>OpenSSL Certificate Locations:</b><pre>";
print_r(openssl_get_cert_locations());
echo "</pre>";