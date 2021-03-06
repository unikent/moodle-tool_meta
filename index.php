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


require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

$id = optional_param('id', false, PARAM_INT);
$action = optional_param('action', false, PARAM_ALPHA);

$PAGE->set_context(\context_system::instance());
$PAGE->set_url('/admin/tool/meta/index.php', array(
    'id' => $id,
    'action' => $action
));

// Capability checks.
if (has_capability('moodle/site:config', \context_system::instance())) {
    admin_externalpage_setup('metamanager');
} else {
    if (!\local_kent\User::has_course_update_role($USER->id)) {
        print_error('accessdenied', 'admin');
    }
}

$PAGE->requires->js(new \moodle_url('https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.8.3/underscore-min.js'));
$PAGE->requires->js_call_amd('tool_meta/app', 'init', array());
$PAGE->requires->css('/admin/tool/meta/less/build/build.css');

if ($id) {
    $course = new \tool_meta\Course($id);
    $PAGE->navbar->add($course->shortname);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_meta'));

$renderer = $PAGE->get_renderer('tool_meta');
if ($id) {
    switch ($action) {
        case 'add':
            $renderer->print_add_table($course);
            break;

        case 'deleteall':
            require_sesskey();
            $course->delete_all_links();

            $renderer->print_link_table($course);
            break;

        case 'delete':
            require_sesskey();
            $courseid = required_param('courseid', PARAM_INT);
            $instanceid = required_param('instance', PARAM_INT);
            $course->delete_link($instanceid, $courseid);

            $renderer->print_link_table($course);
            break;

        case 'submit':
            require_sesskey();

            $links = required_param('courses', PARAM_RAW);
            $links = json_decode($links);
            foreach ($links as $link) {
                $course->add_link($link);
            }

            $renderer->print_link_table($course);
            break;

        default:
            $renderer->print_link_table($course);
            break;
    }
} else {
    $courses = array();
    if (has_capability('moodle/site:config', \context_system::instance())) {
        $courses = \tool_meta\User::get_all_courses();
    } else {
        $courses = \tool_meta\User::get_my_courses();
    }

    $renderer->print_course_table($courses);
}

echo $OUTPUT->footer();
