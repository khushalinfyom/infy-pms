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
        <td>
            <div class="logo"><img width="150px"
                                   src="data:image/png,image/jpeg,image/jpg;base64,{{ base64_encode(file_get_contents(getSettings('app_logo')->logo_path)) }}"
                                   alt=""></div>
        </td>
        <td width="250px" style="padding-left: 10px">
            <div class="main-heading" style="color: {{ $setting['default_invoice_color'] }};font-size: 30px">INVOICE
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <span style="color: {{ $setting['default_invoice_color'] }}">{{ html_entity_decode($setting['app_name']) }}</span><br>
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
</table>
<br>
<table width="100%">
    <thead>
    <tr>
        <td></td>
        <td class="vertical-align-top" width="168px">
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
<table width="100%">
    <tr class="invoice-items">
        <td colspan="2">
            <table class="items-table">
                <thead>
                <tr class="tu">
                    <th style="border: 1px solid {{ $setting['default_invoice_color'] }};padding: 5px;">#</th>
                    <th style="border: 1px solid {{ $setting['default_invoice_color'] }};padding: 5px;">{{ __('messages.time_entry.task') }}</th>
                    <th style="border: 1px solid {{ $setting['default_invoice_color'] }};padding: 5px;">{{ __('messages.invoice.hours') }}</th>
                    <th class="text-right"
                        style="border: 1px solid {{ $setting['default_invoice_color'] }};padding: 5px;">{{ __('messages.invoice.task_amount') }}</th>
                </tr>
                </thead>
                <tbody>
                @if(isset($invoice) && !empty($invoice))
                    @foreach($invoice->invoiceItems as $key => $invoiceItem)
                        <tr>
                            <td style="border: 1px solid {{ $setting['default_invoice_color'] }};padding: 5px;">{{ $key + 1 }}</td>
                            <td style="border: 1px solid {{ $setting['default_invoice_color'] }};padding: 5px;">{{ html_entity_decode($invoiceItem->item_name) }}</td>
                            <td style="border: 1px solid {{ $setting['default_invoice_color'] }};padding: 5px;">{{ $invoiceItem->hours }}</td>
                            <td style="border: 1px solid {{ $setting['default_invoice_color'] }};padding: 5px;">
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
                    <td class="font-weight-bold tu">Amount:</td>
                    <td>
                        <span class="invoice-currency-symbol">&#{{ getCurrencyIconForInvoicePDF($invoice) }}</span> {{ number_format($invoice->sub_total, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold tu">Discount:</td>
                    <td>
                        <span class="invoice-currency-symbol">&#{{ getCurrencyIconForInvoicePDF($invoice) }} {{ number_format($invoice->discount,2) }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold tu">Tax:</td>
                    <td>{{ isset($invoice->tax_id)?$invoice->tax->tax:'0' }}<span
                                class="invoice-currency-symbol">&#37;</span>
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold tu">Total:</td>
                    <td>
                        <span class="invoice-currency-symbol">&#{{ getCurrencyIconForInvoicePDF($invoice) }} </span> {{ number_format($invoice->amount, 2) }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="vertical-align-bottom">
            <strong style="font-size: 20px">Regards: </strong><br>
            <br><span style="color: {{ $setting['default_invoice_color'] }}">{{ $setting['app_name'] }}</span>
        </td>
    </tr>
</table>
</body>
</html>
