@props(['entity', 'model' => null, 'step' => null])
{{-- The company's own extra fields for a load / driver / carrier, drawn from its definitions. --}}
@php($defs = \App\Services\Support\CustomFields::for($entity))
@if ($defs->isNotEmpty())
    <x-form-section :title="__('More details')" icon="layers" :description="__('Fields added by your company in Business settings.')" :step="$step">
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($defs as $f)
                @php($value = old("custom.{$f->key}", $model?->custom[$f->key] ?? null))
                <x-field :name="'custom.'.$f->key" :label="$f->label" :required="$f->is_required && $f->type !== 'checkbox'">
                    @switch($f->type)
                        @case('number')
                            <input id="custom_{{ $f->key }}" name="custom[{{ $f->key }}]" type="number" step="any" value="{{ $value }}" @required($f->is_required)>
                            @break
                        @case('date')
                            <input id="custom_{{ $f->key }}" name="custom[{{ $f->key }}]" type="date" value="{{ $value }}" @required($f->is_required)>
                            @break
                        @case('select')
                            <select id="custom_{{ $f->key }}" name="custom[{{ $f->key }}]" @required($f->is_required)>
                                <option value="">{{ __('Select…') }}</option>
                                @foreach ($f->options ?? [] as $option)
                                    <option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @break
                        @case('checkbox')
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="custom[{{ $f->key }}]" value="1" class="text-brand-primary focus:ring-brand-primary" @checked($value)> {{ __('Yes') }}</label>
                            @break
                        @default
                            <input id="custom_{{ $f->key }}" name="custom[{{ $f->key }}]" type="text" maxlength="255" value="{{ $value }}" @required($f->is_required)>
                    @endswitch
                </x-field>
            @endforeach
        </div>
    </x-form-section>
@endif
