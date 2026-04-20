# ── ECS Task Definition ──────────────────────────────────────────────────────
resource "aws_ecs_task_definition" "otel_app" {
  family                   = "otel-php-app"
  network_mode             = "awsvpc"
  requires_compatibilities = ["FARGATE"]
  cpu                      = "512"
  memory                   = "1024"

  # The execution role pulls ECR images and writes to CloudWatch Logs.
  # The task role grants the collector permission to write X-Ray, CloudWatch.
  execution_role_arn = var.ecs_execution_role_arn
  task_role_arn      = var.ecs_task_role_arn

  container_definitions = jsonencode([
    # ── PHP Application container ────────────────────────────────────────────
    {
      name      = "php-app"
      image     = "${aws_ecr_repository.php_app.repository_url}:latest"
      essential = true

      portMappings = [
        { containerPort = 80, protocol = "tcp" }
      ]

      environment = [
        # In ECS Fargate (awsvpc), the sidecar OTel Collector shares the
        # same network namespace — it IS reachable at 127.0.0.1.
        { name = "OTEL_EXPORTER_OTLP_ENDPOINT", value = "http://127.0.0.1:4318" },
        { name = "OTEL_SERVICE_NAME",            value = "otel-php-app" },
        { name = "OTEL_PHP_AUTOLOAD_ENABLED",    value = "true" },
        { name = "APP_ENV",                      value = "production" },
        { name = "DB_HOST",                      value = var.db_host },
        { name = "DB_NAME",                      value = var.db_name },
        { name = "DB_USER",                      value = var.db_user },
        { name = "DB_PASS",                      value = var.db_pass },
      ]

      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.ecs_logs.name
          "awslogs-region"        = var.region
          "awslogs-stream-prefix" = "php-app"
        }
      }

      healthCheck = {
        command     = ["CMD-SHELL", "curl -f http://localhost/health.php || exit 1"]
        interval    = 15
        timeout     = 5
        retries     = 3
        startPeriod = 30
      }

      dependsOn = [
        { containerName = "otel-collector", condition = "START" }
      ]
    },

    # ── OTel Collector sidecar ───────────────────────────────────────────────
    {
      name      = "otel-collector"
      image     = "${aws_ecr_repository.otel_collector.repository_url}:latest"
      essential = true

      portMappings = [
        { containerPort = 4317, protocol = "tcp" },
        { containerPort = 4318, protocol = "tcp" },
        { containerPort = 13133, protocol = "tcp" }
      ]

      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.ecs_logs.name
          "awslogs-region"        = var.region
          "awslogs-stream-prefix" = "otel-collector"
        }
      }

      healthCheck = {
        command     = ["CMD-SHELL", "wget --quiet --tries=1 --spider http://localhost:13133/ || exit 1"]
        interval    = 15
        timeout     = 5
        retries     = 3
        startPeriod = 10
      }
    }
  ])
}

# ── ECS Service ──────────────────────────────────────────────────────────────
resource "aws_ecs_service" "otel_service" {
  name            = "otel-service"
  cluster         = aws_ecs_cluster.cluster.id
  task_definition = aws_ecs_task_definition.otel_app.arn
  launch_type     = "FARGATE"
  desired_count   = 1

  network_configuration {
    subnets          = data.aws_subnets.default.ids
    security_groups  = [aws_security_group.ecs_sg.id]
    assign_public_ip = true
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.tg.arn
    container_name   = "php-app"
    container_port   = 80
  }

  # Allow Terraform to update the service without destroying it on image updates
  lifecycle {
    ignore_changes = [task_definition]
  }

  depends_on = [aws_lb_listener.listener]
}
