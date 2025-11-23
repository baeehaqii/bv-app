<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LaraZeus\Boredom\BoringAvatarPlugin;
use LaraZeus\Boredom\Enums\Variants;

class BoringAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        // Get configuration from plugin
        $size = BoringAvatarPlugin::get()->getSize() ?? 64;
        $colors = BoringAvatarPlugin::get()->getColors() ?? ['#45B39D', '#F1948A', '#FDAC4B', '#0E0239', '#FFF9F5'];
        $variant = BoringAvatarPlugin::get()->getVariant() ?? Variants::BEAM;
        $isSquare = BoringAvatarPlugin::get()->isSquare() ?? false;

        // Convert colors array to string (remove # for API)
        $colorsString = collect($colors)
            ->map(function ($color) {
                if (Str::startsWith($color, '#')) {
                    return str_replace('#', '', $color);
                }
                return $color;
            })
            ->implode(',');

        // Get name/email identifier
        $name = $record->email ?? $record->name;
        $variant = $variant->value;

        // Build URL with query parameters (http_build_query handles encoding)
        $params = [
            'name' => $name,
            'variant' => $variant,
            'size' => $size,
            'colors' => $colorsString,
        ];

        if ($isSquare) {
            $params['square'] = 'true';
        }

        $url = 'https://source.boringavatars.com/?' . http_build_query($params);
        return $url;
    }
}
