<?php

namespace App\Livewire;

use App\Models\BvSales;
use App\Models\BvSalesComment;
use Livewire\Component;

class SalesComments extends Component
{
    public int $salesId;
    public string $newComment = '';
    public ?int $replyingTo = null;
    public string $replyBody = '';

    public function mount(int $salesId): void
    {
        $this->salesId = $salesId;
    }

    public function getCommentsProperty()
    {
        return BvSalesComment::where('bv_sales_id', $this->salesId)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->latest()
            ->get();
    }

    public function addComment(): void
    {
        if (empty(trim($this->newComment))) {
            return;
        }

        BvSalesComment::create([
            'bv_sales_id' => $this->salesId,
            'user_id' => auth()->id(),
            'body' => trim($this->newComment),
        ]);

        $this->reset('newComment');
    }

    public function startReply(int $commentId): void
    {
        $this->replyingTo = $commentId;
        $this->replyBody = '';
    }

    public function cancelReply(): void
    {
        $this->reset('replyingTo', 'replyBody');
    }

    public function addReply(): void
    {
        if (empty(trim($this->replyBody))) {
            return;
        }

        BvSalesComment::create([
            'bv_sales_id' => $this->salesId,
            'user_id' => auth()->id(),
            'parent_id' => $this->replyingTo,
            'body' => trim($this->replyBody),
        ]);

        $this->reset('replyingTo', 'replyBody');
    }

    public function deleteComment(int $commentId): void
    {
        $comment = BvSalesComment::find($commentId);
        if ($comment && $comment->user_id === auth()->id()) {
            $comment->delete();
        }
    }

    public function render()
    {
        return view('livewire.sales-comments');
    }
}
