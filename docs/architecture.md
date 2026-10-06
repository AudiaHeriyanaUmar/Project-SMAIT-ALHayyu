# Arsitektur SMAIT Al-Hayyu

Diagram berikut merangkum alur aplikasi Laravel berdasarkan route, controller,
model, dan view yang tersedia.

```mermaid
flowchart LR
    Browser["Pengunjung / pengguna"]
    App["Aplikasi Laravel<br/>routes/web.php"]

    subgraph Public["Area publik"]
        Home["Beranda"]
        SPMB["Formulir SPMB"]
        SPMBController["SpmbController"]
        Registration["Validasi data pendaftaran"]
        SaveRegistration["Simpan data pendaftar"]
        HasToken{"Token Fonnte tersedia?"}
        Fonnte["Fonnte WhatsApp API<br/>(jika token tersedia)"]
        SkipMessage["Lewati pengiriman<br/>status failed"]
        SaveBroadcastLog["Simpan status pesan"]
    end

    subgraph Auth["Autentikasi"]
        AuthPages["Login / registrasi / verifikasi"]
        AuthControllers["Controller autentikasi Breeze"]
        Session["Sesi pengguna"]
        Role{"Peran pengguna"}
    end

    subgraph Guru["Area guru"]
        GuruRoutes["Route /guru/*<br/>middleware auth"]
        GuruController["JurnalGuruController"]
        GuruViews["View jurnal guru"]
        GuruForm["Form jurnal + absensi<br/>kelas + mapel + status seluruh siswa"]
    end

    subgraph Admin["Area admin"]
        AdminRoutes["Route /admin/*<br/>middleware auth + role admin"]
        AdminController["AdminJurnalController<br/>pemeriksaan role admin"]
        AdminViews["Dashboard ringkasan / filter / verifikasi / cetak"]
        AttendanceController["AdminAttendanceController"]
        AttendanceViews["Rekap absensi / filter / cetak"]
        CorrectionAudit["Koreksi status<br/>wajib alasan + jejak admin"]
        StudentController["StudentManagementController"]
        StudentViews["Kelola roster<br/>pratinjau CSV / tambah / edit / arsip"]
        ClassController["ClassManagementController"]
        ClassViews["Kelola kelas / promosi siswa<br/>kelas tujuan wajib kosong"]
        AccountController["AccountManagementController"]
        AccountViews["Kelola akun<br/>tambah / edit / hapus / password"]
        ActivityView["Status online / aktivitas terakhir<br/>jam aktif hari ini dan 7 hari"]
        ReportView["Analisis absensi bulanan / semester<br/>alpa berulang / ekspor CSV"]
        AuditController["AdminAuditController"]
        BackupController["DatabaseBackupController<br/>unduh SQL MySQL"]
    end

    subgraph Storage["Database relasional melalui Eloquent"]
        Users[("users")]
        Registrations[("pendaftarans")]
        BroadcastLogs[("broadcast_logs")]
        Journals[("jurnal_gurus")]
        Classes[("kelas")]
        Subjects[("mata_pelajarans")]
        Students[("siswas")]
        Attendance[("absensi_siswas")]
        AttendanceCorrections[("attendance_corrections")]
        ActivitySessions[("account_activity_sessions")]
        DailyActivity[("account_activity_daily")]
        AuditLogs[("admin_audit_logs")]
        Evidence["Penyimpanan privat bukti koreksi"]
    end

    Browser --> App
    App --> Home
    App --> SPMB
    Home -->|Blade view| Browser
    SPMB -->|Blade view| Browser
    SPMB --> SPMBController --> Registration
    Registration --> SaveRegistration -->|data pendaftar| Registrations
    SaveRegistration --> HasToken
    HasToken -->|Ya| Fonnte
    HasToken -->|Tidak| SkipMessage
    Fonnte -->|status respons API| SaveBroadcastLog
    SkipMessage --> SaveBroadcastLog
    SaveBroadcastLog -->|catat status pesan| BroadcastLogs
    Registrations --- BroadcastLogs

    App --> AuthPages --> AuthControllers
    AuthControllers -->|buat / autentikasi pengguna| Users
    AuthControllers --> Session --> Role
    Role -->|guru| GuruRoutes
    Role -->|admin| AdminRoutes
    Role -->|calon_siswa| Dashboard["/dashboard"]
    Dashboard -->|render dashboard| Browser

    GuruRoutes --> GuruController
    GuruController --> GuruViews --> Browser
    GuruController -->|daftar jurnal milik guru| Journals
    GuruController --> GuruForm
    GuruForm -->|baca pilihan| Classes
    GuruForm -->|baca pilihan| Subjects
    GuruForm -->|daftar siswa per kelas| Students
    GuruForm -->|kirim jurnal + status semua siswa| GuruController
    GuruController -->|simpan transaksi jurnal dan absensi| Journals
    GuruController -->|simpan satu status per siswa| Attendance
    Journals --> Classes
    Journals --> Subjects
    Journals --> Users
    Attendance --> Students

    AdminRoutes --> AdminController --> AdminViews --> Browser
    AdminController -->|hitung ringkasan / monitor / filter| Journals
    AdminController -->|verifikasi| Journals
    AdminController -->|cetak laporan| Journals
    AdminRoutes --> AttendanceController --> AttendanceViews --> Browser
    AttendanceController -->|filter tanggal / kelas / siswa / guru / status| Attendance
    AttendanceController -->|persentase / alpa berulang / ekspor| ReportView
    AttendanceController -->|koreksi status dan keterangan| Attendance
    AttendanceController -->|catat admin, status lama/baru, alasan| AttendanceCorrections
    AttendanceController -->|bukti wajib jika jurnal terverifikasi| Evidence
    AttendanceCorrections --> CorrectionAudit
    AttendanceCorrections --> Users
    AdminRoutes --> StudentController --> StudentViews --> Browser
    StudentController -->|pratinjau CSV lalu konfirmasi impor atomik| Students
    AdminRoutes --> ClassController --> ClassViews --> Browser
    ClassController -->|kelola wali / kelas dan promosi ke kelas kosong| Classes
    ClassController -->|pindahkan roster aktif tanpa mengubah jurnal historis| Students
    AdminRoutes --> AccountController --> AccountViews --> Browser
    AccountController -->|buat / ubah / arsip akun| Users
    AccountController -->|ubah password| Users
    AccountController -->|baca aktivitas| ActivitySessions
    AccountController -->|rekap waktu aktif| DailyActivity
    AdminRoutes --> AuditController -->|filter / tampilkan tindakan admin| AuditLogs
    AdminRoutes --> BackupController -->|unduh backup SQL MySQL; pemulihan manual teknisi| Browser

    AuthControllers -->|mulai / akhiri sesi aktivitas| ActivitySessions
    GuruController -->|permintaan web terautentikasi| ActivitySessions
    ActivitySessions -->|akumulasi maksimal 15 menit idle| DailyActivity
    DailyActivity --> ActivityView
    AdminController -->|catat verifikasi, perubahan akun/siswa, ekspor, dan backup| AuditLogs
    AuditLogs -->|retensi terjadwal 14 hari| Cleanup["Scheduler 02:00"]
    ActivitySessions -->|hapus detail sesi berakhir setelah 14 hari| Cleanup

    Students --> Classes
    Attendance --> Journals
```

## Ringkasan alur

- Pendaftaran SPMB dapat dilakukan tanpa login. Data pendaftar disimpan sebelum
  pengiriman pesan WhatsApp; hasil pengiriman dicatat di `broadcast_logs`.
- Guru yang sudah login membuat jurnal mengajar. Jurnal baru berstatus `pending`
  dan terhubung ke pengguna, kelas, serta mata pelajaran. Status absensi setiap
  siswa dalam kelas wajib dipilih sebelum jurnal dan absensi disimpan bersama.
- Admin membuka dashboard monitoring di `/admin/dashboard` untuk melihat
  ringkasan, memfilter jurnal, memverifikasi, dan mencetak laporan. Pemeriksaan
  peran admin dilakukan di controller; pengguna non-admin ditolak.
- Admin membuka `/admin/absensi` untuk memfilter, mencetak rekap, dan melakukan
  koreksi absensi dengan alasan wajib. Riwayat menyimpan admin, nilai lama/baru,
  keterangan, dan waktu koreksi. Koreksi jurnal terverifikasi memerlukan bukti
  PDF/JPG/PNG yang disimpan privat. Analisis per bulan/semester menampilkan
  persentase berdasarkan catatan yang ada, menandai minimal tiga alpa, dan dapat
  diekspor ke CSV.
- Admin mengelola roster di `/admin/students`: menambah, mengubah, memindahkan
  kelas, mengimpor CSV setelah pratinjau dan konfirmasi, mengarsipkan, atau
  mengaktifkan kembali siswa. Arsip menggunakan soft delete sehingga catatan
  absensi tetap utuh; siswa yang diarsipkan tidak muncul di roster untuk jurnal
  baru. NISN tetap unik dan tidak dapat dipakai ulang selama record arsip ada.
- Admin mengelola wali kelas dan roster kelas di `/admin/classes`. Promosi
  memindahkan semua siswa aktif ke kelas tujuan yang kosong sehingga jurnal
  lama tetap terhubung ke kelas historis. Nama kelas yang sudah dipakai jurnal
  tidak dapat diubah.
- Route dashboard mengarahkan guru dan admin ke fitur masing-masing, sedangkan
  calon siswa dan peran lainnya melihat halaman dashboard.
- Admin mengelola akun melalui `/admin/accounts`. Penghapusan akun menggunakan
  soft delete agar jurnal dan riwayat aktivitas terkait tetap tersimpan.
- Sesi aktivitas mencatat login, aktivitas terakhir, logout, dan durasi aktif.
  Heartbeat dikirim saat halaman terlihat dan ada aktivitas pengguna; waktu idle
  dibatasi 15 menit dan durasi harian dipisah pada pergantian tanggal.
- Audit tindakan admin dan detail sesi berakhir dihapus terjadwal setelah 14
  hari; agregat `account_activity_daily` dan riwayat operasional jurnal/absensi
  tidak termasuk retensi audit.
- Admin dapat mengunduh backup SQL dari MySQL. Pemulihan sengaja tidak tersedia
  melalui dashboard dan harus dijalankan manual oleh teknisi.
- `siswas` terhubung ke kelas dan setiap catatan absensi memastikan hanya ada satu
  status siswa untuk jurnal yang sama.
