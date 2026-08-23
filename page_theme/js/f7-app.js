/**
 * f7-app.js — Framework7 9.x bootstrap untuk section /mobile
 *
 * Strategi CSR:
 *  - F7 Router mengambil alih navigasi di section /mobile (Pinecone tidak dipakai).
 *  - Tiap halaman di-fetch via async route:
 *      template = /{route}/template  (HTML fragmen .page)
 *      data     = /{route}/data      (JSON)
 *    Data disimpan ke cache $sayagi agar komponen Alpine `$sayagi.page()`
 *    ter-hydrasi TANPA fetch ulang (cache key: "path/data").
 *  - Setelah F7 merender halaman, panggil Alpine.initTree(page.el) supaya
 *    direktif x-data di template menjadi reaktif.
 */
(function () {
  'use strict';

  // ===== Eksekusi <script> di dalam halaman F7 =====
  // Framework7 TIDAK mengeksekusi <script> pada fragmen halaman yang di-fetch
  // via async route (script hanya berjalan saat SSR/first load; F7 bahkan
  // membuang <script> saat parse fragmen). Karena itu:
  //  - loadF7Page() mengekstrak script dari template sebelum di-inject,
  //    disimpan per routePath (pageScriptsByPath).
  //  - pageInit menjalankan script tsb SEBELUM Alpine.initTree supaya fungsi
  //    global (mis. pageData()) sudah tersedia saat direktif x-data dievaluasi.
  const pageScriptsByPath = new Map();
  let pendingPageScripts = [];

  // Ekstrak semua <script> (inline & src) dari HTML template → HTML bersih + daftar script.
  function extractPageScripts(html) {
    const scripts = [];
    const clean = html.replace(/<script\b[^>]*>([\s\S]*?)<\/script>/gi, (match, body) => {
      const src = match.match(/\bsrc\s*=\s*["']([^"']+)["']/i);
      scripts.push(src ? { type: 'src', src: src[1] } : { type: 'inline', code: body });
      return '';
    });
    return { clean, scripts };
  }

  // Jalankan script page — clone ke <head> mempertahankan scope global
  // (mis. definisi function pageData()).
  function runPageScripts(scripts) {
    scripts.forEach((item) => {
      const el = document.createElement('script');
      if (item.type === 'src') {
        el.src = item.src;
        el.async = true;
      } else {
        el.textContent = item.code;
      }
      document.head.appendChild(el);
    });
  }

  // ===== Loader halaman F7 (template + data) =====
  // cfg    = { path, template } dari window.__F7_ROUTES__:
  //            - path     : URL publik (bisa ber-param, mis. '/books/:id')
  //            - template : path template statis (mis. '/books/detail/template')
  //                         eksplisit dari atribut atau turunan path route.
  // params = nilai parameter route aktual dari F7 (mis. { id: '2' }).
  // URL data diturunkan dari template: buang segment '/template' terakhir,
  // ganti '/data', lalu tambahkan SEMUA parameter (urutan sesuai path).
  // Contoh path '/course/:course_id/lesson/:lesson_id' dgn template
  // '/course/lesson/detail/template' -> data '/course/lesson/detail/data/:course_id/:lesson_id'.
  async function loadF7Page(cfg, params, resolve) {
    const templateUrl = cfg.template;
    // Cache key template statis per route (sama untuk semua id).
    const templateKey = templateUrl.replace(/^\/\/+/, '');

    // Nama parameter diurutkan sesuai urutan kemunculan di path (mis. course_id, lesson_id).
    const paramNames = (cfg.path.match(/:([^/]+)/g) || []).map((s) => s.slice(1));

    // URL data: template - '/template' -> '/data' + semua parameter.
    let dataUrl = templateUrl.replace(/\/template$/, '/data');
    paramNames.forEach((name) => {
      dataUrl += '/' + (params[name] ?? '');
    });

    // Cache key data mengikuti URL PUBLIK (path dgn param disubstitusi, mis.
    // '/books/2/' -> 'books/2/data'), BUKAN folder server — supaya cocok dengan
    // turunan key di $sayagi.page() (el.f7Page.route.url -> "path/data").
    let publicPath = cfg.path;
    paramNames.forEach((name) => {
      publicPath = publicPath.replace(':' + name, params[name] ?? '');
    });
    publicPath = publicPath.replace(/^\/|\/$/g, '');
    if (publicPath === '') publicPath = 'home';
    const cacheKey = publicPath + '/data';

    try {
      // Template: pakai cache bila sudah pernah dimuat (setara preload Pinecone —
      // template yang sudah pernah difetch tidak difetch lagi).
      let template = window.$sayagi ? $sayagi.getCache(templateKey) : null;
      // Script template — di-cache per templateKey supaya dieksekusi di SETIAP
      // navigasi forward (bukan hanya saat template pertama kali di-fetch).
      let scripts = pageScriptsByPath.get(templateKey) || [];
      // Data: pakai cache bila sudah pernah dimuat (hindari fetch ulang).
      let data = window.$sayagi ? $sayagi.getCache(cacheKey) : null;

      if (template == null) {
        const tplRes = await fetch(templateUrl);
        const raw = await tplRes.text();
        // Ekstrak <script> — F7 membuang script saat fragmen di-inject, jadi
        // dijalankan manual via pendingPageScripts (dikonsumsi di pageInit).
        const parsed = extractPageScripts(raw);
        template = parsed.clean;
        scripts = parsed.scripts;
        pageScriptsByPath.set(templateKey, scripts);
        if (window.$sayagi) {
          $sayagi.setCache(templateKey, template);
        }
      }

      if (data == null) {
        const dataRes = await fetch(dataUrl);
        data = await dataRes.json();
        if (window.$sayagi) {
          $sayagi.setCache(cacheKey, data);
        }
      }

      // F7 v9 async route: resolve menerima properti content (bukan template).
      // PENTING: resolve() memicu pageInit SECARA SINKRON. Slot pendingPageScripts
      // harus diisi SEBELUM resolve — jika tidak, pageInit navigasi pertama
      // membaca slot kosong (script halaman baru tereksekusi di navigasi kedua).
      pendingPageScripts = scripts;
      resolve({ content: template });
    } catch (err) {
      console.error('[F7] Gagal memuat halaman:', err);
      resolve({
        content:
          '<div class="page">' +
          '<div class="navbar"><div class="navbar-inner"><div class="title">Error</div></div></div>' +
          '<div class="page-content"><div class="block"><p>Gagal memuat halaman.</p></div></div>' +
          '</div>',
      });
    }
  }

  // ===== Definisi routes F7 (path dengan trailing slash) =====
  // Daftar routes di-generate SERVER-SIDE dari atribut #[FrontendRoute] tiap
  // class PageController (via \Yllumi\Sayagi\FERouter::getF7RoutesScript())
  // lalu di-inject layout mobile sebagai window.__F7_ROUTES__. Tiap config:
  //   { path, template }
  //     - path     : URL publik (mis. '/books/:id', bisa ber-param)
  //     - template : path template statis (mis. '/books/detail/template'),
  //                  eksplisit dari atribut atau turunan path route.
  // URL data diturunkan loadF7Page dari template: buang '/template', ganti
  // '/data', lalu tambahkan semua parameter (urutan sesuai path).
  // Bila window.__F7_ROUTES__ kosong (layout tanpa inject), fallback ke daftar
  // hardcoded di bawah — f7-app.js tetap berfungsi berdiri sendiri.
  function buildF7Route(cfg) {
    return {
      path: cfg.path,
      // F7 v9: fungsi async menerima satu context object ({ to, resolve }),
      // params route aktual diakses via to.params.
      async({ to, resolve }) {
        loadF7Page(cfg, to.params || {}, resolve);
      },
    };
  }

  const fallbackRoutes = [
    // Landing/home web mobile — dipakai utk navigasi in-app (mis.
    // fallback tombol back saat deep-link ke sub-halaman, atau navigasi programatik).
    { path: '/', template: '/home/template' },
  ];

  const routes = (window.__F7_ROUTES__ && window.__F7_ROUTES__.length
    ? window.__F7_ROUTES__
    : fallbackRoutes
  ).map(buildF7Route);

  // ===== Helper navigasi programatik (selalu tersedia) =====
  if (window.$sayagi) {
    $sayagi.f7 = {
      navigate(url) {
        const main = window.f7app && window.f7app.views && window.f7app.views.main;
        if (main) {
          main.router.navigate(url);
        } else {
          window.location.href = url;
        }
      },
    };
  }

  // ===== Inisialisasi App F7 =====
  // Transisi SELALU aktif (sesuai kebutuhan). Fallback pageBeforeIn (650ms) di
  // bawah menangani bila ada transisi yang macet (mis. lingkungan tanpa event
  // transitionend) agar navigasi tidak terblokir.
  (function initApp() {
    const app = new Framework7({
      el: '#app',
      name: 'Sayagi App',
    //   theme: 'ios',
      darkMode: false,
      view: {
        // Browser history dengan URL bersih (history.pushState):
        //  - URL ikut berubah saat navigasi (mis. /mobile/books/)
        //  - refresh kembali ke halaman terakhir (server SSR sesuai URL)
        //  - tombol back/forward browser memicu navigasi F7 via popstate
        browserHistory: true,
        browserHistorySeparator: '',
        // Root = origin TANPA trailing slash (origin + '/' + path berawalan '/'
        // menghasilkan '//mobile/...' = dobel slash).
        browserHistoryRoot: window.location.origin,
        // Server sudah SSR sesuai URL yang diminta -> pakai initial page DOM
        // (mencegah F7 load ulang via async pada deep-link/refresh).
        browserHistoryInitialMatch: true,
        browserHistoryStoreHistory: false,
        animate: true,
        // Navbar tetap di dalam page (tidak dipindah F7) agar scope Alpine tidak rusak.
        iosDynamicNavbar: false,
      },
      routes: routes,
    });
    window.f7app = app;

    // ===== Bersihkan history stale utk halaman awal deep-link =====
    // Saat refresh/load langsung di URL dalam (mis. /books/detail/2/), F7
    // menempatkan '/' (home) sbg entry 'sebelumnya' + membuat elemen home
    // (page-previous) di DOM — sehingga tombol back akan "kembali" ke home
    // (glitch: home flash lalu reload). Untuk halaman awal BUKAN root,
    // bersihkan previous history supaya tombol back memakai data-back-to
    // (fallback) langsung, tanpa melewati home.
    const initRouter = app.views && app.views.main && app.views.main.router;
    const initUrl = initRouter && initRouter.currentRoute && initRouter.currentRoute.url;
    if (initUrl && initUrl !== '/') {
      initRouter.clearPreviousHistory();
    }

    // ===== Tombol back (class .back-btn) =====
    // Link memakai class `prevent-router` (BUKAN class `back`) agar F7 tidak
    // mengintervensi — karena router.back(url) F7 melakukan FULL page load bila
    // url tidak ada di history. Handler ini menangani dua kasus:
    //  1) Ada history -> router.back() (pop, transisi normal).
    //  2) Deep-link (tanpa history) -> router.navigate(href/data-back-to) ke
    //     halaman induk secara in-app (tanpa reload penuh).
    document.addEventListener('click', (e) => {
      const link = e.target && e.target.closest ? e.target.closest('.back-btn') : null;
      if (!link) return;
      e.preventDefault();
      const router = app && app.views && app.views.main && app.views.main.router;
      if (!router) return;
      if ((router.history.length || 0) > 1) {
        router.back();
      } else {
        const target = link.getAttribute('href') || link.getAttribute('data-back-to') || '/';
        // Deep-link: halaman induk TIDAK ada di history, jadi router.back(url)
        // tidak bisa (F7 fallback ke full page load). Pakai navigate in-app dengan:
        //  - transition 'f7-fade' (netral) — BUKAN slide maju, karena ini aksi
        //    "kembali" ke halaman induk, bukan membuka sub-halaman baru.
        //  - clearPreviousHistory: true — hapus halaman deep-link asal dari
        //    history, sehingga halaman induk jadi ROOT. Back berikutnya terus
        //    naik (mis. detail -> books -> /mobile/), bukan melingkar kembali
        //    ke halaman deep-link asal.
        router.navigate(target, { transition: 'f7-fade', clearPreviousHistory: true });
      }
    });

    // ===== Navigasi tabbar tanpa transisi =====
    // Home & books adalah halaman ROOT untuk fitur berbeda -> pindah tab tidak
    // perlu animasi slide (mirip tab bar native). Link tabbar memakai class
    // `prevent-router` agar F7 tidak menavigasi sendiri; handler ini memanggil
    // router.navigate dengan animate:false (pertukaran halaman instan).
    document.addEventListener('click', (e) => {
      const link = e.target && e.target.closest ? e.target.closest('.tabbar-no-transition a') : null;
      if (!link) return;
      e.preventDefault();
      const router = app && app.views && app.views.main && app.views.main.router;
      if (!router) return;
      const href = link.getAttribute('href');
      if (!href || href === '#') return;
      // Jangan navigasi ulang bila sudah berada di tab yang sama.
      const current = (router.currentRoute && router.currentRoute.url || '').replace(/\/+$/, '');
      if (current === href.replace(/\/+$/, '')) return;
      router.navigate(href, { animate: false });
    });

    // Page events halaman awal terpicu saat konstruktor (app belum ter-assign),
    // jadi sinkronkan ulang visibilitas tombol back setelah app selesai dibuat.
    setTimeout(() => {
      const viewEl = app.views && app.views.main && app.views.main.el;
      const currentEl = viewEl && viewEl.querySelector('.page-current');
      if (currentEl) syncBackLink(currentEl);
    }, 0);

    // ===== Hydrasi Alpine setelah halaman F7 dirender =====
    // Skip elemen yang sudah di-initialize Alpine (anti double-init saat back-nav).
    app.on('pageInit', (page) => {
      if (!page || !page.el) return;
      // Eksekusi script halaman yang di-fetch F7 (diekstrak di loadF7Page).
      // Dijalankan SEBELUM Alpine.initTree agar fungsi global (mis. pageData())
      // sudah tersedia saat direktif x-data dievaluasi.
      if (pendingPageScripts.length) {
        runPageScripts(pendingPageScripts);
        pendingPageScripts = [];
      }
      if (!window.Alpine) return;
      const root = page.el.querySelector('[x-data]');
      if (root && !root._x_dataStack) {
        Alpine.initTree(page.el);
      }
      // Sinkronkan tombol back sejak awal (mis. initial page deep-link/refresh).
      syncBackLink(page.el);
    });

    // ===== Helper: sinkronkan visibilitas tombol back =====
    function syncBackLink(pageEl) {
      if (!pageEl) return;
      const main = app && app.views && app.views.main;
      if (!main) return;
      const backLink = pageEl.querySelector('.back-btn');
      if (backLink) {
        const hasPrev = (main.router.history.length || 0) > 1;
        // Tampilkan tombol back bila ada history (pop) ATAU ada fallback
        // data-back-to (kasus deep-link ke sub-halaman tanpa history).
        const hasFallback = !!backLink.getAttribute('data-back-to');
        backLink.style.display = hasPrev || hasFallback ? '' : 'none';
      }
    }

    // Set posisi halaman F7 via classList (setPagePosition butuh instance Dom7).
    function forcePosition(el, position) {
      if (!el) return;
      el.classList.remove('page-previous', 'page-current', 'page-next', 'page-on-left', 'page-on-right');
      el.classList.add('page-' + position);
    }

    // ===== Sembunyikan tombol back bila tidak ada halaman sebelumnya =====
    app.on('pageAfterIn', (page) => {
      syncBackLink(page && page.el);
    });

    // ===== Fallback penyelesaian transisi (jaga-jaga) =====
    // Bila transisi macet saat runtime (mis. lingkungan tanpa event transitionend),
    // selesaikan manual setelah 650ms + sinkronkan state router (history/currentRoute)
    // khusus kasus MUNDUR agar navigasi berikutnya tetap konsisten.
    // Tidak aktif di browser normal (transisi selesai ~400ms).
    app.on('pageBeforeIn', (page) => {
      const main = app && app.views && app.views.main;
      const router = main && main.router;
      if (!router || !page || !page.el) return;
      const nextEl = page.el;
      setTimeout(() => {
        if (!document.body.contains(nextEl)) return;
        const currentEl = main.el.querySelector('.page-current');
        if (currentEl === nextEl) return; // transisi sudah selesai normal
        try {
          const isForward = nextEl.classList.contains('page-next');
          if (isForward) {
            // Transisi MAJU macet: next -> current, current lama -> previous.
            forcePosition(currentEl, 'previous');
            forcePosition(nextEl, 'current');
          } else {
            // Transisi MUNDUR macet: page masuk -> current, current lama -> on-right.
            const oldRoute = currentEl && currentEl.f7Page ? currentEl.f7Page.route : router.currentRoute;
            forcePosition(currentEl, 'on-right');
            forcePosition(nextEl, 'current');
            // Sinkronkan state internal router (mirip yang dilakukan F7 saat back).
            const route = nextEl.f7Page && nextEl.f7Page.route;
            if (route) {
              if (router.history.length > 1) router.history.pop();
              router.currentRoute = route;
              router.previousRoute = oldRoute;
            }
          }
          router.allowPageChange = true;
          router.currentPageEl = nextEl;
          syncBackLink(nextEl);
        } catch (err) {
          console.error('[F7] Fallback transisi gagal:', err);
        }
      }, 650);
    });
  })();
})();
