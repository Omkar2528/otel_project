################################
# ECS CLUSTER & IAM
################################
resource "aws_ecs_cluster" "cluster" {
  name = var.cluster_name
}

# Role for the EC2 Instance (Standalone Host)
resource "aws_iam_role" "ecs_instance_role" {
  name = "${var.cluster_name}-instance-role"
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action    = "sts:AssumeRole"
      Effect    = "Allow"
      Principal = { Service = "ec2.amazonaws.com" }
    }]
  })
}

resource "aws_iam_role_policy_attachment" "ecs_instance_policy" {
  role       = aws_iam_role.ecs_instance_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonEC2ContainerServiceforEC2Role"
}

resource "aws_iam_instance_profile" "ecs_instance_profile" {
  name = "${var.cluster_name}-instance-profile"
  role = aws_iam_role.ecs_instance_role.name
}

# Role for ECS Task Execution (Pulling images/logging)
resource "aws_iam_role" "ecs_task_execution_role" {
  name = "${var.cluster_name}-task-exec-role"
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action    = "sts:AssumeRole"
      Effect    = "Allow"
      Principal = { Service = "ecs-tasks.amazonaws.com" }
    }]
  })
}

resource "aws_iam_role_policy_attachment" "task_exec_policy" {
  role       = aws_iam_role.ecs_task_execution_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy"
}

################################
# NETWORKING (SG & ALB)
################################
resource "aws_security_group" "alb_sg" {
  name   = "otel-alb-sg"
  vpc_id = var.vpc_id
  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_security_group" "ecs_node_sg" {
  name   = "ecs-node-sg"
  vpc_id = var.vpc_id
  ingress {
    from_port       = 0
    to_port         = 65535
    protocol        = "tcp"
    security_groups = [aws_security_group.alb_sg.id]
  }
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_lb" "alb" {
  name               = "otel-java-alb"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb_sg.id]
  subnets            = var.public_subnets
}

resource "aws_lb_target_group" "tg" {
  name        = "otel-java-tg"
  port        = var.app_port
  protocol    = "HTTP"
  vpc_id      = var.vpc_id
  target_type = "ip"
  health_check {
    path    = "/actuator/health"
    matcher = "200-399"
  }
}

resource "aws_lb_listener" "http" {
  load_balancer_arn = aws_lb.alb.arn
  port              = 80
  protocol          = "HTTP"
  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.tg.arn
  }
}

################################
# COMPUTE (EC2 HOST)
################################
resource "aws_launch_template" "ecs_lt" {
  name_prefix   = "ecs-node-"
  image_id      = "ami-0750da559591e1d32" # AL2023 ECS-Optimized us-east-1
  instance_type = "t3.medium"

  iam_instance_profile { name = aws_iam_instance_profile.ecs_instance_profile.name }

  user_data = base64encode(<<-EOF
              #!/bin/bash
              echo ECS_CLUSTER=${var.cluster_name} >> /etc/ecs/ecs.config
              EOF
  )

  network_interfaces {
    associate_public_ip_address = false
    security_groups             = [aws_security_group.ecs_node_sg.id]
  }
}

resource "aws_autoscaling_group" "ecs_asg" {
  vpc_zone_identifier = var.private_subnets
  desired_capacity    = 1
  max_size            = 2
  min_size            = 1
  launch_template {
    id      = aws_launch_template.ecs_lt.id
    version = "$Latest"
  }
}

################################
# ECS TASK & SERVICE
################################
resource "aws_ecs_task_definition" "app_task" {
  family                   = "otel-java-stack"
  network_mode             = "awsvpc"
  requires_compatibilities = ["EC2"]
  cpu                      = "512"
  memory                   = "1024"
  execution_role_arn       = aws_iam_role.ecs_task_execution_role.arn

  container_definitions = jsonencode([
    {
      name         = "java-app"
      image        = var.app_image
      essential    = true
      portMappings = [{ containerPort = var.app_port }]
      environment = [
        { name = "OTEL_EXPORTER_OTLP_ENDPOINT", value = "http://localhost:4317" },
        { name = "OTEL_SERVICE_NAME", value = "java-ecommerce-app" }
      ]
    },
    {
      name         = "otel-collector"
      image        = var.agent_image
      essential    = true
      portMappings = [{ containerPort = 4317 }, { containerPort = 4318 }]
    }
  ])
}

resource "aws_ecs_service" "app_service" {
  name            = "java-app-service"
  cluster         = aws_ecs_cluster.cluster.id
  task_definition = aws_ecs_task_definition.app_task.arn
  launch_type     = "EC2"
  desired_count   = 1

  load_balancer {
    target_group_arn = aws_lb_target_group.tg.arn
    container_name   = "java-app"
    container_port   = var.app_port
  }

  network_configuration {
    subnets         = var.private_subnets
    security_groups = [aws_security_group.ecs_node_sg.id]
  }
}