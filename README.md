# yllumi/sayagi

Panel administrasi siap pakai untuk framework [Webman](https://www.workerman.net/webman). Menyediakan autentikasi, manajemen pengguna, role & privilege berbasis YAML, dynamic entry CRUD, manajemen menu, pengaturan aplikasi, Redis browser, dan email sender—semua terintegrasi dalam satu package.

---

## Daftar Isi

- [Fitur](#fitur)
- [Persyaratan](#persyaratan)
- [Instalasi](#instalasi)
- [Konfigurasi Awal](#konfigurasi-awal)
- [Fitur & Penggunaan](#fitur--penggunaan)
  - [Autentikasi](#autentikasi)
  - [Manajemen User](#manajemen-user)
  - [Manajemen Role](#manajemen-role)
  - [Manajemen Privilege](#manajemen-privilege)
  - [Manajemen Menu Panel](#manajemen-menu-panel)
  - [Pengaturan Aplikasi (Settings)](#pengaturan-aplikasi-settings)
  - [Dynamic Entry CRUD](#dynamic-entry-crud)
  - [Redis Browser](#redis-browser)
  - [Email Sender](#email-sender)
  - [FormBuilder](#formbuilder)
  - [Attribute RequirePrivilege](#attribute-requireprivilege)
- [Perintah CLI (Console Commands)](#perintah-cli-console-commands)
- [Struktur Database](#struktur-database)
- [Lisensi](#lisensi)

---

## Fitur

| Fitur | Deskripsi |
|---|---|
| **Autentikasi** | Login, logout, lupa password, reset password, registrasi (opsional) |
| **Manajemen User** | CRUD user dengan filter status & pencarian |
| **Manajemen Role** | CRUD role untuk pengelompokan hak akses |
| **Manajemen Privilege** | Definisi & pengelolaan privilege berbasis file YAML |
| **Manajemen Menu** | Konfigurasi sidebar menu panel secara dinamis via UI |
| **Settings** | Pengaturan aplikasi, tema, emailer, dan plugin via YAML + UI |
| **Dynamic Entry CRUD** | CRUD generik berbasis schema YAML per plugin |
| **Redis Browser** | Melihat, membuat, mengedit, dan menghapus key Redis |
| **Email Sender** | Pengiriman email via PHPMailer dengan template OTP bawaan |
| **FormBuilder** | Pembuat form dinamis dari array/YAML dengan berbagai tipe field |
| **AuthMiddleware** | Proteksi otomatis route `/panel/*` + pengecekan privilege via PHP Attribute |

---

## Persyaratan

- PHP >= 8.1
- [Webman Framework](https://www.workerman.net/webman)
- MySQL / MariaDB
- Redis (opsional, untuk fitur Redis Browser & cache)
- Composer

---

## Instalasi

### 1. Install via Composer

```bash
composer require yllumi/sayagi
```

### 2. Jalankan Perintah Install

Perintah berikut akan menjalankan migrasi database, seeder awal, dan mempublikasikan file konfigurasi ke project:

```bash
php webman sayagi:install
```

Proses ini akan:
- Membuat tabel-tabel yang dibutuhkan (`mein_users`, `mein_roles`, `mein_privileges`, `mein_settings`, dll.)
- Menyalin file konfigurasi ke `config/plugin/panel/`
- Menyalin file menu & privilege ke `config/plugin/panel/`

### 3. Buat User Admin Pertama

```bash
php webman sayagi:user:create
```

Ikuti prompt interaktif untuk mengisi nama, username, email, dan password. Anda juga bisa memberikan argumen langsung:

```bash
php webman sayagi:user:create "Admin" "admin" "admin@example.com" "password123" --role=1
```

### 4. Akses Panel

Buka browser dan navigasi ke:

```
http://localhost:8787/panel
```

---

## Konfigurasi Awal

File konfigurasi utama setelah instalasi berada di `config/plugin/panel/`:

```
config/plugin/panel/
├── menu.yml        # Konfigurasi menu sidebar
├── privileges.yml  # Definisi privilege sistem
```

Konfigurasi aplikasi dasar ada di `config/plugin/panel/app.php`:

```php
return [
    'enable' => true,
    'debug'  => true,
    'site_title' => 'HeroicAdmin',
    'enable_registration' => false, // Aktifkan melalui env: app.enable_registration=true
];
```

Untuk mengaktifkan registrasi pengguna, tambahkan pada file `.env`:

```env
app.enable_registration=true
```

---

## Fitur & Penggunaan

### Autentikasi

Panel secara otomatis memproteksi semua route `/panel/*` melalui `AuthMiddleware`. Pengguna yang belum login akan diarahkan ke halaman login.

**Route Autentikasi:**

| Method | URL | Fungsi |
|---|---|---|
| GET | `/panel/auth/login` | Halaman login |
| POST | `/panel/auth/login` | Proses login |
| GET | `/panel/auth/logout` | Logout |
| GET | `/panel/auth/forgot` | Halaman lupa password |
| POST | `/panel/auth/forgot` | Kirim link reset password |
| GET | `/panel/auth/reset` | Halaman reset password |
| POST | `/panel/auth/reset` | Proses reset password |
| POST | `/panel/auth/register` | Proses registrasi (jika diaktifkan) |

**Alur Login:**
1. User memasukkan username/email dan password.
2. Password diverifikasi menggunakan Phpass (bcrypt-compatible).
3. Data user disimpan ke session setelah berhasil login.

---

### Manajemen User

Kelola pengguna sistem melalui antarmuka CRUD lengkap dengan filter dan pencarian.

**Route:**

| Method | URL | Fungsi |
|---|---|---|
| GET | `/panel/user` | Daftar user |
| GET | `/panel/user/data` | Data JSON (pagination + search) |
| GET | `/panel/user/create` | Form tambah user |
| POST | `/panel/user/store` | Simpan user baru |
| GET | `/panel/user/edit` | Form edit user |
| POST | `/panel/user/update` | Update user |
| POST | `/panel/user/delete` | Hapus user |

**Fitur:**
- Filter berdasarkan status (active, inactive, all)
- Pencarian berdasarkan nama, username, email, atau nomor telepon
- Assign role kepada user
- Proteksi aksi dengan privilege (`user.read`, `user.write`, `user.delete`)

---

### Manajemen Role

Kelola role/grup pengguna yang dapat ditetapkan pada setiap user.

**Route:**

| Method | URL | Fungsi |
|---|---|---|
| GET | `/panel/role` | Daftar role |
| GET | `/panel/role/data` | Data JSON |
| GET | `/panel/role/create` | Form tambah role |
| POST | `/panel/role/store` | Simpan role baru |
| GET | `/panel/role/edit` | Form edit role |
| POST | `/panel/role/update` | Update role |
| POST | `/panel/role/delete` | Hapus role |

**Fitur:**
- Setiap role dapat memiliki kumpulan privilege
- Diproteksi dengan privilege `role.read`, `role.write`, `role.delete`, `role.set_privilege`

---

### Manajemen Privilege

Privilege didefinisikan dalam file YAML dan dapat dikelola melalui UI panel.

**File:** `config/plugin/panel/privileges.yml`

```yaml
dashboard:
  - { read: 'Read dashboard data' }
  - { set_widget: 'Customize dashboard widgets' }

user:
  - { read: 'Read user data' }
  - { write: 'Write modification of user data' }
  - { delete: 'Delete user records' }
  - { set_role: 'Assign roles to users' }

role:
  - { read: 'Read role data' }
  - { write: 'Write modification of role data' }
  - { delete: 'Delete role records' }
  - { set_privilege: 'Assign privileges to roles' }
```

Format privilege menggunakan notasi `feature.action` (contoh: `user.read`, `role.write`).

**Route:**

| Method | URL | Fungsi |
|---|---|---|
| GET | `/panel/privilege` | Daftar privilege |
| GET | `/panel/privilege/data` | Data JSON |
| GET | `/panel/privilege/features` | Daftar fitur |
| GET | `/panel/privilege/create` | Form tambah privilege |
| POST | `/panel/privilege/store` | Simpan privilege |
| GET | `/panel/privilege/edit` | Form edit privilege |
| POST | `/panel/privilege/update` | Update privilege |
| POST | `/panel/privilege/delete` | Hapus privilege |

**Pengecekan privilege di kode:**

```php
// Cek apakah user boleh melakukan aksi
if (isAllow('user.write')) {
    // user memiliki izin
}
```

---

### Manajemen Menu Panel

Konfigurasi sidebar menu panel secara dinamis melalui UI tanpa perlu edit file secara manual.

**File:** `config/plugin/panel/menu.yml`

```yaml
- id: menu_69d223a8a20be8
  label: Dashboard
  module: dashboard
  icon: 'bi bi-grid-fill'
  url: /panel
  privilege: dashboard.read
  children: {}

- id: menu_abc123
  label: Pengguna
  module: user
  icon: 'bi bi-lock-fill'
  url: '#'
  privilege: user.read
  children:
    - id: menu_abc124
      label: Users
      url: /panel/user
      privilege: user.read
    - id: menu_abc125
      label: Roles
      url: /panel/role
      privilege: role.read
```

**Route:**

| Method | URL | Fungsi |
|---|---|---|
| GET | `/panel/panelmenu` | Kelola menu |
| POST | `/panel/panelmenu/store` | Tambah menu baru |
| GET | `/panel/panelmenu/edit` | Edit menu |
| POST | `/panel/panelmenu/update` | Update menu |
| POST | `/panel/panelmenu/delete` | Hapus menu |
| POST | `/panel/panelmenu/reorder` | Ubah urutan menu |

**Mendapatkan daftar menu di view:**

```php
$menus = sidebarMenus(); // global helper function
```

---

### Pengaturan Aplikasi (Settings)

Pengaturan dikonfigurasi via file YAML dan dikelola melalui antarmuka UI panel. Setiap group setting didefinisikan dalam file `.yml` terpisah di `src/settings/`.

**Group Setting Bawaan:**

| Slug | File | Deskripsi |
|---|---|---|
| `theme` | `theme.yml` | Tema frontend & admin, warna, background |
| `emailer` | `emailer.yml` | Konfigurasi SMTP untuk pengiriman email |
| `app` | `app.yml` | Logo, warna tema, navbar, bottom menu |
| `plugin` | `plugin.yml` | Modul aktif dan entry yang dinonaktifkan |

**Contoh `theme.yml`:**

```yaml
name: Theme
slug: theme
menu_position: 20
setting:
  main_theme:
    field: main_theme
    label: Frontend Theme
    form: select
    default: mobilekit
    options:
      mobilekit: mobilekit
      magazine: magazine
  admin_bgcolor:
    field: admin_bgcolor
    label: Admin Background Color
    form: color
    default: "#52BCD3"
```

**Tipe form yang didukung:** `text`, `number`, `email`, `color`, `image`, `select`, `switcher`, `code`, `mask`

**Route:**

| Method | URL | Fungsi |
|---|---|---|
| GET | `/panel/setting` | Halaman pengaturan |
| GET | `/panel/setting/data` | Data JSON pengaturan |
| POST | `/panel/setting/save` | Simpan pengaturan |

---

### Dynamic Entry CRUD

Fitur paling fleksibel dari sayagi—memungkinkan pembuatan CRUD lengkap hanya dengan mendefinisikan schema dalam file YAML tanpa menulis controller baru.

**Cara Membuat Entry Baru:**

1. Buat file schema di `plugin/{nama_plugin}/app/entry/{slug}.yml`:

```yaml
name: Mahasiswa
table: mahasiswas
fields:
  - field: name
    label: Nama Lengkap
    type: text
    searchable: true
    table_display: true
  - field: nim
    label: NIM
    type: text
    searchable: true
    table_display: true
  - field: jurusan_id
    label: Jurusan
    type: select
    table_display: true
    relation:
      table: jurusans
      value: id
      display: nama_jurusan
  - field: tanggal_lahir
    label: Tanggal Lahir
    type: date
  - field: foto
    label: Foto
    type: image
```

2. Akses CRUD melalui URL:

```
GET  /panel/entry/mahasiswa          → Daftar
GET  /panel/entry/mahasiswa/data     → Data JSON
GET  /panel/entry/mahasiswa/create   → Form tambah
POST /panel/entry/mahasiswa/store    → Simpan
GET  /panel/entry/mahasiswa/edit     → Form edit
POST /panel/entry/mahasiswa/update   → Update
POST /panel/entry/mahasiswa/delete   → Hapus
```

**Tipe Field yang Didukung:**
- `text`, `number`, `email`, `textarea`
- `select` (dengan relasi tabel)
- `date`, `image`, `color`
- `checkbox`, `radio`, `switcher`
- `mask`, `code`

**Fitur Relasi Otomatis:**

Ketika field memiliki `relation`, sistem akan otomatis melakukan LEFT JOIN saat menampilkan data:

```yaml
- field: role_id
  label: Role
  type: select
  relation:
    table: mein_roles
    value: id
    display: role_name
```

---

### Redis Browser

Antarmuka web untuk mengelola Redis key langsung dari panel admin.

**Route:**

| Method | URL | Fungsi |
|---|---|---|
| GET | `/panel/redis` | Halaman Redis browser |
| GET | `/panel/redis/keys?pattern=*` | Daftar key dengan filter pattern |
| GET | `/panel/redis/get?key=xxx` | Ambil nilai & TTL key |
| POST | `/panel/redis/set` | Buat/update key |
| POST | `/panel/redis/delete` | Hapus key |
| POST | `/panel/redis/rename` | Rename key |
| POST | `/panel/redis/flush` | Flush semua key |

**Tipe data Redis yang didukung:** `string`, `list`, `set`, `zset`, `hash`

**Contoh penggunaan API (JSON body untuk set key):**

```json
{
  "key": "app:config",
  "type": "string",
  "value": "hello world",
  "ttl": 3600
}
```

---

### Email Sender

Library untuk mengirim email menggunakan PHPMailer dengan konfigurasi SMTP dari panel settings.

**Penggunaan Dasar:**

```php
use Yllumi\Sayagi\libraries\EmailSender;

$sender = new EmailSender();

// Kirim ke satu penerima
$sender->sendEmail('user@example.com', 'Subject', '<p>Isi email HTML</p>');

// Kirim ke beberapa penerima
$sender->sendEmail(
    ['user1@example.com' => 'User Satu', 'user2@example.com' => 'User Dua'],
    'Subject',
    '<p>Isi email HTML</p>'
);
```

**Template OTP Bawaan:**

```php
use Yllumi\Sayagi\libraries\EmailSender;

$html = EmailSender::otpTemplate('Nama User', '123456', 'Nama Aplikasi');

$sender = new EmailSender();
$sender->sendEmail('user@example.com', 'Kode OTP Anda', $html);
```

**Konfigurasi SMTP** diambil dari environment variable atau settings panel:

```env
mail.smtp_host=smtp.gmail.com
mail.smtp_port=465
mail.smtp_username=user@gmail.com
mail.smtp_password=app_password
mail.from_address=no-reply@example.com
mail.from_name=Nama Aplikasi
```

---

### FormBuilder

Library untuk membuat form HTML dinamis dari array atau schema YAML.

**Penggunaan Basic:**

```php
use Yllumi\Sayagi\libraries\FormBuilder\FormBuilder;

$form = new FormBuilder();
$html = $form->schemaArray([
    ['name' => 'nama', 'type' => 'text', 'label' => 'Nama Lengkap'],
    ['name' => 'email', 'type' => 'email', 'label' => 'Email'],
    ['name' => 'role', 'type' => 'select', 'label' => 'Role', 'options' => [
        1 => 'Admin',
        2 => 'User',
    ]],
])->render($currentValues);
```

**Tipe Field yang Tersedia:**

| Type | Deskripsi |
|---|---|
| `text` | Input teks biasa |
| `number` | Input angka |
| `email` | Input email |
| `textarea` | Input teks panjang |
| `select` | Dropdown pilihan |
| `radio` | Pilihan radio button |
| `checkbox` | Checkbox |
| `switcher` | Toggle switch |
| `date` | Date picker |
| `color` | Color picker |
| `image` | Upload gambar |
| `mask` | Input dengan mask (misal: password, telepon) |
| `code` | Code editor (mendukung mode CSS, YAML, dll.) |

---

### Attribute RequirePrivilege

PHP 8 Attribute untuk deklaratif meng-guard method controller berdasarkan privilege. `AuthMiddleware` akan secara otomatis membaca attribute ini.

**Penggunaan:**

```php
use Yllumi\Sayagi\attributes\RequirePrivilege;

class ProductController extends AdminController
{
    // Guard dengan satu privilege
    #[RequirePrivilege('product.read')]
    public function index(Request $request) { ... }

    // Guard dengan beberapa privilege (semua harus terpenuhi)
    #[RequirePrivilege('product.write')]
    #[RequirePrivilege('inventory.manage')]
    public function store(Request $request) { ... }

    // Dengan whitelist user_id yang selalu diloloskan
    #[RequirePrivilege('report.export', whitelistIds: [1, 2])]
    public function export(Request $request) { ... }
}
```

Jika user tidak memiliki privilege yang dibutuhkan, akan dikembalikan halaman 404.

---

## Perintah CLI (Console Commands)

| Perintah | Deskripsi |
|---|---|
| `php webman sayagi:install` | Instalasi plugin: jalankan migrasi & publish file konfigurasi |
| `php webman sayagi:user:create` | Buat user baru secara interaktif |
| `php webman sayagi:update` | Update plugin ke versi terbaru |
| `php webman make:migration NamaMigrasi` | Buat file migrasi baru menggunakan Phinx |
| `php webman migrate` | Jalankan semua migrasi yang belum dijalankan |
| `php webman migrate:rollback` | Rollback migrasi terakhir |
| `php webman db:seed` | Jalankan database seeder |

**Contoh membuat user via argumen:**

```bash
php webman sayagi:user:create "Budi Santoso" "budi" "budi@example.com" "secret123" \
  --role=1 \
  --phone="081234567890" \
  --status=active
```

**Contoh membuat migrasi:**

```bash
php webman make:migration CreateProductsTable
# File migrasi akan dibuat di database/migrations/
```

---

## Struktur Database

Tabel utama yang dibuat oleh sayagi:

| Tabel | Deskripsi |
|---|---|
| `mein_users` | Data pengguna (id, name, username, email, password, phone, status, role_id) |
| `mein_roles` | Data role (id, role_name, description) |
| `mein_privileges` | Mapping privilege per role (role_id, feature, privilege) |
| `mein_settings` | Key-value store untuk konfigurasi aplikasi (group, field, value) |

---

## Lisensi

MIT License. Lihat file [LICENSE](LICENSE) untuk detail.
