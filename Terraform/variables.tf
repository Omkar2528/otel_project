variable "region" {
  description = "AWS region"
  type        = string
  default     = "us-east-1"
}

variable "app_port" {
  description = "Port the PHP application listens on"
  type        = number
  default     = 80
}

variable "cluster_name" {
  description = "Name of the ECS cluster"
  type        = string
  default     = "otel-cluster"
}

variable "ecs_execution_role_arn" {
  description = "IAM role ARN for ECS task execution (pulls ECR images, writes CloudWatch Logs)"
  type        = string
  # Example: "arn:aws:iam::123456789012:role/ecsTaskExecutionRole"
}

variable "ecs_task_role_arn" {
  description = "IAM role ARN for ECS task (grants X-Ray, CloudWatch Metrics, etc.)"
  type        = string
  # Example: "arn:aws:iam::123456789012:role/ecsTaskRole"
}

variable "db_host" {
  description = "MySQL host (RDS endpoint in production)"
  type        = string
  default     = "mysql"
}

variable "db_name" {
  description = "Database name"
  type        = string
  default     = "test"
}

variable "db_user" {
  description = "Database username"
  type        = string
  default     = "root"
}

variable "db_pass" {
  description = "Database password"
  type        = string
  default     = "root"
}
