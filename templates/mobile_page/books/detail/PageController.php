<?php

declare(strict_types=1);

namespace app\pages\books\detail;

use support\Request;
use app\pages\BaseController;
use Yllumi\Sayagi\attributes\FrontendRoute;

/**
 * Halaman detail buku — web mobile, root app/pages/, port 8779.
 *
 * Folder terpisah: app/pages/books/detail/ namun endpoint publik TETAP
 * /books/{id} (di-register eksplisit di config/route.php, karena router
 * auto-discover hanya memetakan folder -> route, bukan parameter dinamis).
 *
 * SSR:  /books/{id}                 -> getIndex (deep-link / refresh)
 * CSR:  F7 async route /books/:id/ memanggil:
 *       /books/detail/template      -> getTemplate (fragmen .page, via BaseController)
 *       /books/detail/data?id=      -> getData (JSON detail)
 */
#[FrontendRoute(route: '/books/:id/', template: '/books/detail/template')]
class PageController extends BaseController
{
    public $data = [];

    public function getData(Request $request, $id = null)
    {
        // Router hanya mendeteksi method pada segmen URL terakhir,
        // jadi data detail dikirim via query param (?id=) dari loader F7.
        if ($id === null) {
            $id = $request->get('id');
        }

        $book = null;
        foreach (self::catalog() as $item) {
            if ((int) $item['id'] === (int) $id) {
                $book = $item;
                break;
            }
        }

        $this->data = ['book' => $book];
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
