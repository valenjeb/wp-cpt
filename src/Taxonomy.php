<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Devly\WP;

use Devly\WP\CPT\Generator;
use LogicException;
use WP_Query;
use WP_Term_Query;

use function array_key_exists;
use function call_user_func_array;
use function func_get_args;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function str_replace;
use function ucwords;

/**
 * @method self associated_objects(string|string[] $post_types) Assign a PostType to register the Taxonomy to
 * @method self publicly_queryable(bool $queryable = true) Whether queries can be performed on the front end for the post type as part of parse_request().
 * @method self show_ui(bool $visible = true) Whether to generate a default UI for managing this taxonomy.
 * @method self show_in_menu(bool $show = true) Where to show the taxonomy in the admin menu. show_ui must be true.
 * @method self show_in_nav_menus(bool $show = true) Makes this taxonomy available for selection in navigation menus.
 * @method self show_in_rest(bool $show = true)
 * @method self rest_base(string $base)
 * @method self rest_namespace(string $namespace)
 * @method self rest_controller_class(string $class)
 * @method self show_tagcloud(bool $show = true)
 * @method self show_in_quick_edit(bool $show = true)
 * @method self show_admin_column(bool $show = true)
 * @method self meta_box_cb(bool|callable $cb)
 * @method self meta_box_sanitize_cb(callable $cb)
 * @method self query_var(bool|string $query = true)
 * @method self update_count_callback(string $update)
 * @method self default_term(string|array $term)
 */
class Taxonomy extends Generator
{
    public const LABEL_NAME                     = 'name';
    public const LABEL_SINGULAR_NAME            = 'singular_name';
    public const LABEL_MENU_NAME                = 'menu_name';
    public const LABEL_SEARCH_ITEMS             = 'search_items';
    public const LABEL_POPULAR_ITEMS            = 'popular_items';
    public const LABEL_ALL_ITEMS                = 'all_items';
    public const LABEL_PARENT_ITEM              = 'parent_item';
    public const LABEL_PARENT_ITEM_COLON        = 'parent_item_colon';
    public const LABEL_NAME_FIELD_DESCRIPTION   = 'name_field_description';
    public const LABEL_SLUG_FIELD_DESCRIPTION   = 'slug_field_description';
    public const LABEL_PARENT_FIELD_DESCRIPTION = 'parent_field_description';
    public const LABEL_DESC_FIELD_DESCRIPTION   = 'desc_field_description';
    public const LABEL_EDIT_ITEM                = 'edit_item';
    public const LABEL_VIEW_ITEM                = 'view_item';
    public const LABEL_UPDATE_ITEM              = 'update_item';
    public const LABEL_ADD_NEW_ITEM             = 'add_new_item';
    public const LABEL_NEW_ITEM_NAME            = 'new_item_name';
    public const LABEL_SPREAD_ITEMS_WITH_COMMAS = 'separate_items_with_commas';
    public const LABEL_ADD_OR_REMOVE_ITEMS      = 'add_or_remove_items';
    public const LABEL_CHOOSE_FROM_MOST_USED    = 'choose_from_most_used';
    public const LABEL_NOT_FOUND                = 'not_found';
    public const LABEL_NO_TERMS                 = 'no_terms';
    public const LABEL_FILTER_BY_ITEM           = 'filter_by_item';
    public const LABEL_ITEMS_LIST_NAVIGATION    = 'items_list_navigation';
    public const LABEL_ITEMS_LIST               = 'items_list';
    public const LABEL_MOST_USED                = 'most_used';
    public const LABEL_BACK_TO_ITEMS            = 'back_to_items';
    public const LABEL_ITEM_LINK                = 'item_link';
    public const LABEL_ITEM_LINK_DESCRIPTION    = 'item_link_description';

    /** @var string[]  */
    protected array $associated_objects = [];

    /**
     * Register custom taxonomy
     *
     * @param string|array{name: string, singular?: string, plural?: string} $names
     * @param array<string, mixed>                                           $options
     * @param array<string, string>                                          $labels
     *
     * @throws LogicException if the taxonomy exist.
     */
    public static function add($names, array $options = [], array $labels = []): self
    {
        return new self($names, $options, $labels);
    }

    /**
     * Extend existing taxonomy
     *
     * @param string|array{name: string, singular?: string, plural?: string} $names
     * @param array<string, mixed>                                           $options
     * @param array<string, string>                                          $labels
     *
     * @throws LogicException if the taxonomy does not exist.
     */
    public static function extend($names, array $options = [], array $labels = []): self
    {
        $instance = new self($names, $options, $labels);

        $instance->extending = true;

        return $instance;
    }

    /** @inheritDoc */
    protected function mergeWithDefaultOptions(array $options): array
    {
        $defaults = [
            'public' => true,
            'label' => ucwords(str_replace(['_', '-'], ' ', $this->name)),
        ];

        return wp_parse_args($options, $defaults);
    }

    /**
     * Assign a PostType to register the Taxonomy to
     *
     * @param  string|string[] $post_types
     */
    public function associatedObjects($post_types): self
    {
        $post_types = is_array($post_types) ? $post_types : func_get_args();

        foreach ($post_types as $posttype) {
            $this->associated_objects[] = $posttype;
        }

        return $this;
    }

    /**
     * A plural descriptive name for the taxonomy marked for translation.
     */
    public function label(string $label): self
    {
        $this->options['label'] = $label;

        return $this;
    }

    /**
     * Include a description of the taxonomy.
     */
    public function description(string $description): self
    {
        $this->options['description'] = $description;

        return $this;
    }

    /**
     * Whether a taxonomy is intended for use publicly either via the admin interface or by front-end users.
     */
    public function public(bool $public = true): self
    {
        $this->options['public'] = $public;

        return $this;
    }

    /**
     * Whether queries can be performed on the front end for the post type as part of parse_request().
     */
    public function publiclyQueryable(bool $queryable = true): self
    {
        $this->options['publicly_queryable'] = $queryable;

        return $this;
    }

    /**
     * Is this taxonomy hierarchical (have descendants) like categories or not hierarchical like tags.
     *
     * Hierarchical taxonomies will have a list with checkboxes to select an existing category in
     * the taxonomy admin box on the post edit page (like default post categories). Non-hierarchical
     * taxonomies will just have an empty text field to type-in taxonomy terms to associate with the
     * post (like default post tags).
     */
    public function hierarchical(bool $hierarchical = true): self
    {
        $this->options['hierarchical'] = $hierarchical;

        return $this;
    }

    /**
     * Whether to generate a default UI for managing this taxonomy.
     */
    public function showUi(bool $visible = true): self
    {
        $this->options['show_ui'] = $visible;

        return $this;
    }

    /**
     * Where to show the taxonomy in the admin menu. show_ui must be true.
     */
    public function showInMenu(bool $show = true): self
    {
        $this->options['show_in_menu'] = $show;

        return $this;
    }

    /**
     * Makes this taxonomy available for selection in navigation menus.
     */
    public function showInNavMenus(bool $show = true): self
    {
        $this->options['show_in_nav_menus'] = $show;

        return $this;
    }

    /**
     * Whether to include the taxonomy in the REST API.
     *
     * Set this to true in order to use the taxonomy in your gutenberg metablock.
     */
    public function showInRest(bool $show = true): self
    {
        $this->options['show_in_rest'] = $show;

        return $this;
    }

    /**
     * Change the base url of REST API route.
     */
    public function restBase(string $base): self
    {
        $this->options['rest_base'] = $base;

        return $this;
    }

    /**
     * Change the namespace URL of REST API route.
     */
    public function restNamespace(string $namespace): self
    {
        $this->options['rest_namespace'] = $namespace;

        return $this;
    }

    /**
     * Set REST API Controller class name.
     */
    public function restControllerClass(string $class): self
    {
        $this->options['rest_controller_class'] = $class;

        return $this;
    }

    /**
     * Whether to allow the Tag Cloud widget to use this taxonomy.
     */
    public function showTagcloud(bool $show = true): self
    {
        $this->options['show_tagcloud'] = $show;

        return $this;
    }

    /**
     * Whether to show the taxonomy in the quick/bulk edit panel.
     */
    public function showInQuickEdit(bool $show = true): self
    {
        $this->options['show_in_quick_edit'] = $show;

        return $this;
    }

    /**
     * Whether to allow automatic creation of taxonomy columns on associated post-types table.
     */
    public function showAdminColumn(bool $show = true): self
    {
        $this->options['show_admin_column'] = $show;

        return $this;
    }

    /**
     * Provide a callback function name for the meta box display.
     *
     * @param bool|callable $cb Provide a callback function for the meta box display.
     *                          If false, no meta box is shown.
     */
    public function metaBoxCb($cb): self
    {
        $this->options['meta_box_cb'] = $cb;

        return $this;
    }

    /**
     * Callback function for sanitizing taxonomy data saved from a meta box.
     */
    public function metaBoxSanitizeCb(callable $cb): self
    {
        $this->options['meta_box_cb'] = $cb;

        return $this;
    }

    /**
     * Register capabilities for this taxonomy.
     *
     * - manage_termsstring - Default 'manage_categories'.
     * - edit_termsstring - Default 'manage_categories'.
     * - delete_termsstring - Default 'manage_categories'.
     * - assign_termsstring - Default 'edit_posts'.
     *
     * @param string|string[] $capabilities,... Capability name or an array of capabilities
     */
    public function capabilities($capabilities): self
    {
        $this->options['capabilities'] = is_array($capabilities) ? $capabilities : func_get_args();

        return $this;
    }

    /**
     * Triggers the handling of rewrites for this taxonomy.
     *
     * @param bool|array{slug?: string, with_front?: bool, hierarchical?: bool, ep_mask?: int} $rewrite
     */
    public function rewrite($rewrite = true): self
    {
        $this->options['rewrite'] = $rewrite;

        return $this;
    }

    /**
     * Sets the query_var key for this taxonomy.
     *
     * @param bool|string $query False to disable the query_var, set as string to use custom query_var
     *                           instead of default which is $taxonomy, the taxonomy’s “name”. True is
     *                           not seen as a valid entry and will result in 404 issues.
     */
    public function queryVar($query = true): self
    {
        $this->options['query_var'] = $query;

        return $this;
    }

    /**
     * A function name that will be called when the count of an associated $object_type, such as post,
     * is updated. Works much like a hook.
     */
    public function updateCountCallback(string $update): self
    {
        $this->options['update_count_callback'] = $update;

        return $this;
    }

    /**
     * Sets the default term to be used for the taxonomy.
     *
     * @param string|array{name?: string, slug?: string, description?: string} $term
     */
    public function defaultTerm($term): self
    {
        $this->options['default_term'] = $term;

        return $this;
    }

    /**
     * Whether terms in this taxonomy should be sorted in the order they are
     * provided to wp_set_object_terms().
     */
    public function sort(bool $sort = true): self
    {
        $this->options['default_term'] = $sort;

        return $this;
    }

    /**
     * Provide array of arguments to automatically use inside wp_get_object_terms() for this taxonomy.
     *
     * @param array<string, mixed> $args
     */
    public function args(array $args): self
    {
        $this->options['args'] = $args;

        return $this;
    }

    /**
     * Populate custom columns for the Taxonomy
     *
     * @return false|mixed|string
     */
    protected function populateColumns(string $content, string $column, int $term_id)
    {
        if (isset($this->columns->populate[$column])) {
            $content = call_user_func_array($this->columns()->populate[$column], [$content, $column, $term_id]);
        }

        return $content;
    }

    /**
     * Set query to sort custom columns
     */
    protected function sortSortableColumns(WP_Term_Query $query): void
    {
        // don't modify the query if we're not in the post type admin
        if (! is_admin() || ! in_array($this->name, $query->query_vars['taxonomy'] ?? [])) {
            return;
        }

        // check the orderby is a custom ordering
        if (! isset($_GET['orderby']) || ! array_key_exists($_GET['orderby'], $this->columns()->sortable)) {
            return;
        }

        // get the custom sorting options
        $meta = $this->columns()->sortable[$_GET['orderby']];

        // check ordering is not numeric
        if (is_string($meta)) {
            $meta_key = $meta;
            $orderby  = 'meta_value';
        } else {
            $meta_key = $meta[0];
            $orderby  = 'meta_value_num';
        }

        // set the sort order
        $query->query_vars['orderby']  = $orderby;
        $query->query_vars['meta_key'] = $meta_key;
    }

    protected function modifyAdminEditColumns(): void
    {
        if (! isset($this->columns)) {
            return;
        }

        // modify the columns for the Taxonomy
        add_filter(sprintf('manage_edit-%s_columns', $this->name), function (array $columns): array {
            return $this->modifyColumns($columns);
        }, 10);

        // populate the columns for the Taxonomy
        $filter = sprintf('manage_%s_custom_column', $this->name);
        add_filter($filter, function (string $content, string $column, int $term_id): void {
            $this->populateColumns($content, $column, $term_id);
        }, 10, 3);

        // set custom sortable columns
        add_filter(sprintf('manage_edit-%s_sortable_columns', $this->name), function (array $columns): array {
            return $this->setSortableColumns($columns);
        });

        // run action that sorts columns on request
        add_action('parse_term_query', function (WP_Term_Query $query): void {
            $this->sortSortableColumns($query);
        });
    }

    /**
     * Register the Taxonomy to PostTypes
     */
    protected function registerTaxonomyToObjects(): void
    {
        // register Taxonomy to each of the PostTypes assigned
        if (empty($this->associated_objects)) {
            return;
        }

        foreach ($this->associated_objects as $posttype) {
            register_taxonomy_for_object_type($this->name, $posttype);
        }
    }

    protected function modifyMainQuery(WP_Query $query): void
    {
        if (
            $this->name === 'post_tag' && ! is_tag()
            || $this->name === 'category' && ! is_category()
            || ! $query->is_main_query()
            || ! empty($query->query_vars['suppress_filters'])
        ) {
            return;
        }

        $query->set('post_type', wp_parse_args(
            $this->associated_objects,
            $query->get('post_type', ['post'])
        ));
    }

    protected function save(): void
    {
        $this->saved = true;

        if (! $this->extending) {
            $this->doRegister();
        } else {
            $this->doExtend();
        }

        $this->modifyAdminEditColumns();
    }

    /** @throws LogicException */
    protected function doRegister(): void
    {
        if (taxonomy_exists($this->name)) {
            throw new LogicException();
        }

        register_taxonomy($this->name, $this->associated_objects, $this->options);
    }

    /** @throws LogicException */
    protected function doExtend(): void
    {
        if (! taxonomy_exists($this->name)) {
            throw new LogicException();
        }

        $this->registerTaxonomyToObjects();

        if (! isset($this->associated_objects)) {
            return;
        }

        add_filter('pre_get_posts', function (WP_Query $query): void {
            $this->modifyMainQuery($query);
        });
    }
}
