<?php
/**
 * AWS ELB Health Check Endpoint
 * Returns 200 OK to prevent target group de-registration
 */
http_response_code(200);
header('Content-Type: text/plain');
echo 'OK';