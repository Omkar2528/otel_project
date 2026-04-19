variable "vpc_id" { default = "vpc-0018aa4902fa67a2c" }

variable "public_subnets" {
  type    = list(string)
  default = ["subnet-0d062118b30606dce", "subnet-04d2a16e59711c474"]
}

variable "private_subnets" {
  type    = list(string)
  default = ["subnet-0548c87344ac6f8a2", "subnet-0a699b262130a98b7"]
}

variable "cluster_name" { default = "otel-sample-apps" }

variable "app_image" {
  default = "584554046133.dkr.ecr.us-east-1.amazonaws.com/java-ecommerce:latest"
}

variable "agent_image" {
  default = "584554046133.dkr.ecr.us-east-1.amazonaws.com/otel-collector:latest"
}

variable "app_port" { default = 8082 }