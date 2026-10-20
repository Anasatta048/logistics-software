<?php

namespace App\Support;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

/**
 * Resolves config/navigation.php into the list the currently authenticated
 * user is actually allowed to see, evaluating each item's gate once. Used by
 * the sidebar, topbar and mobile drawer (UI_UX_DESIGN_PLAN.md Section 2.2) so
 * they never duplicate the @can checks themselves.
 */
final class Navigation
{
    /**
     * @return array<int, array{label: ?string, open: bool, items: array<int, array<string, mixed>>}>
     */
    public static function forCurrentUser(): array
    {
        $groups = [];

        foreach (config('navigation.groups', []) as $group) {
            $items = [];

            foreach ($group['items'] as $item) {
                if (! Route::has($item['route']) || ! self::allowed($item['gate'])) {
                    continue;
                }

                // `super-admin.*` holds several modules, so there the prefix is two segments.
                $parts = explode('.', $item['route']);
                $prefix = $parts[0] === 'super-admin' ? $parts[0].'.'.$parts[1] : $parts[0];
                $children = self::children($item['children'] ?? [], $prefix);

                $items[] = [
                    'label' => $item['label'],
                    'route' => $item['route'],
                    'icon' => $item['icon'],
                    'active' => request()->routeIs($prefix.'.*') || request()->routeIs($item['route']),
                    // A single "View all" child adds nothing; only real submenus show.
                    'children' => count($children) > 1 ? $children : [],
                ];
            }

            if ($items === []) {
                continue;
            }

            // Groups without a label (Dashboard) are always shown; for the
            // rest, `open` marks the group holding the page you are on.
            $groups[] = [
                'label' => $group['label'],
                'open' => $group['label'] === null
                    || array_filter($items, fn (array $item): bool => $item['active']) !== [],
                'items' => $items,
            ];
        }

        return $groups;
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     * @return array<int, array{label: string, route: string, active: bool, add: bool}>
     */
    private static function children(array $children, string $prefix): array
    {
        $resolved = [];

        foreach ($children as $child) {
            if (! Route::has($child['route']) || ! self::allowed($child['gate'] ?? null)) {
                continue;
            }

            $isAdd = str_ends_with($child['route'], '.create');

            $resolved[] = [
                'label' => $child['label'],
                'route' => $child['route'],
                'add' => $isAdd,
                // "Add" is active only on its own form; the list covers show/edit pages too.
                'active' => $isAdd
                    ? request()->routeIs($child['route'])
                    : request()->routeIs($prefix.'.*') && ! request()->routeIs($prefix.'.create'),
            ];
        }

        return $resolved;
    }

    private static function allowed(null|string|array $gate): bool
    {
        if ($gate === null) {
            return true;
        }

        if (is_array($gate)) {
            [$ability, $model] = $gate;

            return Gate::allows($ability, $model);
        }

        return Gate::allows($gate);
    }
}
