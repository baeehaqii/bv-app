<?php

namespace App\Filament\Resources\DataKols\Pages;

use App\Filament\Resources\DataKols\DataKolResource;
use App\Filament\Resources\DataKols\Schemas\DataKolForm;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;

class CreateDataKol extends CreateRecord
{
    protected static string $resource = DataKolResource::class;

    public function form(Schema $schema): Schema
    {
        return DataKolForm::configure($schema);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validation: Ensure critical fields have been auto-filled from API
        $requiredFields = ['username', 'followers', 'tier', 'engagement_rate'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                Notification::make()
                    ->danger()
                    ->title('Data belum ter-fetch')
                    ->body("Silahkan tunggu hingga data dari {$data['channel']} selesai di-fetch, kemudian coba lagi.")
                    ->send();

                throw new \Exception("Field {$field} harus ter-fill dari API. Pastikan Anda sudah mengisi link_userprofile dan menunggu notifikasi sukses.");
            }
        }

        // Ensure all required fields have default values if not set
        $data['status'] = $data['status'] ?? 'New List';
        $data['terakhir_update'] = $data['terakhir_update'] ?? now()->format('Y-m-d');

        return $data;
    }
}
