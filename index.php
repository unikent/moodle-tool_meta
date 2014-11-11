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

if (has_capability('moodle/site:config', \context_system::instance())) {
    admin_externalpage_setup('metamanager');
} else {
    if (!\local_kent\User::has_course_update_role($USER->id)) {
        print_error('accessdenied', 'admin');
    }
}

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('migrate');
$PAGE->requires->jquery_plugin('dataTables', 'tool_meta');
$PAGE->requires->js('/admin/tool/meta/script/underscore.min.js');
$PAGE->requires->js('/admin/tool/meta/script/app.js');
$PAGE->requires->css('/admin/tool/meta/styles.css');

$id = optional_param('id', false, PARAM_INT);
$action = optional_param('action', false, PARAM_ALPHA);

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
            $instanceid = required_param('instance', PARAM_INT);
            $course->delete_link($instanceid);

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
    $renderer->print_course_table();
}

echo $OUTPUT->footer();