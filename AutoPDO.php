<?php
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\Context\Context;

class AutoPDO extends PDO
{
    private $tracer;

    public function setTracer($tracer)
    {
        $this->tracer = $tracer;
    }

    #[\ReturnTypeWillChange]
    public function query($sql, ?int $mode = null, ...$args)
    {
        // Extract SQL operation for better SigNoz grouping
        $operation = strtoupper(explode(' ', trim($sql))[0]);

        // 1. Get current active context (from the index.php span)
        $parentContext = Context::getCurrent();

        // 2. Build the span as a CHILD of the current context
        $span = $this->tracer->spanBuilder("mysql.$operation")
            ->setParent($parentContext) 
            ->setSpanKind(SpanKind::KIND_CLIENT) 
            ->setAttribute('db.system', 'mysql')
            ->setAttribute('db.statement', $sql)
            ->setAttribute('db.operation', $operation)
            ->startSpan();

        // 3. Make this span active so logs/sub-calls link here
        $scope = $span->activate();

        try {
            $result = parent::query($sql, $mode, ...$args);
            $span->setStatus(StatusCode::STATUS_OK);
            return $result;
        } catch (Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}