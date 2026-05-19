<?php
$host = '127.0.0.1';
$port = 3000;
$timeout = 5;

$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
if (!$fp) {
    echo "ERREUR PHP SOCKET: $errstr ($errno)\n";
} else {
    echo "SUCCES PHP SOCKET\n";
    fclose($fp);
}
