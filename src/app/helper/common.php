<?php

/**
 * Here is your custom functions.
 */

if (!function_exists('sidebarMenus')) {
function sidebarMenus()
{
    $path = base_path() . '/config/plugin/panel/menu.yml';

    if (!file_exists($path)) {
        return [];
    }

    $menus = \Symfony\Component\Yaml\Yaml::parseFile($path) ?? [];

    $filtered = [];
    foreach ($menus as $menu) {
        // Filter children by privilege
        if (!empty($menu['children'])) {
            $menu['children'] = array_values(array_filter(
                $menu['children'],
                fn($child) => empty($child['privilege']) || isAllow($child['privilege'])
            ));
        }

        // Skip parent if it requires a privilege the user doesn't have
        if (!empty($menu['privilege']) && !isAllow($menu['privilege'])) {
            continue;
        }

        // Skip parent with children if no accessible children remain
        if (!empty($menu['children']) || $menu['url'] !== '#') {
            $filtered[] = $menu;
        } elseif (empty($menu['children']) && $menu['url'] === '#') {
            // parent-only container with no visible children — skip
            continue;
        }
    }

    return $filtered;
}
}

if (!function_exists('site_url')) {
    function site_url(string $path = ''): string
    {
        $prefix = env('APP_BASE_PATH', ''); // isi '/p' di .env
        return $prefix . '/' . ltrim($path, '/');
    }
}

if (!function_exists('setting')) {
    /**
     * Retrieve a setting value from cache.
     * Usage: setting('site.site_title')  →  returns the value or $default.
     * Falls back to the default value defined in the YAML config file if
     * the setting has never been saved to the database.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        $dot = strpos($key, '.');
        if ($dot === false) return $default;

        $group  = substr($key, 0, $dot);
        $name   = substr($key, $dot + 1);

        $fields = \Yllumi\Sayagi\app\controller\SettingController::getGroupFromCache($group);

        // Return DB/cache value if it exists
        if (array_key_exists($name, $fields)) {
            return $fields[$name];
        }

        // Fallback: read default from YAML config
        $yamlFile = base_path('config/plugin/panel/settings/' . $group . '.yml');
        if (file_exists($yamlFile)) {
            $cfg = \Symfony\Component\Yaml\Yaml::parseFile($yamlFile);
            if (isset($cfg['setting'][$name]['default'])) {
                return (string) $cfg['setting'][$name]['default'];
            }
        }

        return $default;
    }
}

/**
 * Helper functions for templating and asset management.
 * These are globally available in all views.
 */
// Include a partial view
// View file must be located in app/pages folder 
if (!function_exists('partial')) {
    function partial($view, $data = [])
    {
        extract($data);
        include base_path() . '/app/pages/' . str_replace('.', '/', $view) . '.php';
    }
}

// Render a page view and return its content
if (!function_exists('pageView')) {
    function pageView($view, $data = [])
    {
        extract($data);
        ob_start();
        include base_path() . '/app/pages/' . str_replace('.', '/', $view) . '.php';
        return ob_get_clean();
    }
}

// Generate asset URL with versioning based on file modification time
if (!function_exists('asset_url')) {
function asset_url(string $filePath): string
{
    // Full file path
    $fullFilePath = './public/' . $filePath;

    // Check if file exists
    $version = file_exists($fullFilePath)
        // Add file modification time as version
        ? filemtime($fullFilePath)
        // Fallback version (current timestamp if file doesn't exist)
        : time();

    // Generate full URL with version
    return $filePath . '?v=' . $version;
}
}

if (!function_exists('base_url')) {
    function base_url($path = '') {
        return config('app.url') . ltrim($path, '/');
    }
}

// Dump data in browser friendly way for webman
if (!function_exists('ddb')) {
    function ddb(...$vars)
    {
        $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner();
        $dumper = new \Symfony\Component\VarDumper\Dumper\HtmlDumper();
        $output = fopen('php://memory', 'r+b');

        // If only one parameter, dump it directly instead of as an array
        $data = count($vars) === 1 ? $vars[0] : $vars;

        $dumper->dump($cloner->cloneVar($data), $output);
        rewind($output);
        $result = stream_get_contents($output);
        return response($result);
    }
}

// Collection to array helper
if (!function_exists('to_assoc')) {
    function to_assoc($collection)
    {
        return array_map(function ($item) {
            return (array) $item;
        }, is_array($collection) ? $collection : $collection->all());
    }
}

/**
 * Here is your custom functions.
 */

// ── Compat helpers for FormBuilder (ported from CI4) ─────────────────────
if (! function_exists('esc')) {
    function esc($data, string $context = 'html'): string
    {
        return htmlspecialchars((string) $data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (! function_exists('old')) {
    function old(string $key, mixed $default = null): mixed
    {
        return $default; // No CI4 flash session in webman; always return default
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '')
    {
        return '/panel/' . ltrim($path, '/');
    }
}

function cell($view, $data = [], $plugin = null)
{
    if ($plugin === null) {
        // Auto-detect base view path from caller's namespace (local or vendor)
        $basePath = _detect_plugin_from_backtrace() ?? base_path() . '/app/view/';
    } else {
        $basePath = _plugin_to_view_path($plugin);
    }

    ob_start();
    extract($data);
    include $basePath . str_replace('.', '/', $view) . '.php';
    return ob_get_clean();
}

function render($view, $data = [], $plugin = null, $layout = 'admin')
{
    $content = cell($view, $data, $plugin);
    
    if ($plugin === null) {
        // Auto-detect once and pass explicitly so nested calls don't re-detect
        $basePath = _detect_plugin_from_backtrace();
    } else {
        $basePath = _plugin_to_view_path($plugin);
    }

    if ($basePath) {
        // Prefer plugin-local layout, fallback to panel layout
        $layoutFile = $basePath . '_layouts/' . $layout . '.php';
        if (! file_exists($layoutFile)) {
            $layoutFile = base_path() . '/vendor/yllumi/sayagi/src/app/view/_layouts/' . $layout . '.php';
        }
    } else {
        $layoutFile = base_path() . '/app/view/_layouts/' . $layout . '.php';
    }

    ob_start();
    extract(array_merge($data, ['content' => $content]));
    include $layoutFile;
    $html = ob_get_clean();

    return response($html);
}

/**
 * Helper function for privilege checking.
 */
// $privilege feature.privilege, $whiteListID optional untuk memasukkan user_id yang boleh diloloskan
function isAllow($privilege, $whiteListIDs = [])
{
    // Check if user is in the white list
    $userId = session('user')['user_id'] ?? null;
    if ($userId && in_array($userId, $whiteListIDs)) {
        return true;
    }

    $role_id = session('user')['role_id'] ?? null;
    // Allow all privileges for role_id 1 (Super)
    if ($role_id == 1) {
        return true;
    }

    // Breakdown feature and privilege
    [$feature, $action] = explode('.', $privilege);
    
    // Check if user has the required privilege feature
    $userPrivileges = rolePrivileges($role_id ?? 0);
    if (isset($userPrivileges[$feature]) && in_array($action, $userPrivileges[$feature])) {
        return true;
    }

    return false;
}

function rolePrivileges($roleId)
{
    // Check from cache first
    $cacheKey = "role_privileges:{$roleId}";
    $cached = \support\Cache::get($cacheKey);
    if ($cached !== null) {
        return $cached;
    }

    $privileges = support\Db::table('mein_role_privileges')
        ->select('feature', 'privilege')
        ->where('role_id', $roleId)
        ->get()
        ->toArray();
    $privArray = [];
    foreach ($privileges as $priv) {
        $privArray[$priv->feature][] = $priv->privilege;
    }

    // Cache the result for 1 hour
    \support\Cache::set($cacheKey, $privArray, 3600);

    return $privArray;
}

/**
 * Walk up the call stack to find the first frame whose class originates from
 * a plugin — either a local plugin (plugin\name\) or a vendor package.
 * Returns the absolute base view path (e.g. /path/to/.../app/view/), or null.
 */
function _detect_plugin_from_backtrace(): ?string
{
    $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);

    // First pass: local plugins via plugin\{name}\ namespace convention
    foreach ($frames as $frame) {
        $class = $frame['class'] ?? '';
        if (preg_match('/^plugin\\\\([^\\\\]+)\\\\/', $class, $m)) {
            return base_path() . '/plugin/' . $m[1] . '/app/view/';
        }
    }

    // Second pass: vendor packages — resolve via Composer PSR-4 classmap
    $loader    = require base_path() . '/vendor/autoload.php';
    $prefixMap = $loader->getPrefixesPsr4();

    foreach ($frames as $frame) {
        $class = $frame['class'] ?? '';
        if (! $class || ! str_contains($class, '\\')) {
            continue;
        }

        $bestLen      = 0;
        $bestViewPath = null;

        foreach ($prefixMap as $prefix => $paths) {
            if (
                str_starts_with($class . '\\', $prefix)
                && strlen($prefix) > $bestLen
            ) {
                $candidate = rtrim($paths[0], '/') . '/app/view/';
                if (is_dir($candidate)) {
                    $bestLen      = strlen($prefix);
                    $bestViewPath = $candidate;
                }
            }
        }

        if ($bestViewPath !== null) {
            return $bestViewPath;
        }
    }

    return null;
}

/**
 * Resolve an explicit plugin name to its absolute base view path.
 * Checks local plugin/ directory first, then vendor packages via Composer PSR-4.
 *
 * $plugin can be:
 *   - a local plugin name: "panel", "mahasiswa"
 *   - a vendor/package name: "yllumi/test"
 */
function _plugin_to_view_path(string $plugin): string
{
    // 1. Local plugin directory (single-word names like "panel", "mahasiswa")
    $localPath = base_path() . '/plugin/' . $plugin . '/app/view/';
    if (is_dir($localPath)) {
        return $localPath;
    }

    // 2. Convert vendor/package → expected PSR-4 prefix, e.g. "yllumi/test" → "Yllumi\Test\"
    $expectedPrefix = implode('\\', array_map('ucfirst', explode('/', $plugin))) . '\\';

    $loader = require base_path() . '/vendor/autoload.php';
    foreach ($loader->getPrefixesPsr4() as $prefix => $paths) {
        // Match exact prefix or a prefix that starts with our expected namespace
        if (stripos($prefix, $expectedPrefix) === 0 || stripos($expectedPrefix, $prefix) === 0) {
            $candidate = rtrim($paths[0], '/') . '/app/view/';
            if (is_dir($candidate)) {
                return $candidate;
            }
        }
    }

    // Fallback: return local path even if directory not yet created
    return $localPath;
}