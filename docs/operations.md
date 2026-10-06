# Panduan operasional

## Audit dan aktivitas akun

Tindakan admin penting dicatat di menu **Audit admin**. Audit dan detail sesi aktivitas akun dihapus otomatis setelah berusia lebih dari 14 hari oleh scheduler Laravel pada pukul 02.00. Pastikan scheduler aplikasi berjalan setiap menit (`php artisan schedule:run`) atau gunakan scheduler supervisor yang sesuai deployment.

Ringkasan jam aktif harian di `account_activity_daily` tidak ikut dihapus. Ringkasan ini dipakai untuk tampilan hari ini dan tujuh hari berjalan; detail sesi disimpan hanya untuk investigasi terbaru. Jam aktif adalah estimasi berdasarkan permintaan web dengan batas idle 15 menit, bukan pengukuran waktu layar yang presisi.

## Backup database

Admin dapat mengunduh backup SQL lengkap dari menu **Backup database**. File berisi data pribadi dan hash password; simpan di lokasi terenkripsi, batasi akses, dan jangan letakkan di direktori publik. Unduhan hanya mendukung MySQL.

Pemulihan tidak disediakan melalui dashboard. Teknisi harus menguji backup terlebih dahulu dan membuat salinan database terkini sebelum pemulihan. Untuk pemulihan penuh, aktifkan maintenance mode dan gunakan klien MySQL pada database yang benar, misalnya:

```cmd
php artisan down
mysql -u DB_USER -p DB_NAME < "C:\secure\backup.sql"
if errorlevel 1 exit /b 1
php artisan up
```

Pemulihan SQL penuh mengganti tabel di database target. Pastikan backup sesuai dengan versi aplikasi dan konfigurasi koneksi sebelum menjalankan perintah tersebut.
