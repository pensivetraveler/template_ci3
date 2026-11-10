<?php
// 시험용 SMTP 수신기. 받은 메일을 그대로 파일에 덧붙인다. 사용: php smtp_sink.php 포트 파일
[, $port, $out] = $argv;
$server = stream_socket_server("tcp://127.0.0.1:$port", $errno, $error);
while ($conn = stream_socket_accept($server, -1)) {
    fwrite($conn, "220 sink\r\n");
    $data = false; $message = '';
    while (($line = fgets($conn)) !== false) {
        if ($data) {
            if (rtrim($line) === '.') { file_put_contents($out, "=====\n".$message, FILE_APPEND); $message = ''; $data = false; fwrite($conn, "250 ok\r\n"); continue; }
            $message .= $line;
            continue;
        }
        $command = strtoupper(substr(trim($line), 0, 4));
        if ($command === 'DATA') { $data = true; fwrite($conn, "354 go\r\n"); }
        elseif ($command === 'QUIT') { fwrite($conn, "221 bye\r\n"); break; }
        else fwrite($conn, "250 ok\r\n");
    }
    fclose($conn);
}
