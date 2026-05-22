<?php

namespace Yllumi\Sayagi\app\controller;

use support\Request;
use support\Redis;

class RedisController extends AdminController
{
    protected $data = [
        'page_title' => '',
        'module'     => 'development',
        'submodule'  => 'redis',
    ];

    // ── GET /panel/redis/index ────────────────────────────────
    public function index(Request $request)
    {
        $this->data['page_title'] = 'Redis Management';
        return render('redis/index', $this->data);
    }

    // ── GET /panel/redis/keys?pattern=* ───────────────────────
    public function keys(Request $request)
    {
        $pattern = trim($request->input('pattern', '*')) ?: '*';
        $db      = (int) $request->input('db', 0);
        try {
            $this->switchDb($db);
            $keys = Redis::keys($pattern);
            sort($keys);
            return json(['success' => 1, 'keys' => $keys, 'count' => count($keys)]);
        } catch (\Throwable $e) {
            return json(['success' => 0, 'message' => $e->getMessage()]);
        }
    }

    // ── GET /panel/redis/get?key=xxx ──────────────────────────
    public function getKey(Request $request)
    {
        $key = trim($request->input('key', ''));
        $db  = (int) $request->input('db', 0);
        if ($key === '') return json(['success' => 0, 'message' => 'Key tidak boleh kosong.']);

        try {
            $this->switchDb($db);
            if (!Redis::exists($key)) {
                return json(['success' => 0, 'message' => 'Key tidak ditemukan.']);
            }
            $type  = $this->resolveType($key);
            $ttl   = Redis::ttl($key);
            $value = $this->getValue($key, $type);
            return json(['success' => 1, 'key' => $key, 'type' => $type, 'ttl' => $ttl, 'value' => $value]);
        } catch (\Throwable $e) {
            return json(['success' => 0, 'message' => $e->getMessage()]);
        }
    }

    // ── POST /panel/redis/set  body: { key, type, value, ttl } ─
    public function setKey(Request $request)
    {
        $raw   = json_decode($request->rawBody(), true) ?: $request->post();
        $key   = trim($raw['key'] ?? '');
        $type  = $raw['type'] ?? 'string';
        $value = $raw['value'] ?? '';
        $ttl   = isset($raw['ttl']) && (int)$raw['ttl'] > 0 ? (int)$raw['ttl'] : null;
        $db    = isset($raw['db']) ? (int)$raw['db'] : 0;

        if ($key === '') return json(['success' => 0, 'message' => 'Key tidak boleh kosong.']);

        try {
            $this->switchDb($db);
            // Delete existing to allow type changes
            if (Redis::exists($key)) Redis::del($key);

            $this->setValue($key, $type, $value);

            if ($ttl !== null) Redis::expire($key, $ttl);

            return json(['success' => 1, 'message' => 'Key berhasil disimpan.']);
        } catch (\Throwable $e) {
            return json(['success' => 0, 'message' => $e->getMessage()]);
        }
    }

    // ── POST /panel/redis/delete  body: { key } ───────────────
    public function deleteKey(Request $request)
    {
        $raw = json_decode($request->rawBody(), true) ?: $request->post();
        $key = trim($raw['key'] ?? '');
        $db  = isset($raw['db']) ? (int)$raw['db'] : 0;
        if ($key === '') return json(['success' => 0, 'message' => 'Key tidak boleh kosong.']);

        try {
            $this->switchDb($db);
            Redis::del($key);
            return json(['success' => 1, 'message' => 'Key berhasil dihapus.']);
        } catch (\Throwable $e) {
            return json(['success' => 0, 'message' => $e->getMessage()]);
        }
    }

    // ── POST /panel/redis/rename  body: { old_key, new_key } ──
    public function renameKey(Request $request)
    {
        $raw    = json_decode($request->rawBody(), true) ?: $request->post();
        $oldKey = trim($raw['old_key'] ?? '');
        $newKey = trim($raw['new_key'] ?? '');
        $db     = isset($raw['db']) ? (int)$raw['db'] : 0;
        if ($oldKey === '' || $newKey === '') return json(['success' => 0, 'message' => 'Key tidak boleh kosong.']);

        try {
            $this->switchDb($db);
            Redis::rename($oldKey, $newKey);
            return json(['success' => 1, 'message' => 'Key berhasil diubah namanya.']);
        } catch (\Throwable $e) {
            return json(['success' => 0, 'message' => $e->getMessage()]);
        }
    }

    // ── POST /panel/redis/flush ───────────────────────────────
    public function flush(Request $request)
    {
        $raw = json_decode($request->rawBody(), true) ?: $request->post();
        $db  = isset($raw['db']) ? (int)$raw['db'] : 0;
        try {
            $this->switchDb($db);
            Redis::flushDB();
            return json(['success' => 1, 'message' => 'Database Redis berhasil dikosongkan.']);
        } catch (\Throwable $e) {
            return json(['success' => 0, 'message' => $e->getMessage()]);
        }
    }

    // ── Private helpers ───────────────────────────────────────────

    private function switchDb(int $db): void
    {
        $db = max(0, min(15, $db));
        Redis::select($db);
    }

    private function resolveType(string $key): string
    {
        $type = Redis::type($key);
        // phpredis returns int; predis/Illuminate may return string or Status object
        if (is_int($type)) {
            return [1 => 'string', 2 => 'set', 3 => 'list', 4 => 'zset', 5 => 'hash', 6 => 'stream'][$type] ?? 'unknown';
        }
        return strtolower((string) $type);
    }

    private function getValue(string $key, string $type): mixed
    {
        return match ($type) {
            'string' => Redis::get($key),
            'hash'   => Redis::hGetAll($key),
            'list'   => Redis::lRange($key, 0, -1),
            'set'    => Redis::sMembers($key),
            'zset'   => Redis::zRange($key, 0, -1, true),   // phpredis bool = WITHSCORES
            default  => null,
        };
    }

    private function setValue(string $key, string $type, mixed $value): void
    {
        switch ($type) {
            case 'string':
                Redis::set($key, is_array($value) ? json_encode($value) : (string) $value);
                break;

            case 'hash':
                // value = [ {field, val}, ... ]
                if (is_array($value)) {
                    foreach ($value as $row) {
                        if (isset($row['field']) && $row['field'] !== '') {
                            Redis::hSet($key, $row['field'], $row['val'] ?? '');
                        }
                    }
                }
                break;

            case 'list':
                // value = ['item1', 'item2', ...]
                if (is_array($value)) {
                    foreach (array_filter($value, fn($v) => $v !== '') as $item) {
                        Redis::rPush($key, $item);
                    }
                }
                break;

            case 'set':
                // value = ['item1', 'item2', ...]
                if (is_array($value)) {
                    foreach (array_filter($value, fn($v) => $v !== '') as $item) {
                        Redis::sAdd($key, $item);
                    }
                }
                break;

            case 'zset':
                // value = [ {member, score}, ... ]
                if (is_array($value)) {
                    foreach ($value as $row) {
                        if (isset($row['member']) && $row['member'] !== '') {
                            Redis::zAdd($key, (float) ($row['score'] ?? 0), $row['member']);
                        }
                    }
                }
                break;
        }
    }
}

