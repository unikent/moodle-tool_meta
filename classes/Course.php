<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * meta enrolments manager.
 *
 * @package tool_meta
 * @copyright  2014 University of Kent
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_meta;

/**
 * Course wrapper.
 */
class Course {
    /** Course */
    public $course;

    public function __construct($courseid) {
        global $DB;

        $this->course = $DB->get_record('course', array(
            'id' => $courseid
        ), '*', MUST_EXIST);
    }

    /**
     * Magic!
     */
    public function __get($name) {
        return $this->course->$name;
    }

    /**
     * Returns all linked courses.
     */
    public function get_linked_courses() {
        global $DB;

        $plugins   = enrol_get_plugins(false);
        $instances = enrol_get_instances($this->course->id, false);
        foreach ($instances as $instance) {
            if ($instance->enrol !== 'meta' || !isset($plugins[$instance->enrol])) {
                continue;
            }

            $plugin = $plugins[$instance->enrol];

            $course = $DB->get_record('course', array(
                'id' => $instance->customint1
            ), '*', MUST_EXIST);

            $users = $DB->count_records('user_enrolments', array(
                'enrolid' => $instance->id
            ));

            $course->enrol = $instance;
            $course->users = $users;

            yield $course;
        }
    }
}