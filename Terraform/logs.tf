# ── CloudWatch Log Group for all ECS containers ──────────────────────────────
resource "aws_cloudwatch_log_group" "ecs_logs" {
  name              = "/ecs/otel-app"
  retention_in_days = 30

  tags = {
    Environment = "production"
    Service     = "otel-php-app"
  }
}
