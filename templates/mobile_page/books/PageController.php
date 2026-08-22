<?php

declare(strict_types=1);

namespace app\pages\books;

use support\Request;
use Yllumi\Sayagi\attributes\FrontendRoute;
use app\pages\BaseController;

/**
 * Halaman katalog buku (LIST) — web mobile, root app/pages/, port 8779.
 * Detail buku dipisah ke app/pages/books/detail/ dengan endpoint publik
 * tetap /books/{id} (lihat config/route.php).
 *
 * SSR:  /books          -> getIndex (list)
 * CSR:  F7 async route memanggil:
 *       /books/template -> getTemplate (fragmen .page, via BaseController)
 *       /books/data     -> getData (list JSON)
 */
#[FrontendRoute(route: '/books/', template: '/books/template')]
class PageController extends BaseController
{
    public $data = [];

    public function getData(Request $request)
    {
        $books = self::catalog();

        $this->data = ['books' => $books, 'total' => count($books)];
        return json($this->data);
    }

    /**
     * Sampel data katalog (placeholder ujicoba).
     */
    private static function catalog(): array
    {
        return [
            [
                'id'          => 1,
                'title'       => 'Matematika untuk SD Kelas 1',
                'author'      => 'Tim Pusat Perbukuan',
                'category'    => 'Buku Teks',
                'year'        => 2023,
                'cover'       => 'https://picsum.photos/seed/buku1/120/160',
                'description' => 'Buku teks pelajaran Matematika untuk jenjang SD kelas 1 pada Kurikulum Merdeka.',
            ],
            [
                'id'          => 2,
                'title'       => 'Bahasa Indonesia Kelas 4',
                'author'      => 'Tim Pusat Perbukuan',
                'category'    => 'Buku Teks',
                'year'        => 2022,
                'cover'       => 'https://picsum.photos/seed/buku2/120/160',
                'description' => 'Buku teks Bahasa Indonesia untuk jenjang SD kelas 4 yang menekankan literasi dan keterampilan berbahasa.',
            ],
            [
                'id'          => 3,
                'title'       => 'IPA Terpadu SMP Kelas 7',
                'author'      => 'Kemendikbudristek',
                'category'    => 'Buku Teks',
                'year'        => 2023,
                'cover'       => 'https://picsum.photos/seed/buku3/120/160',
                'description' => 'Materi IPA terpadu untuk SMP kelas 7: makhluk hidup, energi, dan bumi antariksa.',
            ],
            [
                'id'          => 4,
                'title'       => 'Novel: Laut Bercerita',
                'author'      => 'Leila S. Chudori',
                'category'    => 'Buku Nonteks',
                'year'        => 2021,
                'cover'       => 'https://picsum.photos/seed/buku4/120/160',
                'description' => 'Sebuah karya sastra yang merekam perjuangan aktivis dan para korbannya.',
            ],
        ];
    }
}
