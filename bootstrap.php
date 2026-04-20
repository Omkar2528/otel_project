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
 * OTEL_EXPORTER_OTLP_ENDPOINT must be set to the sidecar collector address.
 * In ECS Fargate with awsvpc networking, the sidecar is reachable at 127.0.0.1.
 * In docker-compose, it is reachable by the service name (e.g. http://otel:4318).
 * Always configure via the env var; never hardcode.
 */
$endpoint = rtrim(getenv('OTEL_EXPORTER_OTLP_ENDPOINT') ?: 'http://127.0.0.1:4318', '/');

try {
    $transport = (new OtlpHttpTransportFactory())->create(
        $endpoint . '/v1/traces',
        'application/json'
    );

    $exporter = new SpanExporter($transport);

    $resource = ResourceInfo::create(
        Attributes::create([
            ResourceAttributes::SERVICE_NAME        => getenv('OTEL_SERVICE_NAME') ?: 'otel-php-app',
            ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => getenv('APP_ENV') ?: 'production',
            'host.name'                             => gethostname(),
        ])
    );

    $tracerProvider = new TracerProvider(
        new BatchSpanProcessor($exporter, ClockFactory::getDefault()),
        null,
        $resource
    );

    return [
        'tracer'   => $tracerProvider->getTracer('otel-php-app'),
        'provider' => $tracerProvider,
    ];

} catch (Throwable $e) {
    // Never break the application if OTEL is down
    error_log('[OTEL] Tracer init failed: ' . $e->getMessage());

    return [
        'tracer'   => null,
        'provider' => null,
    ];
}
