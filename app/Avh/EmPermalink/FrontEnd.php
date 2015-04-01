<?php

namespace Avh\EmPermalink;

/**
 * Class FrontEnd
 *
 * @package   Avh\EmPermalink
 * @author    Peter van der Does <peter@avirtualhome.com>
 * @copyright Copyright (c) 2015, AVH Software
 */
class FrontEnd
{
    /**
     * Rewrite tags that can be used in permalink structures.
     *
     * These are translated into the regular expressions stored in
     * {@link WP_Rewrite::$rewritereplace} and are rewritten to the
     * query variables listed in {@link WP_Rewrite::$queryreplace}.
     *
     * The key of the array is the actual structure tag
     *
     * 'regex'
     * Regular expressions to be substituted into rewrite rules in place
     * of rewrite tags, see {@link WP_Rewrite::$rewritecode}.
     *
     * 'query'
     * Query variables that rewrite tags map to, see {@link WP_Rewrite::$rewritecode}.
     * The query should NOT end with the = sign, we add this later.
     *
     * @var array
     */
    private $structure_tags_events = [
        '%event_year%'     => ['regex' => '([0-9]{4})', 'query' => 'event_year', 'replacement' => '0'],
        '%event_monthnum%' => ['regex' => '([0-9]{1,2})', 'query' => 'event_monthnum', 'replacement' => '0'],
        '%event_day%'      => ['regex' => '([0-9]{1,2})', 'query' => 'event_day', 'replacement' => '0'],
        '%event_name%'     => ['regex' => '([^/]+)', 'query' => EM_POST_TYPE_EVENT, 'replacement' => ''],
        '%event_owner%'    => ['regex' => '([^/]+)', 'query' => 'event_owner', 'replacement' => ''],
        '%event_location%' => ['regex' => '([^/]+)', 'query' => 'location', 'replacement' => ''],
    ];
    private $structure_tags_locations = [
        '%location_name%' => ['regex' => '([^/]+)', 'query' => EM_POST_TYPE_LOCATION]
    ];
    private $structure_tags_terms = [
        '%category_name%' => ['regex' => '([^/]+)', 'query' => EM_TAXONOMY_CATEGORY]
    ];

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->setActionFilters();
    }

    /**
     * Rewrite the permalink for the custom post types.
     *
     * @param string   $post_link The post's permalink.
     * @param \WP_Post $post      The post in question.
     * @param bool     $leavename Whether to keep the post name.
     * @param bool     $sample    Is it a sample permalink.
     *
     * @return mixed|string|void
     */
    public function filterPermalink($post_link, $post, $leavename, $sample)
    {
        switch ($post->post_type) {
            case EM_POST_TYPE_EVENT:

                $EM_Event = em_get_event($post->ID, $search_by = 'post_id');
                $rewritecode = array_keys($this->structure_tags_events);

                if ('' != $post_link && !in_array($EM_Event->post_status, ['draft', 'pending', 'auto-draft'])) {
                    $event_start_date = new \DateTime($EM_Event->event_start_date);
                    $this->structure_tags_events['%event_year%']['replacement'] = $event_start_date->format('Y');
                    $this->structure_tags_events['%event_monthnum%']['replacement'] = $event_start_date->format('m');
                    $this->structure_tags_events['%event_day%']['replacement'] = $event_start_date->format('d');

                    if (strpos($post_link, '%category%') !== false) {

                        $EM_Categories = $EM_Event->get_categories();
                        if ($EM_Categories->categories) {
                            usort($EM_Categories->categories, '_usort_terms_by_ID'); // order by ID
                            $category_object = $EM_Categories->categories[0];
                            $category_object = get_term($category_object, EM_TAXONOMY_CATEGORY);
                            $this->structure_tags_events['%category%']['replacement'] = $category_object->slug;
                        }
                    }

                    if (strpos($post_link, '%event_location%') !== false) {
                        $EM_Location = em_get_location($EM_Event->location_id);
                        $this->structure_tags_events['%event_location%']['replacement'] = $EM_Location->location_slug;
                    }

                    if (strpos($post_link, '%event_owner%') !== false) {
                        $authordata = get_userdata($EM_Event->event_owner);
                        $this->structure_tags_events['%event_owner%']['replacement'] = $authordata->user_nicename;
                    }

                    if (strpos($post_link, '%event_name%') !== false) {
                        $this->structure_tags_events['%event_name%']['replacement'] = $EM_Event->event_slug;
                    }

                    foreach ($this->structure_tags_events as $structured_tag => $information) {
                        $rewritereplace_event[] = $information['replacement'];
                    }

                    $rewritereplace = $rewritereplace_event;
                    $post_link = str_replace($rewritecode, $rewritereplace, $post_link);
                    $post_link = user_trailingslashit($post_link, 'single');
                } else { // if they're not using the fancy permalink option
                    $post_link = home_url('?event=' . $EM_Event->event_slug);
                }
                break;

            case EM_POST_TYPE_LOCATION:
                $EM_Location = em_get_location($post->ID, $search_by = 'post_id');
                $rewritecode_location = array_keys($this->structure_tags_locations);
                $rewritecode = $rewritecode_location;

                if ('' != $post_link && !in_array($post->post_status, ['draft', 'pending', 'auto-draft'])) {
                    $unixtime = strtotime($EM_Location->post_date);

                    $rewritereplace_location = [$EM_Location->location_slug];

                    $rewritereplace = $rewritereplace_location;
                    $post_link = str_replace($rewritecode, $rewritereplace, $post_link);
                    $post_link = user_trailingslashit($post_link, 'single');
                } else { // if they're not using the fancy permalink option
                    $post_link = home_url('?p=' . $EM_Location->location_id);
                }
                break;
        }

        return $post_link;
    }

    /**
     * Rewrite the permalink for taxonomies.
     *
     * @param string $termlink Term link URL.
     * @param object $term     Term object.
     * @param string $taxonomy Taxonomy slug.
     *
     * @return mixed|string
     */
    public function filterTermLink($termlink, $term, $taxonomy)
    {
        switch ($taxonomy) {
            case EM_TAXONOMY_CATEGORY:
                if (strpos($termlink, '%category_name%') !== false) {
                    $rewritecode = ['%category_name%'];
                    $rewritereplace = [$term->slug];
                    $termlink = str_replace($rewritecode, $rewritereplace, $termlink);
                    $termlink = user_trailingslashit($termlink, 'single');
                }
        }

        return $termlink;
    }

    /**
     * Setup permalinks
     *
     * Create new placeholders and add the default permalink
     */
    public function generateRewriteRules()
    {
        /**
         * Add new rewrite placeholders
         */
        $structure_tags = $this->structure_tags_events + $this->structure_tags_locations + $this->structure_tags_terms;
        foreach ($structure_tags as $placeholder => $information) {
            $regex = $information['regex'];
            $query_var = $information['query'] . '=';
            add_rewrite_tag($placeholder, $regex, $query_var);
        }

        add_permastruct(
            EM_POST_TYPE_EVENT,
            get_option('dbem_cp_events_slug', EM_POST_TYPE_EVENT_SLUG),
            ['with_front' => false]
        );
        add_permastruct(
            EM_POST_TYPE_LOCATION,
            get_option('dbem_cp_locations_slug', EM_POST_TYPE_LOCATION_SLUG),
            ['with_front' => false]
        );
        add_permastruct(
            EM_TAXONOMY_CATEGORY,
            get_option('dbem_taxonomy_category_slug', EM_TAXONOMY_CATEGORY_SLUG) . '/%category_name%',
            ['hierarchical' => true, 'with_front' => false]
        );

        flush_rewrite_rules();
    }

    /**
     * Parse the query vars.
     *
     * Some of it has already been parsed by Event Manager, we just add our added query vars.
     *
     */
    function parseQuery()
    {
        global $wp_query;
        if (!is_admin()) {
            if (!empty($wp_query->query_vars['post_type']) && ($wp_query->query_vars['post_type'] == EM_POST_TYPE_EVENT || $wp_query->query_vars['post_type'] == 'event-recurring') && (empty($wp_query->query_vars['post_status']) || !in_array(
                        $wp_query->query_vars['post_status'],
                        ['trash', 'pending', 'draft']
                    ))
            ) {
                if (isset($wp_query->query_vars['meta_query'])) {
                    $query = $wp_query->query_vars['meta_query'];
                } else {
                    $query = [];
                }
                $year = date('Y');
                $month = '01';
                $day = '01';
                $is_date = false;
                if (isset ($wp_query->query_vars['event_year'])) {
                    $is_date = true;
                    $year = $wp_query->query_vars['event_year'];
                    $start_date = new \DateTime($year . '/01/01');
                    $end_date = new \DateTime($year . '/12/31');
                }
                if (isset ($wp_query->query_vars['event_month'])) {
                    $is_date = true;
                    $month = $wp_query->query_vars['event_month'];
                    $start_date = new \DateTime($year . '/' . $month . '/01');
                    $end_date = new \DateTime($year . '/' . $month . '/31');
                }
                if (isset ($wp_query->query_vars['event_day'])) {
                    $is_date = true;
                    $day = $wp_query->query_vars['event_day'];
                    $start_date = new \DateTime($year . '/' . $month . '/' . $day);
                    $end_date = new \DateTime($year . '/' . $month . '/' . $day);
                }
                if ($is_date) {
                    $check_date_start = $start_date->format('U');
                    $check_date_end = $end_date->format('U');
                    $query[] = ['key' => '_start_ts', 'value' => $check_date_start, 'compare' => '>='];
                    $query[] = ['key' => '_start_ts', 'value' => $check_date_end, 'compare' => '<'];
                }

                if (!empty($query) && is_array($query)) {
                    $wp_query->query_vars['meta_query'] = $query;
                }
            }
        }
    }

    /**
     * Setup actions and filters.
     *
     */
    public function setActionFilters()
    {
        add_action('init', [$this, 'generateRewriteRules'], 10);
        add_filter('query_vars', [$this, 'setupQueryVars'], 10, 1);
        add_action('parse_query', [$this, 'parseQuery'], 999);
        add_filter('post_type_link', [$this, 'filterPermalink'], 10, 4);
        add_filter('term_link', [$this, 'filterTermLink'], 10, 3);
    }

    /**
     * Add extra query vars
     *
     * @param array $vars
     *
     * @return array
     */
    public function setupQueryVars($vars)
    {
        $structure_tags = $this->structure_tags_events + $this->structure_tags_locations + $this->structure_tags_terms;
        foreach ($structure_tags as $placeholder => $information) {
            $vars[] = $information['query'];
        }

        return $vars;
    }
}
