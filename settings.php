<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin settings file.
 *
 * @package   local_udacity_sync
 * @copyright 2019 - 2021 Mukudu Ltd - Bham UK
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    
    // Our new settings.
    $settings = new admin_settingpage( 'local_udacity_sync', get_string('settingstitle', 'local_udacity_sync') );
    
    // Course Category list for the drop-down.
    $crsecats = core_course_category::make_categories_list('', 0, ' / ');

    
    // Add a setting field to the settings for this page.
    $settings->add(new admin_setting_configselect(
            /* We define the settings name as local_udacity_sync/categorysync, so we can later
             * retrieve this value by calling get_config(‘local_udacity_sync’, ‘categorysync’) */
            'local_udacity_sync/categorysync',
            get_string('catsyncprompt', 'local_udacity_sync'),
            get_string('catsyncdesc', 'local_udacity_sync'),
            1,
            $crsecats
        )
    );
    
    // Add to the admin settings for localplugins.
    $ADMIN->add( 'localplugins', $settings);
}
