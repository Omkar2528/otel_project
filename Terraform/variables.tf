variable "region" {
  default = "us-east-1"
}

variable "app_port" {
  description = "The port the application will run on"
  type        = number
}

variable "cluster_name" {
  description = "The name of the ECS cluster"
  type        = string
}