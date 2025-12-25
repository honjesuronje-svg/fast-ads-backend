.PHONY: build run test clean docker-build

# Build the application
build:
	go build -o bin/ssai-service ./cmd/ssai-service

# Run the application
run:
	go run ./cmd/ssai-service

# Run tests
test:
	go test ./...

# Clean build artifacts
clean:
	rm -rf bin/

# Docker build
docker-build:
	docker build -t fast-ads-ssai:latest .

# Install dependencies
deps:
	go mod download
	go mod tidy

# Format code
fmt:
	go fmt ./...

# Lint code
lint:
	golangci-lint run

