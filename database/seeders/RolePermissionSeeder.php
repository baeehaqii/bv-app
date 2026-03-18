<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = Permission::query()->pluck('name');

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $finance = Role::firstOrCreate(['name' => 'Finance']);
        $salesBd = Role::firstOrCreate(['name' => 'Sales/BD']);
        $operationKolCreative = Role::firstOrCreate(['name' => 'Operation KOL & Creative']);

        // Super Admin gets all permissions managed by Shield.
        $superAdmin->syncPermissions($allPermissions);

        $viewOnlyPermissions = $allPermissions->filter(
            fn(string $name): bool =>
            str_starts_with($name, 'View:') || str_starts_with($name, 'ViewAny:')
        );

        $financePermissions = $this->mergePermissions(
            $viewOnlyPermissions,
            $allPermissions,
            [
                'BvCashflow', // treated as invoice-to-KOL flow in current resources
            ],
            ['Create', 'Update']
        );

        $salesBdPermissions = $this->mergePermissions(
            $viewOnlyPermissions,
            $allPermissions,
            [
                // Sales domain
                'DataClient',
                'SalesTarget',
                'BvSalesList',
                'FormBrief',
                'BvCampign',
                'BvCampignUpcoming',
                'BvTrackerProgresKol',
                'Spk',
                // Media plan external/internal
                'MediaPlan',
                'InternalBudget',
                // Invoice to client support
                'BvQuotation',
            ],
            ['Create', 'Update']
        );

        $operationPermissions = $this->mergePermissions(
            $viewOnlyPermissions,
            $allPermissions,
            [
                // Editable in media planning
                'MediaPlan',
                'InternalBudget',
                // Invoice to KOL is limited to operation + finance
                'BvCashflow',
            ],
            ['Create', 'Update']
        );

        $finance->syncPermissions($financePermissions);
        $salesBd->syncPermissions($salesBdPermissions);
        $operationKolCreative->syncPermissions($operationPermissions);

        $this->command?->info('RolePermissionSeeder: role matrix synced from Shield permissions.');
    }

    private function mergePermissions(
        Collection $base,
        Collection $allPermissions,
        array $subjects,
        array $actions
    ): Collection {
        $extra = collect();

        foreach ($subjects as $subject) {
            foreach ($actions as $action) {
                $extra->push("{$action}:{$subject}");
            }

            // Ensure the target resources still appear in navigation for the role.
            $extra->push("ViewAny:{$subject}");
            $extra->push("View:{$subject}");
        }

        return $base
            ->merge($extra)
            ->filter(fn(string $name): bool => $allPermissions->contains($name))
            ->unique()
            ->values();
    }
}
