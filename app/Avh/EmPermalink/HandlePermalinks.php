<?php

namespace Avh\EmPermalink;

/**
 * Class HandlePermalinks
 *
 * @package   Avh\EmPermalink
 * @author    Peter van der Does <peter@avirtualhome.com>
 * @copyright Copyright (c) 2015, AVH Software
 */
class HandlePermalinks
{
    /**
     * Rewrite tags that can be used in permalink structures.
     * These are translated into the regular expressions stored in
     * {@link WP_Rewrite::$rewritereplace} and are rewritten to the
     * query variables listed in {@link WP_Rewrite::$queryreplace}.
     * The key of the array is the actual structure tag
     * 'regex'
     * Regular expressions to be substituted into rewrite rules in place
     * of rewrite tags, see {@link WP_Rewrite::$rewritecode}.
     * 'query'
     * Query variables that rewrite tags map to, see {@link WP_Rewrite::$rewritecode}.
     * The query should NOT end with the = sign, we add this later.
     * 'replacement'
     * The replacement for the structure tag.
     *
     * @var array
     */
    private $structure_tags_events    = [
        '%event_year%'            => ['regex' => '([0-9]{4})', 'query' => 'event_year', 'replacement' => '0'],
        '%event_monthnum%'        => ['regex' => '([0-9]{1,2})', 'query' => 'event_monthnum', 'replacement' => '0'],
        '%event_monthname_long%'  => ['regex' => '([^/]+)', 'query' => 'event_monthname_long', 'replacement' => ''],
        '%event_monthname_short%' => ['regex' => '([^/]+)', 'query' => 'event_monthname_short', 'replacement' => ''],
        '%event_day%'             => ['regex' => '([0-9]{1,2})', 'query' => 'event_day', 'replacement' => '0'],
        '%event_name%'            => ['regex' => '([^/]+)', 'query' => EM_POST_TYPE_EVENT, 'replacement' => ''],
        '%event_owner%'           => ['regex' => '([^/]+)', 'query' => 'event_owner', 'replacement' => ''],
        '%event_location%'        => ['regex' => '([^/]+)', 'query' => 'location', 'replacement' => ''],
        '%event_category%'        => ['regex' => '([^/]+)', 'query' => 'event_category', 'replacement' => ''],
    ];
    private $structure_tags_locations = [
        '%location_name%' => ['regex' => '([^/]+)', 'query' => EM_POST_TYPE_LOCATION, 'replacement' => '']
    ];
    private $structure_tags_terms     = [
        '%category_name%' => ['regex' => '([^/]+)', 'query' => EM_TAXONOMY_CATEGORY, 'replacement' => '']
    ];

    /**
     * Constructor.
     */
    public function __construct()
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
     * @return string
     */
    public function filterPermalink($post_link, $post, $leavename = false, $sample = false)
    {
        global $wp_rewrite;

        if ($wp_rewrite->permalink_structure !== '') {
            switch ($post->post_type) {
                case EM_POST_TYPE_EVENT:
                case 'event-recurring':
                    $post_link = $this->filterPermalinkEvent($post_link, $post, $leavename, $sample);
                    break;

                case EM_POST_TYPE_LOCATION:
                    $post_link = $this->filterPermalinkLocation($post_link, $post, $leavename, $sample);
                    break;
            }
        }

        return $post_link;
    }

    /**
     * Filter the canonical redirect URL.
     *
     * @param string $redirect_url  The redirect URL.
     * @param string $requested_url The requested URL.
     *
     * @return bool|string
     */
    public function filterRedirectCanonical($redirect_url, $requested_url)
    {
        global $wp_query;
        $em_post_type = [EM_POST_TYPE_EVENT => true, 'event-recurring' => true, EM_POST_TYPE_LOCATION => true];
        if (isset($wp_query->query['post_type'])) {
            $post_type = $wp_query->query['post_type'];

            if (array_key_exists($post_type, $em_post_type)) {
                return false;
            }
        }

        return $redirect_url;
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
                    $rewritecode    = ['%category_name%'];
                    $rewritereplace = [$term->slug];
                    $termlink       = str_replace($rewritecode, $rewritereplace, $termlink);
                    $termlink       = user_trailingslashit($termlink, 'single');
                }
        }

        return $termlink;
    }

    /**
     * Setup permalinks
     * Create new placeholders and add the default permalink
     */
    public function generateRewriteRules()
    {
        /**
         * Add new rewrite placeholders
         */
        $structure_tags = $this->structure_tags_events + $this->structure_tags_locations + $this->structure_tags_terms;
        foreach ($structure_tags as $placeholder => $information) {
            $regex     = $information['regex'];
            $query_var = $information['query'] . '=';
            add_rewrite_tag($placeholder, $regex, $query_var);
        }

        add_permastruct(EM_POST_TYPE_EVENT,
                        get_option('dbem_cp_events_slug', EM_POST_TYPE_EVENT_SLUG),
                        ['with_front' => false]);
        add_permastruct(EM_POST_TYPE_LOCATION,
                        get_option('dbem_cp_locations_slug', EM_POST_TYPE_LOCATION_SLUG),
                        ['with_front' => false]);
        add_permastruct(EM_TAXONOMY_CATEGORY,
                        get_option('dbem_taxonomy_category_slug', EM_TAXONOMY_CATEGORY_SLUG) . '/%category_name%',
                        ['hierarchical' => true, 'with_front' => false]);

        flush_rewrite_rules();
    }

    /**
     * Parse the query vars.
     * Some of it has already been parsed by Event Manager, we just add our added query vars.
     *
     * @param \WP_Query $wp_query Passed by reference.
     */
    public function parseQuery($wp_query)
    {
        if (!is_admin()) {
            if ($this->validatePostType($wp_query, [EM_POST_TYPE_EVENT, 'event-recurring'])) {
                if (empty($wp_query->query_vars['post_status']) || (!$this->validatePostStatus($wp_query,
                                                                                               [
                                                                                                   'trash',
                                                                                                   'pending',
                                                                                                   'draft'
                                                                                               ]))
                ) {
                    $query              = avh_array_get($wp_query->query_vars, 'meta_query', []);
                    $start_year         = '1970';
                    $end_year           = '2038';
                    $start_month        = '01';
                    $end_month          = '12';
                    $start_day          = '01';
                    $end_day            = '31';
                    $is_date            = false;
                    $correct_month_name = true;

                    if (isset($wp_query->query_vars['event_year'])) {
                        $is_date    = true;
                        $start_year = $wp_query->query_vars['event_year'];
                        $end_year   = $start_year;
                    }

                    if (isset($wp_query->query_vars['event_monthnum'])) {
                        $is_date     = true;
                        $start_month = $wp_query->query_vars['event_monthnum'];
                        $end_month   = $start_month;
                    }

                    if (isset($wp_query->query_vars['event_monthname_short'])) {
                        $is_date  = true;
                        $tmp_date = \DateTime::createFromFormat('M', $wp_query->query_vars['event_monthname_short']);
                        if ($tmp_date !== false) {
                            $start_month = $tmp_date->format('m');
                            $end_month   = $start_month;
                        } else {
                            $correct_month_name = false;
                        }
                    }

                    if (isset($wp_query->query_vars['event_monthname_long'])) {
                        $is_date  = true;
                        $tmp_date = \DateTime::createFromFormat('F', $wp_query->query_vars['event_monthname_long']);
                        if ($tmp_date !== false) {
                            $start_month = $tmp_date->format('m');
                            $end_month   = $start_month;
                        } else {
                            $correct_month_name = false;
                        }
                    }

                    if (isset($wp_query->query_vars['event_day'])) {
                        $is_date   = true;
                        $start_day = $wp_query->query_vars['event_day'];
                        $end_day   = $start_day;
                    }

                    if ($is_date && $correct_month_name) {
                        $start_date = $this->getDateTime($start_year, $start_month, $start_day);
                        if ($start_date !== false) {
                            if ($start_day !== $end_day) {
                                $end_date = $this->getDateTime($end_year, $end_month, $start_day);
                                $end_date->modify('last day of this month');
                            } else {
                                $end_date = $this->getDateTime($end_year, $end_month, $end_day);
                            }
                            if ($end_date !== false) {
                                $check_date_start = $start_date->format('U');
                                $end_date->add(new \DateInterval('PT23H59M59S'));
                                $check_date_end = $end_date->format('U');
                                $query[]        = [
                                    'key'     => '_start_ts',
                                    'value'   => [$check_date_start, $check_date_end],
                                    'compare' => 'BETWEEN'
                                ];
                            }
                        }
                    }

                    if (isset($wp_query->query_vars['event_category'])) {
                    }
                    if (!empty($query) && is_array($query)) {
                        $wp_query->query_vars['meta_query'] = $query;
                    }
                }
            }
        }
    }

    /**
     * Setup actions and filters.
     */
    public function setActionFilters()
    {
        add_action('init', [$this, 'generateRewriteRules'], 10);
        add_filter('query_vars', [$this, 'setupQueryVars'], 10, 1);
        add_action('parse_query', [$this, 'parseQuery'], 999);
        add_filter('post_type_link', [$this, 'filterPermalink'], 10, 4);
        add_filter('post_link', [$this, 'filterPermalink'], 10, 3);
        add_filter('term_link', [$this, 'filterTermLink'], 10, 3);
        add_filter('redirect_canonical', [$this, 'filterRedirectCanonical'], 10, 2);
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
        foreach ($structure_tags as $information) {
            $vars[] = $information['query'];
        }

        return $vars;
    }

    /**
     * Filter the permalink for Event posts.
     *
     * @param string   $post_link The post's permalink.
     * @param \WP_Post $post      The post in question.
     * @param bool     $leavename Whether to keep the post name.
     * @param bool     $sample    Is it a sample permalink.
     *
     * @return string
     */
    private function filterPermalinkEvent($post_link, $post, $leavename, $sample)
    {
        $EM_Event            = em_get_event($post->ID, 'post_id');
        $replace_tags        = array_keys($this->structure_tags_events);
        $replace_information = [];

        $event_start_date                                                      = new \DateTime($EM_Event->event_start_date);
        $this->structure_tags_events['%event_year%']['replacement']            = $event_start_date->format('Y');
        $this->structure_tags_events['%event_monthnum%']['replacement']        = $event_start_date->format('m');
        $this->structure_tags_events['%event_monthname_short%']['replacement'] = strtolower($event_start_date->format('M'));
        $this->structure_tags_events['%event_monthname_long%']['replacement']  = strtolower($event_start_date->format('F'));
        $this->structure_tags_events['%event_day%']['replacement']             = $event_start_date->format('d');

        if (strpos($post_link, '%event_category%') !== false) {
            $this->handleEventCategory($EM_Event);
        }

        if (strpos($post_link, '%event_location%') !== false) {
            $this->handleEventLocation($EM_Event);
        }

        if (strpos($post_link, '%event_owner%') !== false) {
            $this->handleEventOwner($EM_Event);
        }

        if (strpos($post_link, '%event_name%') !== false) {
            $this->handleEventName($leavename, $EM_Event);
        }

        foreach ($this->structure_tags_events as $information) {
            $replace_information[] = $information['replacement'];
        }

        $post_link = str_replace($replace_tags, $replace_information, $post_link);
        if ('draft' != $post->post_status) {
            $post_link = user_trailingslashit($post_link, 'single');
        }

        return $post_link;
    }

    /**
     * Filter the permalink for Location posts.
     *
     * @param string   $post_link The post's permalink.
     * @param \WP_Post $post      The post in question.
     * @param bool     $leavename Whether to keep the post name.
     * @param bool     $sample    Is it a sample permalink.
     *
     * @return string
     */
    private function filterPermalinkLocation($post_link, $post, $leavename, $sample)
    {
        $EM_Location         = em_get_location($post->ID, 'post_id');
        $replace_tags        = array_keys($this->structure_tags_locations);
        $replace_information = [];

        if (strpos($post_link, '%location_name%') !== false) {
            if (!$leavename) {
                $this->structure_tags_locations['%location_name%']['replacement'] = $EM_Location->location_slug;
            } else {
                $this->structure_tags_locations['%location_name%']['replacement'] = '%postname%';
            }
        }

        foreach ($this->structure_tags_locations as $information) {
            $replace_information[] = $information['replacement'];
        }

        $post_link = str_replace($replace_tags, $replace_information, $post_link);
        $post_link = user_trailingslashit($post_link, 'single');

        return $post_link;
    }

    /**
     * Get DateTime from given date.
     * Returns false if the given date parameters are invalid.
     *
     * @param $year  string
     * @param $month string
     * @param $day   string
     *
     * @return bool|\DateTime
     */
    private function getDateTime($year, $month, $day)
    {
        $date       = \DateTime::createFromFormat('Y/m/d H:i:s',
                                                  $year . '/' . $month . '/' . $day . '00:00:00');
        $date_error = \DateTime::getLastErrors();
        if (!empty($date_error['warning_count'])) {
            return false;
        }

        return $date;
    }

    /**
     * Handle the %event_category% substitution
     *
     * @param \EM_Event $EM_Event
     */
    private function handleEventCategory($EM_Event)
    {
        $EM_Categories = $EM_Event->get_categories();
        if ($EM_Categories->categories) {
            usort($EM_Categories->categories, '_usort_terms_by_ID');
            $category_object = $EM_Categories->categories[0];
            $category_object = get_term($category_object, EM_TAXONOMY_CATEGORY);

            $this->structure_tags_events['%event_category%']['replacement'] = $category_object->slug;
        }
    }

    /**
     * Handle the %event_location% substitution
     *
     * @param \EM_Event $EM_Event
     */
    private function handleEventLocation($EM_Event)
    {
        $EM_Location = em_get_location($EM_Event->location_id);

        $this->structure_tags_events['%event_location%']['replacement'] = $EM_Location->location_slug;
    }

    /**
     * Handle the %event_name% substitution
     *
     * @param bool      $leavename
     * @param \EM_Event $EM_Event
     */
    private function handleEventName($leavename, $EM_Event)
    {
        if (!$leavename) {
            $this->structure_tags_events['%event_name%']['replacement'] = $EM_Event->event_slug;
        } else {
            $this->structure_tags_events['%event_name%']['replacement'] = '%postname%';
        }
    }

    /**
     * Handle the %event_owner% substitution
     *
     * @param \EM_Event $EM_Event
     */
    private function handleEventOwner($EM_Event)
    {
        $authordata = get_userdata($EM_Event->event_owner);

        $this->structure_tags_events['%event_owner%']['replacement'] = $authordata->user_nicename;
    }

    /**
     * Validate the post status
     *
     * @param \WP_Query $wp_query
     * @param array     $post_status
     *
     * @return bool
     */
    private function validatePostStatus($wp_query, $post_status)
    {
        $valid = false;
        if (isset($wp_query->query_vars['post_status'])) {
            $valid = isset($post_status[ $wp_query->query_vars['post_status'] ]);
        }

        return $valid;
    }

    /**
     * Validate Post Type
     *
     * @param \WP_Query $wp_query
     * @param array     $post_type
     *
     * @return bool
     */
    private function validatePostType($wp_query, $post_type)
    {
        $valid = false;
        if (isset($wp_query->query_vars['post_type'])) {
            $valid = isset($post_type[ $wp_query->query_vars['post_type'] ]);
        }

        return $valid;
    }
}
