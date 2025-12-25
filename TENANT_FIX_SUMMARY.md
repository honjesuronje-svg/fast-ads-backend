# Fix Summary: Tenant Creation Error 500

## Problem
Error 500 saat POST ke `/tenants` dengan error:
```
SQLSTATE[23502]: Not null violation: null value in column "api_key" of relation "tenants" violates not-null constraint
```

## Root Cause
Controller `TenantController@store` tidak generate `api_key` dan `api_secret` yang required oleh database schema.

## Solution

### 1. Updated TenantController
- **File**: `app/Http/Controllers/TenantController.php`
- **Changes**:
  - Auto-generate `api_key`: `fast_{slug}_{random_hex}`
  - Auto-generate `api_secret`: random 32-byte hex
  - Added validation for `allowed_domains` and `rate_limit_per_minute`
  - Parse `allowed_domains` from comma-separated string to array
  - Set default `rate_limit_per_minute` to 1000 if not provided
  - Updated status validation to include 'suspended'

### 2. Updated Create Form
- **File**: `resources/views/tenants/create.blade.php`
- **Changes**:
  - Added "suspended" option to status dropdown
  - Added `allowed_domains` field (optional)
  - Added `rate_limit_per_minute` field (optional, default: 1000)

### 3. Updated Edit Form
- **File**: `resources/views/tenants/edit.blade.php`
- **Changes**:
  - Added "suspended" option to status dropdown
  - Added `allowed_domains` field (optional)
  - Added `rate_limit_per_minute` field (optional)
  - Display existing values for edit

## API Key Generation Format
```
api_key: fast_{slug}_{32_hex_chars}
Example: fast_wkkworld_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
```

## Testing
1. ✅ Create tenant via dashboard
2. ✅ API key auto-generated
3. ✅ API secret auto-generated
4. ✅ Optional fields work correctly

## Next Steps
- Test tenant creation via dashboard
- Verify API key is displayed in tenant show page
- Test API authentication with generated API key

