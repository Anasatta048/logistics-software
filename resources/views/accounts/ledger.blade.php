<x-app-layout>
    <x-slot name="header">{{ __('Ledger') }} — {{ $account->name }}</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <p class="text-sm text-gray-600">
                        {{ __('Current balance:') }} <span class="font-semibold">{{ number_format((float) $account->current_balance, 2) }}</span>
                    </p>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-3 py-2">Date</th>
                                    <th class="px-3 py-2">Type</th>
                                    <th class="px-3 py-2">Amount</th>
                                    <th class="px-3 py-2">Running Balance</th>
                                    <th class="px-3 py-2">Source</th>
                                    <th class="px-3 py-2">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($transactions as $tx)
                                    <tr>
                                        <td class="px-3 py-2"><x-datetime :value="$tx->created_at" /></td>
                                        <td class="px-3 py-2">{{ $tx->type->label() }}</td>
                                        <td class="px-3 py-2">{{ number_format((float) $tx->amount, 2) }}</td>
                                        <td class="px-3 py-2">{{ number_format((float) $tx->running_balance, 2) }}</td>
                                        <td class="px-3 py-2">{{ $tx->source_type->value }} #{{ $tx->source_id }}</td>
                                        <td class="px-3 py-2">{{ $tx->description }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-3 py-4 text-gray-500" colspan="6">{{ __('No transactions yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <a href="{{ route('accounts.index') }}" class="text-sm text-gray-600 hover:underline">{{ __('Back to accounts') }}</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
