# Use a pinned version — "latest" breaks reproducibility
FROM otel/opentelemetry-collector-contrib:0.100.0

COPY otel-collector-config.yaml /etc/otelcol-contrib/config.yaml

# Run as non-root
USER 10001

ENTRYPOINT ["/otelcol-contrib"]
CMD ["--config", "/etc/otelcol-contrib/config.yaml"]
