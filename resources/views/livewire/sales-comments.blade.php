<div class="space-y-4">
    {{-- Comment Header --}}
    <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 pb-2">
        <x-heroicon-o-chat-bubble-left-ellipsis class="w-5 h-5 text-gray-500" />
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
            Comments
            @if($this->comments->count())
                <span class="text-xs font-normal text-gray-400">({{ $this->comments->count() }})</span>
            @endif
        </h3>
    </div>

    {{-- Comment List --}}
    <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
        @forelse($this->comments as $comment)
            <div class="group" wire:key="comment-{{ $comment->id }}">
                {{-- Main Comment --}}
                <div class="flex gap-3">
                    {{-- Avatar --}}
                    <div class="flex-shrink-0">
                        <div
                            class="w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-800 flex items-center justify-center">
                            <span class="text-xs font-semibold text-primary-700 dark:text-primary-300">
                                {{ strtoupper(substr($comment->user->name ?? 'U', 0, 1)) }}
                            </span>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                {{ $comment->user->name ?? 'Unknown' }}
                            </span>
                            <span class="text-xs text-gray-400">
                                {{ $comment->created_at->format('M d') }}
                            </span>
                            @if($comment->user_id === auth()->id())
                                <button wire:click="deleteComment({{ $comment->id }})" wire:confirm="Hapus komentar ini?"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity ml-auto">
                                    <x-heroicon-o-trash class="w-3.5 h-3.5 text-gray-400 hover:text-red-500" />
                                </button>
                            @endif
                        </div>

                        @if($comment->body)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5 whitespace-pre-line">{{ $comment->body }}
                            </p>
                        @endif

                        {{-- Reply Button --}}
                        <button wire:click="startReply({{ $comment->id }})"
                            class="text-xs text-gray-400 hover:text-primary-500 mt-1 transition-colors">
                            Reply
                        </button>

                        {{-- Replies --}}
                        @if($comment->replies->count())
                            <div class="mt-2 space-y-2 border-l-2 border-gray-100 dark:border-gray-700 pl-3">
                                @foreach($comment->replies as $reply)
                                    <div class="group/reply flex gap-2" wire:key="reply-{{ $reply->id }}">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="w-5 h-5 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                                <span class="text-[10px] font-semibold text-gray-600 dark:text-gray-400">
                                                    {{ strtoupper(substr($reply->user->name ?? 'U', 0, 1)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                    {{ $reply->user->name ?? 'Unknown' }}
                                                </span>
                                                <span class="text-[10px] text-gray-400">
                                                    {{ $reply->created_at->format('M d') }}
                                                </span>
                                                @if($reply->user_id === auth()->id())
                                                    <button wire:click="deleteComment({{ $reply->id }})"
                                                        wire:confirm="Hapus balasan ini?"
                                                        class="opacity-0 group-hover/reply:opacity-100 transition-opacity ml-auto">
                                                        <x-heroicon-o-trash class="w-3 h-3 text-gray-400 hover:text-red-500" />
                                                    </button>
                                                @endif
                                            </div>
                                            @if($reply->body)
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 whitespace-pre-line">
                                                    {{ $reply->body }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Reply Form --}}
                        @if($replyingTo === $comment->id)
                            <div class="mt-2 border-l-2 border-primary-200 dark:border-primary-700 pl-3">
                                <div class="flex gap-2">
                                    <div class="flex-1">
                                        <textarea wire:model="replyBody" rows="2" placeholder="Write a reply..."
                                            class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 resize-none"></textarea>
                                        <div class="flex items-center gap-2 mt-1">
                                            <div class="flex-1"></div>
                                            <button wire:click="cancelReply"
                                                class="text-xs text-gray-400 hover:text-gray-600">Cancel</button>
                                            <button wire:click="addReply"
                                                class="text-xs bg-primary-500 hover:bg-primary-600 text-white px-3 py-1 rounded-md transition-colors">
                                                Reply
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-6">
                <x-heroicon-o-chat-bubble-left-ellipsis class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                <p class="text-xs text-gray-400">No comments yet</p>
            </div>
        @endforelse
    </div>

    {{-- New Comment Form --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 mt-1">
                <div class="w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-800 flex items-center justify-center">
                    <span class="text-xs font-semibold text-primary-700 dark:text-primary-300">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </span>
                </div>
            </div>
            <div class="flex-1">
                <textarea wire:model="newComment" rows="2" placeholder="Add a comment..."
                    class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 resize-none"></textarea>
                <div class="flex items-center gap-2 mt-1">
                    <div class="flex-1"></div>
                    <button wire:click="addComment" wire:loading.attr="disabled"
                        class="text-xs bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white px-4 py-1.5 rounded-md transition-colors">
                        <span wire:loading.remove wire:target="addComment">Comment</span>
                        <span wire:loading wire:target="addComment">Sending...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>