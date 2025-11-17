<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "//www.w3.org/TR/html4/strict.dtd">
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
            <div class="main-heading" style="font-size: 40px">INVOICE</div>
            <div class="invoice-number font-color-gray">Invoice Id
                #{{ $invoice->invoice_number }}</div>
        </td>
        <td class="header-right">
            <div class="logo"><img width="100px"
                                   src="data:image/png,image/jpeg,image/jpg;base64,{{ base64_encode(file_get_contents(getSettings('app_logo')->logo_path)) }}"
                                   alt=""></div>
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
<br>
<table width="100%">
    <thead class="text-right">
    <tr>
        <td>
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

<table class="w-100">
    <tr class="invoice-items">
        <td colspan="2">
            <table class="d-items-table table-striped table-bordered">
                <thead>
                <tr>
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
                            <td>{{ $key + 1 }}</td>
                            <td>{{ html_entity_decode($invoiceItem->item_name) }}</td>
                            <td>{{ $invoiceItem->hours }}</td>
                            <td>
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
            <table class="d-invoice-footer">
                <tr>
                    <td class="font-weight-bold">Amount:</td>
                    <td>
                        <span
                                class="invoice-currency-symbol">&#{{ getCurrencyIconForInvoicePDF($invoice) }}</span> {{ number_format($invoice->sub_total, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold">Discount:</td>
                    <td>
                        <span
                                class="invoice-currency-symbol">&#{{ getCurrencyIconForInvoicePDF($invoice) }} {{ number_format($invoice->discount,2) }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold">Tax:</td>
                    <td>{{ isset($invoice->tax_id)?$invoice->tax->tax:'0' }}<span
                                class="invoice-currency-symbol">&#37;</span></td>
                </tr>
                <tr>
                    <td class="font-weight-bold">Total:</td>
                    <td>
                        <span
                                class="invoice-currency-symbol">&#{{ getCurrencyIconForInvoicePDF($invoice) }} </span> {{ number_format($invoice->amount, 2) }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
