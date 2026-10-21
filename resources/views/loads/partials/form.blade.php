@php
    $input = 'mt-1 block w-full rounded-input border-border bg-surface text-text-primary shadow-sm focus:border-brand-primary focus:ring-brand-primary dark:bg-surface-dark dark:border-white/10 dark:text-white';
    $select = $input;
    $section = 'text-sm font-semibold text-text-primary dark:text-white';

    $broker = $selected['broker'];
    $company = $selected['company'];
    $driver = $selected['driver'];

    // A broker/driver typed in and not on file yet comes back after a validation error.
    $newBrokerMc = old('new_broker.broker_mc_number');
    $newDriverName = old('new_driver.name');
    $canCommission = auth()->user()->can('view-company-commission');
    $defaultCommission = $company?->default_commission_percentage ?? 0;

    // Read by load-form.js to lock the saved record's details into the fields.
    $brokerData = $broker ? [
        'id' => $broker->id, 'company_name' => $broker->company_name, 'broker_mc_number' => $broker->broker_mc_number,
        'status' => $broker->status->value,
    ] : null;
    $driverData = $driver ? [
        'id' => $driver->id, 'name' => $driver->name, 'phone' => $driver->phone,
        'truck_number' => $driver->truck_number, 'status' => $driver->status->value, 'status_label' => $driver->status->label(),
        'equipment_type' => $driver->equipment_type,
        'open_loads' => $driver->openLoadsSummary($load?->id),
    ] : null;
@endphp

<div class="space-y-5 form-anim"
    id="load-lookups"
    data-broker-search="{{ route('brokers.search') }}"
    data-company-search="{{ route('companies.search') }}"
    data-driver-search="{{ route('drivers.search') }}"
    data-current-load="{{ $load?->id }}">

    {{-- ───────── Load number & dispatcher ───────── --}}
    <section class="form-section">
        <header class="form-section-head">
            <span class="form-section-icon"><x-icon name="hash" :size="18" /></span>
            <div class="min-w-0 flex-1">
                <h3 class="form-section-title"><span class="form-section-step">1</span>{{ __('Load') }}</h3>
                <p class="form-section-desc">{{ __('The load number, and who dispatches it. Picking a dispatcher limits the companies below to theirs.') }}</p>
            </div>
        </header>
        <div class="form-section-body">
            <div>
                <x-input-label for="reference_number" :value="__('Load number')" />
                <input id="reference_number" data-icon="hash" type="text" name="reference_number" maxlength="64" class="{{ $input }}"
                    value="{{ old('reference_number', $load?->reference_number) }}"
                    placeholder="{{ $load ? '' : __('Leave blank to auto-generate (DEL-000123)') }}">
                <x-input-error class="mt-2" :messages="$errors->get('reference_number')" />
            </div>

            {{-- A dispatcher's loads are always their own (LoadService forces it), so only others pick one. --}}
            @unless (auth()->user()->isScopedToOwnRecords())
            <div>
                <x-input-label for="dispatcher_id" :value="__('Dispatcher')" />
                <select id="dispatcher_id" name="dispatcher_id" class="{{ $select }}" data-enhance>
                    <option value="">{{ __('Me') }}</option>
                    @foreach ($dispatchers as $dispatcher)
                        <option value="{{ $dispatcher->id }}"
                            @selected((int) old('dispatcher_id', $load?->dispatcher_id) === $dispatcher->id)>
                            {{ $dispatcher->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('dispatcher_id')" />
            </div>
            @endunless
        </div>
    </section>

    {{-- ───────── Broker ───────── --}}
    <section class="form-section">
        <header class="form-section-head">
            <span class="form-section-icon"><x-icon name="briefcase" :size="18" /></span>
            <div class="min-w-0 flex-1">
                <h3 class="form-section-title"><span class="form-section-step">2</span>{{ __('Broker') }}</h3>
                <p class="form-section-desc">{{ __('Type the MC number. A known broker fills in its company by itself; an unknown one is saved with this load.') }}</p>
            </div>
            <span id="broker-state" class="lookup-chip hidden"></span>
        </header>

        <input type="hidden" name="broker_id" id="broker_id" value="{{ $broker?->id }}">
        <input type="hidden" name="new_broker[broker_mc_number]" id="new_broker_mc" value="{{ $newBrokerMc }}" @disabled(! $newBrokerMc)>

        <div class="form-section-body">
            <div class="sm:col-span-2">
                <x-input-label for="broker_lookup" :value="__('Broker MC Number')" />
                <select id="broker_lookup" class="{{ $select }}" data-placeholder="{{ __('Search by MC number or company name…') }}">
                    <option value=""></option>
                    @if ($broker)
                        <option value="{{ $broker->id }}" selected
                            data-broker="{{ json_encode($brokerData) }}">
                            {{ $broker->broker_mc_number }} — {{ $broker->company_name }}
                        </option>
                    @elseif ($newBrokerMc)
                        <option value="new:{{ $newBrokerMc }}" selected data-new="1">{{ $newBrokerMc }}</option>
                    @endif
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('broker_id')" />
                <x-input-error class="mt-2" :messages="$errors->get('new_broker.broker_mc_number')" />
            </div>

            <div>
                <x-input-label for="broker_company_name" :value="__('Company Name')" />
                <x-text-input id="broker_company_name" data-icon="briefcase" name="new_broker[company_name]" type="text" class="block w-full"
                    :value="old('new_broker.company_name', $broker?->company_name)" data-broker-field="company_name" />
                <x-input-error class="mt-2" :messages="$errors->get('new_broker.company_name')" />
            </div>
            <div class="sm:col-span-2 border-t border-border/60 pt-4 dark:border-white/10">
                <p class="text-xs text-text-muted">{{ __('Who to contact at this broker for this load — it can differ on every load.') }}</p>
            </div>
            <div>
                <x-input-label for="broker_contact_person" :value="__('Contact Person')" />
                <x-text-input id="broker_contact_person" data-icon="user" name="broker_contact_person" type="text" class="block w-full"
                    :value="old('broker_contact_person', $load?->broker_contact_person)" />
                <x-input-error class="mt-2" :messages="$errors->get('broker_contact_person')" />
            </div>
            <div>
                <x-input-label for="broker_phone" :value="__('Contact Phone')" />
                <x-text-input id="broker_phone" data-icon="phone" name="broker_phone" type="tel" class="block w-full"
                    :value="old('broker_phone', $load?->broker_phone)" required />
                <x-input-error class="mt-2" :messages="$errors->get('broker_phone')" />
            </div>
            <div>
                <x-input-label for="broker_email" :value="__('Contact Email')" />
                <x-text-input id="broker_email" data-icon="mail" name="broker_email" type="email" class="block w-full"
                    :value="old('broker_email', $load?->broker_email)" />
                <x-input-error class="mt-2" :messages="$errors->get('broker_email')" />
            </div>
        </div>
    </section>

    {{-- ───────── Carrier: company, driver, equipment ───────── --}}
    <section class="form-section">
        <header class="form-section-head">
            <span class="form-section-icon"><x-icon name="truck" :size="18" /></span>
            <div class="min-w-0 flex-1">
                <h3 class="form-section-title"><span class="form-section-step">3</span>{{ __('Carrier') }}</h3>
                <p class="form-section-desc">{{ __('Pick the driver first — the company and equipment type follow. A driver not on file can be added right here.') }}</p>
            </div>
            
        </header>
        <div class="form-section-body">
            <div class="sm:col-span-2">
                <div class="flex items-center justify-between gap-3">
                    <x-input-label for="driver_lookup" :value="__('Driver (name or phone)')" />
                    <span id="driver-state" class="lookup-chip hidden"></span>
                </div>

                <input type="hidden" name="driver_id" id="driver_id" value="{{ $driver?->id }}">
                <input type="hidden" name="new_driver[name]" id="new_driver_name" value="{{ $newDriverName }}" @disabled(! $newDriverName)>

                <select id="driver_lookup" class="{{ $select }}" data-placeholder="{{ __('Search driver by name or phone…') }}">
                    <option value=""></option>
                    @if ($driver)
                        <option value="{{ $driver->id }}" selected
                            data-driver="{{ json_encode($driverData) }}">
                            {{ $driver->name }} — {{ $driver->phone }}
                        </option>
                    @elseif ($newDriverName)
                        <option value="new:{{ $newDriverName }}" selected data-new="1">{{ $newDriverName }}</option>
                    @endif
                </select>
                <p id="driver-truck-info" class="mt-1 text-xs font-medium text-text-primary dark:text-white {{ $driver && ($driver->truck_length_ft || $driver->truck_max_weight_lbs) ? '' : 'hidden' }}">
                    @if ($driver)
                        {{ collect([
                            $driver->truck_length_ft || $driver->truck_width_ft || $driver->truck_height_ft
                                ? 'Truck '.collect([$driver->truck_length_ft, $driver->truck_width_ft, $driver->truck_height_ft])->map(fn ($d) => $d === null ? '—' : (float) $d)->implode(' × ').' ft'
                                : null,
                            $driver->truck_max_weight_lbs ? 'max '.number_format((float) $driver->truck_max_weight_lbs).' lbs' : null,
                        ])->filter()->implode(' · ') }}
                    @endif
                </p>
                <details id="driver-loads" class="mt-2 hidden rounded-input border border-border/70 bg-bg/40 text-sm dark:border-white/10 dark:bg-white/5">
                    <summary class="cursor-pointer select-none px-3 py-2 text-xs font-semibold text-text-primary dark:text-white">
                        <span id="driver-loads-title"></span>
                    </summary>
                    <ul id="driver-loads-list" class="divide-y divide-border/60 border-t border-border/60 dark:divide-white/10 dark:border-white/10"></ul>
                </details>
                <p id="driver-hint" class="mt-1 text-xs text-text-muted">{{ __('Search active drivers, or type a new name to add one.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('driver_id')" />
                <x-input-error class="mt-2" :messages="$errors->get('new_driver.name')" />

                @if ($load && ! $load->driver_id && $load->driver_name)
                    <p class="mt-1 text-xs text-warning">
                        {{ __('Previously entered as plain text: “:name”. Search or add the driver to link the record.', ['name' => $load->driver_name]) }}
                    </p>
                @endif
            </div>

            <div>
                <x-input-label for="driver_phone" :value="__('Driver Phone')" />
                <x-text-input id="driver_phone" data-icon="phone" name="new_driver[phone]" type="tel" class="block w-full"
                    :value="old('new_driver.phone', $driver?->phone)" data-driver-field="phone" disabled />
                <x-input-error class="mt-2" :messages="$errors->get('new_driver.phone')" />
            </div>
            <div>
                <x-input-label for="driver_truck" :value="__('Truck Number')" />
                <x-text-input id="driver_truck" data-icon="hash" name="new_driver[truck_number]" type="text" class="block w-full"
                    :value="old('new_driver.truck_number', $driver?->truck_number)" data-driver-field="truck_number" disabled />
                <x-input-error class="mt-2" :messages="$errors->get('new_driver.truck_number')" />
            </div>

            <div>
                <x-input-label for="company_id" :value="__('Company (name or MC number)')" />
                <select id="company_id" name="company_id" required class="{{ $select }}" data-placeholder="{{ __('Search company or MC number…') }}">
                    <option value=""></option>
                    @if ($company)
                        <option value="{{ $company->id }}" selected data-commission="{{ $canCommission ? $company->default_commission_percentage : '' }}">
                            {{ $company->name }} ({{ $company->mc_number }})
                        </option>
                    @endif
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('company_id')" />
            </div>

            <div>
                <x-input-label for="equipment_type" :value="__('Equipment Type')" />
                <select id="equipment_type" name="equipment_type" required class="{{ $select }}" data-enhance>
                    @foreach ($equipmentTypes as $equipmentType)
                        <option value="{{ $equipmentType->name }}"
                            @selected(old('equipment_type', $load?->equipment_type) === $equipmentType->name)>
                            {{ $equipmentType->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('equipment_type')" />
            </div>

        </div>
    </section>

    {{-- ───────── Route ───────── --}}
    <section class="form-section">
        <header class="form-section-head">
            <span class="form-section-icon"><x-icon name="map" :size="18" /></span>
            <div class="min-w-0 flex-1">
                <h3 class="form-section-title"><span class="form-section-step">4</span>{{ __('Route') }}</h3>
                <p class="form-section-desc">{{ __('Where the load picks up and where it delivers. Enter the total miles below (the rate confirmation shows them).') }}</p>
            </div>
            
        </header>
        <div class="space-y-5 p-5">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <x-input-label for="origin_address" :value="__('Origin')" />
                <x-text-input id="origin_address" data-icon="map" name="origin_address" type="text" class="mt-1 block w-full" maxlength="255" required
                    :value="old('origin_address', $load?->origin_address)" placeholder="{{ __('Street, city, state ZIP') }}" autocomplete="off" />
                <x-input-error class="mt-2" :messages="$errors->get('origin_address')" />
            </div>
            <div>
                <x-input-label for="destination_address" :value="__('Destination')" />
                <x-text-input id="destination_address" data-icon="map" name="destination_address" type="text" class="mt-1 block w-full" maxlength="255" required
                    :value="old('destination_address', $load?->destination_address)" placeholder="{{ __('Street, city, state ZIP') }}" autocomplete="off" />
                <x-input-error class="mt-2" :messages="$errors->get('destination_address')" />
            </div>
        </div>
        </div>
    </section>

    {{-- ───────── Schedule & Rate ───────── --}}
    <section class="form-section">
        <header class="form-section-head">
            <span class="form-section-icon"><x-icon name="calendar" :size="18" /></span>
            <div class="min-w-0 flex-1">
                <h3 class="form-section-title"><span class="form-section-step">5</span>{{ __('Schedule & Rate') }}</h3>
                <p class="form-section-desc">{{ __('When it moves, how heavy it is, and what it pays.') }}</p>
            </div>
            
        </header>
        <div class="form-section-body">
            @foreach (['pickup' => __('Pickup'), 'dropoff' => __('Dropoff')] as $stage => $stageLabel)
                @php
                    $zone = old("{$stage}_timezone", $load?->{"{$stage}_timezone"} ?? \App\Support\Timezones::us());
                @endphp
                <div class="space-y-3">
                    <div>
                        <x-input-label :for="$stage . '_timezone'" :value="$stageLabel . ' ' . __('Timezone')" />
                        <select id="{{ $stage }}_timezone" data-icon="globe" name="{{ $stage }}_timezone"
                            class="mt-1 block w-full rounded-input border-border bg-surface text-text-primary shadow-sm focus:border-brand-primary focus:ring-brand-primary dark:bg-surface-dark dark:border-white/10 dark:text-white">
                            @foreach (\App\Support\Timezones::usOptions() as $id => $name)
                                <option value="{{ $id }}" @selected($zone === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get($stage . '_timezone')" />
                    </div>

                    <div>
                        <x-input-label :for="$stage . '_at'" :value="$stageLabel . ' ' . __('At') . ' (' . __('local time in the zone above') . ')'" />
                        <x-text-input :id="$stage . '_at'" data-icon="calendar" :name="$stage . '_at'" type="datetime-local" class="mt-1 block w-full"
                            :value="old($stage . '_at', \App\Support\Timezones::inputValue($load?->{$stage . '_at'}, $load?->{$stage . '_timezone'}))" required
                            data-tz-select="{{ $stage }}_timezone" data-tz-targets="{{ json_encode(\App\Support\Timezones::zones()) }}" />
                        <p class="mt-1 text-xs text-text-muted" data-tz-preview="{{ $stage }}_at"></p>
                        <x-input-error class="mt-2" :messages="$errors->get($stage . '_at')" />
                    </div>
                </div>
            @endforeach

            <div>
                <x-input-label for="total_miles" :value="__('Total Miles')" />
                <x-text-input id="total_miles" data-icon="map" name="total_miles" type="number" step="any" min="0"
                    class="mt-1 block w-full" x-ref="miles" :value="old('total_miles', $load?->total_miles)"
                    required />
                <x-input-error class="mt-2" :messages="$errors->get('total_miles')" />
            </div>

            <div>
                <x-input-label for="weight_lbs" :value="__('Weight (lbs)')" />
                <x-text-input id="weight_lbs" data-icon="package" name="weight_lbs" type="number" step="any" min="0"
                    class="mt-1 block w-full" :value="old('weight_lbs', $load?->weight_lbs)" required />
                <x-input-error class="mt-2" :messages="$errors->get('weight_lbs')" />
            </div>

            <div class="sm:col-span-2">
                <p class="text-sm font-medium text-text-primary dark:text-white">{{ __('Load dimensions') }}</p>
                <div class="mt-1 grid grid-cols-3 gap-4">
                    @foreach (['length_ft' => 'Length (ft)', 'width_ft' => 'Width (ft)', 'height_ft' => 'Height (ft)'] as $field => $label)
                        <div>
                            <x-input-label :for="$field" :value="__($label)" />
                            <x-text-input :id="$field" data-icon="package" :name="$field" type="number" step="any" min="0"
                                class="mt-1 block w-full" :value="old($field, $load?->{$field})" />
                            <x-input-error class="mt-2" :messages="$errors->get($field)" />
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <x-input-label for="rate" :value="__('Rate')" />
                <x-text-input id="rate" data-icon="dollar" name="rate" type="number" step="0.01" min="0" class="mt-1 block w-full"
                    :value="old('rate', $load?->rate)" required />
                <x-input-error class="mt-2" :messages="$errors->get('rate')" />
            </div>

            {{-- No commission input: it comes from the company. This unnamed (never submitted)
                 field only feeds the live estimate in the summary, for people who may see it. --}}
            @can('view-company-commission')
                <input type="hidden" id="company_commission_percentage" value="{{ $load?->company_commission_percentage ?? $defaultCommission }}">
            @endcan
        </div>
    </section>

    {{-- ───────── Assignment & notes ───────── --}}
    <section class="form-section">
        <header class="form-section-head">
            <span class="form-section-icon"><x-icon name="note" :size="18" /></span>
            <div class="min-w-0 flex-1">
                <h3 class="form-section-title"><span class="form-section-step">6</span>{{ __('Assignment & Notes') }}</h3>
                <p class="form-section-desc">{{ __('Who dispatches it, and anything the team should know.') }}</p>
            </div>
            
        </header>
        <div class="form-section-body">

            <div class="sm:col-span-2">
                <x-input-label for="notes" :value="__('Notes')" />
                <textarea id="notes" data-icon="note" name="notes" rows="3" class="{{ $input }}">{{ old('notes', $load?->notes) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('notes')" />
            </div>
        </div>
    </section>

    <x-custom-fields entity="load" :model="$load" :step="7" />
</div>
