<?php

/**
 * This is the index page of the kent meta course module. this page lists the users
 * meta module, allows them to search said list, and allows them to pick a module
 * to manage the meta enrollments for
 *
 * @package    mod
 * @subpackage kentmetacourse
 * @copyright  2012 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     jwk8
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

global $USER, $DB;


$systemcontext = get_context_instance(CONTEXT_SYSTEM);

require_login();


if(!kent_meta_has_edit_course_access() && !has_capability('moodle/site:config', $systemcontext)) {
    throw new required_capability_exception($systemcontext, 'moodle/course:update', 'no_permissions', 'local_kentmetacourse');
}

//Get list of courses that the user has access to but which are not in the connect_course_dets table.
$courses = kent_meta_course_get_my_meta_courses();


//Set up standard moodle page information
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url('/local/kentmetacourse/index.php');
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(get_string('pluginname', 'local_kentmetacourse'));
$PAGE->set_title(get_string('pluginname', 'local_kentmetacourse'));
$PAGE->set_heading(get_string('pluginname', 'local_kentmetacourse'));


//Set up the scripts for the page and then echo them out to the page
$scripts ='<script src="' . $CFG->wwwroot . '/local/kentmetacourse/scripts/jquery-1.7.1.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/kentmetacourse/scripts/underscore-min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/kentmetacourse/scripts/jquery.dataTables.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/kentmetacourse/scripts/jquery.placeholder.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/kentmetacourse/scripts/app.js" type="text/javascript"></script>';
$scripts .= '<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/local/kentmetacourse/styles/styles.css">';
echo $scripts;

//echo out the heading etc to the page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_kentmetacourse'));

//echo out the base table html to the page for datatables to pick up
echo <<< TABLE
<div id="coursetable_wrap" class="index_course_table_wrap">
<div class="options_bar">
	<h3>Please</h3>
	<div class="search">
		<input type="text" id="search_box" name="search_box" placeholder="Search">
	</div>
	<h3>and choose a Module to mange:</h3>
</div>
<table id="coursetable">
    <thead>
        <tr>
            <th id="shortname">Shortname</th>
            <th id="name">Name</th>
        </tr>
    </thead>
    <tbody>
TABLE;

foreach ($courses as $course) {
	echo '<tr href="' . $CFG->wwwroot . '/local/kentmetacourse/module.php?id='. $course->id .'">';
	echo '<td>' . $course->shortname . '</td>';
	echo '<td><a class="course_link" href="'. $CFG->wwwroot .'/course/view.php?id='. $course->id .'" target="_blank">View this course</a> ' . $course->shortname . ':' . $course->fullname . '</td>';
	echo '</tr>';
}

echo <<< TABLE
	  </tbody>
</table>
</div>
TABLE;

// and you guessed it! this outputs the footer
echo $OUTPUT->footer();
