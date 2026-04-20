<?php
http_response_code(200);
header('Content-Type: application/json');

echo json_encode([
    "status" => "ok",
    "service" => getenv('OTEL_SERVICE_NAME') ?: 'php-app',
    "time" => date('c')
]);