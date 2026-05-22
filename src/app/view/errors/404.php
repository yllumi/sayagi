<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Halaman Tidak Ditemukan</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --s50:  #f8f9fa;
            --s100: #f1f3f5;
            --s200: #e9ecef;
            --s300: #dee2e6;
            --s400: #ced4da;
            --s500: #adb5bd;
            --s600: #868e96;
            --s700: #495057;
            --s900: #212529;
            --accent: #5c6bc0;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(ellipse 80% 50% at 50% -10%, rgba(193,199,210,.55) 0%, transparent 70%),
                var(--s100);
            color: var(--s900);
            padding: 2rem;
        }

        .container {
            text-align: center;
            max-width: 480px;
        }

        /* ── Big 404 number ── */
        .code-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .code-bg {
            font-size: 10rem;
            font-weight: 800;
            letter-spacing: -6px;
            line-height: 1;
            background: linear-gradient(160deg, var(--s300) 0%, var(--s400) 50%, var(--s500) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            user-select: none;
        }

        .code-icon {
            position: absolute;
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--s200), var(--s300));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow:
                0 4px 16px rgba(0,0,0,.10),
                inset 0 1px 0 rgba(255,255,255,.7);
            font-size: 1.5rem;
            color: var(--s600);
        }

        /* ── Text ── */
        h1 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--s900);
            letter-spacing: -.3px;
            margin-bottom: .5rem;
        }

        p {
            font-size: .93rem;
            color: var(--s600);
            line-height: 1.65;
            margin-bottom: 2rem;
        }

        /* ── Divider ── */
        .divider {
            width: 40px;
            height: 3px;
            border-radius: 99px;
            background: linear-gradient(90deg, var(--s300), var(--s400));
            margin: 1rem auto 1.5rem;
        }

        /* ── Buttons ── */
        .actions {
            display: flex;
            gap: .75rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .65rem 1.4rem;
            font-family: inherit;
            font-size: .88rem;
            font-weight: 600;
            border-radius: 10px;
            text-decoration: none;
            cursor: pointer;
            transition: transform .15s, box-shadow .2s, filter .15s;
            border: none;
        }

        .btn:hover { transform: translateY(-2px); }
        .btn:active { transform: translateY(0); }

        .btn-primary {
            background: linear-gradient(135deg, #5c6bc0, #3f51b5);
            color: #fff;
            box-shadow: 0 4px 14px rgba(63,81,181,.3);
        }

        .btn-primary:hover {
            filter: brightness(1.08);
            box-shadow: 0 6px 20px rgba(63,81,181,.4);
        }

        .btn-secondary {
            background: var(--s200);
            color: var(--s700);
            box-shadow: 0 1px 4px rgba(0,0,0,.07);
        }

        .btn-secondary:hover {
            background: var(--s300);
            box-shadow: 0 3px 10px rgba(0,0,0,.1);
        }

        /* ── Footer note ── */
        .footer-note {
            margin-top: 2.5rem;
            font-size: .78rem;
            color: var(--s500);
        }

        /* ── Responsive ── */
        @media (max-width: 400px) {
            .code-bg  { font-size: 7rem; }
            .code-icon { width: 50px; height: 50px; font-size: 1.2rem; }
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="code-wrap">
            <span class="code-bg">404</span>
            <div class="code-icon">
                <i class="bi bi-compass"></i>
            </div>
        </div>

        <h1>Halaman Tidak Ditemukan</h1>
        <div class="divider"></div>
        <p>Halaman yang Anda cari tidak ada, mungkin telah dipindahkan,<br>atau URL yang dimasukkan salah.</p>

        <div class="actions">
            <a href="<?= site_url('panel') ?>" class="btn btn-primary">
                <i class="bi bi-house-door"></i> Kembali ke Dashboard
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Halaman Sebelumnya
            </a>
        </div>

        <p class="footer-note">Error 404 &mdash; <?= date('Y') ?> Panel</p>
    </div>
</body>

</html>
