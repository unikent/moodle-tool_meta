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
 * Output rendering for the plugin.
 *
 * @package    tool_meta
 * @copyright  2014 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Implements the plugin renderer
 *
 * @copyright  2014 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_meta_renderer extends plugin_renderer_base {
    /**
     * Prints a list of courses in a dataTables format.
     */
    public function print_course_table() {
        echo <<<HTML
            <div id="coursetable_wrap" class="index_course_table_wrap">
                <div class="options_bar">
                    <h3>Please</h3>
                    <div class="search">
                        <input type="text" id="search_box" name="search_box" placeholder="Search">
                    </div>
                    <h3>and choose a module to manage:</h3>
                </div>
                <table id="coursetable">
                    <thead>
                        <tr>
                            <th id="shortname">Shortname</th>
                            <th id="name">Name</th>
                        </tr>
                    </thead>
                    <tbody>
HTML;

        if (has_capability('moodle/site:config', \context_system::instance())) {
            $courses = \tool_meta\User::get_all_courses();
        } else {
            $courses = \tool_meta\User::get_my_courses();
        }

        foreach ($courses as $course) {
            $editurl = new \moodle_url('/admin/tool/meta/index.php', array(
                'id' => $course->id
            ));

            $courseurl = new \moodle_url('/course/view.php', array(
                'id' => $course->id
            ));

            echo '<tr href="' . $editurl->out(true) .'">';
            echo '<td>' . $course->shortname . '</td>';
            echo '<td><a class="course_link" href="'. $courseurl->out(true) .'" target="_blank">View this course</a> ';
            echo $course->shortname . ':' . $course->fullname . '</td>';
            echo '</tr>';
        }

        echo <<<HTML
                    </tbody>
                </table>
            </div>
HTML;
    }

    /**
     * Print out a table with courses linked to a given module.
     */
    public function print_link_table($course) {
        global $DB;

        $editurl = new \moodle_url('/course/view.php', array(
            'id' => $course->id
        ));

        $courselink = \html_writer::tag('a', $course->shortname, array(
            'href' => $editurl,
            'target' => '_blank'
        ));

        echo <<<HTML
            <div id="linkedcourses_wrap">
                <h3>$courselink has the following meta enrolments (changes will be implemented overnight):</h3>
HTML;

        $rows = array();
        $linked = $course->get_linked_courses();
        foreach ($linked as $linkedcourse) {
            $viewurl = new moodle_url('/course/view.php', array(
                'id' => $linkedcourse->id
            ));

            $deleteurl = new moodle_url('/admin/tool/meta/index.php', array(
                'action' => 'delete',
                'sesskey' => sesskey(),
                'instance' => $linkedcourse->enrol->id
            ));

            $row = '<li>';
            $row .= \html_writer::tag('a', $linkedcourse->shortname, array(
                'href' => $viewurl
            ));
            $row .= $linkedcourse->users . ($linkedcourse->users === '1' ? ' user' : ' users');
            $row .= \html_writer::tag('a', 'x', array(
                'class' => 'delete_link',
                'href' => $deleteurl
            ));

            $rows[] = $row;
        }

        if (count($rows) > 0) {
            echo '<ul id="linkedcourse">';
            echo implode("\n", $rows);
            echo '</ul>';

            $url = new moodle_url('/admin/tool/meta/index.php', array(
                'action' => 'delete_all',
                'sesskey' => sesskey(),
            ));
            echo \html_writer::tag('a', 'Remove all enrolments', array(
                'id' => 'delete_all',
                'href' => $url
            ));
        } else {
            echo '<div id="linkedcourse" class="no_enrolments">No Enrolments</div>';
        }

        $url = new moodle_url('/admin/tool/meta/index.php', array(
            'id' => $course->id,
            'action' => 'add'
        ));
        echo \html_writer::tag('a', 'Add enrolments', array(
            'id' => 'add_modules',
            'href' => $url
        ));

        echo '</div>';
    }

    /**
     * Print out a table with courses to add to a given module.
     */
    public function print_add_table($course) {
        $baseurl = new \moodle_url('/admin/tool/meta/index.php', array(
            'id' => $course->id,
            'action' => 'add',
            'sesskey' => sesskey()
        ));

        echo <<<HTML
            <form id="meta_enrol" name="meta_enrol" action="$baseurl" method="POST">
                <input type="hidden" name="courses" id="courses" value='' />
                <input type="submit" id="meta_enrol_sub" />
            </form>

            <div id="coursetable_wrap" class="add_course_table_wrap">
                <div class="options_bar">
                    <div class="search">
                        <input type="text" id="search_box" name="search_box" placeholder="Search">
                    </div>
                    <h3>then pick below and</h3>
                    <h3></h3>
                    <div class="optbtn" id="add_enrol">add enrollments</div>
                    <div class="optbtn" id="sel">select all</div>
                    <div class="optbtn hidden" id="desel">deselect all</div>
                </div>
                <table id="coursetable">
                    <thead>
                        <tr>
                            <th id="shortname">Shortname</th>
                            <th id="name">Name</th>
                            <th id="enrol">Enrollments</th>
                        </tr>
                    </thead>
                    <tbody>
HTML;

        $courses = $course->get_possible_links();
        foreach ($courses as $course) {
            $courseurl = new \moodle_url('/course/view.php', array(
                'id' => $course->id
            ));

            echo '<tr course="' . $course->id .'">';
            echo '<td>'.$course->shortname.'</td>';
            echo '<td>';
            echo \html_writer::tag('a', 'View this course', array(
                'href' => $courseurl,
                'class' => 'course_link',
                'target' => '_blank'
            ));
            echo $course->shortname . ':' . $course->fullname . '</td>';
            echo '<td>' . $course->enrolcount . '</td>';
            echo '</tr>';
        }

        echo <<<HTML
                    </tbody>
                </table>
            </div>
HTML;
    }
}