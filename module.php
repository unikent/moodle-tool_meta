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
$instanceid = optional_param('instance', 0, PARAM_INT);
$confirm    = optional_param('confirm', 0, PARAM_BOOL);

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$instances = enrol_get_instances($course->id, false);
$plugins   = enrol_get_plugins(false);
$enrol = enrol_get_plugin('meta');

$context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);
$systemcontext = get_context_instance(CONTEXT_SYSTEM);

$url = new moodle_url('/local/kentmetacourse/module.php', array('sesskey'=>sesskey(), 'id'=>$course->id));
$linkedcourse = '';
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
		if ($action === 'delete') {
            if (isset($instances[$instanceid]) and isset($plugins[$instances[$instanceid]->enrol])) {
                $instance = $instances[$instanceid];
                $plugin = $plugins[$instance->enrol];

                if ($confirm) {
                    $plugin->delete_instance($instance);
                    redirect($PAGE->url);
                }
               
                echo $OUTPUT->header();
                $yesurl = new moodle_url($url, array('id'=>$course->id, 'action'=>'delete', 'instance'=>$instance->id, 'confirm'=>1,'sesskey'=>sesskey()));
                $displayname = $plugin->get_instance_name($instance);
                $users = $DB->count_records('user_enrolments', array('enrolid'=>$instance->id));
                $message = get_string('deleteinstanceconfirm', 'enrol', array('name'=>$displayname, 'users'=>$users));
                echo $OUTPUT->confirm($message, $yesurl, $PAGE->url);
                echo $OUTPUT->footer();
                die();
            }
        } else if ($action === 'delete_all') {

            if($confirm) {
                foreach ($instances as $instance) {
                    $plugin = $plugins[$instance->enrol];
                    $plugin->delete_instance($instance);
                }

                redirect($PAGE->url);
            }

            echo $OUTPUT->header();
            $yesurl = new moodle_url($url, array('id'=>$course->id, 'action'=>'delete_all', 'confirm'=>1,'sesskey'=>sesskey()));
            $message = get_string('delete_all', 'local_kentmetacourse');
            echo $OUTPUT->confirm($message, $yesurl, $PAGE->url);
            echo $OUTPUT->footer();
            die();

        }
}

//Setting up the enrollments bar
foreach ($instances as $instance) {
	if (!isset($plugins[$instance->enrol])) {
        continue;
    }

    $plugin = $plugins[$instance->enrol];

    $edit = array();

    if($instance->enrol === 'meta') {
    	$instcourse = $DB->get_record('course', array('id'=>$instance->customint1), '*', MUST_EXIST);
    	$instusers = $DB->count_records('user_enrolments', array('enrolid'=>$instance->id));
    	$aurl = new moodle_url($url, array('action'=>'delete', 'instance'=>$instance->id));
    	$linkedcourse .= '<li><a href="' . $CFG->wwwroot . '/course/view.php?id='. $instcourse->id .'">' . $instcourse->shortname . '</a> - '. $instusers . ($instusers === '1' ? ' user' : ' users') . '<a class="delete_link" href="'. $aurl .'">x</a></li>';
    }
}

$editbtns = '';

if($linkedcourse === '') {
    $linkedcourse = '<div id="linkedcourse" class="no_enrollments">No Enrollments</div>';
    $deleteall = '';
} else {
    $linkedcourse = '<ul id="linkedcourse">' . $linkedcourse . '</ul>';
    $durl = new moodle_url($url, array('action'=>'delete_all'));
    $editbtns .= "<a id='delete_all' href='$durl'>Remove all enrollments</a>";
}

$addurl = new moodle_url('/local/kentmetacourse/add.php', array('id'=>$course->id));
$editbtns .= "<a id='add_modules' href='$addurl'>Add enrollments</a>";

//Outputting to the page
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(get_string('pluginname', 'local_kentmetacourse'));

$PAGE->set_title('Manage meta enrollments');
$PAGE->set_heading('Manage meta enrollments');

echo $OUTPUT->header();

$scripts ='<script src="' . $CFG->wwwroot . '/lib/jquery/jquery-1.7.1.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/kentmetacourse/scripts/underscore-min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/kentmetacourse/scripts/jquery.dataTables.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/kentmetacourse/scripts/jquery.placeholder.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/kentmetacourse/scripts/app.js" type="text/javascript"></script>';
$scripts .= '<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/local/kentmetacourse/styles/styles.css">';
echo $scripts;


echo $OUTPUT->heading(get_string('manageheader', 'local_kentmetacourse'));

$coursetitle = get_string('coursetitle', 'local_kentmetacourse');

echo '<a id="back_btn" href="' . $CFG->wwwroot . '/local/kentmetacourse/index.php">Back</a>';

echo <<< LINKEDCOURSES
	<div id="linkedcourses_wrap">
        <h3><a href="$CFG->wwwroot/course/view.php?id=$course->id" target="_blank">$course->shortname</a> $coursetitle</h3>
		$linkedcourse
        $editbtns
	</div>
LINKEDCOURSES;

echo $OUTPUT->footer();