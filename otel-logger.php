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

$serviceName = 'otel-php-auto-OP';
$endpoint = getenv('OTEL_EXPORTER_OTLP_ENDPOINT') ?: 'http://127.0.0.1:4318';

$transport = (new OtlpHttpTransportFactory())->create(
    $endpoint . '/v1/logs',
    'application/json'
);

$exporter = new LogsExporter($transport);
$processor = new BatchLogRecordProcessor(
    $exporter, 
    ClockFactory::getDefault(),
    2048, 1000, 512
);

// Fix: Using the string keys directly to avoid "Undefined constant" errors
$resource = ResourceInfo::create(Attributes::create([
    ResourceAttributes::SERVICE_NAME => $serviceName,
    'deployment.environment' => 'dev', // Manual string key
]));

$loggerProvider = LoggerProvider::builder()
    ->addLogRecordProcessor($processor)
    ->setResource($resource)
    ->build();

return [
    'logger' => $loggerProvider->getLogger('my-app-logger'),
    'provider' => $loggerProvider
];