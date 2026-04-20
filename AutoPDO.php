<?php

use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\Context\Context;

/**
 * AutoPDO — PDO wrapper that automatically creates child spans for every query.
 * Compatible with PHP 8.0+.
 */
class AutoPDO extends PDO
{
    private ?object $tracer = null;

    public function setTracer(?object $tracer): void
    {
        $this->tracer = $tracer;
    }

    #[\ReturnTypeWillChange]
    public function query(string $sql, ?int $fetchMode = null, mixed ...$fetchModeArgs): \PDOStatement|false
    {
        if (!$this->tracer) {
            // OTEL disabled — fall through to parent normally
            return $fetchMode !== null
                ? parent::query($sql, $fetchMode, ...$fetchModeArgs)
                : parent::query($sql);
        }

        $operation = strtoupper(strtok(ltrim($sql), " \t\n\r"));

        $span = $this->tracer->spanBuilder("db.$operation")
            ->setParent(Context::getCurrent())
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setAttribute('db.system',         'mysql')
            ->setAttribute('db.operation',      $operation)
            ->setAttribute('db.statement',      $sql)
            ->setAttribute('db.name',           getenv('DB_NAME') ?: 'test')
            ->setAttribute('net.peer.name',     getenv('DB_HOST') ?: 'mysql')
            ->setAttribute('net.peer.port',     3306)
            ->startSpan();

        $scope = $span->activate();

        try {
            $result = $fetchMode !== null
                ? parent::query($sql, $fetchMode, ...$fetchModeArgs)
                : parent::query($sql);

            $span->setStatus(StatusCode::STATUS_OK);
            return $result;

        } catch (Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;

        } finally {
            $scope->detach();
            $span->end();
        }
    }
}