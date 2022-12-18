<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Devly\WP;

use Devly\WP\CPT\Generator;
use LogicException;
use WP_Query;

use function call_user_func_array;
use function func_get_args;
use function func_num_args;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function str_replace;
use function ucwords;

/**
 * @method self exclude_from_search(bool $exclude = true)
 * @method self publicly_queryable(bool $queryable = true)
 * @method self show_ui(bool $queryable = true)
 * @method self show_in_menu($show = true)
 * @method self show_in_nav_menus(bool $show = true)
 * @method self show_in_admin_bar(bool $show = true)
 * @method self show_in_rest(bool $show = true)
 * @method self rest_base(string $base)
 * @method self rest_namespace(string $namespace)
 * @method self rest_controller_class(string $class)
 * @method self menu_position(int $position)
 * @method self menu_icon(string $icon)
 * @method self capability_type(string|string[] $type)
 * @method self map_meta_cap(bool $map = true)
 * @method self register_meta_box_cb(callable $cb)
 * @method self has_archive(string|bool $archive = true)
 * @method self query_var(string|bool $query = true)
 * @method self can_export(bool $can = true)
 * @method self delete_with_user(bool $delete = true)
 * @method self template_lock(string|false $lock)
 * @method self remove_support(string|string[] $supports)
 * @method self remove_taxonomies(string|string[] $taxonomies)
 */
class PostType extends Generator
{
    public const LABEL_NAME                     = 'name';
    public const LABEL_SINGULAR_NAME            = 'singular_name';
    public const LABEL_ADD_NEW                  = 'add_new';
    public const LABEL_ADD_NEW_ITEM             = 'add_new_item';
    public const LABEL_EDIT_ITEM                = 'edit_item';
    public const LABEL_VIEW_ITEM                = 'view_item';
    public const LABEL_VIEW_ITEMS               = 'view_items';
    public const LABEL_SEARCH_ITEMS             = 'search_items';
    public const LABEL_NOT_FOUND                = 'not_found';
    public const LABEL_NOT_FOUND_IN_TRASH       = 'not_found_in_trash';
    public const LABEL_PARENT_ITEM_COLON        = 'parent_item_colon';
    public const LABEL_ALL_ITEMS                = 'all_items';
    public const LABEL_ARCHIVES                 = 'archives';
    public const LABEL_ATTRIBUTES               = 'attributes';
    public const LABEL_INSERT_INTO_ITEM         = 'insert_into_item';
    public const LABEL_UPLOADED_TO_THIS_ITEM    = 'uploaded_to_this_item';
    public const LABEL_FEATURED_IMAGE           = 'featured_image';
    public const LABEL_SET_FEATURED_IMAGE       = 'set_featured_image';
    public const LABEL_REMOVE_FEATURED_IMAGE    = 'remove_featured_image';
    public const LABEL_USE_FEATURED_IMAGE       = 'use_featured_image';
    public const LABEL_MENU_NAME                = 'menu_name';
    public const LABEL_FILTER_ITEMS_LIST        = 'filter_items_list';
    public const LABEL_FILTER_BY_DATE           = 'filter_by_date';
    public const LABEL_ITEMS_LIST_NAVIGATION    = 'items_list_navigation';
    public const LABEL_ITEMS_LIST               = 'items_list';
    public const LABEL_ITEM_PUBLISHED           = 'item_published';
    public const LABEL_ITEM_PUBLISHED_PRIVATELY = 'item_published_privately';
    public const LABEL_ITEM_INVERTED_TO_DRAFT   = 'item_reverted_to_draft';
    public const LABEL_ITEM_SCHEDULED           = 'item_scheduled';
    public const LABEL_ITEM_UPDATED             = 'item_updated';
    public const LABEL_ITEM_LINK                = 'item_link';
    public const LABEL_ITEM_LINK_DESCRIPTION    = 'item_link_description';


    protected string $name;
    protected string $singular;
    protected string $plural;
    /** @var array<string, string>  */
    protected array $labels = [];
    /** @var array|string[] */
    protected array $remove_support = [];
    /** @var string[] */
    protected array $filters = [];
    /** @var string[]  */
    protected array $remove_taxonomies = [];

    /**
     * @param string                $name    The post type or taxonomy name to register.
     * @param array<string, mixed>  $options Array of arguments for registering a post
     *                                       type or a taxonomy.
     * @param array<string, string> $labels  An array of labels for the post type or
     *                                       taxonomy.
     *
     * @throws LogicException if the post type exist.
     */
    public static function add(string $name, array $options = [], array $labels = []): self
    {
        return new self($name, $options, $labels);
    }

    /**
     * Extend existing post type
     *
     * @throws LogicException if the post type does not exist.
     */
    public static function extend(string $name): self
    {
        $factory = new self($name);

        $factory->extending = true;

        return $factory;
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
     * Name of the post type shown in the menu. Usually plural.
     */
    public function label(string $label): self
    {
        $this->options['label'] = $label;

        return $this;
    }

    /**
     * A short descriptive summary of what the post type is.
     *
     * @return static
     */
    public function description(string $description): self
    {
        $this->options['description'] = $description;

        return $this;
    }

    /**
     * Whether a post type is intended for use publicly either via the admin interface or by front-end users
     *
     * While the default settings of $exclude_from_search, $publicly_queryable,
     * $show_ui, and $show_in_nav_menus are inherited from $public, each does
     * not rely on this relationship and controls a very specific intention.
     *
     * @return static
     */
    public function public(bool $public = true): self
    {
        $this->options['public'] = $public;

        return $this;
    }

    /**
     * Whether the post type is hierarchical (e.g. page).
     */
    public function hierarchical(bool $hierarchical = true): self
    {
        $this->options['hierarchical'] = $hierarchical;

        return $this;
    }

    /**
     * Whether to exclude posts with this post type from front end search results.
     */
    public function excludeFromSearch(bool $exclude = true): self
    {
        $this->options['exclude_from_search'] = $exclude;

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
     * Whether to generate and allow a UI for managing this post type in the admin.
     */
    public function showUi(bool $visible = true): self
    {
        $this->options['show_ui'] = $visible;

        return $this;
    }

    /**
     * Where to show the post type in the admin menu.
     *
     * @param bool|string $show If true, the post type is shown in its own top level menu.
     *                          If false, no menu is shown. If a string of an existing top
     *                          level menu ('tools.php' or 'edit.php?post_type=page', for
     *                          example), the post type will be placed as a sub-menu of that.
     */
    public function showInMenu($show = true): self
    {
        $this->options['show_in_menu'] = $show;

        return $this;
    }

    /**
     * Makes this post type available for selection in navigation menus.
     */
    public function showInNavMenus(bool $show = true): self
    {
        $this->options['show_in_nav_menus'] = $show;

        return $this;
    }

    /**
     * Makes this post type available via the admin bar.
     */
    public function showInAdminBar(bool $show = true): self
    {
        $this->options['show_in_admin_bar'] = $show;

        return $this;
    }

    /**
     * Whether to include the post type in the REST API.
     *
     * Set this to true for the post type to be available in the block editor.
     *
     * Requires at least WP version 4.7 to work.
     */
    public function showInRest(bool $show = true): self
    {
        $this->options['show_in_rest'] = $show;

        return $this;
    }

    /**
     * Change the base URL of REST API route.
     *
     * Requires at least WP version 4.7 to work.
     */
    public function restBase(string $base): self
    {
        $this->options['rest_base'] = $base;

        return $this;
    }

    /**
     * Change the namespace URL of REST API route.
     *
     * Requires at least WP version 5.9 to work.
     */
    public function restNamespace(string $namespace): self
    {
        $this->options['rest_namespace'] = $namespace;

        return $this;
    }

    /**
     * Set REST API controller class name for this post type.
     *
     * Requires at least WP version 4.7 to work.
     */
    public function restControllerClass(string $class): self
    {
        $this->options['rest_controller_class'] = $class;

        return $this;
    }

    /**
     * The position in the menu order the post type should appear.
     * - 5 – below Posts
     * - 10 – below Media
     * - 15 – below Links
     * - 20 – below Pages
     * - 25 – below comments
     * - 60 – below first separator
     * - 65 – below Plugins
     * - 70 – below Users
     * - 75 – below Tools
     * - 80 – below Settings
     * - 100 – below second separator
     */
    public function menuPosition(int $position): self
    {
        $this->options['menu_position'] = $position;

        return $this;
    }

    /**
     * The URL to the icon to be used for this menu.
     *
     * Pass a base64-encoded SVG using a data URI, which will be colored to match the color scheme
     * -- this should begin with 'data:image/svg+xml;base64,'. Pass the name of a Dashicons helper
     * class to use a font icon, e.g. 'dashicons-chart-pie'. Pass 'none' to leave div.wp-menu-image
     * empty so an icon can be added via CSS.
     */
    public function menuIcon(string $icon): self
    {
        $this->options['menu_icon'] = $icon;

        return $this;
    }

    /**
     * The string to use to build the read, edit, and delete capabilities.
     *
     * May be passed as an array to allow for alternative plurals when using this argument as a
     * base to construct the capabilities, e.g. array('story', 'stories').
     *
     * @param string|string[] $type,...
     */
    public function capabilityType($type): self
    {
        $this->options['capability_type'] = is_array($type) || func_num_args() === 1 ? $type : func_get_args();

        return $this;
    }

    /**
     * List of capabilities for this post type.
     *
     * @param string|string[] $capabilities,...
     */
    public function capabilities($capabilities): self
    {
        $this->options['capabilities'] = is_array($capabilities) ? $capabilities : func_get_args();

        return $this;
    }

    /**
     * Whether to use the internal default meta capability handling.
     */
    public function mapMetaCap(bool $map = true): self
    {
        $this->options['map_meta_cap'] = $map;

        return $this;
    }

    /**
     * Core feature(s) the post type supports.
     *
     * Core features include 'title', 'editor', 'comments', 'revisions',
     * 'trackbacks', 'author', 'excerpt', 'page-attributes', 'thumbnail',
     * 'custom-fields', and 'post-formats'.
     *
     * @param string|string[] $supports,...
     */
    public function supports($supports): self
    {
        $this->options['supports'] = is_array($supports) ? $supports : func_get_args();

        return $this;
    }

    /**
     * Provide a callback function that sets up the meta boxes for the edit form.
     *
     * Do remove_meta_box() and add_meta_box() calls in the callback.
     */
    public function registerMetaBoxCb(callable $cb): self
    {
        $this->options['register_meta_box_cb'] = $cb;

        return $this;
    }

    /**
     * List of taxonomy identifiers that will be registered for the post type.
     *
     * @param string|string[] $taxonomies,...
     */
    public function taxonomies($taxonomies): self
    {
        $this->options['taxonomies'] = is_array($taxonomies) ? $taxonomies : func_get_args();

        return $this;
    }

    /**
     * Whether there should be post type archives
     *
     * Will generate the proper rewrite rules if rewrite is enabled.
     *
     * @param string|bool $archive True to enable archive or the archive slug to use.
     */
    public function hasArchive($archive = true): self
    {
        $this->options['has_archive'] = $archive;

        return $this;
    }

    /**
     * Triggers the handling of rewrites for this post type.
     *
     * @param bool|array{slug?: string, with_front?: bool, feeds?: bool, pages?: bool, ep_mask?: int} $rewrite
     */
    public function rewrite($rewrite = true): self
    {
        $this->options['rewrite'] = $rewrite;

        return $this;
    }

    /**
     * Sets the query_var key for this post type.
     *
     * @param string|bool $query If false, a post type cannot be loaded at ?{query_var}={post_slug}.
     *                           If specified as a string, the query ?{query_var_string}={post_slug}
     *                           will be valid.
     */
    public function queryVar($query = true): self
    {
        $this->options['query_var'] = $query;

        return $this;
    }

    /**
     * Whether to allow this post type to be exported.
     */
    public function canExport(bool $can = true): self
    {
        $this->options['can_export'] = $can;

        return $this;
    }

    /**
     * Whether to delete posts of this type when deleting a user.
     */
    public function deleteWithUser(bool $delete = true): self
    {
        $this->options['delete_with_user'] = $delete;

        return $this;
    }

    /**
     * Set blocks to use as the default initial state for an editor session.
     *
     * @param array<array{name: string, attributes: array<string, mixed>}> $template Each item should be an array
     *                                                                               containing block name and
     *                                                                               optional attributes.
     */
    public function template(array $template): self
    {
        $this->options['template'] = $template;

        return $this;
    }

    /**
     * Whether the block template should be locked if template is set.
     *
     * @param string|false $lock If set to 'all', the user is unable to insert new blocks, move existing
     *                           blocks and delete blocks. If set to 'insert', the user is able to move
     *                           existing blocks but is unable to insert new blocks and delete blocks.
     */
    public function templateLock($lock): self
    {
        $this->options['template_lock'] = $lock;

        return $this;
    }

    /**
     * Remove core feature(s) supported by the post type
     *
     * @param string|string[] $supports,...
     */
    public function removeSupport($supports): self
    {
        $this->remove_support = is_array($supports) ? $supports : func_get_args();

        return $this;
    }

    protected function doRemoveSupport(): void
    {
        if (empty($this->remove_support)) {
            return;
        }

        foreach ($this->remove_support as $support) {
            remove_post_type_support($this->name, $support);
        }
    }

    /**
     * Add filters to the PostType
     *
     * @param string[] $filters An array of Taxonomy filters
     */
    public function filters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Modify and display filters on the admin edit screen
     *
     * @param  string $posttype The current screen post type
     */
    protected function modifyFilters(string $posttype): void
    {
        // first check we are working with this PostType
        if ($posttype !== $this->name) {
            return;
        }

        // calculate what filters to add
        $filters = $this->getFilters();

        foreach ($filters as $taxonomy) {
            // if the taxonomy doesn't exist, ignore it
            if (! taxonomy_exists($taxonomy)) {
                continue;
            }

            // get the taxonomy object
            $tax = get_taxonomy($taxonomy);

            // get the terms for the taxonomy
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'orderby' => 'name',
                'hide_empty' => false,
            ]);

            // if there are no terms in the taxonomy, ignore it
            if (empty($terms)) {
                continue;
            }

            // start the html for the filter dropdown
            $selected = null;

            if (isset($_GET[$taxonomy])) {
                $selected = sanitize_title($_GET[$taxonomy]);
            }

            $dropdown_args = [
                'option_none_value' => '',
                'hide_empty'        => 0,
                'hide_if_empty'     => false,
                'show_count'        => true,
                'taxonomy'          => $tax->name,
                'name'              => $taxonomy,
                'orderby'           => 'name',
                'hierarchical'      => true,
                'show_option_none'  => sprintf('Show all %s', $tax->label),
                'value_field'       => 'slug',
                'selected'          => $selected,
            ];

            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
            wp_dropdown_categories($dropdown_args);
        }
    }

    /**
     * Calculate the filters for the PostType
     *
     * @return array|string[]
     */
    protected function getFilters(): array
    {
        // if custom filters have been set, use them
        if (! empty($this->filters)) {
            return $this->filters;
        }

        // if no custom filters have been set, and there are
        // Taxonomies assigned to the PostType
        if (isset($this->options['taxonomies']) && ! empty($this->options['taxonomies'])) {
            // create filters for each taxonomy assigned to the PostType
            return $this->options['taxonomies'];
        }

        return [];
    }

    protected function overrideLabels(): void
    {
        $get_post_type = get_post_type_object('post');
        $labels        = $get_post_type->labels;

        foreach ($this->labels as $key => $label) {
            $labels->$key = $label;
        }
    }

    /** @param string|string[] $taxonomies,... */
    public function removeTaxonomies($taxonomies): self
    {
        $this->remove_taxonomies = is_array($taxonomies) ? $taxonomies : func_get_args();

        return $this;
    }

    protected function doRemoveTaxonomies(): void
    {
        if (empty($this->remove_taxonomies)) {
            return;
        }

        foreach ($this->remove_taxonomies as $taxonomy) {
            unregister_taxonomy_for_object_type($taxonomy, $this->name);
        }
    }

    /**
     * Populate custom columns for the PostType
     *
     * @param  string $column  The column slug
     * @param  int    $post_id The post ID
     */
    protected function populateColumns(string $column, int $post_id): void
    {
        if (! isset($this->columns->populate[$column])) {
            return;
        }

        call_user_func_array($this->columns()->populate[$column], [$column, $post_id]);
    }

    /**
     * Set query to sort custom columns
     */
    protected function sortSortableColumns(WP_Query $query): void
    {
        // don't modify the query if we're not in the post type admin
        if (! is_admin() || $query->get('post_type') !== $this->name) {
            return;
        }

        $orderby = $query->get('orderby');

        // if the sorting a custom column
        if (! $this->columns()->isSortable($orderby)) {
            return;
        }

        // get the custom column options
        $meta = $this->columns()->sortableMeta($orderby);

        // determine type of ordering
        if (is_string($meta)) {
            $meta_key   = $meta;
            $meta_value = 'meta_value';
        } else {
            $meta_key   = $meta[0];
            $meta_value = 'meta_value_num';
        }

        // set the custom order
        $query->set('meta_key', $meta_key);
        $query->set('orderby', $meta_value);
    }

    protected function modifyAdminEditColumns(): void
    {
        if (! isset($this->columns)) {
            return;
        }

        // modify the admin edit columns.
        add_filter(sprintf('manage_%s_posts_columns', $this->name), function ($columns): array {
            return $this->modifyColumns($columns);
        }, 50, 1);

        // populate custom columns

        $filter = sprintf('manage_%s_posts_custom_column', $this->name);
        add_filter($filter, function (string $column, int $post_id): void {
            $this->populateColumns($column, $post_id);
        }, 10, 2);

        // run filter to make columns sortable.
        add_filter(sprintf('manage_edit-%s_sortable_columns', $this->name), function (array $columns): array {
            return $this->setSortableColumns($columns);
        });

        // run action that sorts columns on request.
        add_action('pre_get_posts', function (WP_Query $query): void {
            $this->sortSortableColumns($query);
        });
    }

    /**
     * Flush rewrite rules
     *
     * @link https://codex.wordpress.org/Function_Reference/flush_rewrite_rules
     */
    public function flush(bool $hard = true): self
    {
        flush_rewrite_rules($hard);

        return $this;
    }

    /** @param string[] $names */
    protected function shouldModifyQuery(string $name, array $names, WP_Query $query): bool
    {
        if ($name === 'category') {
            return is_category()
                && in_array('category', $names)
                && empty($query->query_vars['suppress_filters']);
        }

        return is_tag()
            && in_array('post_tag', $names)
            && empty($query->query_vars['suppress_filters']);
    }

    protected function save(): void
    {
        if (! $this->extending) {
            $this->register();
        } else {
            $this->extendPost();
        }

        // modify filters on the admin edit screen
        add_action('restrict_manage_posts', function (string $posttype): void {
            $this->modifyFilters($posttype);
        });

        if (isset($this->options['taxonomies'])) {
            add_filter('pre_get_posts', function (WP_Query $query): void {
                $this->modifyMainQuery($query);
            });
        }

        $this->modifyAdminEditColumns();
    }

    protected function modifyMainQuery(WP_Query $query): void
    {
        if (
            ! $this->shouldModifyQuery('category', $this->options['taxonomies'], $query)
            && ! $this->shouldModifyQuery('post_tag', $this->options['taxonomies'], $query)
        ) {
            return;
        }

        $query->set('post_type', wp_parse_args([$this->name], $query->get('post_type', ['post'])));
    }

    /** @throws LogicException */
    protected function register(): void
    {
        if (post_type_exists($this->name)) {
            throw new LogicException(sprintf('Post type "%s" already exists.', $this->name));
        }

        register_post_type($this->name, $this->options);
    }

    /** @throws LogicException */
    protected function extendPost(): void
    {
        if (! post_type_exists($this->name)) {
            throw new LogicException(sprintf('Post type "%s" does not exist.', $this->name));
        }

        $this->overrideLabels();

        if (! empty($this->remove_support)) {
            $this->doRemoveSupport();
        }

        if (! empty($this->remove_taxonomies)) {
            $this->doRemoveTaxonomies();
        }

        if (isset($this->options['supports'])) {
            add_post_type_support($this->name, $this->options['supports']);
        }

        if (! isset($this->options['taxonomies'])) {
            return;
        }

        foreach ($this->options['taxonomies'] as $tax) {
            register_taxonomy_for_object_type($tax, $this->name);
        }
    }
}
