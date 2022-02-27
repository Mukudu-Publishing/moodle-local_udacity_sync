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
 * Index test file.
 *
 * @package   local_udacity_sync
 * @copyright 2019 - 2021 Mukudu Ltd - Bham UK
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

// Protect the page.
require_login();
if (!is_siteadmin()) { // Only site admins.
    print_error('nopermissions', 'core');
}

// Set up the page for display.
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($SITE->fullname . ': ' . get_string('pluginname', 'local_udacity_sync'));
$PAGE->set_url(new moodle_url('/local/udacity_sync/configsettings.php'));
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_udacity_sync'));

$task = new local_udacity_sync\task\udacitysync();
echo html_writer::tag('div', html_writer::tag('p', $task->get_name()));

try {
    $task->execute(1);
} catch (Exception $e) {
    echo $OUTPUT->notification($e->getMessage(), 'error');
    echo $OUTPUT->footer();
    die;
}
echo $OUTPUT->notification('Successfully executed.', 'success');
echo $OUTPUT->footer();
