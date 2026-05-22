<div id="page-docs" x-data="docsPage()">

    <!-- Top Bar -->
    <div class="dc-topbar">
        <a href="/" class="dc-back">
            <i class="bi bi-arrow-left"></i>
        </a>
        <span class="dc-topbar-title">Documentation</span>
        <button class="dc-menu-toggle" @click="sidebarOpen = !sidebarOpen">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <div class="dc-layout">

        <!-- Sidebar -->
        <nav class="dc-sidebar" :class="{ 'dc-sidebar-open': sidebarOpen }" @click.outside="sidebarOpen = false">
            <div class="dc-sidebar-inner">
                <template x-for="section in nav" :key="section.id">
                    <div class="dc-nav-group">
                        <div class="dc-nav-group-title" x-text="section.label"></div>
                        <template x-for="item in section.items" :key="item.id">
                            <a class="dc-nav-item" native
                                :class="{ 'active': activeSection === item.id }"
                                :href="'#' + item.id"
                                @click="activeSection = item.id; sidebarOpen = false"
                                x-text="item.label">
                            </a>
                        </template>
                    </div>
                </template>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="dc-main" @scroll.window="updateActive()">

            <!-- ── Introduction ── -->
            <section id="intro" class="dc-section">
                <h1 class="dc-h1">Heroic Webman</h1>
                <p class="dc-lead">Framework untuk membangun aplikasi SPA berbasis PHP + Alpine.js di atas Webman (Workerman). Server-side rendering pada first load, client-side navigation untuk halaman berikutnya.</p>
                <div class="dc-callout">
                    <i class="bi bi-stack dc-callout-icon"></i>
                    <div>
                        <strong>Stack</strong> — Webman (PHP persistent process) · Alpine.js 3 · Pinecone Router
                    </div>
                </div>
            </section>

            <!-- ── Directory Structure ── -->
            <section id="structure" class="dc-section">
                <h2 class="dc-h2">Struktur Direktori</h2>
                <p class="dc-p">Setiap halaman adalah sebuah <em>folder</em> di dalam <code>app/pages/</code> yang berisi setidaknya dua file:</p>
                <div class="dc-code">
                    <pre>app/pages/
├── _layouts/
│   └── index.php          <span class="cc">← HTML shell (satu file untuk semua halaman)</span>
├── home/
│   ├── PageController.php <span class="cc">← Controller halaman</span>
│   └── template.php       <span class="cc">← Template Alpine.js / HTML</span>
├── about/
│   ├── PageController.php
│   └── template.php
└── dashboard/
    ├── PageController.php
    └── template.php</pre>
                </div>
                <p class="dc-p">Nama folder = segmen URL. Folder <code>home</code> → route <code>/</code> (root), folder lain → <code>/nama-folder</code>.</p>
            </section>

            <!-- ── Creating a Page ── -->
            <section id="create-page" class="dc-section">
                <h2 class="dc-h2">Membuat Halaman Baru</h2>

                <h3 class="dc-h3">1. Buat PageController</h3>
                <div class="dc-code">
                    <pre><span class="ck">&lt;?php</span> <span class="ck">namespace</span> app\pages\about;

<span class="ck">use</span> Yllumi\Sayagi\attributes\FrontendRoute;
<span class="ck">use</span> Yllumi\Sayagi\BaseController;

<span class="ca">#[FrontendRoute(route: '/about')]</span>
<span class="ck">class</span> <span class="cn">PageController</span> <span class="ck">extends</span> <span class="cn">BaseController</span>
{
    <span class="ck">public</span> <span class="cv">$data</span> = [];

    <span class="ck">public function</span> <span class="cf">getData</span>()
    {
        <span class="cv">$this</span>-&gt;data[<span class="cs">'title'</span>] = <span class="cs">'About Us'</span>;
        <span class="cv">$this</span>-&gt;data[<span class="cs">'content'</span>] = <span class="cs">'Deskripsi halaman about.'</span>;
    }
}</pre>
                </div>

                <h3 class="dc-h3">2. Buat template.php</h3>
                <div class="dc-code">
                    <pre><span class="cc">&lt;!-- app/pages/about/template.php --&gt;</span>
&lt;div x-data=<span class="cs">"$sayagi.page()"</span>&gt;

    &lt;h1 x-text=<span class="cs">"data.title"</span>&gt;&lt;/h1&gt;
    &lt;p x-text=<span class="cs">"data.content"</span>&gt;&lt;/p&gt;

&lt;/div&gt;</pre>
                </div>
                <p class="dc-p">Selesai. Buka <code>/about</code> — Heroic otomatis mendeteksi controller dan merender halaman.</p>
            </section>

            <!-- ── BaseController ── -->
            <section id="base-controller" class="dc-section">
                <h2 class="dc-h2">BaseController</h2>
                <p class="dc-p"><code>Yllumi\Sayagi\BaseController</code> adalah kelas induk semua PageController. Ia menyediakan tiga method otomatis:</p>

                <div class="dc-table-wrap">
                    <table class="dc-table">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>HTTP</th>
                                <th>Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>getIndex()</code></td>
                                <td>GET <code>/page</code></td>
                                <td>Render full HTML (SSR) untuk first load</td>
                            </tr>
                            <tr>
                                <td><code>getTemplate()</code></td>
                                <td>GET <code>/page/template</code></td>
                                <td>Kembalikan fragment template untuk SPA navigation</td>
                            </tr>
                            <tr>
                                <td><code>getData()</code></td>
                                <td>GET <code>/page/data</code></td>
                                <td>Override ini untuk menyediakan data JSON ke Alpine</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 class="dc-h3">Property <code>$data</code></h3>
                <p class="dc-p">Semua data yang diset pada <code>$this->data</code> di dalam <code>getData()</code> akan:</p>
                <ul class="dc-ul">
                    <li>Di-inject ke template sebagai variabel PHP saat SSR (<code>getIndex</code>)</li>
                    <li>Dikirim sebagai JSON ke Alpine saat SPA navigation melalui endpoint <code>/data</code></li>
                </ul>

                <div class="dc-code">
                    <pre><span class="ck">public function</span> <span class="cf">getData</span>()
{
    <span class="cc">// Data ini otomatis tersedia di template sebagai $this->data</span>
    <span class="cv">$this</span>-&gt;data[<span class="cs">'users'</span>] = User::all();
    <span class="cv">$this</span>-&gt;data[<span class="cs">'count'</span>] = User::count();
}</pre>
                </div>

                <div class="dc-callout dc-callout-warning">
                    <i class="bi bi-exclamation-triangle dc-callout-icon"></i>
                    <div><strong>Catatan:</strong> Jika perlu endpoint <code>/data</code> terpisah dari SSR, override <code>getData()</code> saja — BaseController otomatis memanggilnya dari <code>getIndex()</code> dan endpoint GET <code>/data</code> diarahkan ke method <code>getData()</code> yang sama.</div>
                </div>
            </section>

            <!-- ── FrontendRoute ── -->
            <section id="frontend-route" class="dc-section">
                <h2 class="dc-h2">Attribute #[FrontendRoute]</h2>
                <p class="dc-p">Pasang attribute ini di atas <code>class PageController</code> untuk mengkonfigurasi route frontend di Pinecone Router.</p>

                <div class="dc-table-wrap">
                    <table class="dc-table">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Tipe</th>
                                <th>Default</th>
                                <th>Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>route</code></td>
                                <td>string</td>
                                <td><em>auto</em></td>
                                <td>Path URL frontend, mis. <code>/about</code>. Jika kosong, otomatis dari nama folder.</td>
                            </tr>
                            <tr>
                                <td><code>template</code></td>
                                <td>string</td>
                                <td><em>auto</em></td>
                                <td>Path template relatif ke <code>app/pages/</code>, jika berbeda dari konvensi.</td>
                            </tr>
                            <tr>
                                <td><code>preload</code></td>
                                <td>bool</td>
                                <td><code>false</code></td>
                                <td>Jika <code>true</code>, template di-preload oleh Pinecone Router saat app dimuat.</td>
                            </tr>
                            <tr>
                                <td><code>handler</code></td>
                                <td>array</td>
                                <td><code>[]</code></td>
                                <td>Array nama fungsi Alpine untuk <code>x-handler</code> (mis. guard autentikasi).</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 class="dc-h3">Contoh Penggunaan</h3>
                <div class="dc-code">
                    <pre><span class="cc">// Auto-route dari nama folder (/about)</span>
<span class="ca">#[FrontendRoute]</span>

<span class="cc">// Route eksplisit</span>
<span class="ca">#[FrontendRoute(route: '/tentang-kami')]</span>

<span class="cc">// Route root (/)</span>
<span class="ca">#[FrontendRoute(route: '/')]</span>

<span class="cc">// Preload + custom template path</span>
<span class="ca">#[FrontendRoute(route: '/dashboard', template: 'dashboard/main', preload: true)]</span>

<span class="cc">// Dengan guard handler (fungsi Alpine global)</span>
<span class="ca">#[FrontendRoute(route: '/profile', handler: ['isLoggedIn'])]</span></pre>
                </div>
            </section>

            <!-- ── Template ── -->
            <section id="template" class="dc-section">
                <h2 class="dc-h2">Template</h2>
                <p class="dc-p">File <code>template.php</code> adalah fragment HTML murni yang di-render oleh Pinecone Router. Untuk halaman yang butuh data, bungkus dengan <code>x-data="$sayagi.page()"</code>:</p>

                <div class="dc-code">
                    <pre>&lt;div x-data=<span class="cs">"$sayagi.page()"</span>&gt;

    <span class="cc">&lt;!-- Loading state --&gt;</span>
    &lt;div x-show=<span class="cs">"ui.loading"</span>&gt;Loading...&lt;/div&gt;

    <span class="cc">&lt;!-- Data dari endpoint /page/data --&gt;</span>
    &lt;h1 x-text=<span class="cs">"data.title"</span>&gt;&lt;/h1&gt;

    &lt;ul&gt;
        &lt;template x-for=<span class="cs">"item in data.items || []"</span> :key=<span class="cs">"item.id"</span>&gt;
            &lt;li x-text=<span class="cs">"item.name"</span>&gt;&lt;/li&gt;
        &lt;/template&gt;
    &lt;/ul&gt;

&lt;/div&gt;</pre>
                </div>

                <h3 class="dc-h3">Template Statis (tanpa data)</h3>
                <p class="dc-p">Jika halaman tidak memerlukan data dinamis, cukup tulis HTML biasa tanpa <code>x-data</code>:</p>
                <div class="dc-code">
                    <pre>&lt;div id=<span class="cs">"page-about"</span>&gt;
    &lt;h1&gt;Tentang Kami&lt;/h1&gt;
    &lt;p&gt;Konten statis di sini.&lt;/p&gt;
&lt;/div&gt;</pre>
                </div>

                <h3 class="dc-h3">PHP di Template (SSR)</h3>
                <p class="dc-p">Variabel dari <code>getData()</code> tersedia langsung saat SSR, namun hindari echo PHP di template agar SPA navigation tetap konsisten — gunakan Alpine <code>x-text</code> / <code>x-bind</code> saja.</p>
            </section>

            <!-- ── $sayagi.page() ── -->
            <section id="heroic-page" class="dc-section">
                <h2 class="dc-h2"><code>$sayagi.page()</code></h2>
                <p class="dc-p">Factory function Alpine yang mengelola pengambilan data, caching, dan state halaman.</p>

                <h3 class="dc-h3">Opsi Konfigurasi</h3>
                <div class="dc-code">
                    <pre>x-data=<span class="cs">"$sayagi.page({
    title: 'Judul Halaman',        // Set document.title
    url: 'custom/data',            // Override URL endpoint data
    clearCachePath: 'other/data',  // Hapus cache path lain saat init
    headers: { 'X-Custom': '1' }, // Custom request headers
})"</span></pre>
                </div>

                <h3 class="dc-h3">Properties yang Tersedia di Template</h3>
                <div class="dc-table-wrap">
                    <table class="dc-table">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Tipe</th>
                                <th>Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>data</code></td>
                                <td>object</td>
                                <td>Data dari endpoint JSON <code>/page/data</code></td>
                            </tr>
                            <tr>
                                <td><code>ui.loading</code></td>
                                <td>bool</td>
                                <td><code>true</code> saat sedang fetch data</td>
                            </tr>
                            <tr>
                                <td><code>ui.error</code></td>
                                <td>bool</td>
                                <td><code>true</code> jika fetch gagal</td>
                            </tr>
                            <tr>
                                <td><code>ui.errorMessage</code></td>
                                <td>string</td>
                                <td>Pesan error dari response</td>
                            </tr>
                            <tr>
                                <td><code>meta</code></td>
                                <td>object</td>
                                <td>Data custom dari opsi <code>meta: {}</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 class="dc-h3">Methods</h3>
                <div class="dc-code">
                    <pre><span class="cc">// Muat ulang data dari URL berbeda</span>
<span class="cv">this</span>.loadPage(<span class="cs">'other/data'</span>);

<span class="cc">// Trigger fetch ulang</span>
<span class="cv">this</span>.fetchData();

<span class="cc">// Assign response dan cache</span>
<span class="cv">this</span>.assignResponseData(response);</pre>
                </div>

                <h3 class="dc-h3">SSR Hydration</h3>
                <p class="dc-p">Saat first load, <code>getIndex()</code> menyuntikkan data ke <code>window.__HEROIC_SSR_DATA__</code>. <code>$sayagi.page()</code> mendeteksi ini dan langsung mengisi <code>data</code> tanpa fetch ke server — membuat halaman terasa instan.</p>
                <p class="dc-p">Saat navigasi SPA ke halaman berikutnya, data di-fetch dari endpoint <code>/page/data</code> dan di-cache di memori browser.</p>
            </section>

            <!-- ── $sayagi utilities ── -->
            <section id="heroic-utils" class="dc-section">
                <h2 class="dc-h2">Utilitas <code>$sayagi</code></h2>

                <h3 class="dc-h3"><code>$sayagi.fetch(url, headers?)</code></h3>
                <p class="dc-p">Wrapper <code>fetch()</code> yang secara otomatis menambahkan <code>Authorization: Bearer {token}</code> dari localStorage dan base URL.</p>
                <div class="dc-code">
                    <pre>$sayagi.fetch(<span class="cs">'api/products'</span>)
    .then(res =&gt; <span class="cv">this</span>.data.products = res.data);</pre>
                </div>

                <h3 class="dc-h3"><code>$sayagi.post(url, data?, headers?)</code></h3>
                <p class="dc-p">HTTP POST menggunakan <code>FormData</code>. Mendukung file upload, array, dan nested object.</p>
                <div class="dc-code">
                    <pre>$sayagi.post(<span class="cs">'api/save'</span>, { name: <span class="cv">this</span>.form.name, file: <span class="cv">this</span>.file })
    .then(res =&gt; console.log(res.data));</pre>
                </div>

                <h3 class="dc-h3">Cache Manual</h3>
                <div class="dc-code">
                    <pre>$sayagi.setCache(<span class="cs">'key'</span>, data);   <span class="cc">// Simpan ke cache</span>
$sayagi.getCache(<span class="cs">'key'</span>);         <span class="cc">// Ambil dari cache (null jika tidak ada)</span>
$sayagi.clearCache(<span class="cs">'key'</span>);       <span class="cc">// Hapus cache</span></pre>
                </div>

                <h3 class="dc-h3">Token Auth</h3>
                <p class="dc-p">Token disimpan di <code>localStorage</code> dengan key <code>heroic_token</code>. Semua request fetch/post otomatis menyertakannya.</p>
                <div class="dc-code">
                    <pre>localStorage.setItem(<span class="cs">'heroic_token'</span>, token); <span class="cc">// Set token</span>
localStorage.removeItem(<span class="cs">'heroic_token'</span>);     <span class="cc">// Hapus (logout)</span></pre>
                </div>
            </section>

            <!-- ── Helper Functions ── -->
            <section id="helpers" class="dc-section">
                <h2 class="dc-h2">Helper Functions (PHP)</h2>

                <h3 class="dc-h3"><code>partial($view, $data?)</code></h3>
                <p class="dc-p">Include file partial dari <code>app/pages/</code>. Cocok untuk header, footer, komponen yang dipakai banyak halaman.</p>
                <div class="dc-code">
                    <pre><span class="cc">&lt;?php</span> partial(<span class="cs">'_layouts/partials/head'</span>) <span class="cc">?&gt;</span>
<span class="cc">&lt;?php</span> partial(<span class="cs">'_components/card'</span>, [<span class="cs">'title'</span> =&gt; <span class="cs">'Hello'</span>]) <span class="cc">?&gt;</span></pre>
                </div>

                <h3 class="dc-h3"><code>pageView($view, $data?)</code></h3>
                <p class="dc-p">Render view ke string (untuk SSR injection). Digunakan internal oleh BaseController.</p>
                <div class="dc-code">
                    <pre>$html = pageView(<span class="cs">'home/template'</span>, [<span class="cs">'title'</span> =&gt; <span class="cs">'Hello'</span>]);</pre>
                </div>

                <h3 class="dc-h3"><code>asset_url($filePath)</code></h3>
                <p class="dc-p">Generate URL aset dengan cache-busting otomatis berdasarkan waktu modifikasi file.</p>
                <div class="dc-code">
                    <pre>&lt;script src=<span class="cs">"&lt;?= asset_url('js/main.js') ?&gt;"</span>&gt;&lt;/script&gt;
<span class="cc">&lt;!-- Output: js/main.js?v=1716000000 --&gt;</span></pre>
                </div>

                <h3 class="dc-h3"><code>base_url($path?)</code></h3>
                <p class="dc-p">Kembalikan URL absolut berdasarkan <code>config('app.url')</code>.</p>
                <div class="dc-code">
                    <pre>base_url(<span class="cs">'api/products'</span>);
<span class="cc">// Output: https://example.com/api/products</span></pre>
                </div>
            </section>

            <!-- ── Routing ── -->
            <section id="routing" class="dc-section">
                <h2 class="dc-h2">Routing</h2>
                <p class="dc-p">Heroic menggunakan <strong>dua layer routing</strong>:</p>

                <h3 class="dc-h3">1. Backend Routing (PageRouter)</h3>
                <p class="dc-p">Webman menangkap semua request melalui fallback route. <code>PageRouter</code> mencocokkan path URL dengan struktur folder <code>app/pages/</code> dan mendelegasikan ke method controller yang sesuai:</p>
                <div class="dc-table-wrap">
                    <table class="dc-table">
                        <thead>
                            <tr>
                                <th>URL</th>
                                <th>Method</th>
                                <th>Controller Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>GET /about</code></td>
                                <td>GET</td>
                                <td><code>getIndex()</code></td>
                            </tr>
                            <tr>
                                <td><code>GET /about/template</code></td>
                                <td>GET</td>
                                <td><code>getTemplate()</code></td>
                            </tr>
                            <tr>
                                <td><code>GET /about/data</code></td>
                                <td>GET</td>
                                <td><code>getData()</code></td>
                            </tr>
                            <tr>
                                <td><code>POST /about/save</code></td>
                                <td>POST</td>
                                <td><code>postSave()</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 class="dc-h3">2. Frontend Routing (Pinecone Router)</h3>
                <p class="dc-p">Pinecone Router berjalan di browser, menangani navigasi SPA. Setiap halaman yang punya <code>#[FrontendRoute]</code> otomatis didaftarkan sebagai route.</p>
                <div class="dc-code">
                    <pre>&lt;template x-route=<span class="cs">"/"</span> x-template=<span class="cs">"/home/template"</span>&gt;&lt;/template&gt;
&lt;template x-route=<span class="cs">"/about"</span> x-template=<span class="cs">"/about/template"</span>&gt;&lt;/template&gt;
<span class="cc">&lt;!-- Di-generate otomatis oleh FERouter::getRouter() --&gt;</span></pre>
                </div>
                <p class="dc-p">Link antar halaman cukup menggunakan <code>&lt;a href="/about"&gt;</code> biasa — Pinecone Router akan mengintersepsi dan melakukan SPA navigation tanpa reload.</p>
            </section>

            <!-- ── FERouter ── -->
            <section id="fe-router" class="dc-section">
                <h2 class="dc-h2">FERouter (PHP)</h2>
                <p class="dc-p"><code>Yllumi\Sayagi\FERouter</code> menghasilkan HTML router yang di-inject ke layout.</p>

                <h3 class="dc-h3"><code>FERouter::getRouter()</code></h3>
                <div class="dc-code">
                    <pre>FERouter::getRouter(
    string $ssrRoute = '',   <span class="cc">// Route yang di-SSR, mis. '/'</span>
    string $ssrContent = '', <span class="cc">// HTML hasil SSR</span>
    ?array $ssrData = null   <span class="cc">// Data JSON untuk hydration</span>
): string</pre>
                </div>
                <p class="dc-p">Digunakan di <code>_layouts/index.php</code>:</p>
                <div class="dc-code">
                    <pre>&lt;div id=<span class="cs">"router"</span> x-data=<span class="cs">"router()"</span>&gt;
    <span class="cc">&lt;?=</span> \Yllumi\Sayagi\FERouter::getRouter(
        $ssr_route ?? <span class="cs">''</span>,
        $ssr_content ?? <span class="cs">''</span>,
        $ssr_data ?? null
    ) <span class="cc">?&gt;</span>
&lt;/div&gt;</pre>
                </div>

                <h3 class="dc-h3"><code>FERouter::ssrDataScript()</code></h3>
                <p class="dc-p">Generate script inline untuk menyuntikkan data SSR ke <code>window.__HEROIC_SSR_DATA__</code>.</p>
                <div class="dc-code">
                    <pre>&lt;script&gt;
    <span class="cc">&lt;?=</span> \Yllumi\Sayagi\FERouter::ssrDataScript($ssr_data ?? null) <span class="cc">?&gt;</span>
&lt;/script&gt;</pre>
                </div>
            </section>

            <!-- ── Custom Methods ── -->
            <section id="custom-methods" class="dc-section">
                <h2 class="dc-h2">Method Controller Kustom</h2>
                <p class="dc-p">Selain method standar, kamu bisa menambahkan method apapun di controller. Konvensi penamaan: <code>{httpVerb}{ActionName}()</code>.</p>
                <div class="dc-code">
                    <pre><span class="ck">class</span> <span class="cn">PageController</span> <span class="ck">extends</span> <span class="cn">BaseController</span>
{
    <span class="cc">// GET /users/data → getData()</span>
    <span class="ck">public function</span> <span class="cf">getData</span>()
    {
        <span class="ck">return</span> json([<span class="cs">'users'</span> =&gt; User::all()]);
    }

    <span class="cc">// POST /users/store → postStore()</span>
    <span class="ck">public function</span> <span class="cf">postStore</span>(Request <span class="cv">$request</span>)
    {
        User::create(<span class="cv">$request</span>-&gt;post());
        <span class="ck">return</span> json([<span class="cs">'ok'</span> =&gt; true]);
    }

    <span class="cc">// GET /users/123 → getIndex($id)</span>
    <span class="ck">public function</span> <span class="cf">getIndex</span>(Request <span class="cv">$request</span>, <span class="ck">string</span> <span class="cv">$id</span>)
    {
        <span class="ck">return</span> json(User::find(<span class="cv">$id</span>));
    }
}</pre>
                </div>
            </section>

            <div class="dc-footer">
                <a href="/" class="dc-footer-link">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Home
                </a>
            </div>

        </main>
    </div>
</div>

<script>
    Alpine.data('docsPage', function() {
        return {
            sidebarOpen: false,
            activeSection: 'intro',
            nav: [{
                    id: 'start',
                    label: 'Mulai',
                    items: [{
                            id: 'intro',
                            label: 'Introduction'
                        },
                        {
                            id: 'structure',
                            label: 'Struktur Direktori'
                        },
                        {
                            id: 'create-page',
                            label: 'Membuat Halaman'
                        },
                    ]
                },
                {
                    id: 'core',
                    label: 'Core',
                    items: [{
                            id: 'base-controller',
                            label: 'BaseController'
                        },
                        {
                            id: 'frontend-route',
                            label: '#[FrontendRoute]'
                        },
                        {
                            id: 'template',
                            label: 'Template'
                        },
                        {
                            id: 'routing',
                            label: 'Routing'
                        },
                    ]
                },
                {
                    id: 'client',
                    label: 'Client Side',
                    items: [{
                            id: 'heroic-page',
                            label: '$sayagi.page()'
                        },
                        {
                            id: 'heroic-utils',
                            label: 'Utilitas $sayagi'
                        },
                    ]
                },
                {
                    id: 'reference',
                    label: 'Reference',
                    items: [{
                            id: 'helpers',
                            label: 'Helper Functions'
                        },
                        {
                            id: 'fe-router',
                            label: 'FERouter (PHP)'
                        },
                        {
                            id: 'custom-methods',
                            label: 'Method Kustom'
                        },
                    ]
                },
            ],

            init() {
                console.log('Docs page initialized');
            },

            updateActive() {
                const sections = document.querySelectorAll('.dc-section');
                let current = 'intro';
                sections.forEach(s => {
                    if (s.getBoundingClientRect().top <= 100) current = s.id;
                });
                this.activeSection = current;
            }
        };
    });
</script>

<style>
    #page-docs {
        font-family: 'Poppins', sans-serif;
        background: #fff;
        min-height: 100vh;
        color: #1c1917;
    }

    /* Top Bar */
    .dc-topbar {
        position: sticky;
        top: 0;
        z-index: 100;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0 16px;
        height: 52px;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(8px);
        border-bottom: 1px solid #f0ede9;
    }

    .dc-back {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        color: #78716c;
        text-decoration: none;
        background: #fafaf9;
        border: 1px solid #e7e5e4;
        flex-shrink: 0;
    }

    .dc-back:hover {
        background: #f5f5f4;
        color: #1c1917;
    }

    .dc-topbar-title {
        font-size: 15px;
        font-weight: 600;
        color: #1c1917;
        flex: 1;
    }

    .dc-menu-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        border: 1px solid #e7e5e4;
        background: #fafaf9;
        color: #78716c;
        font-size: 18px;
        cursor: pointer;
    }

    /* Layout */
    .dc-layout {
        display: flex;
        min-height: calc(100vh - 52px);
    }

    /* Sidebar */
    .dc-sidebar {
        position: fixed;
        top: 52px;
        left: 0;
        bottom: 0;
        width: 240px;
        background: #fff;
        border-right: 1px solid #f0ede9;
        overflow-y: auto;
        transform: translateX(-100%);
        transition: transform 0.2s ease;
        z-index: 90;
    }

    .dc-sidebar-open {
        transform: translateX(0);
    }

    .dc-sidebar-inner {
        padding: 16px 12px 32px;
    }

    .dc-nav-group {
        margin-bottom: 20px;
    }

    .dc-nav-group-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: #a8a29e;
        padding: 0 8px;
        margin-bottom: 6px;
    }

    .dc-nav-item {
        display: block;
        padding: 7px 10px;
        border-radius: 8px;
        font-size: 14px;
        color: #57534e;
        text-decoration: none;
        transition: all 0.15s;
        margin-bottom: 2px;
    }

    .dc-nav-item:hover {
        background: #fafaf9;
        color: #1c1917;
    }

    .dc-nav-item.active {
        background: #fff7ed;
        color: #ea580c;
        font-weight: 600;
    }

    /* Main */
    .dc-main {
        flex: 1;
        padding: 0 20px 60px;
        max-width: 760px;
        margin: 0 auto;
        width: 100%;
        box-sizing: border-box;
    }

    /* Sections */
    .dc-section {
        padding-top: 40px;
        border-bottom: 1px solid #f5f5f4;
        padding-bottom: 8px;
        scroll-margin-top: 72px;
    }

    .dc-section:last-of-type {
        border-bottom: none;
    }

    .dc-h1 {
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -0.5px;
        margin: 0 0 12px;
        color: #1c1917;
    }

    .dc-h2 {
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 12px;
        color: #1c1917;
        letter-spacing: -0.3px;
    }

    .dc-h3 {
        font-size: 15px;
        font-weight: 700;
        margin: 20px 0 8px;
        color: #292524;
    }

    .dc-lead {
        font-size: 15px;
        color: #57534e;
        line-height: 1.7;
        margin: 0 0 16px;
    }

    .dc-p {
        font-size: 14px;
        color: #57534e;
        line-height: 1.75;
        margin: 0 0 12px;
    }

    .dc-ul {
        margin: 0 0 12px;
        padding-left: 20px;
    }

    .dc-ul li {
        font-size: 14px;
        color: #57534e;
        line-height: 1.75;
    }

    /* Code blocks */
    .dc-code {
        background: #fafaf9;
        border: 1px solid #e7e5e4;
        border-radius: 10px;
        padding: 16px;
        overflow-x: auto;
        margin-bottom: 14px;
    }

    .dc-code pre {
        margin: 0;
        font-family: 'Courier New', Consolas, monospace;
        font-size: 13px;
        line-height: 1.8;
        color: #292524;
        white-space: pre;
    }

    .cc {
        color: #a8a29e;
    }

    /* comment */
    .ck {
        color: #7c3aed;
    }

    /* keyword */
    .cn {
        color: #0e7490;
    }

    /* class name */
    .cf {
        color: #b45309;
    }

    /* function name */
    .cv {
        color: #15803d;
    }

    /* variable */
    .cs {
        color: #b91c1c;
    }

    /* string */
    .ca {
        color: #9333ea;
    }

    /* attribute */

    /* Callout */
    .dc-callout {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 10px;
        padding: 14px 16px;
        font-size: 14px;
        color: #0c4a6e;
        line-height: 1.65;
        margin-bottom: 14px;
    }

    .dc-callout-warning {
        background: #fffbeb;
        border-color: #fed7aa;
        color: #7c2d12;
    }

    .dc-callout-icon {
        font-size: 18px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    /* Table */
    .dc-table-wrap {
        overflow-x: auto;
        margin-bottom: 14px;
    }

    .dc-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }

    .dc-table th {
        background: #fafaf9;
        border: 1px solid #e7e5e4;
        padding: 8px 12px;
        text-align: left;
        font-weight: 700;
        color: #1c1917;
    }

    .dc-table td {
        border: 1px solid #f0ede9;
        padding: 8px 12px;
        color: #44403c;
        line-height: 1.6;
    }

    .dc-table td code,
    .dc-table th code {
        background: #f5f5f4;
        border: 1px solid #e7e5e4;
        border-radius: 4px;
        padding: 1px 6px;
        font-size: 12.5px;
        color: #ea580c;
    }

    /* Inline code */
    .dc-p code,
    .dc-lead code,
    .dc-ul code {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 4px;
        padding: 1px 6px;
        font-size: 12.5px;
        color: #ea580c;
        font-family: 'Courier New', monospace;
    }

    /* Footer */
    .dc-footer {
        padding: 40px 0 20px;
        text-align: center;
    }

    .dc-footer-link {
        display: inline-flex;
        align-items: center;
        font-size: 14px;
        color: #78716c;
        text-decoration: none;
        padding: 10px 20px;
        border: 1px solid #e7e5e4;
        border-radius: 10px;
    }

    .dc-footer-link:hover {
        background: #fafaf9;
        color: #1c1917;
    }

    @media (min-width: 768px) {
        .dc-menu-toggle {
            display: none;
        }

        .dc-sidebar {
            position: sticky;
            top: 52px;
            height: calc(100vh - 52px);
            transform: translateX(0);
            flex-shrink: 0;
        }

        .dc-layout {
            align-items: flex-start;
        }

        .dc-main {
            padding: 0 40px 60px;
        }
    }
</style>