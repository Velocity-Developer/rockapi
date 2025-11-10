# Build Extraction Script

Script PHP untuk mengekstrak file build.zip backend dan frontend ke direktori yang ditentukan.

## Lokasi File

- **Script**: `rockapi/extract-build.php`
- **Konfigurasi**: `rockapi/.env`

## Konfigurasi Environment

Tambahkan konfigurasi berikut ke file `.env`:

```env
# Extract Build Configuration
EXTRACT_BUILD_SECRET_KEY=your_super_secret_key_change_this_in_production
BACKEND_BUILD_PATH=/laravel/build.zip
FRONTEND_BUILD_PATH=/public_html/build.zip
BACKEND_EXTRACT_PATH=/laravel/
FRONTEND_EXTRACT_PATH=/public_html/
LOG_PATH=/laravel/logs/extract-build.log
LOG_MAX_SIZE=10485760
LOG_RETENTION_DAYS=30
```

### Konfigurasi Production

Untuk production server, sesuaikan path sesuai struktur folder:

```env
# Production Configuration
EXTRACT_BUILD_SECRET_KEY=prod_secret_key_here
BACKEND_BUILD_PATH=/home/user/laravel/build.zip
FRONTEND_BUILD_PATH=/home/user/public_html/build.zip
BACKEND_EXTRACT_PATH=/home/user/laravel/
FRONTEND_EXTRACT_PATH=/home/user/public_html/
LOG_PATH=/home/user/laravel/logs/extract-build.log
```

## Penggunaan

### URL Format

```
GET /laravel/extract-build.php?secret=SECRET_KEY&target=TARGET&dry_run=BOOLEAN
```

### Parameters

| Parameter | Required | Default | Options | Description |
|-----------|----------|---------|---------|-------------|
| `secret` | Yes | - | String | Secret key dari .env |
| `target` | No | `all` | `backend`, `frontend`, `all` | Target yang diekstrak |
| `dry_run` | No | `false` | `true`, `false` | Mode preview tanpa ekstrak |

### Examples

#### 1. Ekstrak Backend dan Frontend
```bash
curl "http://localhost/rockapi/extract-build.php?secret=your_secret_key&target=all"
```

#### 2. Ekstrak Backend Saja
```bash
curl "http://localhost/rockapi/extract-build.php?secret=your_secret_key&target=backend"
```

#### 3. Ekstrak Frontend Saja
```bash
curl "http://localhost/rockapi/extract-build.php?secret=your_secret_key&target=frontend"
```

#### 4. Dry Run Mode (Preview)
```bash
curl "http://localhost/rockapi/extract-build.php?secret=your_secret_key&target=all&dry_run=true"
```

## Response Format

### Success Response

```json
{
  "status": "success",
  "message": "Build extraction completed",
  "execution": {
    "dry_run": false,
    "timestamp": "2025-01-10 10:30:45",
    "duration_ms": 2340
  },
  "results": {
    "backend": {
      "status": "extracted",
      "message": "Backend extracted successfully",
      "zip_path": "/laravel/build.zip",
      "extract_path": "/laravel/",
      "zip_size": "15.2 MB",
      "files_count": 245
    },
    "frontend": {
      "status": "extracted",
      "message": "Frontend extracted successfully",
      "zip_path": "/public_html/build.zip",
      "extract_path": "/public_html/",
      "zip_size": "3.8 MB",
      "files_count": 89
    }
  }
}
```

### Dry Run Response

```json
{
  "status": "success",
  "message": "Dry run completed - no files extracted",
  "execution": {
    "dry_run": true,
    "timestamp": "2025-01-10 10:25:15",
    "duration_ms": 120
  },
  "preview": {
    "backend": {
      "zip_exists": true,
      "zip_size": "15.2 MB",
      "extract_path_writable": true,
      "will_extract": true,
      "zip_path": "/laravel/build.zip",
      "extract_path": "/laravel/"
    },
    "frontend": {
      "zip_exists": true,
      "zip_size": "3.8 MB",
      "extract_path_writable": true,
      "will_extract": true,
      "zip_path": "/public_html/build.zip",
      "extract_path": "/public_html/"
    }
  }
}
```

### Error Response

```json
{
  "status": "error",
  "error_type": "authentication",
  "message": "Invalid secret key",
  "code": 403,
  "execution": {
    "dry_run": false,
    "timestamp": "2025-01-10 10:30:45",
    "duration_ms": 45
  }
}
```

## Logging

Script akan membuat log file di lokasi yang dikonfigurasi (`LOG_PATH`).

### Log Format

```
[2025-01-10 10:30:45] INFO: Authentication successful (IP: 192.168.1.100)
[2025-01-10 10:30:45] INFO: START: target=all, dry_run=false (IP: 192.168.1.100)
[2025-01-10 10:30:45] INFO: Starting extraction process (IP: 192.168.1.100)
[2025-01-10 10:30:46] INFO: Starting backend extraction from /laravel/build.zip to /laravel/ (IP: 192.168.1.100)
[2025-01-10 10:30:46] INFO: Backend ZIP contains 245 files (IP: 192.168.1.100)
[2025-01-10 10:30:47] INFO: Backend extraction completed successfully (IP: 192.168.1.100)
[2025-01-10 10:30:47] INFO: Starting frontend extraction from /public_html/build.zip to /public_html/ (IP: 192.168.1.100)
[2025-01-10 10:30:47] INFO: Frontend ZIP contains 89 files (IP: 192.168.1.100)
[2025-01-10 10:30:48] INFO: Frontend extraction completed successfully (IP: 192.168.1.100)
[2025-01-10 10:30:48] INFO: Extraction process completed (IP: 192.168.1.100)
```

### Log Rotation

- **Max Size**: 10MB (dapat diubah dengan `LOG_MAX_SIZE`)
- **Retention**: 30 hari (dapat diubah dengan `LOG_RETENTION_DAYS`)
- **Rotated Files**: `extract-build-YYYY-MM-DD-HH-mm-ss.log`

## Security

### Secret Key

Generate secret key yang kuat dan unik:

```bash
# Generate random secret key
openssl rand -base64 32
```

### File Permissions

Pastikan file dan folder memiliki permission yang tepat:

```bash
# Script file
chmod 644 extract-build.php

# Log directory
mkdir -p logs
chmod 755 logs

# Extract directories
chmod 755 /laravel/
chmod 755 /public_html/
```

### Access Control

1. **IP Whitelist** (optional): Tambahkan validasi IP jika diperlukan
2. **Rate Limiting**: Implementasikan rate limiting untuk prevent abuse
3. **HTTPS**: Gunakan HTTPS untuk production environment

## Error Types

| Error Type | HTTP Code | Description |
|------------|-----------|-------------|
| `authentication` | 403 | Invalid or missing secret key |
| `validation` | 400 | Invalid parameters |
| `file_not_found` | 404 | Build ZIP file not found |
| `permission` | 500 | Directory not writable |
| `general` | 400 | General error |
| `exception` | 500 | Unexpected exception |

## Integration Examples

### CI/CD Pipeline

```bash
#!/bin/bash
# deploy.sh

# Extract builds
curl -s "https://yoursite.com/extract-build.php?secret=$SECRET_KEY&target=all"

# Check response
if [[ $? -eq 0 ]]; then
    echo "Deployment successful"
else
    echo "Deployment failed"
    exit 1
fi
```

### Webhook Integration

```php
// webhook-handler.php
<?php
$secret = $_ENV['EXTRACT_BUILD_SECRET_KEY'];
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if ($data['event'] === 'build.completed') {
    // Trigger extraction
    file_get_contents("https://yoursite.com/extract-build.php?secret={$secret}&target=all");
}
```

## Troubleshooting

### Common Issues

1. **Permission Denied**
   ```bash
   chmod 755 /laravel/
   chmod 755 /public_html/
   ```

2. **ZIP File Not Found**
   - Pastikan build.zip ada di path yang benar
   - Check file path di konfigurasi .env

3. **Secret Key Invalid**
   - Verify secret key di .env
   - Check untuk whitespace atau karakter khusus

4. **Log Directory Not Writable**
   ```bash
   mkdir -p logs
   chmod 755 logs
   ```

### Debug Mode

Enable error logging dengan memeriksa log file:

```bash
tail -f logs/extract-build.log
```

## Production Deployment

1. **Update .env** dengan production values
2. **Generate strong secret key**
3. **Set proper file permissions**
4. **Test dengan dry run mode**
5. **Monitor logs** untuk aktivitas
6. **Implement rate limiting** jika diperlukan
7. **Backup files** sebelum ekstrak (optional)

## Requirements

- PHP 8.0+
- ZipArchive extension
- Writable log directory
- Writable extract directories
- Proper file permissions