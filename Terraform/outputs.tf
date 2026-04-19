output "alb_dns_name" {
  description = "The DNS name of the application load balancer"
  value       = aws_lb.alb.dns_name
}

output "ecs_cluster_name" {
  description = "The name of the ECS cluster"
  value       = aws_ecs_cluster.cluster.name
}

output "ecs_service_name" {
  description = "The name of the ECS service"
  value       = aws_ecs_service.app_service.name
}