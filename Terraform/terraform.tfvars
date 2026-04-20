region       = "us-east-1"
app_port     = 80
cluster_name = "otel-cluster"

# Set these to your actual IAM role ARNs
ecs_execution_role_arn = "arn:aws:iam::056449379091:role/ecsTaskExecutionRole"
ecs_task_role_arn      = "arn:aws:iam::056449379091:role/ecsTaskRole"

# Set to your RDS endpoint in production
db_host = "your-rds-endpoint.rds.amazonaws.com"
db_name = "test"
db_user = "root"
# db_pass — pass via TF_VAR_db_pass env var; never commit to git
