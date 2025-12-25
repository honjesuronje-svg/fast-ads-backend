# Installing Go for Phase 2

## Quick Install (Ubuntu/Debian)

```bash
# Remove old Go if exists
sudo rm -rf /usr/local/go

# Download Go 1.21
wget https://go.dev/dl/go1.21.5.linux-amd64.tar.gz

# Extract
sudo tar -C /usr/local -xzf go1.21.5.linux-amd64.tar.gz

# Add to PATH (add to ~/.bashrc or ~/.profile)
echo 'export PATH=$PATH:/usr/local/go/bin' >> ~/.bashrc
source ~/.bashrc

# Verify
go version
```

## Alternative: Using Package Manager

```bash
# Ubuntu 22.04+
sudo apt-get update
sudo apt-get install golang-go

# Verify
go version
```

## Setup Go Workspace

```bash
# Set GOPATH (optional, Go 1.11+ uses modules)
export GOPATH=$HOME/go
export PATH=$PATH:$GOPATH/bin

# Add to ~/.bashrc
echo 'export GOPATH=$HOME/go' >> ~/.bashrc
echo 'export PATH=$PATH:$GOPATH/bin' >> ~/.bashrc
```

## Verify Installation

```bash
go version
# Should output: go version go1.21.x linux/amd64

go env
# Check Go environment
```

## Next Steps

After installing Go:

```bash
cd /home/lamkapro/fast-ads-backend/golang-ssai

# Download dependencies
go mod download

# Build
go build -o bin/ssai-service ./cmd/ssai-service

# Run
./bin/ssai-service
```

