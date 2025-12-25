# Nginx Configuration for FAST Ads Backend

## Domains

### 1. ads.wkkworld.com
**Purpose:** Laravel Dashboard/Admin Panel
- Dashboard: `https://ads.wkkworld.com/dashboard`
- Login: `https://ads.wkkworld.com/login`
- Web routes for admin interface

### 2. api-ads.wkkworld.com
**Purpose:** API Endpoints and SSAI Service
- API Endpoints: `https://api-ads.wkkworld.com/api/v1/*`
- SSAI Service: `https://api-ads.wkkworld.com/fast/*`
- Health Check: `https://api-ads.wkkworld.com/health`

## Installation

### Quick Setup
```bash
cd /home/lamkapro/fast-ads-backend/deployment/nginx
sudo ./setup-domains.sh
```

### Manual Setup

1. **Copy configuration files:**
   ```bash
   # For Debian/Ubuntu
   sudo cp conf.d/ads.wkkworld.com.conf /etc/nginx/sites-available/
   sudo cp conf.d/api-ads.wkkworld.com.conf /etc/nginx/sites-available/
   sudo ln -s /etc/nginx/sites-available/ads.wkkworld.com.conf /etc/nginx/sites-enabled/
   sudo ln -s /etc/nginx/sites-available/api-ads.wkkworld.com.conf /etc/nginx/sites-enabled/
   
   # For CentOS/RHEL
   sudo cp conf.d/*.conf /etc/nginx/conf.d/
   ```

2. **Test configuration:**
   ```bash
   sudo nginx -t
   ```

3. **Reload nginx:**
   ```bash
   sudo systemctl reload nginx
   ```

## SSL Certificates

### Install with Certbot
```bash
# Install certbot if not installed
sudo apt-get install certbot python3-certbot-nginx

# Get certificates
sudo certbot certonly --nginx -d ads.wkkworld.com
sudo certbot certonly --nginx -d api-ads.wkkworld.com

# Or get both at once
sudo certbot certonly --nginx -d ads.wkkworld.com -d api-ads.wkkworld.com
```

### Auto-renewal
Certbot should set up auto-renewal automatically. Test with:
```bash
sudo certbot renew --dry-run
```

## Configuration Details

### ads.wkkworld.com (Dashboard)
- **Port:** 443 (HTTPS), 80 (HTTP redirect)
- **Backend:** Laravel PHP-FPM (port 8000)
- **Purpose:** Admin dashboard, login, management pages
- **Features:**
  - Static file caching
  - Gzip compression
  - Security headers

### api-ads.wkkworld.com (API)
- **Port:** 443 (HTTPS), 80 (HTTP redirect)
- **Backend:** 
  - Laravel PHP-FPM for `/api/v1/*` (port 8000)
  - Golang SSAI for `/fast/*` (port 8080)
- **Purpose:** REST API endpoints and SSAI service
- **Features:**
  - Rate limiting (100 req/min for API, 1000 req/min for SSAI)
  - CORS headers
  - HLS manifest caching
  - Cloudflare header support

## Endpoints

### API Endpoints (api-ads.wkkworld.com)
```
POST /api/v1/ads/decision          - Ad decision API
GET  /api/v1/ads/vmap/{tenant}/{channel} - VMAP generation
GET  /api/v1/ads/vast/{tenant}/{channel} - VAST generation
POST /api/v1/tracking/events       - Tracking events
GET  /fast/{tenant}/{channel}.m3u8 - SSAI manifest
GET  /health                        - Health check
```

### Dashboard (ads.wkkworld.com)
```
GET  /                              - Redirect to dashboard
GET  /login                         - Login page
GET  /dashboard                     - Dashboard overview
GET  /tenants                       - Tenants management
GET  /channels                      - Channels management
GET  /ads                           - Ads management
GET  /campaigns                     - Campaigns management
GET  /api-keys                      - API keys management
```

## Rate Limiting

### API Endpoints
- **Zone:** `api_limit`
- **Rate:** 100 requests per minute
- **Burst:** 20 requests

### SSAI Service
- **Zone:** `ssai_limit`
- **Rate:** 1000 requests per minute
- **Burst:** 50 requests (manifests), 200 requests (segments)

## Troubleshooting

### Check nginx status
```bash
sudo systemctl status nginx
```

### Check nginx logs
```bash
# Dashboard logs
sudo tail -f /var/log/nginx/ads.wkkworld.com.access.log
sudo tail -f /var/log/nginx/ads.wkkworld.com.error.log

# API logs
sudo tail -f /var/log/nginx/api-ads.wkkworld.com.access.log
sudo tail -f /var/log/nginx/api-ads.wkkworld.com.error.log
```

### Test endpoints
```bash
# Dashboard
curl -I https://ads.wkkworld.com

# API
curl -I https://api-ads.wkkworld.com/api/v1/ads/decision

# SSAI
curl -I https://api-ads.wkkworld.com/fast/tenant1/channel1.m3u8
```

### Check PHP-FPM socket
```bash
ls -la /var/run/php/
```

If PHP version is different, update the socket path in nginx configs:
```nginx
fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
```

### Check Laravel permissions
```bash
sudo chown -R www-data:www-data /var/www/fast-ads-backend/laravel-backend
sudo chmod -R 755 /var/www/fast-ads-backend/laravel-backend/storage
sudo chmod -R 755 /var/www/fast-ads-backend/laravel-backend/bootstrap/cache
```

## DNS Configuration

Add these A records to your DNS:
```
ads.wkkworld.com      A    YOUR_SERVER_IP
api-ads.wkkworld.com  A    YOUR_SERVER_IP
```

Or use CNAME if you have a main domain:
```
ads.wkkworld.com      CNAME    main.wkkworld.com
api-ads.wkkworld.com  CNAME    main.wkkworld.com
```

## Security Notes

1. **SSL/TLS:** Always use HTTPS in production
2. **Rate Limiting:** Adjust limits based on your traffic
3. **Firewall:** Only allow ports 80, 443, and SSH (22)
4. **Updates:** Keep nginx and SSL certificates updated
5. **Logs:** Monitor logs regularly for suspicious activity

## Performance Tuning

### Increase worker connections
Edit `/etc/nginx/nginx.conf`:
```nginx
worker_processes auto;
worker_connections 4096;
```

### Enable caching (optional)
Add to nginx config:
```nginx
proxy_cache_path /var/cache/nginx levels=1:2 keys_zone=ssai_cache:10m max_size=1g inactive=60m;
```

## Support

For issues or questions:
1. Check nginx error logs
2. Verify SSL certificates
3. Check Laravel and Golang services are running
4. Verify DNS records
5. Check firewall rules

