# yllumi/sayagi

Panel administrasi siap pakai untuk framework [Webman](https://www.workerman.net/webman). Menyediakan autentikasi, manajemen pengguna, role & privilege berbasis YAML, dynamic entry CRUD, manajemen menu, pengaturan aplikasi, Redis browser, dan email sender — semua terintegrasi dalam satu package.

> Dokumentasi arsitektur internal: [ARCHITECTURE.md](ARCHITECTURE.md)

---

## Fitur

- **Autentikasi** — login, logout, lupa/reset password, registrasi opsional, CAPTCHA
- **Manajemen User & Role** — CRUD lengkap dengan filter status/role, pencarian, dan sorting kolom
- **Privilege berbasis YAML** — definisi `feature.action` + guard deklaratif `#[RequirePrivilege]`
- **Dynamic Entry CRUD** — CRUD generik dari schema YAML tanpa menulis controller
- **Menu Panel** — konfigurasi sidebar menu via UI, difilter berdasarkan privilege
- **Settings** — pengaturan aplikasi/tema/emailer via YAML + UI (tersimpan di DB + cache)
- **Redis Browser** — kelola key Redis langsung dari panel
- **Email Sender** — PHPMailer + template OTP bawaan
- **FormBuilder** — pembuat form dinamis (13 tipe field)
- **Override Template Admin** — ganti partial/layout panel per-file dari project tanpa menyentuh vendor
- **Starter Page Template** — halaman publik `basic` (Pinecone SSR/SPA) & `mobile` (Framework7)

---

## Persyaratan

- PHP >= 8.1, [Webman](https://www.workerman.net/webman) >= 2.1, MySQL/MariaDB
- Redis (opsional — untuk Redis Browser & cache)

---

## Instalasi

```bash
# 1. Install package
composer require yllumi/sayagi

# 2. Publish config, aset tema, dan pilih starter page template (non-database)
php webman sayagi:install

# 3. Setup database: migrasi + seeder + buat user admin pertama
php webman sayagi:install-admin
```

`sayagi:install-admin` akan menanyakan **nama, username, email, dan password** untuk user admin pertama (status `active`, role `Super`). User tambahan dibuat dengan `sayagi:user:create`.

> Config & aset sudah otomatis ter-publish oleh Composer saat `require`/`update`. `sayagi:install` tetap dijalankan sekali untuk: mengganti `config/view.php` ke handler Raw, memilih starter page template, dan merename `IndexController` bawaan.

### Akses Panel

```
http://localhost:8778/panel
```

> Port mengikuti `listen` di `config/process.php` project (default: 8778).

---

## Konfigurasi Awal

File utama setelah instalasi:

```
config/plugin/panel/menu.yml              # menu sidebar
config/plugin/panel/privileges.yml        # definisi privilege
config/plugin/panel/settings/*.yml        # schema group setting
config/plugin/yllumi/sayagi/app.php       # konfigurasi plugin
app/view/overrides/                       # override template panel (opsional)
```

`config/plugin/yllumi/sayagi/app.php`:

```php
return [
    'enable' => true,
    'site_title' => 'HeroicAdmin',
    'enable_registration' => getenv('app.enable_registration') === 'true' ? true : false,
];
```

Aktifkan registrasi via `.env`:

```env
app.enable_registration=true
```

---

## Perintah CLI

| Perintah | Deskripsi |
|---|---|
| `php webman sayagi:install` | Publish config & pilih starter template ke `app/pages/` (non-database) |
| `php webman sayagi:install-admin` | Migrasi + seeder + buat user admin pertama |
| `php webman sayagi:publish-template [basic\|mobile]` | Publish ulang starter template (`--force` untuk menimpa) |
| `php webman sayagi:user:create` | Buat user tambahan (interaktif atau via argumen) |
| `php webman sayagi:update` | Update plugin ke versi terbaru |
| `php webman make:migration Nama` | Buat file migrasi Phinx |
| `php webman migrate` | Jalankan migrasi yang belum dijalankan |
| `php webman migrate -a` | Jalankan migrasi SEMUA plugin + core sayagi |
| `php webman migrate:rollback` | Rollback migrasi terakhir |
| `php webman db:seed` | Jalankan database seeder |

Contoh user via argumen:

```bash
php webman sayagi:user:create "Budi Santoso" "budi" "budi@example.com" "secret123" \
  --role=1 --phone="081234567890" --status=active
```

Contoh membuat migrasi:

```bash
php webman make:migration CreateProductsTable
```

---

## Fitur & Penggunaan

### Autentikasi

Semua route `/panel/*` dilindungi `PanelAuthMiddleware`; user yang belum login diarahkan ke halaman login.

| Method | URL | Fungsi |
|---|---|---|
| GET/POST | `/panel/auth/login` | Login |
| GET | `/panel/auth/logout` | Logout |
| GET/POST | `/panel/auth/forgot` | Lupa password |
| GET/POST | `/panel/auth/reset` | Reset password |
| POST | `/panel/auth/register` | Registrasi (jika diaktifkan) |
| GET | `/panel/auth/captcha` | CAPTCHA |

Password diverifikasi dengan **Phpass** (portable PHP password hashing). Setelah login, data user disimpan ke session.

### Guard Privilege dengan Attribute

```php
use Yllumi\Sayagi\attributes\RequirePrivilege;

class ProductController extends AdminController
{
    #[RequirePrivilege('product.read')]
    public function index(Request $request) { ... }

    #[RequirePrivilege('product.write')]
    #[RequirePrivilege('inventory.manage')]   // semua harus terpenuhi
    public function store(Request $request) { ... }

    #[RequirePrivilege('report.export', whitelistIds: [1, 2])]
    public function export(Request $request) { ... }
}
```

Format privilege `feature.action` (mis. `user.read`, `role.write`). User dengan `role_id = 1` (Super) lolos semua pemeriksaan. Cek manual di kode: `isAllow('user.write')`.

### Dynamic Entry CRUD

Buat CRUD lengkap cukup dengan schema YAML di `plugin/{nama}/panel/entry/{slug}.yml`:

```yaml
name: Mahasiswa
table: mahasiswas
fields:
  - field: name
    label: Nama Lengkap
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
```

Field dengan `relation` otomatis di-LEFT JOIN saat menampilkan data. Route otomatis tersedia:

```
/panel/entry/mahasiswa          → daftar
/panel/entry/mahasiswa/data     → data JSON
/panel/entry/mahasiswa/create   → form tambah
/panel/entry/mahasiswa/store    → simpan
/panel/entry/mahasiswa/edit     → form edit
/panel/entry/mahasiswa/update   → update
/panel/entry/mahasiswa/delete   → hapus
```

### FormBuilder

```php
use Yllumi\Sayagi\libraries\FormBuilder\FormBuilder;

$form = new FormBuilder();
$html = $form->schemaArray([
    ['name' => 'nama', 'type' => 'text', 'label' => 'Nama Lengkap'],
    ['name' => 'role', 'type' => 'select', 'label' => 'Role', 'options' => [1 => 'Admin', 2 => 'User']],
])->render($currentValues);
```

Tipe field: `text`, `number`, `email`, `textarea`, `select`, `radio`, `checkbox`, `switcher`, `date`, `color`, `image`, `mask`, `code`.

### Redis Browser & Email Sender

- **Redis Browser** di `/panel/redis` — lihat/buat/edit/hapus/rename/flush key (tipe `string`, `list`, `set`, `zset`, `hash`).
- **Email Sender** — kirim email via SMTP (konfigurasi dari settings/`.env` `mail.*`):

```php
use Yllumi\Sayagi\libraries\EmailSender;

$sender = new EmailSender();
$sender->sendEmail('user@example.com', 'Subject', '<p>Isi email</p>');

$html = EmailSender::otpTemplate('Nama User', '123456', 'Nama Aplikasi');
$sender->sendEmail('user@example.com', 'Kode OTP Anda', $html);
```

---

## Override Template Admin

View panel bisa dioverride per-file dari project tanpa mengubah file package.
Resolver `render()` / `cell()` mencari file dengan urutan prioritas:

1. **Override project** — `app/view/overrides/{plugin}/{path}.php`
2. **Plugin lokal** — `plugin/{name}/app/view/{path}.php`
3. **Package vendor** — `vendor/yllumi/sayagi/src/app/view/{path}.php`

Untuk panel admin, `{plugin}` adalah `yllumi/sayagi`.

### Contoh: override sidebar

```bash
mkdir -p app/view/overrides/yllumi/sayagi/_partials
cp vendor/yllumi/sayagi/src/app/view/_partials/sidebar.php \
   app/view/overrides/yllumi/sayagi/_partials/sidebar.php
```

Edit file hasil salinan tersebut. Selama file override ada, file itu yang dipakai;
partial lain tetap memakai versi package. Contoh menambah banner di atas menu:

```php
<div class="sidebar-menu">
    <div class="text-center px-3 mb-3">
        <img src="<?= site_url('/panel_theme/banner.png') ?>" alt="Banner" class="img-fluid rounded">
    </div>

    <ul class="menu">
        <!-- loop sidebarMenus() bawaan tetap di sini -->
    </ul>
</div>
```

### Partial & layout yang bisa dioverride

| File package | Path override |
|---|---|
| `_partials/sidebar.php` | `app/view/overrides/yllumi/sayagi/_partials/sidebar.php` |
| `_partials/header.php` | `app/view/overrides/yllumi/sayagi/_partials/header.php` |
| `_partials/footer.php` | `app/view/overrides/yllumi/sayagi/_partials/footer.php` |
| `_partials/alerts.php` | `app/view/overrides/yllumi/sayagi/_partials/alerts.php` |
| `_partials/bottommenu.php` | `app/view/overrides/yllumi/sayagi/_partials/bottommenu.php` |
| `_layouts/admin.php` | `app/view/overrides/yllumi/sayagi/_layouts/admin.php` |
| `_layouts/auth.php` | `app/view/overrides/yllumi/sayagi/_layouts/auth.php` |

### Override milik plugin

Plugin lokal maupun vendor memakai nama plugin yang sama seperti yang dipakai
saat memanggil `render()`, misalnya:

```
app/view/overrides/article/_layouts/admin.php
app/view/overrides/yllumi/package/_partials/sidebar.php
```

Bila plugin tidak menyediakan layout sendiri, layout otomatis fallback ke
`_layouts/admin.php` milik panel `yllumi/sayagi`.

### Menu vs markup

- **Isi menu** (item, urutan, label, icon, privilege) → ubah
  `config/plugin/panel/menu.yml` atau lewat UI **Development → Panel Menu**.
- **Markup/desain** (banner, class CSS, struktur sidebar) → override template
  seperti contoh di atas.

> Jangan mengedit langsung file di `vendor/yllumi/sayagi/src/app/view/`; perubahan
> akan hilang saat package di-update. Jalankan `php webman restart` setelah
> mengubah template bila OPcache aktif.

---

## Starter Page Template

| Template | Deskripsi | Isi bawaan |
|---|---|---|
| `basic` | Web desktop: router Pinecone + SSR/ISR/SPA | `home/`, `docs/`, `notfound/`, `offline/` |
| `mobile` | Aplikasi mobile Framework7: page stack, tabbar | `home/`, `books/`, `notfound/` |

Template disalin ke **`app/pages/`**; setiap folder otomatis menjadi route publik:

```
app/pages/
├── home/                 # route /
│   ├── PageController.php
│   └── template.php
├── books/                # route /books/
│   ├── PageController.php
│   └── template.php
└── books/detail/         # route /books/:id/ (via #[FrontendRoute])
    └── PageController.php
```

```php
use Yllumi\Sayagi\attributes\FrontendRoute;

#[FrontendRoute(route: '/books/', template: '/books/template')]
class PageController extends BaseController
{
    public function getData(Request $request)
    {
        $this->data = ['books' => /* ... */];
        return json($this->data);
    }
}
```

`BaseController` menangani SSR (`getIndex`), fragmen async (`getTemplate`), dan data JSON (`getData`). Route dinamis (mis. `/books/{id}`) didaftarkan eksplisit di `config/route.php`.

---

## Arsitektur

Untuk detail internal — struktur package, request lifecycle, routing, autentikasi & privilege, settings, database, dan sistem migrasi — lihat [ARCHITECTURE.md](ARCHITECTURE.md).

---

## Lisensi

MIT License. Lihat file [LICENSE](LICENSE) untuk detail.
