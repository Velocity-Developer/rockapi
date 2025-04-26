# ROCKAPI (New Nglorok API)

API New Nglorok

# Development
Alur pengembangan atau instalasi Rockapi

## Requirement
1. Server Database MySQL / MariaDB
2. PHP 8 keatas
3. Composer
4. Nodejs / BunJS

## Instal
1. clone repository `rockapi`
2. Buat file .env
```bash 
cp .env.example .env
```
3. Generate Key baru
```bash 
php artisan key:generate
```
4. Buat database dan table
```bash 
php artisan migrate
```
5. Import Database dari `Nglorok Lama`
6. jalankan SEEDER untuk database
```bash 
php artisan db:seed
```

## Mulai
```bash 
php artisan serve --port=8005
```
