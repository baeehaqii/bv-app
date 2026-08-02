<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Service scraping (Instagram/Tiktok/Youtube/Threads/PostCommentsFetcher)
         * melempar exception di constructor bila key kosong. Mesin CI tidak punya
         * .env, jadi tanpa ini seluruh test-nya gagal di sana padahal lolos lokal.
         *
         * Di-set lewat config(), BUKAN <env> di phpunit.xml: env() membaca
         * $_SERVER lebih dulu daripada $_ENV, jadi nilai yang sudah ada di shell
         * bisa menang atas setelan phpunit — config() tidak punya ambiguitas itu.
         *
         * Nilainya sengaja dummy dan menimpa key asli: semua test memakai
         * Http::fake(), dan tidak boleh ada satu pun panggilan API sungguhan
         * (setiap panggilan memakan kredit berbayar).
         */
        config(['services.scrapecreators.api_key' => 'test-key-tidak-dipakai']);
    }
}
