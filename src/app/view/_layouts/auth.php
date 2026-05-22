<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Login' ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>[x-cloak]{display:none!important}*, *::before, *::after{ box-sizing: border-box; margin: 0; padding: 0;} :root{ --silver-50: #f8f9fa; --silver-100: #f1f3f5; --silver-200: #e9ecef; --silver-300: #dee2e6; --silver-400: #ced4da; --silver-500: #adb5bd; --silver-600: #868e96; --silver-700: #495057; --silver-800: #343a40; --silver-900: #212529; --accent: #5c6bc0; --accent-dark: #3f51b5; --white: #ffffff; --shadow-sm: 0 1px 3px rgba(0, 0, 0, .08), 0 1px 2px rgba(0, 0, 0, .06); --shadow-md: 0 4px 16px rgba(0, 0, 0, .10), 0 2px 6px rgba(0, 0, 0, .07); --shadow-lg: 0 10px 40px rgba(0, 0, 0, .13), 0 4px 12px rgba(0, 0, 0, .08); --radius: 14px;} body{ font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; align-items: stretch; background: var(--silver-100); color: var(--silver-900);} .auth-left{ flex: 1; display: none; position: relative; overflow: hidden;} @media (min-width: 960px){ .auth-left{ display: flex;}} .auth-left-inner{ position: relative; z-index: 2; display: flex; flex-direction: column; justify-content: flex-end; padding: 3rem; width: 100%; height: 100%;} .auth-left::before{ content: ''; position: absolute; inset: 0; background: radial-gradient(ellipse 70% 60% at 20% 30%, rgba(255, 255, 255, .25) 0%, transparent 70%), radial-gradient(ellipse 50% 50% at 80% 80%, rgba(0, 0, 0, .12) 0%, transparent 70%); z-index: 1;} .auth-left::after{ content: ''; position: absolute; width: 420px; height: 420px; border-radius: 50%; border: 60px solid rgba(255, 255, 255, .12); top: -120px; right: -120px; z-index: 1;} .deco-circle{ position: absolute; border-radius: 50%; border: 40px solid rgba(255, 255, 255, .08); bottom: -80px; left: -80px; width: 320px; height: 320px; z-index: 1;} .auth-brand{ display: flex; align-items: center; gap: .65rem; position: absolute; top: 2.5rem; left: 3rem; z-index: 3;} .auth-brand-icon{ width: 38px; height: 38px; background: rgba(255, 255, 255, .25); border-radius: 10px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); font-size: 1.1rem; color: #fff;} .auth-brand-name{ font-weight: 700; font-size: 1.05rem; letter-spacing: .4px; color: #fff;} .auth-tagline h4{ font-size: 1rem; font-weight: 700; color: #fff; line-height: 1.3; margin-bottom: .75rem;} .auth-tagline p{ font-size: 1.2rem; font-style: italic; color: rgba(255, 255, 255, .78); margin-bottom: 1rem; line-height: 1.65;} .auth-features{ margin-top: 2rem; display: flex; flex-direction: column; gap: .6rem;} .auth-feature-item{ display: flex; align-items: center; gap: .6rem; font-size: .85rem; color: rgba(255, 255, 255, .82);} .auth-feature-item i{ width: 22px; height: 22px; background: rgba(255, 255, 255, .2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .7rem; flex-shrink: 0;} .auth-right{ width: 100%; max-width: 500px; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 2.5rem 2rem; background: var(--white); box-shadow: var(--shadow-lg); position: relative; z-index: 5;} @media (min-width: 960px){ .auth-right{ min-width: 440px;}} .auth-right-inner{ width: 100%; max-width: 380px;} .auth-header{ text-align: center; margin-bottom: 2rem;} .auth-logo{ display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; color: var(--silver-700); margin-bottom: 3rem;} .auth-header h1{ font-size: 1.5rem; font-weight: 700; color: var(--silver-900); letter-spacing: -.3px;} .auth-header p{ font-size: .88rem; color: var(--silver-600); margin-top: .35rem;} .form-group{ margin-bottom: 1.1rem;} .form-label{ display: block; font-size: .8rem; font-weight: 600; color: var(--silver-700); margin-bottom: .45rem; letter-spacing: .2px; text-transform: uppercase;} .input-wrapper{ position: relative;} .input-icon{ position: absolute; top: 50%; left: .9rem; transform: translateY(-50%); color: var(--silver-500); font-size: .95rem; pointer-events: none; transition: color .2s;} .form-control{ width: 100%; padding: .72rem 2.6rem .72rem 2.5rem; font-family: inherit; font-size: .92rem; color: var(--silver-900); background: var(--silver-50); border: 1.5px solid var(--silver-300); border-radius: 10px; outline: none; transition: border-color .2s, box-shadow .2s, background .2s;} .form-control::placeholder{ color: var(--silver-400);} .form-control:focus{ background: var(--white); border-color: var(--accent); box-shadow: 0 0 0 3.5px rgba(92, 107, 192, .13);} .form-control:focus~.input-icon, .input-wrapper:focus-within .input-icon{ color: var(--accent);} .toggle-password{ position: absolute; top: 50%; right: .85rem; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--silver-500); font-size: .95rem; padding: .2rem; transition: color .2s; display: flex;} .toggle-password:hover{ color: var(--silver-700);} .form-extras{ display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;} .form-check{ display: flex; align-items: center; gap: .45rem; cursor: pointer; user-select: none;} .form-check input[type="checkbox"]{ width: 15px; height: 15px; accent-color: var(--accent); cursor: pointer;} .form-check-label{ font-size: .83rem; color: var(--silver-700);} .forgot-link{ font-size: .83rem; color: var(--accent); text-decoration: none; font-weight: 500; transition: color .2s;} .forgot-link:hover{ color: var(--accent-dark); text-decoration: underline;} .btn-login{ width: 100%; padding: .82rem; font-family: inherit; font-size: .95rem; font-weight: 600; letter-spacing: .2px; color: var(--white); background: linear-gradient(135deg, #5c6bc0 0%, #3f51b5 100%); border: none; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: .5rem; transition: transform .15s, box-shadow .2s, filter .2s; box-shadow: 0 4px 14px rgba(63, 81, 181, .35);} .btn-login:hover{ filter: brightness(1.08); box-shadow: 0 6px 20px rgba(63, 81, 181, .45); transform: translateY(-1px);} .btn-login:active{ transform: translateY(0); box-shadow: none;} .btn-login:disabled{ opacity: .65; cursor: not-allowed; transform: none;} .btn-login .spinner{ display: none; width: 16px; height: 16px; border: 2.5px solid rgba(255, 255, 255, .4); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite;} @keyframes spin{ to{ transform: rotate(360deg);}} .auth-divider{ display: flex; align-items: center; gap: .75rem; margin: 1.4rem 0; color: var(--silver-400); font-size: .78rem; letter-spacing: .3px;} .auth-divider::before, .auth-divider::after{ content: ''; flex: 1; height: 1px; background: var(--silver-200);} .auth-alert{ display: none; padding: .75rem 1rem; border-radius: 9px; font-size: .85rem; margin-bottom: 1.1rem; align-items: center; gap: .55rem; border-left: 4px solid;} .auth-alert.error{ background: #fff5f5; color: #c62828; border-color: #e53935; display: flex;} .auth-alert.success{ background: #f1f8e9; color: #2e7d32; border-color: #4caf50; display: flex;} .auth-footer{ margin-top: 2rem; text-align: center; font-size: .82rem; color: var(--silver-500);} .auth-footer a{ color: var(--accent); text-decoration: none; font-weight: 500;} .auth-footer a:hover{ text-decoration: underline;} @media (max-width: 480px){ .auth-right{ padding: 2rem 1.25rem;}} </style>
</head>

<body>

    <!-- ── Left decorative panel ── -->
    <div class="auth-left" style="background: url('https://image.web.id/images/snapedit_1773581921854.jpg') no-repeat center/cover; position: relative;">
        <div style="position:absolute;top:0;left:0;width:100%;height:400px;background:linear-gradient(to bottom,rgba(52,58,64,0.9),rgba(52,58,64,0));z-index:2;"></div>
        <div style="position:absolute;bottom:0;left:0;width:100%;height:400px;background:linear-gradient(to top,rgba(52,58,64,0.9),rgba(52,58,64,0));z-index:2;"></div>
        <div class="deco-circle"></div>

        <div class="auth-left-inner">
            <div class="auth-tagline">
                <?php
                $quotes = [
                    [
                        "sentence" => "The only way to do great work is to love what you do.",
                        "author"   => "Steve Jobs"
                    ],
                    [
                        "sentence" => "In the middle of every difficulty lies opportunity.",
                        "author"   => "Albert Einstein"
                    ],
                    [
                        "sentence" => "It does not matter how slowly you go as long as you do not stop.",
                        "author"   => "Confucius"
                    ],
                    [
                        "sentence" => "The future belongs to those who believe in the beauty of their dreams.",
                        "author"   => "Eleanor Roosevelt"
                    ],
                    [
                        "sentence" => "Happiness is not something ready made. It comes from your own actions.",
                        "author"   => "Dalai Lama"
                    ],
                    [
                        "sentence" => "Everything you’ve ever wanted is on the other side of fear.",
                        "author"   => "George Addair"
                    ],
                    [
                        "sentence" => "Creativity takes courage.",
                        "author"   => "Henri Matisse"
                    ],
                    [
                        "sentence" => "Do what you can, with what you have, where you are.",
                        "author"   => "Theodore Roosevelt"
                    ],
                    [
                        "sentence" => "Believe you can and you're halfway there.",
                        "author"   => "Theodore Roosevelt"
                    ],
                    [
                        "sentence" => "An unexamined life is not worth living.",
                        "author"   => "Socrates"
                    ]
                ];
                $currentQuote = $quotes[array_rand($quotes)];
                echo '<p>&quot;' . $currentQuote['sentence'] . '&quot;</p>';
                echo '<h4>—' . $currentQuote['author'] . '</h4>';
                ?>
            </div>
        </div>
    </div>

    <!-- ── Right form panel ── -->
    <div class="auth-right">
        <div class="auth-right-inner">

            <?= $content ?>

        </div>
    </div>


</body>

</html>