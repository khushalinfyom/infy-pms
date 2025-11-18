<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <style>
        .client-entry-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .client-entry-container img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 0 6px rgba(0,0,0,0.08);
            flex-shrink: 0;
        }

        .client-entry-details {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .client-entry-name {
            font-weight: 700;
            font-size: 1rem;
            color: #1f2937;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        .client-entry-email {
            color: #6b7280;
            font-size: 0.9rem;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        .dark .client-entry-name {
            color: #e5e7eb;
        }

        .dark .client-entry-email {
            color: #9ca3af;
        }
    </style>

    @php
        $client = $getClient();
    @endphp

    <div {{ $getExtraAttributeBag() }} class="client-entry-container">
        <img src="{{ $getImageUrl() }}" alt="{{ $client['name'] }}">

        <div class="client-entry-details">
            <div class="client-entry-name" title="{{ $client['name'] }}">
                {{ $client['name'] }}
            </div>

            <div class="client-entry-email" title="{{ $client['email'] }}">
                {{ $client['email'] }}
            </div>
        </div>
    </div>

</x-dynamic-component>
