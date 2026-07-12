<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Samakan nilai channel SOW lama (dibuat via form dengan key lowercase)
// ke vokabulari kanonik DataKolForm::$channelOptions, supaya byChannel()
// di rate card KOL bisa menemukannya.
return new class extends Migration
{
    private array $map = [
        'instagram' => 'Instagram',
        'tiktok'    => 'Tiktok',
        'youtube'   => 'Youtube Channels',
        'twitter'   => 'X',
        'threads'   => 'Threads',
        'facebook'  => 'Facebook',
        'other'     => null,
    ];

    public function up(): void
    {
        foreach ($this->map as $old => $new) {
            DB::table('master_sows')->where('channel', $old)->update(['channel' => $new]);
        }
    }

    public function down(): void
    {
        foreach ($this->map as $old => $new) {
            if ($new === null) {
                continue;
            }
            DB::table('master_sows')->where('channel', $new)->update(['channel' => $old]);
        }
    }
};
