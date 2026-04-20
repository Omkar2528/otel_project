<?php

use OpenTelemetry\API\Logs\LogRecord;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;

/**
 * SINGLE SOURCE OF TRUTH FOR HEALTH CHECK
 */
if ($_SERVER['REQUEST_URI'] === '/health') {
    header('Content-Type: text/plain');
    http_response_code(200);
    exit('OK');
}

/**
 * SAFE OTEL BOOTSTRAP
 */
$otel = require 'bootstrap.php';
$tracer = $otel['tracer'];
$traceProvider = $otel['provider'];

$logData = require 'otel-logger.php';
$otelLogger = $logData['logger'];
$logProvider = $logData['provider'];

require 'AutoPDO.php';

/**
 * If OTEL is disabled, we still run app normally
 */
$span = null;
$scope = null;

if ($tracer) {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    $span = $tracer->spanBuilder("$method $uri")->startSpan();
    $scope = $span->activate();

    $span->setAttribute('http.method', $method);
    $span->setAttribute('http.target', $uri);
}

try {

    $pdo = new AutoPDO("mysql:host=mysql;dbname=test", "root", "root");
    $pdo->setTracer($tracer);

    $sql = "SELECT * FROM products";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($data);

    if ($span) {
        $span->setAttribute('http.status_code', 200);
        $span->setStatus(StatusCode::STATUS_OK);
    }

} catch (Exception $e) {

    if ($span) {
        $span->setAttribute('http.status_code', 500);
        $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
        $span->recordException($e);
    }

    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error"]);

} finally {

    if ($span) {
        $span->end();
    }

    if ($scope) {
        $scope->detach();
    }

    try {
        if (isset($logProvider)) $logProvider->shutdown();
        if (isset($traceProvider)) $traceProvider->shutdown();
    } catch (Throwable $t) {
        error_log("OTEL shutdown error: " . $t->getMessage());
    }
}