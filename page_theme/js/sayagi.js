(function () {
  "use strict";

  // INISIALISASI SEGERA - $sayagi langsung tersedia
  window.$sayagi = window.$sayagi || {};
  const $sayagi = window.$sayagi;

  // Definisikan base_url jika belum ada
  window.base_url = window.base_url || "";

  window.$sayagi.cached = {};

  // Fungsi untuk menyimpan cache
  $sayagi.setCache = function (key, data) {
    $sayagi.cached[key] = data;
  };

  // Fungsi untuk mengambil cache
  $sayagi.getCache = function (key) {
    return $sayagi.cached[key] ?? null;
  };

  // Fungsi untuk menghapus cache
  $sayagi.clearCache = function (key) {
    delete $sayagi.cached[key];
  };

  // Contoh fungsi
  $sayagi.toastr = function (message, type = "success", position = "top") {
    if (typeof Toastify === "undefined") {
      console.warn("Toastify not loaded yet");
      return;
    }
    Toastify({
      text: message,
      close: true,
      duration: 5000,
      className: type,
      gravity: position,
      offset: { y: 40 },
    }).showToast();
  };

  /**************************************************************************
   * Fetch Ajax Data
   **************************************************************************/
  $sayagi.fetch = function (url, headers = {}) {
    // Pastikan base_url diakhiri dengan '/'
    if (!window.base_url.endsWith("/")) {
      window.base_url += "/";
    }

    // Gabungkan base_url dan url
    let fullUrl = window.base_url + url;

    // Default headers
    const defaultHeaders = {
      "X-Requested-With": "XMLHttpRequest",
      Authorization: `Bearer ` + localStorage.getItem("heroic_token"),
    };

    // Merge defaultHeaders dengan headers dari parameter
    const finalHeaders = {
      ...defaultHeaders,
      ...headers,
    };

    return fetch(fullUrl, {
      method: "GET",
      headers: finalHeaders,
    })
      .then(async (response) => {
        // Parse response body
        const contentType = response.headers.get("content-type");
        let data;

        if (contentType && contentType.includes("application/json")) {
          data = await response.json();
        } else {
          data = await response.text();
        }

        // Buat response object mirip axios
        const axiosLikeResponse = {
          data: data,
          status: response.status,
          statusText: response.statusText,
          headers: response.headers,
          config: { url: fullUrl, headers: finalHeaders },
          request: response,
        };

        // Throw error jika status tidak OK (mirip axios)
        if (!response.ok) {
          const error = new Error(response.statusText || "Request failed");
          error.response = axiosLikeResponse;
          throw error;
        }

        return axiosLikeResponse;
      })
      .catch((error) => {
        if (error.response && error.response.status === 401) {
          console.warn("Unauthorized. Removing token.");
          localStorage.removeItem("heroic_token");
        }
        console.error("Fetch error:", error);
        throw error; // Lempar ulang error supaya bisa ditangani caller
      });
  };

  /**************************************************************************
   * Post Ajax Data
   **************************************************************************/
  $sayagi.post = function (url, data = {}, headers = {}) {
    // Membuat objek FormData
    const formData = new FormData();

    for (const key in data) {
      if (data.hasOwnProperty(key)) {
        const value = data[key];

        if (Array.isArray(value)) {
          value.forEach((item) => formData.append(`${key}[]`, item));
        } else if (value instanceof File || value instanceof Blob) {
          formData.append(key, value);
        } else if (typeof value === "object" && value !== null) {
          formData.append(key, JSON.stringify(value));
        } else {
          formData.append(key, value);
        }
      }
    }

    // Header default
    const defaultHeaders = {
      "X-Requested-With": "XMLHttpRequest",
      Authorization: `Bearer ` + localStorage.getItem("heroic_token"),
    };

    // Merge defaultHeaders dan custom headers
    const finalHeaders = {
      ...defaultHeaders,
      ...headers,
    };

    return fetch(url, {
      method: "POST",
      headers: finalHeaders,
      body: formData,
    })
      .then(async (response) => {
        // Parse response body
        const contentType = response.headers.get("content-type");
        let data;

        if (contentType && contentType.includes("application/json")) {
          data = await response.json();
        } else {
          data = await response.text();
        }

        // Buat response object mirip axios
        const axiosLikeResponse = {
          data: data,
          status: response.status,
          statusText: response.statusText,
          headers: response.headers,
          config: { url: url, headers: finalHeaders },
          request: response,
        };

        // Throw error jika status tidak OK (mirip axios)
        if (!response.ok) {
          const error = new Error(response.statusText || "Request failed");
          error.response = axiosLikeResponse;
          throw error;
        }

        return axiosLikeResponse;
      })
      .catch((error) => {
        if (error.response && error.response.status === 401) {
          console.warn("Unauthorized. Removing token.");
          localStorage.removeItem("heroic_token");
        }
        console.error("Post error:", error);
        throw error; // Lempar ulang error supaya bisa ditangani caller
      });
  };

  $sayagi.page = function ({
    title = null,
    url = null,
    clearCachePath = false,
    headers = {},
    meta = {},
  } = {}) {
    return {
      // Configuration properties
      config: {
        title,
        url,
        headers,
        clearCachePath,
      },

      // UI properties
      ui: {
        loading: false,
        submitting: false,
        empty: false,
        nextPage: null,
        loadMore: false,
        error: false,
        errorMessage: "",
      },

      // Raw data and meta properties
      data: {},

      // Another custom data set by user
      meta: meta,

      // Function to initialize the page
      init() {
        // Auto-detect data URL from current route if not provided.
        // Urutan sumber URL (paling akurat dulu):
        //  1) Elemen halaman itu sendiri (el.f7Page.route.url) — per-element,
        //     tidak terpengaruh currentRoute global yang bisa STALE saat halaman
        //     di-init ulang (mis. back-nav ke initial page F7: halaman awal
        //     dihapus lalu dibuat ulang dari cache DOM).
        //  2) Framework7 router.currentRoute (URL browser belum tentu ter-update
        //     saat pageInit).
        //  3) Pinecone Router context.path.
        //  4) Fallback terakhir window.location.pathname.
        if (!this.config.url) {
          const pageEl = this.$el && this.$el.closest ? this.$el.closest('.page') : null;
          const pageUrl = pageEl && pageEl.f7Page && pageEl.f7Page.route ? pageEl.f7Page.route.url : null;
          const f7Url = pageUrl || window.f7app?.views?.main?.router?.currentRoute?.url;
          const pineconePath = this.$router?.context?.path;
          const pathname = f7Url || pineconePath || window.location.pathname;
          const pagePath = pathname === '/' ? 'home' : pathname.replace(/^\/|\/$/g, '');
          this.config.url = pagePath + '/data';
        }

        // Use SSR data injected by server on first load (hydration).
        // Validate the URL to prevent stale SSR data from a different page
        // contaminating this page (e.g. custom page functions that don't consume it).
        if (window.__HEROIC_SSR_DATA__) {
          if (window.__HEROIC_SSR_URL__ === this.config.url) {
            this.data = window.__HEROIC_SSR_DATA__;
            $sayagi.setCache(this.config.url, this.data);
          }
          // Always clear — prevents bleed-through across navigations
          window.__HEROIC_SSR_DATA__ = null;
          window.__HEROIC_SSR_URL__ = null;
        }

        // Set the page title
        this._setTitle();

        if (this.config.clearCachePath) {
          // Clear cached data
          $sayagi.clearCache(this.config.clearCachePath);
        }

        // Skip network fetch when data was already hydrated from SSR
        if (!this.data || Object.keys(this.data).length === 0) {
          this.loadPage();
        }
      },

      loadPage(fetchUrl = null) {
        // Prevent same url fetch many times if already loaded
        if (fetchUrl == this.config.url) return;

        this.config.url = fetchUrl ?? this.config.url;

        // Initialize page data if requested
        if (this.config.url) {
          // Use cached data if exists
          if ($sayagi.getCache(this.config.url)) {
            this.data = $sayagi.getCache(this.config.url);
          } else {
            this.fetchData();
          }
        }
        window.scrollTo(0, 0);
      },

      fetchData() {
        this.ui.loading = true;
        $sayagi
          .fetch(this.config.url, this.config.headers)
          .then((response) => {
            if (response.status == 200) {
              this.assignResponseData(response);
            } else {
              this.ui.error = true;
              this.ui.errorMessage = response.message;
            }
          })
          .catch((error) => {
            this.ui.error = true;
            console.error("Error fetching page data:", error);
          })
          .finally(() => {
            this.ui.loading = false;
          });
      },

      assignResponseData(response, cache = true) {
        this.data = response.data;

        if (cache) $sayagi.setCache(this.config.url, this.data);
      },

      _setTitle() {
        if (this.config.title) {
          document.title = this.config.title;
        }
      },
    };
  };

  /**************************************************************************
   * Helper Functions
   **************************************************************************/

  // COOKIE SETTER GETTER
  $sayagi.setCookie = function (name, value, days) {
    let expires = "";
    if (days) {
      let date = new Date();
      date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
      expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
  };

  $sayagi.getCookie = function (name) {
    let nameEQ = name + "=";
    let ca = document.cookie.split(";");
    for (let i = 0; i < ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) == " ") c = c.substring(1, c.length);
      if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
  };

  $sayagi.nl2br = function (str, is_xhtml) {
    if (typeof str === "undefined" || str === null) {
      return "";
    }
    var breakTag =
      is_xhtml || typeof is_xhtml === "undefined" ? "<br />" : "<br>";
    return (str + "").replace(
      /([^>\r\n]?)(\r\n|\n\r|\r|\n)/g,
      "$1" + breakTag + "$2",
    );
  };

  $sayagi.formatDate = function (dateString) {
    if (dateString && dateString != "0000-00-00") {
      const date = new Date(dateString);
      const options = { day: "numeric", month: "long", year: "numeric" };
      return new Intl.DateTimeFormat("id-ID", options).format(date);
    }
    return "";
  };

  $sayagi.currency = function (amount) {
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  };

  // Load dependencies di background (async)
  (async function loadDependencies() {
    const dependencies = [
      {
        name: "Toastify",
        check: () => typeof window.Toastify !== "undefined",
        url: "https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js",
        css: "https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css",
      },
    ];

    for (const dep of dependencies) {
      if (!dep.check()) {
        console.log(`Loading ${dep.name}...`);

        if (dep.css) {
          const link = document.createElement("link");
          link.rel = "stylesheet";
          link.href = dep.css;
          document.head.appendChild(link);
        }

        await new Promise((resolve, reject) => {
          const script = document.createElement("script");
          script.src = dep.url;
          script.onload = resolve;
          script.onerror = reject;
          document.head.appendChild(script);
        });
      }
    }
  })();

  // ==================== // ==================== // ==================== //
  // Function helper untuk memuat dan memasang komponen
  // ==================== // ==================== // ==================== //
  const templateCache = {};
  $sayagi.loadComponent = async function (element, path) {
    let html;

    // 1. Cek apakah template sudah ada di cache
    if (templateCache[path]) {
      html = templateCache[path];
    } else {
      // 2. Jika belum ada, fetch dari server
      const res = await fetch(path);
      html = await res.text();
      // Simpan ke cache untuk penggunaan berikutnya
      templateCache[path] = html;
    }

    // 3. Proses "Props" (sama seperti sebelumnya)
    let processedHtml = html;
    for (let i = 0; i < element.attributes.length; i++) {
      const attr = element.attributes[i];
      const placeholder = new RegExp(`{{${attr.name}}}`, "g");
      processedHtml = processedHtml.replace(placeholder, attr.value);
    }

    // 4. Ganti placeholder yang tersisa dengan empty string
    processedHtml = processedHtml.replace(/\{\{[^}]+\}\}/g, "");

    element.innerHTML = processedHtml;

    // 5. Beritahu Alpine untuk memproses elemen baru ini
    if (window.Alpine) {
      window.Alpine.initTree(element);
    }
  };

  // Expose ke global scope untuk kompatibilitas
  window.loadComponent = $sayagi.loadComponent;
})();
