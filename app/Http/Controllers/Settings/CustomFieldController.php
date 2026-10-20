<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Settings -> Custom fields: extra details a company wants on loads, drivers and carriers. */
final class CustomFieldController extends Controller
{
    private const MAX_PER_ENTITY = 25;

    public function index(): View
    {
        return view('settings.custom-fields', [
            'fields' => CustomField::query()->orderBy('entity')->orderBy('sort_order')->orderBy('id')->get()->groupBy('entity'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'entity' => ['required', Rule::in(array_keys(CustomField::ENTITIES))],
            'label' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::in(array_keys(CustomField::TYPES))],
            'options' => ['nullable', 'string', 'max:1000', 'required_if:type,select'],
            'is_required' => ['nullable', 'boolean'],
            'show_in_list' => ['nullable', 'boolean'],
        ]);

        if (CustomField::query()->where('entity', $data['entity'])->count() >= self::MAX_PER_ENTITY) {
            return back()->withErrors(['label' => 'That is the most custom fields one screen can carry ('.self::MAX_PER_ENTITY.').'])->withInput();
        }

        CustomField::create([
            'entity' => $data['entity'],
            'label' => $data['label'],
            'key' => $this->uniqueKey($data['entity'], $data['label']),
            'type' => $data['type'],
            'options' => $data['type'] === 'select' ? $this->options($data['options']) : null,
            'is_required' => $request->boolean('is_required'),
            'show_in_list' => $request->boolean('show_in_list'),
            'sort_order' => (int) CustomField::query()->where('entity', $data['entity'])->max('sort_order') + 1,
        ]);

        return back()->with('success', "Field {$data['label']} added.");
    }

    public function update(Request $request, CustomField $customField): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'options' => ['nullable', 'string', 'max:1000'],
        ]);

        // The key and type stay: they are how saved values are found and read.
        $customField->update([
            'label' => $data['label'],
            'options' => $customField->type === 'select' ? $this->options($data['options'] ?? '') : null,
            'is_required' => $request->boolean('is_required'),
            'show_in_list' => $request->boolean('show_in_list'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Field saved.');
    }

    public function destroy(CustomField $customField): RedirectResponse
    {
        // Hiding keeps the values already saved on records; deleting only drops the definition.
        $customField->delete();

        return back()->with('success', 'Field removed. Values already saved stay on their records but are no longer shown.');
    }

    private function uniqueKey(string $entity, string $label): string
    {
        $base = Str::slug($label, '_') ?: 'field';
        $key = $base;

        for ($i = 2; CustomField::query()->where('entity', $entity)->where('key', $key)->exists(); $i++) {
            $key = "{$base}_{$i}";
        }

        return $key;
    }

    /** @return list<string> */
    private function options(string $raw): array
    {
        return collect(preg_split('/\R|,/', $raw))->map(fn ($o) => trim($o))->filter()->unique()->values()->take(30)->all();
    }
}
