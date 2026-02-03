@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-lg font-semibold">Notifications</h1>
        <div class="flex items-center gap-3">
            {{-- Filter tabs --}}
            <div class="flex rounded-md border border-gray-200 bg-white text-sm">
                <a
                    href="{{ route('notifications.index') }}"
                    class="rounded-l-md px-3 py-1.5 {{ !request('filter') ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-50' }}"
                >
                    All
                </a>
                <a
                    href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                    class="rounded-r-md border-l border-gray-200 px-3 py-1.5 {{ request('filter') === 'unread' ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-50' }}"
                >
                    Unread
                </a>
            </div>

            @if ($notifications->where('is_read', false)->count() > 0 || request('filter') === 'unread')
                <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50">
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>
    </div>

    @forelse ($notifications as $notification)
        <a
            href="{{ route('notifications.click', $notification) }}"
            class="block rounded-lg border bg-white p-4 transition hover:shadow-sm {{ !$notification->is_read ? 'border-l-4 border-l-blue-500 border-t-gray-200 border-r-gray-200 border-b-gray-200' : 'border-gray-200' }}"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                            {{ match($notification->event_type->category()) {
                                'Agenda' => 'bg-amber-100 text-amber-800',
                                'Calendar' => 'bg-blue-100 text-blue-800',
                                'Chat' => 'bg-green-100 text-green-800',
                                'Video' => 'bg-purple-100 text-purple-800',
                                'System' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800',
                            } }}">
                            {{ $notification->event_type->category() }}
                        </span>
                        @unless($notification->is_read)
                            <span class="h-2 w-2 rounded-full bg-blue-500" title="Unread"></span>
                        @endunless
                    </div>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $notification->title }}</p>
                    <p class="mt-0.5 text-sm text-gray-600">{{ $notification->message }}</p>
                </div>
                <time class="shrink-0 text-xs text-gray-400" datetime="{{ $notification->created_at->toIso8601String() }}">
                    {{ $notification->created_at->diffForHumans() }}
                </time>
            </div>
        </a>
    @empty
        <div class="rounded-lg border border-gray-200 bg-white px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            <p class="mt-2 text-sm text-gray-500">No notifications yet.</p>
        </div>
    @endforelse

    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
