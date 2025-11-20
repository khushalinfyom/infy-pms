<x-filament-panels::page>
    <div class="mb-4">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex-1 max-w-md">
                    {{ $this->form->getComponent('search') }}
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div>
                        {{ $this->form->getComponent('status') }}
                    </div>
                    <div>
                        {{ $this->form->getComponent('client_id') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{ $this->projectsInfolist }}

    <x-filament::pagination :paginator="$this->getProjectsProperty()" :page-options="$this->getPerPageOptions()" :current-page-option-property="'perPage'" />
</x-filament-panels::page>
