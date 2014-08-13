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
 * @package    tool_meta
 * @copyright  2014 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_meta;

/**
 * A set of basic utility functions for the plugin.
 */
class User {
    /**
     * Returns all courses.
     */
    public static function get_all_courses() {
        global $DB;

        $rs = $DB->get_recordset_select('course', 'id > 1');
        foreach ($rs as $course) {
            yield $course;
        }
        $rs->close();
    }

    /**
     * Returns all courses a user can update.
     */
    public static function get_my_courses() {
        $courses = enrol_get_my_courses('id');
        foreach ($courses as $id => $course) {
            $context = \context_course::instance($id);
            if (has_capability('moodle/course:enrolconfig', $context) && has_capability('enrol/meta:config', $context)) {
                yield $course;
            }
        }
    }

    /**
     * Returns true if a user has any access to edit any course.
     */
    public static function has_course_update_role() {
        global $DB, $USER;

        if (has_capability('moodle/site:config', \context_system::instance())) {
            return true;
        }

        $sql = <<<SQL
            SELECT COUNT(ra.id)
            FROM {role_assignments} ra
            WHERE ra.userid = :userid AND ra.roleid IN (
                SELECT rc.roleid
                FROM {role_capabilities} rc
                WHERE rc.capability = :capability AND rc.permission = 1
                GROUP BY rc.roleid
            )
SQL;
        return $DB->count_records_sql($sql, array(
            'userid' => $USER->id,
            'capability' => 'moodle/course:update'
        )) > 0;
    }
}