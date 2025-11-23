<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LocalAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        // Get user initials
        $name = $record->name ?? 'User';
        $initials = Str::of($name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr(strtoupper($word), 0, 1))
            ->implode('');

        // Generate a color based on user ID/email for consistency
        $hash = hash('md5', $record->email ?? $record->id);

        // Awin Theme Color Palette - Purple & Neon Green
        $colors = [
            '#6600ff',  // primary-500 - bright purple
            '#48009f',  // primary-600 - medium purple (brand)
            '#3a007f',  // primary-700 - dark purple
            '#8533ff',  // primary-400 - light purple
            '#a366ff',  // primary-300 - lighter purple
        ];

        // Pick color based on hash
        $colorIndex = intval(substr($hash, 0, 6), 16) % count($colors);
        $bgColor = $colors[$colorIndex];

        // Create SVG with initials
        $svg = $this->generateInitialsSVG($initials, $bgColor);

        // Return as data URL
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    private function generateInitialsSVG(string $initials, string $bgColor): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">
    <rect width="64" height="64" rx="32" fill="$bgColor"/>
    <text x="32" y="36" font-family="Arial, sans-serif" font-size="28" font-weight="bold" 
          fill="white" text-anchor="middle" alignment-baseline="middle">$initials</text>
</svg>
SVG;
    }
}
