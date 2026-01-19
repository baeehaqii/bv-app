<?php

namespace App\Service;

use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Google\Service\Sheets\Spreadsheet;
use Google\Service\Sheets\SpreadsheetProperties;
use Google\Service\Sheets\ValueRange;
use Google\Service\Sheets\Request;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\CellData;
use Google\Service\Sheets\CellFormat;
use Google\Service\Sheets\Color;
use Google\Service\Sheets\ExtendedValue;
use Google\Service\Sheets\GridRange;
use Google\Service\Sheets\RepeatCellRequest;
use Google\Service\Sheets\RowData;
use Google\Service\Sheets\TextFormat;
use Google\Service\Sheets\UpdateCellsRequest;
use Google\Service\Drive;
use App\Models\MediaPlan;
use Exception;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    protected GoogleClient $client;
    protected Sheets $sheetsService;
    protected Drive $driveService;

    public function __construct()
    {
        // Client initialization moved to methods that need it
    }

    /**
     * Get configured Google Client
     */
    public function getClient(): GoogleClient
    {
        $credentialsPath = storage_path('app/google/oauth-credentials.json');

        if (!file_exists($credentialsPath)) {
            throw new Exception('Google OAuth credentials not found. Please place oauth-credentials.json in storage/app/google/');
        }

        $client = new GoogleClient();
        $client->setAuthConfig($credentialsPath);
        $client->setScopes([
            Sheets::SPREADSHEETS,
            Drive::DRIVE_FILE,
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Set Redirect URI - MUST match exactly what's in Google Console
        $client->setRedirectUri(route('google.callback'));

        return $client;
    }

    /**
     * Create a new Google Spreadsheet from Media Plan data
     * 
     * @param MediaPlan $mediaPlan
     * @param array $accessToken Token from OAuth flow
     * @return array Returns spreadsheet info ['id' => string, 'url' => string]
     */
    public function createMediaPlanSpreadsheet(MediaPlan $mediaPlan, array $accessToken): array
    {
        // Initialize client with user token
        $this->client = $this->getClient();
        $this->client->setAccessToken($accessToken);

        // Check if token expired and refresh if possible
        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            } else {
                throw new Exception('Token expired and no refresh token available. Please login again.');
            }
        }

        $this->sheetsService = new Sheets($this->client);
        $this->driveService = new Drive($this->client);

        // Load relationships
        $mediaPlan->load([
            'kols' => function ($query) {
                $query->where('is_selected', true)
                    ->orderBy('row_number')
                    ->with(['dataKol', 'internalBudgetItems']);
            },
            'internalBudget.items'
        ]);

        // Generate spreadsheet title
        $title = 'MediaPlan_' . $mediaPlan->quotation_number . '_' . now()->format('Ymd_His');

        // Create new spreadsheet
        $spreadsheet = new Spreadsheet([
            'properties' => new SpreadsheetProperties([
                'title' => $title,
            ]),
        ]);

        $createdSpreadsheet = $this->sheetsService->spreadsheets->create($spreadsheet);
        $spreadsheetId = $createdSpreadsheet->getSpreadsheetId();

        Log::info('📊 Google Spreadsheet created (OAuth)', [
            'spreadsheetId' => $spreadsheetId,
            'title' => $title,
            'user' => auth()->id(),
        ]);

        // Add data
        $this->populateSpreadsheet($spreadsheetId, $mediaPlan);
        $this->applyStyles($spreadsheetId, count($mediaPlan->kols) + 1);

        $spreadsheetUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}";

        return [
            'id' => $spreadsheetId,
            'url' => $spreadsheetUrl,
            'title' => $title,
        ];
    }

    /**
     * Move spreadsheet to a specific folder
     */
    protected function moveToFolder(string $fileId, string $folderId): void
    {
        try {
            // Get current parents
            $file = $this->driveService->files->get($fileId, ['fields' => 'parents']);
            $previousParents = join(',', $file->parents ?? []);

            // Move to new folder
            $this->driveService->files->update($fileId, new Drive\DriveFile(), [
                'addParents' => $folderId,
                'removeParents' => $previousParents,
                'fields' => 'id, parents',
            ]);

            Log::info('📁 Spreadsheet moved to folder', [
                'fileId' => $fileId,
                'folderId' => $folderId,
            ]);
        } catch (Exception $e) {
            Log::warning('Failed to move spreadsheet to folder', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Populate spreadsheet with Media Plan data
     */
    protected function populateSpreadsheet(string $spreadsheetId, MediaPlan $mediaPlan): void
    {
        $rows = [];

        // Header row
        $rows[] = [
            'No',
            'Domisili',
            'Username',
            'Link',
            'Channel',
            'Categories',
            'Followers',
            'Tier',
            'ER %',
            'Avg Views',
            'Engagement',
            'CPI/CPV',
            'CPE',
            'Scope of Work',
            'Rate',
            'Notes',
        ];

        // Data rows
        $rowNumber = 1;
        foreach ($mediaPlan->kols as $kol) {
            $budgetItems = $kol->internalBudgetItems ?? collect([]);
            $links = is_array($kol->links) ? $kol->links : [];
            $category = $kol->categories ?? $kol->dataKol?->category ?? '-';
            $domisili = $kol->domisili ?? '-';

            if ($budgetItems->count() > 0) {
                foreach ($budgetItems as $itemIndex => $budgetItem) {
                    if ($itemIndex === 0) {
                        $rows[] = [
                            $rowNumber,
                            $domisili,
                            $kol->name ?? '-',
                            $links[$itemIndex] ?? ($links[0] ?? '-'),
                            $kol->channel ?? '-',
                            $category,
                            $kol->followers ?? 0,
                            $kol->tier ?? '-',
                            ($kol->er_percent ?? 0) . '%',
                            $kol->impression ?? 0,
                            $kol->engagement ?? 0,
                            $kol->cpi_cpv ?? 0,
                            $kol->cpe ?? 0,
                            ($budgetItem->qty ?? 1) . 'x ' . ($budgetItem->scope_item ?? '-'),
                            $budgetItem->rounded ?? 0,
                            $kol->notes ?? '',
                        ];
                    } else {
                        $rows[] = [
                            '',
                            '',
                            '',
                            $links[$itemIndex] ?? '-',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            ($budgetItem->qty ?? 1) . 'x ' . ($budgetItem->scope_item ?? '-'),
                            $budgetItem->rounded ?? 0,
                            '',
                        ];
                    }
                }
            } else {
                $fallbackScopeItems = is_array($kol->scope_items) ? $kol->scope_items : [];
                $rows[] = [
                    $rowNumber,
                    $domisili,
                    $kol->name ?? '-',
                    $links[0] ?? '-',
                    $kol->channel ?? '-',
                    $category,
                    $kol->followers ?? 0,
                    $kol->tier ?? '-',
                    ($kol->er_percent ?? 0) . '%',
                    $kol->impression ?? 0,
                    $kol->engagement ?? 0,
                    $kol->cpi_cpv ?? 0,
                    $kol->cpe ?? 0,
                    count($fallbackScopeItems) > 0 ? implode(', ', $fallbackScopeItems) : '-',
                    $kol->rate ?? 0,
                    $kol->notes ?? '',
                ];
            }

            $rowNumber++;
        }

        // Add empty row before summary
        $rows[] = [''];

        // Summary section
        $totalFollowers = $mediaPlan->kols->sum('followers');
        $totalImpressions = $mediaPlan->kols->sum('impression');
        $totalEngagement = $mediaPlan->kols->sum('engagement');
        $kolCount = $mediaPlan->kols->count();

        $totalRate = 0;
        $totalScopeItems = 0;
        foreach ($mediaPlan->kols as $kol) {
            $kolBudgetItems = $kol->internalBudgetItems ?? collect([]);
            $totalRate += $kolBudgetItems->sum('rounded');
            $totalScopeItems += $kolBudgetItems->count();
        }

        if ($totalRate === 0) {
            $totalRate = $mediaPlan->kols->sum('rate');
        }

        $rows[] = ['SUMMARY'];
        $rows[] = ['Total KOLs', $kolCount . ' KOL(s)', '', 'Total Est. Views', $totalImpressions];
        $rows[] = ['Total Followers', $totalFollowers, '', 'Total Est. Engagement', $totalEngagement];
        $rows[] = ['Total Scope Items', $totalScopeItems . ' Item(s)', '', 'Total Rate', 'Rp ' . number_format($totalRate, 0, ',', '.')];
        $rows[] = [''];
        $rows[] = ['Campaign: ' . ($mediaPlan->campaign_name ?? '-'), '', 'Brand: ' . ($mediaPlan->brand ?? '-'), '', 'Quotation: ' . ($mediaPlan->quotation_number ?? '-'), '', 'Generated: ' . now()->format('d M Y H:i')];

        // Write data to spreadsheet
        $range = 'Sheet1!A1';
        $body = new ValueRange([
            'values' => $rows,
        ]);

        $this->sheetsService->spreadsheets_values->update(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'RAW']
        );
    }

    /**
     * Apply styling to spreadsheet
     */
    protected function applyStyles(string $spreadsheetId, int $dataRowCount): void
    {
        try {
            $requests = [];

            // Style header row (purple background, bold text)
            $requests[] = new Request([
                'repeatCell' => [
                    'range' => [
                        'sheetId' => 0,
                        'startRowIndex' => 0,
                        'endRowIndex' => 1,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 16,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'backgroundColor' => [
                                'red' => 0.847,
                                'green' => 0.706,
                                'blue' => 0.996,
                            ],
                            'textFormat' => [
                                'bold' => true,
                                'foregroundColor' => [
                                    'red' => 0.345,
                                    'green' => 0.11,
                                    'blue' => 0.529,
                                ],
                            ],
                            'horizontalAlignment' => 'CENTER',
                            'verticalAlignment' => 'MIDDLE',
                        ],
                    ],
                    'fields' => 'userEnteredFormat(backgroundColor,textFormat,horizontalAlignment,verticalAlignment)',
                ],
            ]);

            // Auto resize columns
            $requests[] = new Request([
                'autoResizeDimensions' => [
                    'dimensions' => [
                        'sheetId' => 0,
                        'dimension' => 'COLUMNS',
                        'startIndex' => 0,
                        'endIndex' => 16,
                    ],
                ],
            ]);

            // Freeze header row
            $requests[] = new Request([
                'updateSheetProperties' => [
                    'properties' => [
                        'sheetId' => 0,
                        'gridProperties' => [
                            'frozenRowCount' => 1,
                        ],
                    ],
                    'fields' => 'gridProperties.frozenRowCount',
                ],
            ]);

            $batchUpdateRequest = new BatchUpdateSpreadsheetRequest([
                'requests' => $requests,
            ]);

            $this->sheetsService->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);

        } catch (Exception $e) {
            Log::warning('Failed to apply styles to spreadsheet', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Share spreadsheet with specific email
     */
    public function shareWithEmail(string $spreadsheetId, string $email, string $role = 'reader'): void
    {
        try {
            $permission = new Drive\Permission([
                'type' => 'user',
                'role' => $role, // 'reader', 'writer', 'commenter'
                'emailAddress' => $email,
            ]);

            $this->driveService->permissions->create($spreadsheetId, $permission, [
                'sendNotificationEmail' => true,
            ]);

            Log::info('Spreadsheet shared', [
                'spreadsheetId' => $spreadsheetId,
                'email' => $email,
                'role' => $role,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to share spreadsheet', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Make spreadsheet accessible to anyone with the link
     */
    public function makePublic(string $spreadsheetId, string $role = 'reader'): void
    {
        try {
            $permission = new Drive\Permission([
                'type' => 'anyone',
                'role' => $role,
            ]);

            $this->driveService->permissions->create($spreadsheetId, $permission);

            Log::info('Spreadsheet made public', [
                'spreadsheetId' => $spreadsheetId,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to make spreadsheet public', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
