<?php

// 🔥 HEALTH CHECK ENDPOINT (CRITICAL FOR ECS)
if ($_SERVER['REQUEST_URI'] === '/health') {
    http_response_code(200);
    echo json_encode(["status" => "ok"]);
    exit;
}
require __DIR__ . '/vendor/autoload.php';

use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SemConv\ResourceAttributes;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\SDK\Common\Time\ClockFactory;

$endpoint = getenv('OTEL_EXPORTER_OTLP_ENDPOINT') ?: 'http://otel:4318';

$transport = (new OtlpHttpTransportFactory())->create(
    $endpoint . '/v1/traces',
    'application/json'
);

$exporter = new SpanExporter($transport);

$resource = ResourceInfo::create(
    Attributes::create([
        ResourceAttributes::SERVICE_NAME => 'otel-php-auto-OP',
        'deployment.environment' => 'dev',
    ])
);

$tracerProvider = new TracerProvider(
    new BatchSpanProcessor($exporter, ClockFactory::getDefault()),
    null,
    $resource
);

return [
    'tracer' => $tracerProvider->getTracer('otel-php-auto-op'),
    'provider' => $tracerProvider
];