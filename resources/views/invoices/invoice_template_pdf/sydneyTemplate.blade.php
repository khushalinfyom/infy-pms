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
<div style="border: 5px solid {{ $setting['default_invoice_color'] }}">
    <table width="100%" class="ml-4 mr-4 mt-4 mb-4">
        <tr>
            <td colspan="2">
                <div class="main-heading">INVOICE</div>
                <span style="color: {{ $setting['default_invoice_color'] }}">#{{ $invoice->invoice_number }}</span><br>
                {{ $invoice->created_at->format('jS M,Y g:i A') }}<br>
            </td>
            <td width="170px">
                <div class="logo"><img width="130px"
                                       src="data:image/png,image/jpeg,image/jpg;base64,{{ base64_encode(file_get_contents(getSettings('app_logo')->logo_path)) }}"
                                       alt=""></div>
            </td>
        </tr>
    </table>
    <br>
    <table width="100%" class="ml-4 mr-4">
        <thead>
        <tr>
            <td class="vertical-align-top">
                <strong class="from-font-size">From :</strong><br>
                <span style="color: {{ $setting['default_invoice_color'] }};font-size: 15px">{{ html_entity_decode($setting['app_name']) }}</span><br>
                {{ html_entity_decode($setting['company_address']) }}<br>
                Mo: {{ $setting['company_phone'] }}
            </td>
            <td class="vertical-align-top">
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
            <td class="vertical-align-top" width="180px">
                <strong>Issue
                    Date:</strong> 
                <p>{{ $invoice->issue_date ? Carbon\Carbon::parse($invoice->issue_date)->format('jS M, Y') : 'N/A' }}</p>
                @if(! empty($invoice->due_date))
                    <strong>Due Date:</strong> <p>{{ Carbon\Carbon::parse($invoice->due_date)->format('jS M, Y')}}</p>
                @endif
            </td>
        </tr>
        </thead>
    </table>
    <br>
    <table width="100%" class="ml-1 mr-4 mb-4">
        <tr class="invoice-items">
            <td colspan="2">
                <table class="items-table">
                    <thead>
                    <tr class="tu" style="background: {{ $setting['default_invoice_color'] }};color: white">
                        <th>#</th>
                        <th>{{ __('messages.time_entry.task') }}</th>
                        <th>{{ __('messages.invoice.hours') }}</th>
                        <th class="text-left">{{ __('messages.invoice.task_amount') }}</th>
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
                                <span class="float-left"><span class="invoice-currency-symbol">
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
                        <td class="text-left pl-2">
                            <span class="invoice-currency-symbol">&#{{ getCurrencyIconForInvoicePDF($invoice) }}</span> {{ number_format($invoice->sub_total, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold tu">Discount:</td>
                        <td class="text-left pl-2">
                            <span class="invoice-currency-symbol">&#{{ getCurrencyIconForInvoicePDF($invoice) }} {{ number_format($invoice->discount,2) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold tu">Tax:</td>
                        <td class="text-left pl-2">{{ isset($invoice->tax_id)?$invoice->tax->tax:'0' }}<span
                                    class="invoice-currency-symbol">&#37;</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold tu">Total:</td>
                        <td class="text-left pl-2">
                            <span class="invoice-currency-symbol">&#{{ getCurrencyIconForInvoicePDF($invoice) }} </span> {{ number_format($invoice->amount, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
{{--                <h1>Thank You!</h1>--}}
                <br>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="vertical-align-bottom">
                <strong style="color: {{ $setting['default_invoice_color'] }}; font-size: 20px">Regards</strong>
                <br>{{ $setting['app_name'] }}
            </td>
        </tr>
    </table>
</div>
</body>
</html>
