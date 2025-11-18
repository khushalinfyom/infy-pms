<div class="">
    <x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
        <div class="pdf-theme">

            @if ($invoiceTemplate)
                @php
                    $invoice = (object) [
                        'invoice_number' => '9CQ5X7',
                        'created_at' => now(),
                        'issue_date' => now()->format('Y-m-d'),
                        'due_date' => now()->addDays(7)->format('Y-m-d'),
                        'sub_total' => 300,
                        'discount' => 50,
                        'amount' => 250,
                        'tax_id' => null,
                        'tax' => (object) ['tax' => 0],
                        'invoiceProjects' => collect([(object) ['name' => '<Project Name>']]),
                        'invoiceClients' => collect([
                            (object) ['name' => '<Client Name>', 'email' => '<Client Email>'],
                        ]),
                        'invoiceItems' => collect([
                            (object) [
                                'item_name' => 'Task 1',
                                'hours' => 4,
                                'task_amount' => 100,
                            ],
                            (object) [
                                'item_name' => 'Task 2',
                                'hours' => 5,
                                'task_amount' => 100,
                            ],
                            (object) [
                                'item_name' => 'Task 3',
                                'hours' => 4,
                                'task_amount' => 100,
                            ],
                        ]),
                    ];

                    $setting = [
                        'app_name' => 'InfyOmLabs',
                        'company_address' => 'Surat',
                        'company_phone' => '26878307170',
                        'default_invoice_color' => $invColor ?? '#000000',
                    ];

                    if (!function_exists('getSettings')) {
                        function getSettings($key)
                        {
                            return (object) [
                                'logo_path' => public_path('assets/img/logo-red-black.png'),
                            ];
                        }
                    }

                    $html = view("invoices.invoice_template_pdf.$invoiceTemplate", [
                        'companyName' => 'InfyOM',
                        'companyAddress' => 'Rajkot',
                        'companyPhone' => '+7405868976',
                        'gstNo' => '22AAAAA0000A1Z5',
                        'invColor' => $invColor,
                        'invoice' => $invoice,
                        'setting' => $setting,
                    ])->render();

                    $encoded = base64_encode($html);
                @endphp

                <div style="width:100%; height:900px; border:1px solid #ddd;">
                    <iframe style="width:100%; height:100%; border:0; background:white;"
                        src="data:text/html;base64,{{ $encoded }}">
                    </iframe>
                </div>
            @endif

        </div>
    </x-dynamic-component>
</div>
