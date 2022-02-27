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
 * The scheduled task class file.
 *
 * @package   local_udacity_sync
 * @copyright 2019 - 2021 Mukudu Ltd - Bham UK
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_udacity_sync\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * The udacitysync scheduled task class.
 *
 * @package   local_udacity_sync
 * @copyright 2019 - 2021 Mukudu Ltd - Bham UK
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class udacitysync extends \core\task\scheduled_task {

    /** @var string $apiurl - the REST endpoint */
    private $apiurl = 'https://www.udacity.com/public-api/v1/courses';

    /** @var string $testingfile - the local file for testing */
    private $testingfile = __DIR__ . '/catalog.json';

    /** @var string $track - the course tracks to sync */
    private $track = 'Web Development';

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskname', 'local_udacity_sync');
    }

    /**
     * Do the job.
     *
     * Throw exceptions on errors (the job will be retried).
     * @param mixed $testing - if this is a test run.
     */
    public function execute($testing = null) {
        global $DB;

        $catlog = null;

        if (isset($testing)) {
            // Check if we have a local JSON file and read that instead - saves on API calls.
            if (file_exists($this->testingfile)) {
                $catlog = json_decode(
                    file_get_contents($this->testingfile));
                if ($catlog === null) {
                    print_error('errorjsonparse',
                        'local_udacity_sync',
                        null,
                        $this->get_last_json_errormsg());
                }
            } // Else we are using curl to get data.
            $testing = true;
        } else {
            $testing = false;
        }

        if (empty($catlog)) {  //
            // Moodle's RESTful cURL class.
            $c = new \curl(array('cache' => $testing));  // Use cache - not when developing :).
            $requestparams = array();   // No params req.

            if ($response = $c->get($this->apiurl, $requestparams)) {   // HTTP GET Method.
                if (($catlog = json_decode($response)) === null) {
                    print_error('Unable to parse file');
                }
                if ($testing && !file_exists($this->testingfile)) {
                    file_put_contents($this->testingfile, $catlog);
                }
            } else {
                $ci = $c->get_info();
                if ( (int) $ci['http_code'] >= 500) {
                    print_error('errorapicall', 'local_udacity_sync', null, $this->get_request_status($ci['http_code']));
                } else {
                    print_error('errorservererror', 'local_udacity_sync', null, $this->get_request_status($ci['http_code']));
                }
            }
        }

        // Course Category to synchronise.
        $synccategory = get_config('local_udacity_sync', 'categorysync');

        foreach ($catlog->courses as $catcourse) {
            if (!empty($catcourse->tracks)) {
                if (in_array($this->track, $catcourse->tracks)) {
                    $record = new \stdClass();
                    $record->idnumber = $catcourse->key;
                    $record->category = $synccategory;
                    $record->fullname = $catcourse->title;
                    $record->shortname = $catcourse->slug;
                    $record->summary = $catcourse->summary;
                    // Add the course image to the summary - being clever.
                    $record->summary .= "\n![alt text]("  . $catcourse->image . ' "' . $record->shortname . '")';
                    $record->summaryformat = FORMAT_MARKDOWN;

                    // Have we got an existing record?
                    if ($course = $DB->get_record('course', array('idnumber' => $catcourse->key))) {
                        // Test if anything has changed.
                        $updaterequired = false;
                        if ($course->shortname == $record->shortname) {
                            if ($course->fullname == $record->fullname) {
                                // Hmmmm!
                                if ($course->summary = $record->summary) {
                                    if ($course->category != $record->category) {
                                        $updaterequired = true;
                                    }
                                } else {
                                    $updaterequired = true;
                                }
                            } else {
                                $updaterequired = true;
                            }
                        } else {
                            $updaterequired = true;
                        }

                        if ($updaterequired) {
                            $record->id = $course->id;
                            update_course($record);
                        }
                    } else {
                        create_course($record);
                    }
                }
            }
        }

        return true;
    }

}