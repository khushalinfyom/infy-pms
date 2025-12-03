<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;

// Route::get('/', function () {
//     return view('welcome');
// })->name('home');

Route::get('invoices/{id}/pdf', [InvoiceController::class, 'generatePdf'])->name('invoice.pdf');
