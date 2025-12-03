<div class="flex items-center gap-4 py-4" style="margin-left: 12px">

    <img src="{{ $record->getFirstMediaUrl(\App\Models\User::IMAGE_PATH) ?:
        'https://ui-avatars.com/api/?name=' . urlencode($record->name) }}"
        class="h-12 w-12 rounded-full object-cover" />

    <div class="flex flex-col gap-1">
        <span class="font-semibold text-gray-900 dark:text-gray-100">
            {{ $record->name }}
        </span>

        <span class="text-gray-500 dark:text-gray-400 text-sm">
            {{ $record->email }}
        </span>
    </div>

</div>
