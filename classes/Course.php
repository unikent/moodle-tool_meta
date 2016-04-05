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
     * Make sure we are allowed to touch certain things.
     */
    private function check_enrol_change_allowed() {
        $enrol = enrol_get_plugin('metaplus');
        if (!$enrol->get_newinstance_link($this->course->id)) {
            print_error('You do not have permissions to add to that course.');
        }
    }

    /**
     * Add a metaplus enrolment link.
     */
    public function add_link($id) {
        $this->check_enrol_change_allowed();

        $enrol = enrol_get_plugin('metaplus');
        return $enrol->add_instance($this->course, array('customint1' => $id));
    }

    /**
     * Delete a link
     */
    public function delete_link($instanceid) {
        $this->check_enrol_change_allowed();

        $plugins   = enrol_get_plugins(false);
        $instances = enrol_get_instances($this->course->id, false);

        if (!isset($instances[$instanceid]) || !isset($plugins[$instances[$instanceid]->enrol])) {
            return false;
        }

        $instance = $instances[$instanceid];
        $plugin = $plugins[$instance->enrol];

        return $plugin->delete_instance($instance);
    }

    /**
     * Delete a link
     */
    public function delete_all_links() {
        $this->check_enrol_change_allowed();

        $plugins   = enrol_get_plugins(false);
        $instances = enrol_get_instances($this->course->id, false);

        foreach ($instances as $instance) {
            if ($instance->enrol === 'metaplus') {
                $plugin = $plugins[$instance->enrol];
                $plugin->delete_instance($instance);
            }
        }
    }

    /**
     * Get courses we *can* link too.
     */
    public function get_possible_links() {
        global $DB;

        if (has_capability('moodle/site:config', \context_system::instance())) {
            $courses = User::get_all_courses();
        } else {
            $courses = User::get_my_courses();
        }

        $exclusions = array($this->course->id);
        $linked = $this->get_linked_courses();
        foreach ($linked as $link) {
            $exclusions[] = $link->id;
        }

        foreach ($courses as $course) {
            if (in_array($course->id, $exclusions)) {
                continue;
            }

            $course->enrolcount = $this->count_enrolments($course);

            yield $course;
        }
    }

    /**
     * Grab the number of enrolments for a given course.
     */
    private function count_enrolments($course) {
        global $DB;

        $enrols = enrol_get_instances($course->id, false);
        $count = 0;
        foreach ($enrols as $enrol) {
            $count += $DB->count_records('user_enrolments', array(
                'enrolid' => $enrol->id
            ));
        }

        return $count;
    }

    /**
     * Returns all linked courses.
     */
    public function get_linked_courses() {
        global $DB;

        $plugins   = enrol_get_plugins(false);
        $instances = enrol_get_instances($this->course->id, false);
        foreach ($instances as $instance) {
            if (($instance->enrol !== 'meta' && $instance->enrol !== 'metaplus') || !isset($plugins[$instance->enrol])) {
                continue;
            }

            $plugin = $plugins[$instance->enrol];

            $course = $DB->get_record('course', array(
                'id' => $instance->customint1
            ));

            if (!$course) {
                continue;
            }

            $users = $DB->count_records('user_enrolments', array(
                'enrolid' => $instance->id
            ));

            $course->enrol = $instance;
            $course->users = $users;
            $course->totalusers = $this->count_enrolments($course);

            yield $course;
        }
    }
}
