<x-filament-panels::page>
    <div class="container mx-auto">
        <!-- Details Card -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Invoice Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-600 mb-1">Name</label>
                    <span class="text-gray-900">{{ $this->record->name }}</span>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-600 mb-1">Client Names</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->record->invoiceClients as $client)
                            <span
                                class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">{{ $client->name }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-600 mb-1">Invoice Number</label>
                    <span class="text-gray-900">INV-{{ $this->record->invoice_number }}</span>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-600 mb-1">Projects</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->record->invoiceProjects as $project)
                            <span
                                class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">{{ $project->name }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-600 mb-1">Issue Date</label>
                    <span
                        class="text-gray-900">{{ $this->record->issue_date ? $this->record->issue_date->format('F j, Y') : '-' }}</span>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-600 mb-1">Due Date</label>
                    <span
                        class="text-gray-900">{{ $this->record->due_date ? $this->record->due_date->format('F j, Y') : 'N/A' }}</span>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-600 mb-1">Tax</label>
                    <span class="text-gray-900">
                        @if ($this->record->tax)
                            {{ $this->record->tax->name }} ({{ $this->record->tax->tax }}%)
                        @else
                            N/A
                        @endif
                    </span>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-600 mb-1">Created On</label>
                    <span
                        class="text-gray-900">{{ $this->record->created_at ? $this->record->created_at->format('F j, Y') : '-' }}</span>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-600 mb-1">Status</label>
                    <span
                        class="@if ($this->record->status == \App\Models\Invoice::STATUS_PAID) text-green-600 @elseif($this->record->status == \App\Models\Invoice::STATUS_SENT) text-yellow-600 @else text-gray-600 @endif font-medium">
                        {{ $this->record->status_text }}
                    </span>
                </div>
                <div class="flex flex-col md:col-span-2">
                    <label class="text-sm font-medium text-gray-600 mb-1">Notes</label>
                    <span class="text-gray-900">{{ $this->record->notes ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Tasks Card -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Tasks</h2>
            <div class="overflow-x-auto">
                <table class="w-full table-auto border-collapse border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border border-gray-300 px-4 py-2 text-left font-semibold text-gray-700">Task
                                Title</th>
                            <th class="border border-gray-300 px-4 py-2 text-left font-semibold text-gray-700">Hours
                            </th>
                            <th class="border border-gray-300 px-4 py-2 text-left font-semibold text-gray-700">Task
                                Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->record->invoiceItems as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="border border-gray-300 font-semibold px-4 py-2">
                                    {{ $item->item_name }}

                                    @php
                                        $projectName = '-';
                                        if ($item->item_project_id) {
                                            $project = \App\Models\Project::find($item->item_project_id);
                                            $projectName = $project ? $project->name : '-';
                                        } elseif ($item->task && $item->task->project) {
                                            $projectName = $item->task->project->name;
                                        }
                                    @endphp

                                    <span class="text-sm text-gray-500">({{ $projectName }})</span>
                                </td>

                                <td class="border border-gray-300 px-4 py-2">
                                    <span class="inline-flex items-center rounded-md px-1 py-1 text-md font-semibold">
                                        @php
                                            $decimal = floatval($item->hours);
                                            $fullHours = floor($decimal);
                                            $minutes = round(($decimal - $fullHours) * 60);
                                        @endphp

                                        @if ($fullHours > 0 && $minutes > 0)
                                            {{ $fullHours }} hr {{ $minutes }} min
                                        @elseif ($fullHours > 0)
                                            {{ $fullHours }} hr
                                        @else
                                            {{ $minutes }} min
                                        @endif
                                    </span>
                                </td>
                                <td class="border border-gray-300 px-4 py-2 font-semibold">
                                    @php
                                        // Get currency symbol based on project
                                        $currencySymbol = '$'; // Default
                                        if ($item->item_project_id) {
                                            $project = \App\Models\Project::find($item->item_project_id);
                                            if ($project) {
                                                $currencySymbol = \App\Models\Project::getCurrencyClass(
                                                    $project->currency,
                                                );
                                            }
                                        }
                                    @endphp
                                    {{ $currencySymbol }} {{ number_format($item->task_amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="border border-gray-300 px-4 py-2 text-center text-gray-500">No
                                    tasks found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary Card -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Summary</h2>
            <div class="text-right space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-700">Sub Total:</span>
                    <span class="text-gray-900">
                        @php
                            $currencySymbol = '$'; // Default
                            if ($this->record->invoiceProjects->first()) {
                                $project = $this->record->invoiceProjects->first();
                                $currencySymbol = \App\Models\Project::getCurrencyClass($project->currency);
                            }
                        @endphp
                        {{ $currencySymbol }} {{ number_format($this->record->sub_total, 2) }}
                    </span>
                </div>
                @if ($this->record->tax && $this->record->tax->tax > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-700">Tax ({{ $this->record->tax->tax }}%):</span>
                        <span class="text-gray-900">
                            {{ $currencySymbol }}
                            {{ number_format(($this->record->sub_total * $this->record->tax->tax) / 100, 2) }}
                        </span>
                    </div>
                @endif
                @if ($this->record->discount > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-700">Discount ({{ $this->record->discount }}%):</span>
                        <span class="text-gray-900">
                            {{ $currencySymbol }}
                            {{ number_format(($this->record->sub_total * $this->record->discount) / 100, 2) }}
                        </span>
                    </div>
                @endif
                <div class="flex justify-between border-t border-gray-300 pt-2">
                    <span class="text-lg font-bold text-blue-600">Total:</span>
                    <span class="text-lg font-bold text-blue-600">{{ $currencySymbol }}
                        {{ number_format($this->record->amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</x-filament-panels::page>
