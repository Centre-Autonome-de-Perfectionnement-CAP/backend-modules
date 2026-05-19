<?php
$host = '127.0.0.1';
$port = 3000;
$payload = json_encode(['to' => '22958370390', 'text' => 'Test raw HTTP']);

$fp = @fsockopen($host, $port, $errno, $errstr, 5);
if (!$fp) {
    die("ERREUR SOCKET\n");
}

$request = "POST /send-message HTTP/1.1\r\n";
$request .= "Host: $host:$port\r\n";
$request .= "Content-Type: application/json\r\n";
$request .= "Content-Length: " . strlen($payload) . "\r\n";
$request .= "Connection: close\r\n\r\n";
$request .= $payload;

fwrite($fp, $request);

$response = "";
while (!feof($fp)) {
    $response .= fgets($fp, 128);
}
fclose($fp);

echo "REPONSE SERVEUR NODE :\n$response\n";
