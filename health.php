<?php
/**
 * AWS ALB / ECS Health Check Endpoint
 * Must respond 200 within the ALB timeout window.
 * No OTEL init here — keep it absolutely minimal.
 */
http_response_code(200);
header('Content-Type: text/plain');
header('Cache-Control: no-cache');
echo 'OK';
