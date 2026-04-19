<?php
use OpenTelemetry\API\Logs\LogRecord;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;

// 1. Setup Otel (Traces and Logs)
$otel = require 'bootstrap.php';
$tracer = $otel['tracer'];
$traceProvider = $otel['provider'];

$logData = require 'otel-logger.php';
$otelLogger = $logData['logger'];
$logProvider = $logData['provider'];

require 'AutoPDO.php';

// 2. Start Root Span
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$span = $tracer->spanBuilder("$method $uri")->startSpan();

// CRITICAL: Activate the scope so the tracer knows this is the "Parent"
$scope = $span->activate(); 
$currentContext = Context::getCurrent();

// Set high-level attributes for SigNoz
$span->setAttribute('http.method', $method);
$span->setAttribute('http.target', $uri);

try {
    $otelLogger->emit(
        (new LogRecord("Inbound Request: $method $uri"))
            ->setSeverityText('INFO')
            ->setContext($currentContext)
    );

    // Initialize Database
    $pdo = new AutoPDO("mysql:host=mysql;dbname=test", "root", "root");
    $pdo->setTracer($tracer);
    
    // This call will now automatically see the active scope and nest inside it
    $sql = "SELECT * FROM products";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $otelLogger->emit(
        (new LogRecord("Query Executed: $sql")) 
            ->setSeverityText('INFO')
            ->setContext($currentContext)
            ->setAttribute('db.rows', count($data))
    );

    // Set Status and Status Code for Golden Signals
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
    // 4. End the Root Span and detach the scope
    $span->end();
    $scope->detach(); 
    
    // Flush data
    $logProvider->shutdown();
    $traceProvider->shutdown();
}