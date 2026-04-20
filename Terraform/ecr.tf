# ── ECR repository for the PHP application ──────────────────────────────────
resource "aws_ecr_repository" "php_app" {
  name                 = "otel-php-app"
  force_delete         = true
  lifecycle {
    prevent_destroy    = true
  }
  image_tag_mutability = "MUTABLE"

  image_scanning_configuration {
    scan_on_push = true
  }

 
}

# ── ECR repository for the OTel Collector ────────────────────────────────────
resource "aws_ecr_repository" "otel_collector" {
  name                 = "otel-collector"
  force_delete         = true
  lifecycle {
    prevent_destroy    = true
  }
  image_tag_mutability = "MUTABLE"

  image_scanning_configuration {
    scan_on_push = true
  }

  
}

