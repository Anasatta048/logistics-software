<?php

namespace App\Observers;

use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One observer for every audited model (see config/activity.php): it records
 * created / updated / deleted / restored as a sentence plus a field-by-field
 * before→after, so an admin can read the trail without touching the database.
 */
final class ActivityObserver
{
    private const CREATED_FIELDS = 15;

    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly ChangeFormatter $format,
    ) {
    }

    public function created(Model $model): void
    {
        // A record numbered by a second write (loads) is logged when the
        // number lands, so the entry carries its real reference.
        if ($this->label($model) === null) {
            return;
        }

        $this->logCreated($model);
    }

    public function updated(Model $model): void
    {
        $column = $this->meta($model)['label'];

        if ($column !== 'id' && blank($model->getOriginal($column)) && filled($model->getAttribute($column))) {
            $this->logCreated($model);

            return;
        }

        $original = (new $model())->setRawAttributes($model->getOriginal(), true);
        $ignored = config('activity.ignore', []);
        $rows = [];

        foreach (array_keys($model->getChanges()) as $key) {
            if (in_array($key, $ignored, true)) {
                continue;
            }

            $old = $this->format->value($original, $key);
            $new = $this->format->value($model, $key);

            if ($old !== $new) {
                $rows[] = ['field' => $this->format->fieldLabel($key), 'old' => $old, 'new' => $new];
            }
        }

        if (array_key_exists('password', $model->getChanges())) {
            $rows[] = ['field' => 'Password', 'old' => null, 'new' => 'Changed'];
        }

        if ($rows === []) {
            return;
        }

        $shown = collect($rows)
            ->take(3)
            ->map(fn (array $r): string => $r['old'] === null ? "{$r['field']} {$r['new']}" : "{$r['field']}: {$r['old']} → {$r['new']}")
            ->implode('; ');
        $more = count($rows) > 3 ? ' (+'.(count($rows) - 3).' more)' : '';

        $this->write($model, 'updated', "Updated {$this->meta($model)['noun']} {$this->label($model)} — {$shown}{$more}", $rows);
    }

    public function deleted(Model $model): void
    {
        $this->write($model, 'deleted', "Deleted {$this->meta($model)['noun']} {$this->label($model)}");
    }

    public function restored(Model $model): void
    {
        $this->write($model, 'restored', "Restored {$this->meta($model)['noun']} {$this->label($model)}");
    }

    private function logCreated(Model $model): void
    {
        $ignored = config('activity.ignore', []);
        $rows = [];

        foreach ($model->getAttributes() as $key => $raw) {
            if ($raw === null || $raw === '' || in_array($key, $ignored, true)) {
                continue;
            }

            $rows[] = ['field' => $this->format->fieldLabel($key), 'old' => null, 'new' => $this->format->value($model, $key)];

            if (count($rows) >= self::CREATED_FIELDS) {
                break;
            }
        }

        $this->write($model, 'created', "Created {$this->meta($model)['noun']} {$this->label($model)}", $rows);
    }

    /**
     * @param  list<array{field: string, old: ?string, new: ?string}>|null  $rows
     */
    private function write(Model $model, string $action, string $description, ?array $rows = null): void
    {
        $this->logger->log($this->meta($model)['module'], $action, $description, $model, $this->label($model), $rows);
    }

    /**
     * @return array{module: string, noun: string, label: string, prefix?: string}
     */
    private function meta(Model $model): array
    {
        return config('activity.models.'.$model::class);
    }

    private function label(Model $model): ?string
    {
        $meta = $this->meta($model);
        $value = $model->getAttribute($meta['label']);

        if ($value === null || $value === '') {
            return null;
        }

        $text = $meta['label'] === 'file_path' ? Str::afterLast((string) $value, '/') : (string) $value;

        return ($meta['prefix'] ?? '').$text;
    }
}
