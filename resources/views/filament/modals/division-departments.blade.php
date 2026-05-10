<div class="space-y-3 py-2 max-h-[60vh] overflow-y-auto pr-1">
    @forelse ($division->departments as $department)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <div class="flex items-center justify-between mb-2">
                <span class="font-semibold text-sm text-gray-900 dark:text-white">
                    {{ $department->name }}
                </span>
                <span class="text-xs bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 px-2 py-0.5 rounded-full">
                    {{ $department->positions->count() }} jabatan
                </span>
            </div>
            @if ($department->positions->isNotEmpty())
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($department->positions as $position)
                        <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 px-2 py-0.5 rounded">
                            {{ $position->name }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500 text-center py-4">Belum ada departemen.</p>
    @endforelse
</div>
