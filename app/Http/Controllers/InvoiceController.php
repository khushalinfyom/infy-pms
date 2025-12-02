<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Response;

class InvoiceController extends Controller
{
    public function generatePdf($id)
    {
        $invoice = Invoice::with(['invoiceClients', 'invoiceProjects', 'invoiceItems.projects', 'tax'])->findOrFail($id);

        $defaultTemplate = getSettingValue('default_invoice_template');
        // dd($defaultTemplate);
        $setting = [
            'app_name' => getSettingValue('app_name'),
            'company_address' => getSettingValue('company_address'),
            'company_phone' => getSettingValue('company_phone'),
            'default_invoice_color' => getSettingValue('default_invoice_color'),
        ];

        // $html = View::make("invoices.invoice_template_pdf.{$defaultTemplate}", compact('invoice', 'setting'))->render();

        // return Response::make($html, 200, [
        //     'Content-Type' => 'text/html',
        //     'Content-Disposition' => 'inline; filename="invoice-' . $invoice->invoice_number . '.html"',
        // ]);

        $pdf = Pdf::loadView("invoices.invoice_template_pdf.{$defaultTemplate}", compact('invoice', 'setting'));
        return $pdf->stream('invoice-' . $invoice->invoice_number . '.pdf');
    }
}
