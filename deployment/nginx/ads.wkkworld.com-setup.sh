#!/bin/bash

# Setup script for ads.wkkworld.com nginx configuration
# Run this script as root or with sudo

set -e

echo "=========================================="
echo "FAST Ads Backend - Nginx Setup"
echo "Domain: ads.wkkworld.com"
echo "=========================================="

# Variables
NGINX_CONF_DIR="/etc/nginx/conf.d"
NGINX_SITES_DIR="/etc/nginx/sites-available"
NGINX_SITES_ENABLED="/etc/nginx/sites-enabled"
PROJECT_DIR="/var/www/fast-ads-backend"
CONF_FILE="ads.wkkworld.com.conf"

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root or with sudo"
    exit 1
fi

# Create project directory if not exists
echo "Creating project directory..."
mkdir -p $PROJECT_DIR
mkdir -p $PROJECT_DIR/laravel-backend/public
mkdir -p /var/log/nginx

# Copy nginx configuration
echo "Installing nginx configuration..."
if [ -d "$NGINX_SITES_DIR" ]; then
    # Debian/Ubuntu style
    cp deployment/nginx/conf.d/$CONF_FILE $NGINX_SITES_DIR/$CONF_FILE
    ln -sf $NGINX_SITES_DIR/$CONF_FILE $NGINX_SITES_ENABLED/$CONF_FILE
else
    # CentOS/RHEL style
    cp deployment/nginx/conf.d/$CONF_FILE $NGINX_CONF_DIR/$CONF_FILE
fi

# Create SSL directory if not exists
echo "Creating SSL certificate directory..."
mkdir -p /etc/letsencrypt/live/ads.wkkworld.com

# Test nginx configuration
echo "Testing nginx configuration..."
nginx -t

if [ $? -eq 0 ]; then
    echo "✓ Nginx configuration is valid"
else
    echo "✗ Nginx configuration has errors. Please fix them before proceeding."
    exit 1
fi

# Reload nginx
echo "Reloading nginx..."
systemctl reload nginx

echo ""
echo "=========================================="
echo "Setup completed!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Install SSL certificate:"
echo "   certbot certonly --nginx -d ads.wkkworld.com"
echo ""
echo "2. Update PHP-FPM socket path in nginx config if needed:"
echo "   Current: /var/run/php/php8.1-fpm.sock"
echo "   Check: ls /var/run/php/"
echo ""
echo "3. Set proper permissions for Laravel:"
echo "   chown -R www-data:www-data $PROJECT_DIR/laravel-backend"
echo "   chmod -R 755 $PROJECT_DIR/laravel-backend/storage"
echo "   chmod -R 755 $PROJECT_DIR/laravel-backend/bootstrap/cache"
echo ""
echo "4. Update Laravel .env file:"
echo "   APP_URL=https://ads.wkkworld.com"
echo ""
echo "5. Test the configuration:"
echo "   curl -I https://ads.wkkworld.com"
echo "   curl -I https://ads.wkkworld.com/api/v1/ads/decision"
echo "   curl -I https://ads.wkkworld.com/fast/tenant1/channel1.m3u8"
echo ""

