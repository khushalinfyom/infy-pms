<div class="relative group flex flex-wrap gap-2">
    @foreach ($getRecord()->getMedia('attachments') as $attachment)
        <div
            style="position: relative; width: 100px; height: 100px; border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden; margin: 0.25rem;">

            <img src="{{ $attachment->getUrl() }}" alt="{{ $attachment->file_name }}"
                style="width: 100%; height: 100%; object-fit: cover; display: block;">

            {{-- Modal with trigger --}}
            <x-filament::modal width="md">
                <x-slot name="trigger">
                    <x-filament::button size="sm" color="danger" icon="heroicon-o-trash"
                        style="position: absolute; top: 5px; right: 5px; padding: 2px; border-radius: 9999px; background-color: rgba(255,255,255,0.8);">
                    </x-filament::button>
                </x-slot>

                <div style="text-align: center;">

                    <div class="fi-modal-header">
                        <button class="fi-icon-btn fi-size-md fi-modal-close-btn" title="Close" aria-label="Close"
                            type="button" wire:loading.attr="disabled" tabindex="-1"
                            x-on:click="$dispatch('close-modal')">
                            <x-heroicon-o-x-mark class="fi-icon fi-size-lg" />
                        </button>
                        <div
                            style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
                            <div class="fi-modal-icon-ctn">
                                <div class="fi-modal-icon-bg fi-color fi-color-danger">
                                    <x-heroicon-o-trash class="fi-icon fi-size-lg" />
                                </div>
                            </div>
                            <div>
                                <h2 class="fi-modal-heading">
                                    Delete Attachment
                                </h2>
                                <p class="fi-modal-description">
                                    Are you sure you want to delete this attachment?
                                </p>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.5rem; margin-top: 28px;">
                        <x-filament::button color="gray" wire:loading.attr="disabled"
                            x-on:click="$dispatch('close-modal')" style="width: 100%;">
                            Cancel
                        </x-filament::button>

                        <x-filament::button color="danger" wire:click="deleteAttachment('{{ $attachment->id }}')"
                            style="width: 100%;">
                            Confirm
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::modal>

        </div>
    @endforeach
</div>
