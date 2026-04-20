<?php

use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\API\Logs\LogRecord;
use OpenTelemetry\API\Logs\Severity;



// ── Safe OTEL bootstrap ──
$otel        = require __DIR__ . '/bootstrap.php';
$tracer      = $otel['tracer'];
$traceProvider = $otel['provider'];

$logData     = require __DIR__ . '/otel-logger.php';
$otelLogger  = $logData['logger'];
$logProvider = $logData['provider'];

require __DIR__ . '/AutoPDO.php';

// ── Root span ──
$span  = null;
$scope = null;

if ($tracer) {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri    = strtok($_SERVER['REQUEST_URI'] ?? '/', '?'); // strip query string

    $span  = $tracer->spanBuilder("$method $uri")->startSpan();
    $scope = $span->activate();

    $span->setAttribute('http.method',  $method);
    $span->setAttribute('http.target',  $uri);
    $span->setAttribute('http.scheme',  'http');
    $span->setAttribute('net.host.name', $_SERVER['HTTP_HOST'] ?? 'unknown');
}

// ── Helper: emit a structured log via OTEL ──
function otelLog(?object $logger, string $level, string $message, array $attrs = []): void
{
    if (!$logger) {
        return;
    }
    try {
        $record = (new LogRecord($message))
            ->setSeverityText($level)
            ->setSeverityNumber(match (strtoupper($level)) {
                'DEBUG' => Severity::DEBUG,
                'WARN'  => Severity::WARN,
                'ERROR' => Severity::ERROR,
                default => Severity::INFO,
            });
        foreach ($attrs as $k => $v) {
            $record->setAttribute($k, $v);
        }
        $logger->emit($record);
    } catch (Throwable) {
        // never break app
    }
}

try {
    $dsn    = sprintf('mysql:host=%s;dbname=%s', getenv('DB_HOST') ?: 'mysql', getenv('DB_NAME') ?: 'test');
    $dbUser = getenv('DB_USER') ?: 'root';
    $dbPass = getenv('DB_PASS') ?: 'root';

    $pdo = new AutoPDO($dsn, $dbUser, $dbPass);
    $pdo->setTracer($tracer);

    $sql  = 'SELECT * FROM products';
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    otelLog($otelLogger, 'INFO', 'Products fetched successfully', [
        'db.row_count' => count($data),
        'db.statement' => $sql,
    ]);

    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode($data);

    if ($span) {
        $span->setAttribute('http.status_code', 200);
        $span->setStatus(StatusCode::STATUS_OK);
    }

} catch (Throwable $e) {

    otelLog($otelLogger, 'ERROR', 'Unhandled exception: ' . $e->getMessage(), [
        'exception.type'    => get_class($e),
        'exception.message' => $e->getMessage(),
    ]);

    if ($span) {
        $span->setAttribute('http.status_code', 500);
        $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
        $span->recordException($e);
    }

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal Server Error']);

} finally {

    if ($span)  $span->end();
    if ($scope) $scope->detach();

    // Flush OTEL pipelines before PHP exits
    try {
        if ($logProvider)   $logProvider->shutdown();
        if ($traceProvider) $traceProvider->shutdown();
    } catch (Throwable $t) {
        error_log('[OTEL] Shutdown error: ' . $t->getMessage());
    }
}