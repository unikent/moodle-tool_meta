<?php

/**
 * This is the module page of the kent meta course module. this page shows the 
 * modules the chosen meta module is using the enrollments from it also allows
 * the user to add more enrollments from other courses
 *
 * @package    mod
 * @subpackage kentmetacourse
 * @copyright  2012 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     jwk8
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once("$CFG->dirroot/enrol/meta/locallib.php");
require_once(dirname(__FILE__) . '/lib.php');

global $USER, $DB;

$id = required_param('id', PARAM_INT); // course id
$action     = optional_param('action', '', PARAM_ACTION);
$module = optional_param('module', 0, PARAM_INT);
$confirm    = optional_param('confirm', 0, PARAM_BOOL);
$addcourses = optional_param('courses', 0, PARAM_TEXT);

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$instances = enrol_get_instances($course->id, false);
$plugins   = enrol_get_plugins(false);
$enrol = enrol_get_plugin('meta');
$instcourses = array();

$context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);
$systemcontext = get_context_instance(CONTEXT_SYSTEM);

$url = new moodle_url('/local/kentmetacourse/add.php', array('sesskey'=>sesskey(), 'id'=>$course->id));
$courses = '';

$PAGE->set_context($systemcontext);
$PAGE->set_url($url , array('id'=>$course->id));

require_login($course);

if(!kent_meta_has_edit_course_access() && !has_capability('moodle/site:config', $systemcontext)) {
    throw new required_capability_exception($systemcontext, 'moodle/course:update', 'no_permissions', 'local_kentmetacourse');
}

if (!$enrol->get_newinstance_link($course->id)) {
    redirect(new moodle_url($url, array('id'=>$course->id)));
}

if ($action and confirm_sesskey()) {
		if ($action === 'add' && isset($addcourses)) {
            $addcourses = json_decode($addcourses);
            foreach ($addcourses as $c) {
                $eid = $enrol->add_instance($course, array('customint1' => $c));
            }
            redirect(new moodle_url('/local/kentmetacourse/module.php', array('id'=>$course->id)));
        }
}

foreach ($instances as $instance) {
    if (!isset($plugins[$instance->enrol])) {
        continue;
    }

    $plugin = $plugins[$instance->enrol];

    $edit = array();

    if($instance->enrol === 'meta') {
        $instcourse = $DB->get_record('course', array('id'=>$instance->customint1), '*', MUST_EXIST);
        $instcourses[] = (int)$instcourse->id;
    }
}

$mycourses = kent_meta_course_get_my_courses();
//Setting up the table data
foreach ($mycourses as $mycourse) {

    if(!in_array($mycourse->id, $instcourses)) {
        $enrols = enrol_get_instances($mycourse->id, false);
        $enrolc = 0;
        foreach ($enrols as $enrollment) {
           $enrolc += $DB->count_records('user_enrolments', array('enrolid'=>$enrollment->id)); 
        }
    	$courses .= '<tr course="' . $mycourse->id .'">';
        $courses .= '<td>'.$mycourse->shortname.'</td>';
    	$courses .= '<td><a class="course_link" href="'. $CFG->wwwroot .'/course/view.php?id='. $mycourse->id .'" target="_blank">View this course</a>' . $mycourse->shortname . ':' . $mycourse->fullname . '</td>';
        $courses .= '<td>' . $enrolc . '</td>';
    	$courses .= '</tr>';
    }
}


//Outputting to the page
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(get_string('pluginname', 'local_kentmetacourse'));

$PAGE->set_title('Add meta enrollments');
$PAGE->set_heading('Add meta enrollments');

echo $OUTPUT->header();

$scripts ='<script src="' . $CFG->wwwroot . '/lib/jquery/jquery-1.7.1.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/kentmetacourse/scripts/underscore-min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/kentmetacourse/scripts/jquery.dataTables.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/kentmetacourse/scripts/jquery.placeholder.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/kentmetacourse/scripts/app.js" type="text/javascript"></script>';
$scripts .= '<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/local/kentmetacourse/styles/styles.css">';
echo $scripts;


echo $OUTPUT->heading(get_string('addheader', 'local_kentmetacourse'));

$coursetitle = get_string('coursetitle', 'local_kentmetacourse');

echo '<a id="back_btn" href="' . $CFG->wwwroot . '/local/kentmetacourse/module.php?id='. $id .'">Back</a>';

$surl = new moodle_url($url, array('action'=>'add'));

echo <<<FORM
    <form id="meta_enrol" name="meta_enrol" action="$surl" method="post">
        <input type="hidden" name="courses" id="courses" value='' />
        <input type="submit" id="meta_enrol_sub" />
    </form>
FORM;

echo <<< TABLE
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
TABLE;

echo $courses;

echo <<< TABLE
	  </tbody>
</table>
</div>
TABLE;

echo $OUTPUT->footer();
