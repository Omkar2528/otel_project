FROM otel/opentelemetry-collector-contrib:latest

# Copy your config into the container
COPY otel-collector-config.yaml /etc/otelcol/config.yaml

# Set the entrypoint to use this config
ENTRYPOINT ["/otelcol-contrib"]
CMD ["--config", "/etc/otelcol/config.yaml"]