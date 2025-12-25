#!/bin/bash

# Setup script for both domains: ads.wkkworld.com and api-ads.wkkworld.com
# Run this script as root or with sudo

set -e

echo "=========================================="
echo "FAST Ads Backend - Nginx Domain Setup"
echo "Dashboard: ads.wkkworld.com"
echo "API: api-ads.wkkworld.com"
echo "=========================================="

# Variables
NGINX_CONF_DIR="/etc/nginx/conf.d"
NGINX_SITES_DIR="/etc/nginx/sites-available"
NGINX_SITES_ENABLED="/etc/nginx/sites-enabled"
PROJECT_DIR="/var/www/fast-ads-backend"
DASHBOARD_CONF="ads.wkkworld.com.conf"
API_CONF="api-ads.wkkworld.com.conf"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

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

# Copy nginx configurations
echo "Installing nginx configurations..."

if [ -d "$NGINX_SITES_DIR" ]; then
    # Debian/Ubuntu style
    echo "  - Installing $DASHBOARD_CONF..."
    cp $SCRIPT_DIR/conf.d/$DASHBOARD_CONF $NGINX_SITES_DIR/$DASHBOARD_CONF
    ln -sf $NGINX_SITES_DIR/$DASHBOARD_CONF $NGINX_SITES_ENABLED/$DASHBOARD_CONF
    
    echo "  - Installing $API_CONF..."
    cp $SCRIPT_DIR/conf.d/$API_CONF $NGINX_SITES_DIR/$API_CONF
    ln -sf $NGINX_SITES_DIR/$API_CONF $NGINX_SITES_ENABLED/$API_CONF
else
    # CentOS/RHEL style
    echo "  - Installing $DASHBOARD_CONF..."
    cp $SCRIPT_DIR/conf.d/$DASHBOARD_CONF $NGINX_CONF_DIR/$DASHBOARD_CONF
    
    echo "  - Installing $API_CONF..."
    cp $SCRIPT_DIR/conf.d/$API_CONF $NGINX_CONF_DIR/$API_CONF
fi

# Create SSL directories if not exists
echo "Creating SSL certificate directories..."
mkdir -p /etc/letsencrypt/live/ads.wkkworld.com
mkdir -p /etc/letsencrypt/live/api-ads.wkkworld.com

# Copy rate limiting configuration
echo "Installing rate limiting configuration..."
cp $SCRIPT_DIR/rate-limiting.conf /etc/nginx/rate-limiting.conf

# Add rate limiting include to nginx.conf if not exists
if ! grep -q "include /etc/nginx/rate-limiting.conf" /etc/nginx/nginx.conf; then
    echo "Adding rate limiting include to nginx.conf..."
    # Find http block and add include before closing brace
    sed -i '/^http {/a\    include /etc/nginx/rate-limiting.conf;' /etc/nginx/nginx.conf
fi

# Test nginx configuration
echo ""
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
echo ""
echo "1. Install SSL certificates:"
echo "   certbot certonly --nginx -d ads.wkkworld.com"
echo "   certbot certonly --nginx -d api-ads.wkkworld.com"
echo ""
echo "2. Update PHP-FPM socket path in nginx configs if needed:"
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
echo "5. Test the configurations:"
echo "   # Dashboard"
echo "   curl -I https://ads.wkkworld.com"
echo "   curl -I https://ads.wkkworld.com/login"
echo ""
echo "   # API"
echo "   curl -I https://api-ads.wkkworld.com/api/v1/ads/decision"
echo "   curl -I https://api-ads.wkkworld.com/fast/tenant1/channel1.m3u8"
echo ""
echo "6. Update DNS records:"
echo "   A record: ads.wkkworld.com -> Your Server IP"
echo "   A record: api-ads.wkkworld.com -> Your Server IP"
echo ""

