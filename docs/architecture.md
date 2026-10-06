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
        AccountController["AccountManagementController"]
        AccountViews["Kelola akun<br/>tambah / edit / hapus / password"]
        ActivityView["Status online / aktivitas terakhir<br/>jam aktif hari ini dan 7 hari"]
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
    AttendanceController -->|koreksi status dan keterangan| Attendance
    AttendanceController -->|catat admin, status lama/baru, alasan| AttendanceCorrections
    AttendanceCorrections --> CorrectionAudit
    AttendanceCorrections --> Users
    AdminRoutes --> AccountController --> AccountViews --> Browser
    AccountController -->|buat / ubah / arsip akun| Users
    AccountController -->|ubah password| Users
    AccountController -->|baca aktivitas| ActivitySessions
    AccountController -->|rekap waktu aktif| DailyActivity

    AuthControllers -->|mulai / akhiri sesi aktivitas| ActivitySessions
    GuruController -->|permintaan web terautentikasi| ActivitySessions
    ActivitySessions -->|akumulasi maksimal 15 menit idle| DailyActivity
    DailyActivity --> ActivityView

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
  keterangan, dan waktu koreksi.
- Route dashboard mengarahkan guru dan admin ke fitur masing-masing, sedangkan
  calon siswa dan peran lainnya melihat halaman dashboard.
- Admin mengelola akun melalui `/admin/accounts`. Penghapusan akun menggunakan
  soft delete agar jurnal dan riwayat aktivitas terkait tetap tersimpan.
- Sesi aktivitas mencatat login, aktivitas terakhir, logout, dan durasi aktif.
  Heartbeat dikirim saat halaman terlihat dan ada aktivitas pengguna; waktu idle
  dibatasi 15 menit dan durasi harian dipisah pada pergantian tanggal.
- `siswas` terhubung ke kelas dan setiap catatan absensi memastikan hanya ada satu
  status siswa untuk jurnal yang sama.
