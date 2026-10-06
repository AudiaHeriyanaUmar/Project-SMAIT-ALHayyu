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
        GuruForm["Form jurnal<br/>kelas + mata pelajaran"]
    end

    subgraph Admin["Area admin"]
        AdminRoutes["Route /admin/dashboard dan /admin/jurnal<br/>middleware auth"]
        AdminController["AdminJurnalController<br/>pemeriksaan role admin"]
        AdminViews["Dashboard ringkasan / filter / verifikasi / cetak"]
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
    GuruForm -->|kirim jurnal berstatus pending| GuruController
    Journals --> Classes
    Journals --> Subjects
    Journals --> Users

    AdminRoutes --> AdminController --> AdminViews --> Browser
    AdminController -->|hitung ringkasan / monitor / filter| Journals
    AdminController -->|verifikasi| Journals
    AdminController -->|cetak laporan| Journals

    Students --> Classes
    Attendance --> Journals
```

## Ringkasan alur

- Pendaftaran SPMB dapat dilakukan tanpa login. Data pendaftar disimpan sebelum
  pengiriman pesan WhatsApp; hasil pengiriman dicatat di `broadcast_logs`.
- Guru yang sudah login membuat jurnal mengajar. Jurnal baru berstatus `pending`
  dan terhubung ke pengguna, kelas, serta mata pelajaran.
- Admin membuka dashboard monitoring di `/admin/dashboard` untuk melihat
  ringkasan, memfilter jurnal, memverifikasi, dan mencetak laporan. Pemeriksaan
  peran admin dilakukan di controller; pengguna non-admin ditolak.
- Route dashboard mengarahkan guru dan admin ke fitur masing-masing, sedangkan
  calon siswa dan peran lainnya melihat halaman dashboard.
- `siswas` dan `absensi_siswas` ditampilkan sebagai model data terkait; keduanya
  belum memiliki alur aktif pada route yang digambarkan.
