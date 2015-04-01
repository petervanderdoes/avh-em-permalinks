<?php
namespace Avh\EmPermalink\Helpers;

/**
 * Class CommonHelper
 *
 * @package   RpsCompetition\Helpers
 * @author    Peter van der Does <peter@avirtualhome.com>
 * @copyright Copyright (c) 2014-2015, AVH Software
 */
class CommonHelper
{
    /**
     * Sort an array on multiple columns
     *
     * @param array $array
     * @param array $cols
     *
     * @return array
     */
    public static function arrayMsort($array, $cols)
    {
        $row_is_object = false;
        $sort_column_array = [];

        // Create multiple arrays using the array $cols.
        // These arrays hold the values of each field that we want to sort on.
        foreach ($cols as $col => $order) {
            $sort_column_array[$col] = [];
            foreach ($array as $key => $row) {
                if (is_object($row)) {
                    $row = (array) $row;
                    $row_is_object = true;
                }
                $sort_column_array[$col][$key] = strtolower($row[$col]);
            }
        }

        $params = [];
        foreach ($cols as $col => $order) {
            $params[] = &$sort_column_array[$col];
            foreach ($order as $order_element) {
                // pass by reference, as required by php 5.3
                $params[] = &$order_element;
                unset($order_element);
            }
        }

        $params[] = &$array;
        call_user_func_array('array_multisort', $params);
        if ($row_is_object) {
            foreach ($array as $key => $row) {
                $array[$key] = (object) $row;
            }
        }

        return $array;
    }

    /**
     * Create a directory if it does not exist.
     *
     * @param string $path
     */
    public static function createDirectory($path)
    {
        if (!file_exists($path)) { // Create the directory if it is missing
            wp_mkdir_p($path);
        }
    }

    /**
     * Get the thumbnail to display on the My Entries page.
     *
     * @param $current_competition
     *
     * @return mixed
     */
    public static function getCompetitionThumbnail($current_competition)
    {
        $image = [];
        $image['Color Digital'] = '/thumb-comp-digital-color.jpg';
        $image['Color Prints'] = '/thumb-comp-print-color.jpg';
        $image['B&W Digital'] = '/thumb-comp-digital-bw.jpg';
        $image['B&W Prints'] = '/thumb-comp-print-bw.jpg';
        $img = $image[$current_competition->Medium];

        return $img;
    }

    /**
     * Get the Dynamic Pages.
     *
     * These are the pages where we implement javascript to get different competitions and seasons within the page.
     *
     * @return array
     */
    public static function getDynamicPages()
    {
        $options = get_option('avh-rps');
        $pages_array = [$options['monthly_entries_post_id'] => true, $options['monthly_winners_post_id'] => true];

        return $pages_array;
    }

    /**
     * Improve the default WordPress plugins_url.
     * The standard function requires a file at the end of the 2nd parameter.
     *
     * @param string $file
     * @param string $directory
     *
     * @return string
     */
    public static function getPluginUrl($file, $directory)
    {
        if (is_dir($directory)) {
            $directory .= '/foo';
        }

        return plugins_url($file, $directory);
    }

    /**
     * Get the user classification based on the medium
     *
     * @param integer $userID
     * @param string  $medium
     *
     * @return string
     */
    public static function getUserClassification($userID, $medium)
    {
        switch ($medium) {
            case 'B&W Digital':
                $index = get_user_meta($userID, 'rps_class_bw', true);
                break;
            case 'Color Digital':
                $index = get_user_meta($userID, 'rps_class_color', true);
                break;
            case 'B&W Prints':
                $index = get_user_meta($userID, 'rps_class_print_bw', true);
                break;
            case 'Color Prints':
                $index = get_user_meta($userID, 'rps_class_print_color', true);
                break;
            default:
                $index = '';
        }

        return ucfirst($index);
    }

    /**
     * Check if the user is a paid member
     *
     * @param integer|null $user_id UserID to check
     *
     * @return boolean true if a paid member, false if non-existing user or non-paid member.`
     */
    public static function isPaidMember($user_id = null)
    {
        if (is_numeric($user_id)) {
            $user = get_user_by('id', $user_id);
        } else {
            $user = wp_get_current_user();
        }

        if (empty($user)) {
            return false;
        }

        return in_array('s2member_level4', (array) $user->roles);
    }

    /**
     * Check if user pressed cancel and if so redirect the user
     *
     * @param \Symfony\Component\Form\Form $form   The Form that was submitted
     * @param string                       $cancel The field to check for cancellation
     * @param string                       $redirect_to
     */
    public static function isRequestCanceled($form, $cancel, $redirect_to)
    {
        if ($form->get($cancel)
                 ->isClicked()
        ) {
            wp_redirect($redirect_to);
            exit();
        }
    }

    /**
     * Check if the given date in the given format is valid.
     *
     * @param  string $date
     * @param string  $format
     *
     * @return bool
     */
    public static function isValidDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = \DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) == $date;
    }
}
