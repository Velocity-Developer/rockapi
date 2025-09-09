# Laravel Build Script untuk Production

Script ini digunakan untuk membungkus aplikasi Laravel dalam file ZIP yang siap untuk deployment ke production server.

## Instalasi

```bash
npm install
```

## Penggunaan

### Build aplikasi
```bash
npm run build
```

### Bersihkan file build lama
```bash
npm run clean
```

Menghapus folder `dist/` beserta semua isinya untuk membersihkan hasil build lama.

### Build dengan membersihkan file lama terlebih dahulu
```bash
npm run build:clean
```

## File dan Folder yang Dikecualikan

Script ini secara otomatis mengecualikan file dan folder berikut:

- `node_modules/` - Dependencies Node.js
- `.git/` - Git repository
- `.github/` - GitHub workflows
- `storage/` - Public storage files
- `bootstrap/cache/*` - Bootstrap cache
- `.env` - Environment file
- `.env.example` - Environment example
- `.gitignore` - Git ignore file
- `.gitattributes` - Git attributes
- `.editorconfig` - Editor config
- `phpunit.xml` - PHPUnit config
- `tests/` - Test files
- `README.md` - Documentation
- `package.json` - Node.js package file
- `package-lock.json` - Node.js lock file
- `build-script.js` - Build script itself
- `composer.lock` - Composer lock file
- `yarn.lock` - Yarn lock file
- `*.log` - Log files
- `build-*.zip` - Previous build files

## Output

Script akan menghasilkan file ZIP dengan format nama:
```
dist/build-YYYY-MM-DDTHH-mm-ss.zip
```

File ini berisi semua file Laravel yang diperlukan untuk production, siap untuk di-upload ke server. Semua hasil build disimpan di folder `dist/`.

Contoh: `dist/build-2025-09-09T04-41-23.zip`

## Catatan

- File ZIP akan disimpan di root directory Laravel
- Symbolic links akan diabaikan secara otomatis
- Kompresi menggunakan level maksimal (level 9)
- Script akan menampilkan progress dan ukuran file hasil

## Deployment

1. Upload file ZIP ke server production
2. Extract di directory web server
3. Jalankan perintah Laravel deployment:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan migrate --force
   ```
4. Set permission yang sesuai untuk storage dan bootstrap/cache
5. Buat symbolic link untuk storage:
   ```bash
   php artisan storage:link
   ```