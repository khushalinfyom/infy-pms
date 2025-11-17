{{-- <x-filament::page>
    @once('head')
        <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
    @endonce
    <x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
        <div class="pdf-theme">
            @if (!empty($invoiceTemplate))
                {{-- @include('invoices.invoice_template_pdf.' . $invoiceTemplate, [
                    'companyName' => 'InfyOM',
                    'companyAddress' => 'Rajkot',
                    'companyPhone' => '+7405868976',
                    'gstNo' => '22AAAAA0000A1Z5',
                    'invColor' => $invColor,
                ]) --}}
            {{-- @endif
        </div>
    </x-dynamic-component>
</x-filament::page>  --}}
