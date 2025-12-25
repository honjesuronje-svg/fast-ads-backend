#!/bin/bash

# Create self-signed SSL certificates for testing
# These should be replaced with Let's Encrypt certificates in production

set -e

echo "Creating self-signed SSL certificates for testing..."

CERT_DIR="/etc/letsencrypt/live"

# Create directories
sudo mkdir -p $CERT_DIR/ads.wkkworld.com
sudo mkdir -p $CERT_DIR/api-ads.wkkworld.com

# Generate self-signed certificate for ads.wkkworld.com
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout $CERT_DIR/ads.wkkworld.com/privkey.pem \
    -out $CERT_DIR/ads.wkkworld.com/fullchain.pem \
    -subj "/C=US/ST=State/L=City/O=Organization/CN=ads.wkkworld.com"

# Generate self-signed certificate for api-ads.wkkworld.com
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout $CERT_DIR/api-ads.wkkworld.com/privkey.pem \
    -out $CERT_DIR/api-ads.wkkworld.com/fullchain.pem \
    -subj "/C=US/ST=State/L=City/O=Organization/CN=api-ads.wkkworld.com"

echo "Self-signed certificates created successfully!"
echo ""
echo "Note: These are for testing only. For production, use Let's Encrypt:"
echo "  sudo certbot certonly --nginx -d ads.wkkworld.com"
echo "  sudo certbot certonly --nginx -d api-ads.wkkworld.com"

