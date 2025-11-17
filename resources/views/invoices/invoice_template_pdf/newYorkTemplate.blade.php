<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <link rel="shortcut icon" href="{{ asset(getSettingValue('favicon')) }}" type="image/x-icon" sizes="16x16">
    <title>{{ __('messages.invoice.invoice_pdf') }}</title>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/style/css/invoice-pdf.css') }}" rel="stylesheet" type="text/css"/>
</head>
<body>
<table width="100%">
    <tr>
        <td class="header-left">
            <div class="logo"><img width="100px"
                                   src="data:image/png,image/jpeg,image/jpg;base64,{{ base64_encode(file_get_contents(getSettings('app_logo')->logo_path)) }}"
                                   alt=""></div>
        </td>
        <td class="header-right">
            <div class="main-heading" style="font-size: 50px">INVOICE</div>
        </td>
    </tr>
</table>
<table width="100%">
    <thead>
    <tr>
        <td>
            {{ html_entity_decode($setting['app_name']) }}<br>
            <p class="w-75 mb-0">{{ html_entity_decode($setting['company_address']) }}</p>
            Mo: {{ $setting['company_phone'] }}
        </td>
        <td width="250px">
            <strong>Invoice Id:</strong> #{{ $invoice->invoice_number }}<br>
            <strong>Invoice Date:</strong> {{ $invoice->created_at->format('jS M,Y g:i A') }}<br>
            <strong>Issue
                Date:</strong> {{ $invoice->issue_date ? Carbon\Carbon::parse($invoice->issue_date)->format('jS M, Y') : 'N/A' }}
            <br>
            @if(! empty($invoice->due_date))
                <strong>Due Date:</strong> {{ Carbon\Carbon::parse($invoice->due_date)->format('jS M, Y')}}
            @endif
        </td>
    </tr>
    </thead>
</table>
<br>
<table width="100%">
    <thead>
    <tr>
        <td>
            <strong class="to-font-size">To:</strong><br>
            <b>Project:</b>
            @foreach($invoice->invoiceProjects as $invoiceProject)
                {{$loop->first ?'': ', '}}
                {{html_entity_decode($invoiceProject->name)}}
            @endforeach<br>
            <b>Name:</b>
            @foreach($invoice->invoiceClients as $invoiceClient)
                {{$loop->first ? '':', '}}
                {{html_entity_decode($invoiceClient->name)}}
            @endforeach<br>
            @if(count(array_filter($invoice->invoiceClients->pluck('email')->toArray())) > 0)
                <b>Email:</b>
                {{ implode(', ', array_filter($invoice->invoiceClients->pluck('email')->toArray())) }}
            @endif
        </td>
    </tr>
    </thead>
</table>
<table class="w-100">
    <tr class="invoice-items">
        <td colspan="2">
            <table class="items-table">
                <thead style="background: {{ $setting['default_invoice_color'] }};color: white;padding: 8px;">
                <tr class="tu">
                    <th>#</th>
                    <th>{{ __('messages.time_entry.task') }}</th>
                    <th>{{ __('messages.invoice.hours') }}</th>
                    <th class="text-right">{{ __('messages.invoice.task_amount') }}</th>
                </tr>
                </thead>
                <tbody>
                @if(isset($invoice) && !empty($invoice))
                    @foreach($invoice->invoiceItems as $key => $invoiceItem)
                        <tr>
                            <td style="border-bottom: 1px solid {{ $setting['default_invoice_color'] }}">{{ $key + 1 }}</td>
                            <td style="border-bottom: 1px solid {{ $setting['default_invoice_color'] }}">{{ html_entity_decode($invoiceItem->item_name) }}</td>
                            <td style="border-bottom: 1px solid {{ $setting['default_invoice_color'] }}">{{ $invoiceItem->hours }}</td>
                            <td style="border-bottom: 1px solid {{ $setting['default_invoice_color'] }}">
                                <span class="float-right"><span class="invoice-currency-symbol">
                                        @if($invoiceItem->task_amount != 0)
                                            &#{{ getCurrencyIconForInvoicePDF($invoice) }} {{ number_format($invoiceItem->task_amount, 2) }}
                                        @else
                                            {{ __('messages.invoice.fix_rate') }}
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
            <table class="invoice-footer">
                <tr>
                    <td class="font-weight-bold tu"
                        style="border-bottom: 1px solid {{ $setting['default_invoice_color'] }}">Amount:
                    </td>
                    <td style="border-bottom: 1px solid {{ $setting['default_invoice_color'] }}">
                        <span class="invoice-currency-symbol">&#{{ getCurrencyIconForInvoicePDF($invoice) }}</span> {{ number_format($invoice->sub_total, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold tu"
                        style="border-bottom: 1px solid {{ $setting['default_invoice_color'] }}">Discount:
                    </td>
                    <td style="border-bottom: 1px solid {{ $setting['default_invoice_color'] }}">
                        <span class="invoice-currency-symbol">&#{{ getCurrencyIconForInvoicePDF($invoice) }} {{ number_format($invoice->discount,2) }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold tu"
                        style="border-bottom: 1px solid {{ $setting['default_invoice_color'] }}">Tax:
                    </td>
                    <td style="border-bottom: 1px solid {{ $setting['default_invoice_color'] }}">{{ isset($invoice->tax_id)?$invoice->tax->tax:'0' }}
                        <span
                                class="invoice-currency-symbol">&#37;</span></td>
                </tr>
                <tr>
                    <td class="font-weight-bold tu"
                        style="border-bottom: 1px solid {{ $setting['default_invoice_color'] }}">Total:
                    </td>
                    <td style="border-bottom: 1px solid {{ $setting['default_invoice_color'] }}">
                        <span class="invoice-currency-symbol">&#{{ getCurrencyIconForInvoicePDF($invoice) }} </span> {{ number_format($invoice->amount, 2) }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="vertical-align-bottom">
            <br><strong style="color: {{ $setting['default_invoice_color'] }}; font-size: 20px">Regards</strong>
            <br><span>{{ $setting['app_name'] }}</span>
        </td>
    </tr>
</table>
</body>
</html>
