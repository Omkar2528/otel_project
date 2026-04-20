output "alb_dns_name" {
  description = "Public DNS name of the Application Load Balancer"
  value       = aws_lb.alb.dns_name
}

output "php_app_ecr_url" {
  description = "ECR URL for the PHP application image"
  value       = aws_ecr_repository.php_app.repository_url
}

output "otel_collector_ecr_url" {
  description = "ECR URL for the OTel Collector image"
  value       = aws_ecr_repository.otel_collector.repository_url
}

output "ecs_cluster_name" {
  description = "ECS Cluster name"
  value       = aws_ecs_cluster.cluster.name
}
