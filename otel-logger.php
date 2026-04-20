<?php

require __DIR__ . '/vendor/autoload.php';

use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\Processor\BatchLogRecordProcessor;
use OpenTelemetry\Contrib\Otlp\LogsExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SemConv\ResourceAttributes;
use OpenTelemetry\SDK\Common\Time\ClockFactory;

/**
 * FIX: Use the same endpoint env-var as bootstrap.php — must NOT hardcode 127.0.0.1
 * in docker-compose (service name is needed) vs ECS (127.0.0.1 is correct for sidecar).
 */
$endpoint = rtrim(getenv('OTEL_EXPORTER_OTLP_ENDPOINT') ?: 'http://127.0.0.1:4318', '/');

try {
    $transport = (new OtlpHttpTransportFactory())->create(
        $endpoint . '/v1/logs',
        'application/json'
    );

    $exporter  = new LogsExporter($transport);
    $processor = new BatchLogRecordProcessor(
        $exporter,
        ClockFactory::getDefault(),
        2048,   // maxQueueSize
        1000,   // scheduledDelayMillis
        512     // maxExportBatchSize
    );

    $resource = ResourceInfo::create(Attributes::create([
        ResourceAttributes::SERVICE_NAME               => getenv('OTEL_SERVICE_NAME') ?: 'otel-php-app',
        ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => getenv('APP_ENV') ?: 'production',
        'host.name'                                    => gethostname(),
    ]));

    $loggerProvider = LoggerProvider::builder()
        ->addLogRecordProcessor($processor)
        ->setResource($resource)
        ->build();

    return [
        'logger'   => $loggerProvider->getLogger('otel-php-app-logger'),
        'provider' => $loggerProvider,
    ];

} catch (Throwable $e) {
    error_log('[OTEL] Logger init failed: ' . $e->getMessage());

    return [
        'logger'   => null,
        'provider' => null,
    ];
}