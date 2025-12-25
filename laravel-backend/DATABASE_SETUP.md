# Database Setup Guide

## Option 1: Use Existing PostgreSQL User

If you have an existing PostgreSQL user (like `wkkworld` or `securetv`), you can use it:

```bash
cd /home/lamkapro/fast-ads-backend/laravel-backend

# Edit .env
nano .env

# Change these lines:
DB_USERNAME=wkkworld  # or securetv, or postgres
DB_PASSWORD=your_existing_password
DB_DATABASE=fast_ads

# Create database as that user
sudo -u postgres psql -c "CREATE DATABASE fast_ads OWNER wkkworld;"
# or
createdb -U wkkworld fast_ads

# Run migrations
php artisan migrate
```

## Option 2: Fix Password Authentication

The issue might be with PostgreSQL's `pg_hba.conf` configuration. To fix:

```bash
# Edit pg_hba.conf
sudo nano /etc/postgresql/*/main/pg_hba.conf

# Find the line for IPv4 local connections:
# host    all             all             127.0.0.1/32            md5

# Make sure it uses 'md5' or 'password' (not 'peer' or 'ident')
# Then restart PostgreSQL:
sudo systemctl restart postgresql
```

## Option 3: Use MySQL Instead

If PostgreSQL is giving trouble, switch to MySQL:

```bash
cd /home/lamkapro/fast-ads-backend/laravel-backend

# Edit .env
nano .env

# Change:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fast_ads
DB_USERNAME=root
DB_PASSWORD=your_mysql_password

# Create database
mysql -u root -p -e "CREATE DATABASE fast_ads;"

# Run migrations
php artisan migrate
```

## Option 4: Use SQLite for Development

For quick testing without database setup:

```bash
cd /home/lamkapro/fast-ads-backend/laravel-backend

# Edit .env
nano .env

# Change:
DB_CONNECTION=sqlite
# Remove or comment out DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD

# Create database file
touch database/database.sqlite

# Run migrations
php artisan migrate
```

## Current Status

- Database user `fast_ads_user` created
- Database `fast_ads` created
- Password authentication issue (likely pg_hba.conf configuration)

## Quick Test

Test connection manually:
```bash
PGPASSWORD=fast_ads_password psql -h 127.0.0.1 -U fast_ads_user -d fast_ads -c "SELECT 1;"
```

If this works but Laravel doesn't, it's a configuration issue.

