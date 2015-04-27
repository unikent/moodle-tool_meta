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
    public function print_course_table($courses) {
        $rows = "";
        foreach ($courses as $course) {
            $editurl = new \moodle_url('/admin/tool/meta/index.php', array(
                'id' => $course->id
            ));

            $courseurl = new \moodle_url('/course/view.php', array(
                'id' => $course->id
            ));

            $rows .= '<tr href="' . $editurl->out(true) .'">';
            $rows .= '<td>' . $course->shortname . '</td>';
            $rows .= '<td><a class="course_link" href="'. $courseurl->out(true) .'" target="_blank">View this course</a> ';
            $rows .= $course->shortname . ':' . $course->fullname . '</td>';
            $rows .= '</tr>';
        }

        echo <<<HTML5
            <div id="coursetable_wrap" class="index_course_table_wrap container-fluid">
                <div class="row">
                    <div class="col-xs-12">
                        <p>Please select a module to manage.</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <input type="text" id="search_box" name="search_box" placeholder="Filter..." class="form-control" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <div class="table-responsive">
                            <table id="coursetable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th id="shortname">Shortname</th>
                                        <th id="name">Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    $rows
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
HTML5;
    }

    /**
     * Print out a table with courses linked to a given module.
     */
    public function print_link_table($course) {
        global $DB;

        $editurl = new \moodle_url('/course/view.php', array(
            'id' => $course->id
        ));

        $rows = array();
        $linked = $course->get_linked_courses();
        foreach ($linked as $linkedcourse) {
            $viewurl = new moodle_url('/course/view.php', array(
                'id' => $linkedcourse->id
            ));

            $deleteurl = new moodle_url('/admin/tool/meta/index.php', array(
                'id' => $course->id,
                'action' => 'delete',
                'sesskey' => sesskey(),
                'instance' => $linkedcourse->enrol->id
            ));

            $row = '<li>';
            $row .= \html_writer::tag('a', $linkedcourse->shortname, array(
                'href' => $viewurl
            ));
            $row .= ' (' .$linkedcourse->users . ($linkedcourse->users === '1' ? ' user' : ' users') . ')';
            $row .= \html_writer::tag('a', 'x', array(
                'class' => 'delete_link',
                'href' => $deleteurl
            ));

            $rows[] = $row;
        }

        if (count($rows) > 0) {
            $courselink = \html_writer::tag('a', $course->shortname, array(
                'href' => $editurl,
                'target' => '_blank'
            ));

            $rows = implode("\n", $rows);
            echo <<<HTML5
                <div class="row">
                    <div class="col-xs-12">
                        <p>{$courselink} has the following meta enrolments:</p>
                        <ul id="linkedcourse">
                            $rows
                        </ul>
                    </div>
                </div>
HTML5;

            echo \html_writer::tag('a', 'Remove all enrolments', array(
                'class' => 'btn btn-danger',
                'href' => new moodle_url('/admin/tool/meta/index.php', array(
                    'id' => $course->id,
                    'action' => 'deleteall',
                    'sesskey' => sesskey(),
                ))
            ));
        } else {
            echo '<p>No Enrolments</p>';
        }

        echo \html_writer::tag('a', 'Add enrolments', array(
            'class' => 'btn btn-primary',
            'href' => new moodle_url('/admin/tool/meta/index.php', array(
                'id' => $course->id,
                'action' => 'add'
            ))
        ));

    }

    /**
     * Print out a table with courses to add to a given module.
     */
    public function print_add_table($course) {
        $formurl = new \moodle_url('/admin/tool/meta/index.php', array(
            'id' => $course->id,
            'action' => 'submit',
            'sesskey' => sesskey()
        ));

        echo <<<HTML5
            <form id="meta_enrol" name="meta_enrol" action="$formurl" method="POST">
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
                    <div class="optbtn" id="add_enrol">add enrolments</div>
                    <div class="optbtn" id="sel">select all</div>
                    <div class="optbtn hidden" id="desel">deselect all</div>
                </div>
                <table id="coursetable">
                    <thead>
                        <tr>
                            <th id="shortname">Shortname</th>
                            <th id="name">Name</th>
                            <th id="enrol">Enrolments</th>
                        </tr>
                    </thead>
                    <tbody>
HTML5;

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

        echo <<<HTML5
                    </tbody>
                </table>
            </div>
HTML5;
    }
}