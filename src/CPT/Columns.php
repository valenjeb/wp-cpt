<?php

declare(strict_types=1);

namespace Devly\WP\CPT;

use Devly\Utils\SmartObject;

use function array_key_exists;
use function array_keys;
use function array_search;
use function array_slice;
use function count;
use function is_array;
use function is_string;
use function str_replace;
use function ucfirst;

/**
 * @method bool is_sortable(string $orderby)
 * @method string|string[] sortable_meta(string $orderby)
 */
class Columns
{
    use SmartObject;

    /**
     * Holds an array of all the defined columns.
     *
     * @var array<string, string>
     */
    public array $items = [];

    /**
     * An array of columns to add.
     *
     * @var array<string, string>
     */
    public array $add = [];

    /**
     * An array of columns to hide.
     *
     * @var string[]
     */
    public array $hide = [];

    /**
     * An array of columns to reposition.
     *
     * @var array<string, int>
     */
    public array $positions = [];

    /**
     * An array of custom populate callbacks.
     *
     * @var array<string, mixed>
     */
    public array $populate = [];

    /**
     * An array of columns that are sortable.
     *
     * @var array<string, mixed>
     */
    public array $sortable = [];

    /**
     * Set the all columns
     *
     * @param array<string, string> $columns an array of all the columns to replace
     */
    public function set(array $columns): void
    {
        $this->items = $columns;
    }

    /**
     * Add a new column
     *
     * @param string|array<T> $columns  The slug of the column or a list of columns.
     * @param string|null     $label    The label for the column or 'null' to use the
     *                                  column slug.
     * @param callable|null   $populate a custom callback to populate a column.
     *
     * @template T of array{column: string, label?: string, position?: int, populate?: callable}
     */
    public function add($columns, ?string $label = null, ?int $position = null, ?callable $populate = null): self
    {
        if (! is_array($columns)) {
            $columns = [
                [
                    'column' => $columns,
                    'label' => $label,
                    'populate' => $populate,
                    'position' => $position,
                ],
            ];
        }

        foreach ($columns as $column) {
            $id       = $column['column'];
            $label    = $column['label'] ?? str_replace(['_', '-'], ' ', ucfirst($id));
            $position = $column['position'] ?? null;
            $callback = $column['populate'] ?? null;

            $this->add[$id] = $label;

            if (! empty($callback)) {
                $this->populate[$id] = $callback;
            }

            if (empty($position)) {
                continue;
            }

            $this->positions[$id] = (int) $position;
        }

        return $this;
    }

    /**
     * Add a column to hide
     *
     * @param string|string[] $columns the slug of the column to die
     */
    public function hide($columns): self
    {
        if (! is_array($columns)) {
            $columns = [$columns];
        }

        foreach ($columns as $column) {
            $this->hide[] = $column;
        }

        return $this;
    }

    /**
     * Set a custom callback to populate a column
     *
     * @param string $column   the column slug
     * @param mixed  $callback callback function
     */
    public function populate(string $column, $callback): self
    {
        $this->populate[$column] = $callback;

        return $this;
    }

    /**
     * Define the position for a columns
     *
     * @param array<string, int> $columns an array of columns
     */
    public function order(array $columns): self
    {
        foreach ($columns as $column => $position) {
            $this->positions[$column] = $position;
        }

        return $this;
    }

    /**
     * Set columns that are sortable
     *
     * @param array<string, mixed> $sortable
     */
    public function sortable(array $sortable): self
    {
        foreach ($sortable as $column => $options) {
            $this->sortable[$column] = $options;
        }

        return $this;
    }

    /**
     * Check if an orderby field is a custom sort option.
     *
     * @param string $orderby the orderby value from query params
     */
    public function isSortable(string $orderby): bool
    {
        if (array_key_exists($orderby, $this->sortable)) {
            return true;
        }

        foreach ($this->sortable as $column => $options) {
            if ($options === $orderby) {
                return true;
            }

            if (is_array($options) && isset($options[0]) && $options[0] === $orderby) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get meta key for an orderby.
     *
     * @param string $orderby the orderby value from query params
     *
     * @return string|array<int, string>
     */
    public function sortableMeta(string $orderby)
    {
        if (array_key_exists($orderby, $this->sortable)) {
            return $this->sortable[$orderby];
        }

        foreach ($this->sortable as $column => $options) {
            if (is_string($options) && $options === $orderby) {
                return $options;
            }

            if (is_array($options) && isset($options[0]) && $options[0] === $orderby) {
                return $options;
            }
        }

        return '';
    }

    /**
     * Modify the columns for the object
     *
     * @param array<string, string> $columns WordPress default columns
     *
     * @return array<string, string> The modified columns
     */
    public function modifyColumns(array $columns): array
    {
        // if user defined set columns, return those
        if (! empty($this->items)) {
            return $this->items;
        }

        // add additional columns
        if (! empty($this->add)) {
            foreach ($this->add as $key => $label) {
                $columns[$key] = $label;
            }
        }

        // unset hidden columns
        if (! empty($this->hide)) {
            foreach ($this->hide as $key) {
                unset($columns[$key]);
            }
        }

        // if user has made added custom columns
        if (! empty($this->positions)) {
            foreach ($this->positions as $key => $position) {
                // find index of the element in the array
                $index = array_search($key, array_keys($columns));
                // retrieve the element in the array of columns
                $item = array_slice($columns, $index, 1);
                // remove item from the array
                unset($columns[$key]);

                // split columns array into two at the desired position
                $start = array_slice($columns, 0, $position, true);
                $end   = array_slice($columns, $position, count($columns) - 1, true);

                // insert column into position
                $columns = $start + $item + $end;
            }
        }

        return $columns;
    }
}
