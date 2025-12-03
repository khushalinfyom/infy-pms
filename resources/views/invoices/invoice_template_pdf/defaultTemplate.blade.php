<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "//www.w3.org/TR/html4/strict.dtd">
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <link rel="shortcut icon" href="{{ asset(getSettingValue('favicon')) }}" type="image/x-icon" sizes="16x16">
    <title>{{ __('messages.users.invoice_pdf') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Lato", sans-serif;
            padding: 30px;
            font-size: 14px;
        }

        .font-color-gray {
            color: #7a7a7a;
        }

        .main-heading {
            font-size: 34px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header-right {
            text-align: right;
            vertical-align: top;
        }

        .logo,
        .company-name {
            margin-bottom: 8px;
        }

        .font-weight-bold {
            font-weight: bold;
        }

        .address {
            margin-top: 60px;
        }

        .address tr:first-child td {
            padding-bottom: 10px;
        }

        .d-items-table {
            width: 100%;
            border: 0;
            border-collapse: collapse;
            margin-top: 40px;
        }

        .d-items-table thead {
            background: #2f353a;
            color: #fff;
        }

        .d-items-table td,
        .d-items-table th {
            padding: 8px;
            font-size: 14px;
            border-bottom: 1px solid #ccc;
            text-align: left;
            vertical-align: top;
        }

        .d-invoice-footer {
            margin-top: 15px;
            width: 80%;
            float: right;
            text-align: right;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
        }

        .items-table td,
        .items-table th {
            padding: 8px;
            font-size: 14px;
            text-align: left;
            vertical-align: top;
        }

        .invoice-footer {
            margin-top: 15px;
            width: 100%;
            text-align: right;
        }

        .number-align {
            text-align: right !important;
        }

        .invoice-currency-symbol {
            font-family: "DejaVu Sans";
        }

        .vertical-align-top {
            vertical-align: text-top;
        }

        .tu {
            text-transform: uppercase;
        }

        .l-col-66 {
            width: 100%;
        }

        .thank {
            font-size: 45px;
            line-height: 1.2em;
            text-align: center;
            font-style: italic;
            padding-right: 25px;
        }

        .to-font-size {
            font-size: 15px;
        }

        .from-font-size {
            font-size: 15px;
        }
    </style>
</head>

<body>
    <table width="100%">
        <tr>
            <td class="header-left">
                <div class="main-heading" style="font-size: 40px">INVOICE</div>
                <div class="invoice-number font-color-gray">Invoice Id
                    #{{ $invoice->invoice_number }}</div>
            </td>
            <td class="header-right">
                <div class="logo"><img width="100px" src="{{ asset(getSettingValue('app_logo')) }}"
                        {{-- src="data:image/png,image/jpeg,image/jpg;base64,{{ base64_encode(file_get_contents(getSettings('app_logo')->logo_path)) }}" --}} alt=""></div>
            </td>
        </tr>
    </table>
    <br>
    <table width="100%">
        <thead>
            <tr>
                <td colspan="2" class="vertical-align-top" width="200px">
                    <strong class="from-font-size">From :</strong><br>
                    {{ html_entity_decode($setting['app_name']) }}<br>
                    {{ html_entity_decode($setting['company_address']) }}<br>
                    Mo: {{ $setting['company_phone'] }}
                </td>
                <td colspan="5" class="vertical-align-top">
                    <strong class="from-font-size">To :</strong><br>
                    <b>Project:</b>
                    @foreach ($invoice->invoiceProjects as $invoiceProject)
                        {{ $loop->first ? '' : ', ' }}
                        {{ html_entity_decode($invoiceProject->name) }}
                    @endforeach
                    <br>
                    <b>Name:</b>
                    @foreach ($invoice->invoiceClients as $invoiceClient)
                        {{ $loop->first ? '' : ', ' }}
                        {{ html_entity_decode($invoiceClient->name) }}
                    @endforeach
                    <br>

                    @if (count(array_filter($invoice->invoiceClients->pluck('email')->toArray())) > 0)
                        <b>Email:</b>
                        {{ implode(', ', array_filter($invoice->invoiceClients->pluck('email')->toArray())) }}
                    @endif
                </td>
            </tr>
        </thead>
    </table>
    <br>
    <table width="100%">
        <thead class="text-right">
            <tr>
                <td>
                    <strong>Invoice Date:</strong> {{ $invoice->created_at->format('jS M,Y g:i A') }}<br>
                    <strong>Issue
                        Date:</strong>
                    {{ $invoice->issue_date ? Carbon\Carbon::parse($invoice->issue_date)->format('jS M, Y') : 'N/A' }}
                    <br>
                    @if (!empty($invoice->due_date))
                        <strong>Due Date:</strong> {{ Carbon\Carbon::parse($invoice->due_date)->format('jS M, Y') }}
                    @endif
                </td>
            </tr>
        </thead>
    </table>

    <table width="100%">
        <tr class="invoice-items">
            <td colspan="2">
                <table class="d-items-table table-striped table-bordered">
                    <thead style="background: {{ $setting['default_invoice_color'] }}; ">
                        <tr>
                            <th>#</th>
                            <th>Task</th>
                            <th>Hours</th>
                            <th class="text-right">Task Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($invoice) && !empty($invoice))
                            @foreach ($invoice->invoiceItems as $key => $invoiceItem)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ html_entity_decode($invoiceItem->item_name) }}</td>
                                    <td>{{ decimalHoursToHHMM($invoiceItem->hours) }} H</td>
                                    <td>
                                        <span class="float-right"><span class="invoice-currency-symbol">
                                                @if ($invoiceItem->task_amount != 0)
                                                    &#{{ getCurrencyIconForInvoicePDF($invoice) }}
                                                    {{ number_format($invoiceItem->task_amount, 2) }}
                                                @else
                                                    {{ __('messages.users.fix_rate') }}
                                            </span>
                            @endif
                            </span>
            </td>
        </tr>
        @endforeach
        @endif
        </tbody>
    </table>
    </td>
    </tr>
    <tr>
        <td></td>
        <td>
            <table class="d-invoice-footer">
                <tr>
                    <td class="font-weight-bold">Amount:</td>
                    <td>
                        <span class="invoice-currency-symbol">{!! currencyEntityForInvoice($invoice) !!}</span>
                        {{ number_format($invoice->sub_total, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold">Discount:</td>
                    <td>
                        <span class="invoice-currency-symbol">{!! currencyEntityForInvoice($invoice) !!}</span>
                        {{ number_format($invoice->discount, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold">Tax:</td>
                    <td>{{ isset($invoice->tax_id) ? $invoice->tax->tax : '0' }}<span
                            class="invoice-currency-symbol">&#37;</span></td>
                </tr>
                <tr>
                    <td class="font-weight-bold">Total:</td>
                    <td>
                        <span class="invoice-currency-symbol">{!! currencyEntityForInvoice($invoice) !!}</span>
                        {{ number_format($invoice->amount, 2) }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    </table>
</body>

</html>
