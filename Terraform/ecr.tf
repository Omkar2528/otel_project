# ── ECR repository for the PHP application ──────────────────────────────────
resource "aws_ecr_repository" "php_app" {
  name                 = "otel-php-app"
  image_tag_mutability = "MUTABLE"

  image_scanning_configuration {
    scan_on_push = true
  }

  lifecycle_policy {
    policy = jsonencode({
      rules = [{
        rulePriority = 1
        description  = "Keep last 10 images"
        selection = {
          tagStatus   = "any"
          countType   = "imageCountMoreThan"
          countNumber = 10
        }
        action = { type = "expire" }
      }]
    })
  }
}

# ── ECR repository for the OTel Collector ────────────────────────────────────
resource "aws_ecr_repository" "otel_collector" {
  name                 = "otel-collector"
  image_tag_mutability = "MUTABLE"

  image_scanning_configuration {
    scan_on_push = true
  }

  lifecycle_policy {
    policy = jsonencode({
      rules = [{
        rulePriority = 1
        description  = "Keep last 10 images"
        selection = {
          tagStatus   = "any"
          countType   = "imageCountMoreThan"
          countNumber = 10
        }
        action = { type = "expire" }
      }]
    })
  }
}

output "php_app_ecr_url" {
  description = "ECR URL for the PHP application"
  value       = aws_ecr_repository.php_app.repository_url
}

output "otel_collector_ecr_url" {
  description = "ECR URL for the OTel Collector"
  value       = aws_ecr_repository.otel_collector.repository_url
}
