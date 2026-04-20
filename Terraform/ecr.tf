resource "aws_ecr_repository" "app_repo" {
  name = "otel-php-app"

  image_scanning_configuration {
    scan_on_push = true
  }

  image_tag_mutability = "MUTABLE"
}

output "ecr_repo_url" {
  value = aws_ecr_repository.app_repo.repository_url
}