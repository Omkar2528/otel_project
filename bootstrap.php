<?php

require __DIR__ . '/vendor/autoload.php';

use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SemConv\ResourceAttributes;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\SDK\Common\Time\ClockFactory;

/**
 * FIX: correct ECS/docker service discovery
 * NOT localhost
 */
$endpoint = getenv('OTEL_EXPORTER_OTLP_ENDPOINT') ?: 'http://otel:4318';

try {
    $transport = (new OtlpHttpTransportFactory())->create(
        rtrim($endpoint, '/') . '/v1/traces',
        'application/json'
    );

    $exporter = new SpanExporter($transport);

    $resource = ResourceInfo::create(
        Attributes::create([
            ResourceAttributes::SERVICE_NAME => getenv('OTEL_SERVICE_NAME') ?: 'otel-php-auto-OP',
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

} catch (Throwable $e) {
    // 🔥 CRITICAL: never break app if OTEL is down
    error_log("OTEL init failed: " . $e->getMessage());

    return [
        'tracer' => null,
        'provider' => null
    ];
}