resource "aws_ecs_task_definition" "otel_app" {
  family                   = "otel-php-app"
  network_mode             = "awsvpc"
  requires_compatibilities = ["FARGATE"]
  cpu                      = "512"
  memory                   = "1024"
  execution_role_arn       = "arn:aws:iam::056449379091:role/ecsTaskExecutionRole"

  container_definitions = jsonencode([
    {
      name      = "php-app"
      image     = "056449379091.dkr.ecr.us-east-1.amazonaws.com/otel-php-app:latest"
      essential = true
      portMappings = [
        {
          containerPort = 80
          protocol      = "tcp"
        }
      ]
      environment = [
        { name = "OTEL_EXPORTER_OTLP_ENDPOINT", value = "http://localhost:4318" },
        { name = "OTEL_EXPORTER_OTLP_PROTOCOL", value = "http/protobuf" },
        { name = "OTEL_PHP_AUTOLOAD_ENABLED", value = "true" },
        { name = "OTEL_SERVICE_NAME", value = "otel-php-app" }
      ]
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = "/ecs/otel-app"
          "awslogs-region"        = "us-east-1"
          "awslogs-stream-prefix" = "ecs-php"
        }
      }
    },
    {
      name      = "otel-collector"
      image     = "056449379091.dkr.ecr.us-east-1.amazonaws.com/otel-collector:latest"
      essential = true
      portMappings = [
        { containerPort = 4317, protocol = "tcp" },
        { containerPort = 4318, protocol = "tcp" }
      ]
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = "/ecs/otel-app"
          "awslogs-region"        = "us-east-1"
          "awslogs-stream-prefix" = "ecs-otel"
        }
      }
    }
  ])
}