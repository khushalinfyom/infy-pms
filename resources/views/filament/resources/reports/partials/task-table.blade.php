<div class="fi-section rounded-lg  bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">

    <div class="overflow-hidden rounded-lg ">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-800">
                <tr>
                    <th class="px-6 py-4 text-left text-lg font-semibold uppercase text-gray-700 dark:text-gray-300">
                        Task
                    </th>
                    <th class="px-6 py-4 text-left text-lg font-semibold uppercase text-gray-700 dark:text-gray-300">
                        Hours
                    </th>
                    <th class="px-6 py-4 text-left text-lg font-semibold uppercase text-gray-700 dark:text-gray-300">
                        Amount
                    </th>
                </tr>
            </thead>


            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse ($rows as $index => $row)
                    <tr
                        class="transition-colors duration-150 hover:bg-blue-50 dark:hover:bg-gray-800/50 {{ $index % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-800' }}">

                        <td class="px-6 py-4">
                            <span class="text-md font-medium text-gray-900 dark:text-white">
                                {{ $row['task'] }} ( {{ $row['project'] }} )
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-flex items-center rounded-md px-3 py-1 text-md font-semibold">
                                {{ $row['duration'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-md px-3 py-1 text-md font-semibold">
                                    {{ $row['currency_symbol'] }} {{ number_format($row['amount'], 2) }}
                                </span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="mb-4 h-12 w-12 text-gray-400 dark:text-gray-500" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400">
                                    {{ __('No tasks found for this report') }}
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (!empty($rows))
        <div class="mt-4 flex justify-end">
            <div
                class="w-full md:w-1/2 lg:w-1/3 rounded-xl " style="margin-left: 1000px">

                <div class="px-6 py-5 space-y-4">

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Total Hours</span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ isset($invoiceSummary) ? round($invoiceSummary['total_hours'], 2) : 0 }} hrs
                        </span>
                    </div>

                    <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $invoiceSummary['currency_symbol'] ?? '' }}{{ isset($invoiceSummary) ? number_format($invoiceSummary['subtotal'], 2) : '0.00' }}
                        </span>
                    </div>

                    @if (isset($invoiceSummary) && $invoiceSummary['tax_rate'] > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                Tax ({{ $invoiceSummary['tax_rate'] }}%)
                            </span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $invoiceSummary['currency_symbol'] }}{{ number_format($invoiceSummary['tax_amount'], 2) }}
                            </span>
                        </div>
                    @endif

                    @if (isset($invoiceSummary) && $invoiceSummary['discount_value'] > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                Discount ({{ $invoiceSummary['discount_value'] }}%)
                            </span>
                            <span class="text-lg font-semibold text-red-600 dark:text-red-400">
                                {{ $invoiceSummary['currency_symbol'] }}{{ number_format($invoiceSummary['discount_amount'], 2) }}
                            </span>
                        </div>
                    @endif
                </div>

                <div
                    class="border-t border-gray-300 px-6 py-4 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 rounded-b-xl">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-bold text-gray-900 dark:text-white">Total Amount</span>
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ $invoiceSummary['currency_symbol'] ?? '' }}{{ isset($invoiceSummary) ? number_format($invoiceSummary['total'], 2) : '0.00' }}
                        </span>
                    </div>
                </div>

            </div>
        </div>
    @endif

</div>
