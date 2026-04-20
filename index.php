<?php
use OpenTelemetry\API\Logs\LogRecord;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;

/**
 * 1. ALB HEALTH CHECK BYPASS
 * This must be at the very top to avoid 404s and timeouts
 */
if ($_SERVER['REQUEST_URI'] === '/health') {
    header('Content-Type: text/plain');
    http_response_code(200);
    exit('OK');
}

// 2. Setup Otel
$otel = require 'bootstrap.php';
$tracer = $otel['tracer'];
$traceProvider = $otel['provider'];

$logData = require 'otel-logger.php';
$otelLogger = $logData['logger'];
$logProvider = $logData['provider'];

require 'AutoPDO.php';

// 3. Start Span
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$span = $tracer->spanBuilder("$method $uri")->startSpan();

$scope = $span->activate(); 
$currentContext = Context::getCurrent();

$span->setAttribute('http.method', $method);
$span->setAttribute('http.target', $uri);

try {
    $otelLogger->emit(
        (new LogRecord("Inbound Request: $method $uri"))
            ->setSeverityText('INFO')
            ->setContext($currentContext)
    );

    $pdo = new AutoPDO("mysql:host=mysql;dbname=test", "root", "root");
    $pdo->setTracer($tracer);
    
    $sql = "SELECT * FROM products";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $otelLogger->emit(
        (new LogRecord("Query Executed: $sql")) 
            ->setSeverityText('INFO')
            ->setContext($currentContext)
            ->setAttribute('db.rows', count($data))
    );

    $span->setAttribute('http.status_code', 200);
    $span->setStatus(StatusCode::STATUS_OK);

    header('Content-Type: application/json');
    echo json_encode($data);

} catch (Exception $e) {
    $span->setAttribute('http.status_code', 500);
    $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
    $span->recordException($e);

    $otelLogger->emit(
        (new LogRecord("Critical Error: " . $e->getMessage()))
            ->setSeverityText('ERROR')
            ->setContext($currentContext)
    );

    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error"]);

} finally {
    $span->end();
    $scope->detach(); 
    
    // Graceful OTel Shutdown - wrap in try/catch to prevent 500s if collector is slow
    try {
        if (isset($logProvider)) { $logProvider->shutdown(); }
        if (isset($traceProvider)) { $traceProvider->shutdown(); }
    } catch (Throwable $t) {
        // Ignore shutdown errors to ensure user gets the response
    }
}