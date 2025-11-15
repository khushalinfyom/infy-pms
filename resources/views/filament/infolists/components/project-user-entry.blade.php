<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <style>
        .project-user-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap; 
        }

        .project-user-container img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 0 6px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            flex-shrink: 0;
        }

        .project-user-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            min-width: 0;
            flex: 1;
        }

        .project-user-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .project-user-name {
            color: #e5e7eb;
        }

        .project-user-email {
            font-size: 0.85rem;
            color: #6b7280;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .project-user-email {
            color: #9ca3af;
        }

        .project-user-role {
            font-size: 0.75rem;
            font-weight: 500;
            color: #bd5400;
            background: #fefbeb;
            padding: 2px 6px;
            border-radius: 4px;
            margin-top: 5px;
            white-space: nowrap;
        }

        .dark .project-user-role {
            color: #d1d5db;
            background: #1f2937;
        }

        @media (max-width: 640px) {
            .project-user-container {
                gap: 0.5rem;
            }

            .project-user-container img {
                width: 50px;
                height: 50px;
            }

            .project-user-name,
            .project-user-email,
            .project-user-role {
                white-space: normal;
                text-overflow: unset;
                overflow: visible;
            }

            .project-user-name {
                font-size: 0.9rem;
            }

            .project-user-email {
                font-size: 0.8rem;
            }

            .project-user-role {
                font-size: 0.7rem;
                padding: 1px 4px;
            }
        }

        @media (max-width: 400px) {
            .project-user-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .project-user-container img {
                width: 45px;
                height: 45px;
            }
        }
    </style>

    @php
        $user = $getUser();
    @endphp

    <div {{ $getExtraAttributeBag() }} class="project-user-container">
        <img src="{{ $getImageUrl() }}" alt="{{ $user['name'] }}">
        <div class="project-user-details">
            <div class="project-user-name" title="{{ $user['name'] ?? '' }}">{{ $user['name'] ?? '' }}</div>
            <div class="project-user-email" title="{{ $user['email'] ?? '' }}">{{ $user['email'] ?? '' }}</div>
            <div class="project-user-role">{{ $user['role'] ?? 'Member' }}</div>
        </div>
    </div>
</x-dynamic-component>
