resource "aws_cloudwatch_log_group" "ecs_logs" {
  name              = "/ecs/otel-app"
  retention_in_days = 7
}