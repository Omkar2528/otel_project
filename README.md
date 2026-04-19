# SRE Observability Platform using OpenTelemetry, AWS, and Terraform

## Overview

This project demonstrates a cloud-native observability platform built using OpenTelemetry to enable vendor-agnostic monitoring.

The system is containerized using Docker and deployed on AWS using Terraform, following Infrastructure as Code (IaC) principles.

The primary goal of this project is to design a flexible observability architecture that avoids vendor lock-in and supports scalable, production-ready monitoring systems.

---

## Problem Statement

Traditional monitoring solutions tightly couple applications with specific vendors such as New Relic, Datadog, or CloudWatch.

This leads to:
- Vendor lock-in
- High migration cost
- Limited flexibility in multi-cloud environments

---

## Solution

This project uses OpenTelemetry to decouple telemetry generation from monitoring backends.

### Architecture Flow:

Application → OpenTelemetry SDK → OTel Collector → Monitoring Backend

- The application generates telemetry (traces/metrics)
- OpenTelemetry Collector processes and exports the data
- Data can be sent to any monitoring tool without changing application code

---

## Key Features

- Infrastructure provisioning using Terraform (AWS EC2)
- Containerized deployment using Docker and Docker Compose
- OpenTelemetry-based distributed tracing
- Vendor-agnostic observability architecture
- Automated deployment using EC2 user-data scripts
- Incident-ready design for SRE workflows

---

## Tech Stack

- AWS EC2
- Terraform (Infrastructure as Code)
- Docker & Docker Compose
- OpenTelemetry (OTel Collector + SDK)
- PHP Application

---

## Project Structure

├── app.php
├── Dockerfile
├── docker-compose.yml
├── otel-config.yaml
├── terraform/
├── scripts/
└── README.md




