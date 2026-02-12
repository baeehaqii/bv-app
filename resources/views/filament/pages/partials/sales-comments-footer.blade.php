<div class="px-6 pb-6">
    @if($record && $record->id)
        @livewire('sales-comments', ['salesId' => $record->id], key('comments-' . $record->id))
    @endif
</div>