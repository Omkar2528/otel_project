#!/bin/bash
yum update -y

# Install Docker
amazon-linux-extras install docker -y
service docker start
usermod -a -G docker ec2-user

# Install Docker Compose
curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose

# Clone repo
cd /home/ec2-user
git clone https://github.com/<your-username>/otel-sre-platform.git
cd otel-sre-platform

# Run app
docker-compose up -d