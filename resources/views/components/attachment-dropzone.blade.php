{{--
    Drag-and-drop file picker for load documents. Posts `attachments[]` (files)
    and `attachment_types[]` (one type per file, same order); the real file
    input is kept in sync through DataTransfer so a plain form submit carries
    everything, with no upload JS.
--}}
<div x-data="{
        items: [],
        over: false,
        types: @js(collect(App\Enums\AttachmentType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()])->values()),
        add(fileList) {
            for (const file of fileList) {
                if (file.size > 5 * 1024 * 1024) { alert(file.name + ' is larger than 5 MB.'); continue; }
                this.items.push({ file, type: this.types[0].value });
            }
            this.sync();
        },
        remove(i) { this.items.splice(i, 1); this.sync(); },
        sync() {
            const dt = new DataTransfer();
            this.items.forEach(item => dt.items.add(item.file));
            this.$refs.input.files = dt.files;
        },
        size(bytes) { return bytes > 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB'; },
    }" {{ $attributes->merge(['class' => 'space-y-3']) }}>

    <div x-on:dragover.prevent="over = true" x-on:dragleave.prevent="over = false"
        x-on:drop.prevent="over = false; add($event.dataTransfer.files)"
        x-on:click="$refs.input.click()"
        x-bind:class="over ? 'border-brand-primary bg-brand-primary/5' : 'border-border dark:border-white/15'"
        class="cursor-pointer rounded-card border-2 border-dashed px-6 py-8 text-center transition-colors">
        <x-icon name="file-text" :size="28" class="mx-auto text-text-muted" />
        <p class="mt-2 text-sm font-medium text-text-primary dark:text-white">
            {{ __('Drag & drop documents here, or click to browse') }}
        </p>
        <p class="mt-1 text-xs text-text-muted">{{ __('PDF, JPG or PNG — up to 5 MB each') }}</p>
        <input x-ref="input" type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="hidden"
            x-on:click.stop x-on:change="items = []; add($event.target.files)">
    </div>

    <ul class="space-y-2" x-show="items.length" x-cloak>
        <template x-for="(item, i) in items" x-bind:key="i">
            <li class="flex flex-wrap items-center gap-3 rounded-input border border-border/60 dark:border-white/10 px-3 py-2 text-sm">
                <span class="min-w-0 flex-1 truncate text-text-primary dark:text-white" x-text="item.file.name"></span>
                <span class="text-xs text-text-muted" x-text="size(item.file.size)"></span>
                <select name="attachment_types[]" x-model="item.type"
                    class="rounded-input border-border bg-surface py-1 text-sm text-text-primary shadow-sm focus:border-brand-primary focus:ring-brand-primary dark:bg-surface-dark dark:border-white/10 dark:text-white">
                    <template x-for="t in types" x-bind:key="t.value">
                        <option x-bind:value="t.value" x-text="t.label"></option>
                    </template>
                </select>
                <button type="button" x-on:click="remove(i)" class="text-status-cancelled hover:underline" aria-label="{{ __('Remove') }}">&times;</button>
            </li>
        </template>
    </ul>

    <x-input-error :messages="$errors->get('attachments')" />
    <x-input-error :messages="collect($errors->get('attachments.*'))->flatten()->all()" />
    <x-input-error :messages="collect($errors->get('attachment_types.*'))->flatten()->all()" />
</div>
