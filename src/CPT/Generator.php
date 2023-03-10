<?php

declare(strict_types=1);

namespace Devly\WP\CPT;

use Devly\Utils\SmartObject;
use LogicException;

use function array_merge;

/**
 * @method self use_options(array $options)
 * @method self use_labels(array $labels)
 */
abstract class Generator
{
    use SmartObject;

    public bool $extending = false;
    protected string $name;
    /** @var array<string, mixed>  */
    protected array $options = [];
    protected Columns $columns;

    /**
     * @param string                $name    The post type or taxonomy name to register.
     * @param array<string, mixed>  $options Array of arguments for registering a post
     *                                       type or a taxonomy.
     * @param array<string, string> $labels  An array of labels for the post type or
     *                                       taxonomy.
     *
     * @throws LogicException
     */
    protected function __construct(string $name, array $options = [], array $labels = [])
    {
        $this->name = $name;

        $this->useOptions($this->mergeWithDefaultOptions($options));
        $this->useLabels($labels);

        add_action('init', function (): void {
            $this->save();
        });
    }

    /**
     * Provides default options
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed> merged options.
     */
    abstract protected function mergeWithDefaultOptions(array $options): array;

    /**
     * @param array<string, mixed> $options
     *
     * @return static
     */
    public function useOptions(array $options): self
    {
        $this->options = wp_parse_args($options, $this->options);

        return $this;
    }

    /**
     * An array of labels for this post type.
     *
     * @param array<string, string> $labels
     *
     * @return static
     */
    public function useLabels(array $labels): self
    {
        $this->options['labels'] = wp_parse_args($labels, $this->options['labels'] ?? []);

        return $this;
    }

    /**
     * Get the Column Manager for the object
     */
    public function columns(): Columns
    {
        if (! isset($this->columns)) {
            $this->columns = new Columns();
        }

        return $this->columns;
    }

    /**
     * Modify the columns for the object
     *
     * @param array<string, string> $columns Default WordPress columns
     *
     * @return array<string, string> The modified columns
     */
    protected function modifyColumns(array $columns): array
    {
        return $this->columns->modifyColumns($columns);
    }

    /**
     * Make custom columns sortable
     *
     * @param array<string, mixed> $columns Default WordPress sortable columns
     *
     * @return array<string, mixed>
     */
    protected function setSortableColumns(array $columns): array
    {
        if (! empty($this->columns()->sortable)) {
            $columns = array_merge($columns, $this->columns()->sortable);
        }

        return $columns;
    }

    /** @throws LogicException */
    abstract protected function save(): void;
}
