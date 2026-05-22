<?php

namespace Yllumi\Sayagi\attributes;

use Attribute;

/**
 * Pasang di atas method controller untuk mewajibkan privilege tertentu.
 * Attribute ini bisa diulang untuk mensyaratkan beberapa privilege sekaligus
 * (semua harus terpenuhi).
 *
 * Contoh penggunaan:
 *
 **  Satu privilege
 *   #[RequirePrivilege('dashboard.read')]
 *   public function index(Request $request) { ... }
 *
 **  Beberapa privilege (AND) - tumpuk attribute-nya
 *   #[RequirePrivilege('user.read')]
 *   #[RequirePrivilege('user.manage')]
 *   public function adminIndex(Request $request) { ... }
 *
 **  Dengan whitelist user_id yang selalu diloloskan
 *   #[RequirePrivilege('report.export', whitelistIds: [1, 2])]
 *   public function export(Request $request) { ... }
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class RequirePrivilege
{
    public function __construct(
        public readonly string $privilege,
        public readonly array  $whitelistIds = [],
    ) {}
}
