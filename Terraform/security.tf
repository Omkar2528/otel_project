# ── ECS Task Security Group ──────────────────────────────────────────────────
resource "aws_security_group" "ecs_sg" {
  name   = "otel-ecs-sg"
  vpc_id = data.aws_vpc.default.id

  # Accept traffic from the ALB only
  ingress {
    from_port       = 80
    to_port         = 80
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