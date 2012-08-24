<?php
defined('MOODLE_INTERNAL') || die();

function kent_meta_course_get_my_meta_courses($fields = NULL, $sort = 'sortorder ASC') {
    global $CFG, $DB, $USER;

    // Guest account does not have any courses
    if (isguestuser() or !isloggedin()) {
        return(array());
    }

    $systemcontext = get_context_instance(CONTEXT_SYSTEM);

    if(has_capability('moodle/site:config', $systemcontext)) {
    	$sql = 'SELECT c.id, c.fullname, c.shortname FROM {course} AS c LEFT JOIN {connect_course_dets} AS cd ON c.id = cd.course WHERE isnull(cd.course)';
    	$courses = $DB->get_records_sql($sql);
    	return $courses;
    }


    $basefields = array('id','fullname', 'shortname');

    if (empty($fields)) {
        $fields = $basefields;
    } else if (is_string($fields)) {
        // turn the fields from a string to an array
        $fields = explode(',', $fields);
        $fields = array_map('trim', $fields);
        $fields = array_unique(array_merge($basefields, $fields));
    } else if (is_array($fields)) {
        $fields = array_unique(array_merge($basefields, $fields));
    } else {
        throw new coding_exception('Invalid $fields parameter in enrol_get_my_courses()');
    }
    if (in_array('*', $fields)) {
        $fields = array('*');
    }

    $orderby = "";
    $sort    = trim($sort);
    if (!empty($sort)) {
        $rawsorts = explode(',', $sort);
        $sorts = array();
        foreach ($rawsorts as $rawsort) {
            $rawsort = trim($rawsort);
            if (strpos($rawsort, 'c.') === 0) {
                $rawsort = substr($rawsort, 2);
            }
            $sorts[] = trim($rawsort);
        }
        $sort = 'c.'.implode(',c.', $sorts);
        $orderby = "ORDER BY $sort";
    }

    $wheres = array("c.id <> :siteid", 'isnull(cd.course)');
    $params = array('siteid'=>SITEID);

    if (isset($USER->loginascontext) and $USER->loginascontext->contextlevel == CONTEXT_COURSE) {
        // list _only_ this course - anything else is asking for trouble...
        $wheres[] = "courseid = :loginas";
        $params['loginas'] = $USER->loginascontext->instanceid;
    }

    $coursefields = 'c.' .join(',c.', $fields);
    list($ccselect, $ccjoin) = context_instance_preload_sql('c.id', CONTEXT_COURSE, 'ctx');
    $wheres = implode(" AND ", $wheres);

    //note: we can not use DISTINCT + text fields due to Oracle and MS limitations, that is why we have the subselect there

    $sql = "SELECT $coursefields $ccselect
              FROM {course} c
              LEFT JOIN {connect_course_dets} AS cd ON c.id = cd.course
              JOIN (SELECT DISTINCT e.courseid
                      FROM {enrol} e
                      JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = :userid)
                     WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)
                   ) en ON (en.courseid = c.id)
           $ccjoin
             WHERE $wheres
          $orderby";
    $params['userid']  = $USER->id;
    $params['active']  = ENROL_USER_ACTIVE;
    $params['enabled'] = ENROL_INSTANCE_ENABLED;
    $params['now1']    = round(time(), -2); // improves db caching
    $params['now2']    = $params['now1'];

    //$totalcourses = count($DB->get_records_sql($sql, $params));
    //$courses = $DB->get_records_sql($sql, $params, $page, $perpage);
    $courses = $DB->get_records_sql($sql, $params);

    return $courses;
}

function kent_meta_course_get_my_courses($fields = NULL, $sort = 'sortorder ASC') {
    global $CFG, $DB, $USER;

    // Guest account does not have any courses
    if (isguestuser() or !isloggedin()) {
        return(array());
    }

    $systemcontext = get_context_instance(CONTEXT_SYSTEM);

    if(has_capability('moodle/site:config', $systemcontext)) {
    	$sql = 'SELECT c.id, c.fullname FROM {course} AS c LEFT JOIN {connect_course_dets} AS cd ON c.id = cd.course WHERE cd.course IS NOT NULL';
    	$courses = $DB->get_records_sql($sql);
    	return $courses;
    }


    $basefields = array('id','fullname');

    if (empty($fields)) {
        $fields = $basefields;
    } else if (is_string($fields)) {
        // turn the fields from a string to an array
        $fields = explode(',', $fields);
        $fields = array_map('trim', $fields);
        $fields = array_unique(array_merge($basefields, $fields));
    } else if (is_array($fields)) {
        $fields = array_unique(array_merge($basefields, $fields));
    } else {
        throw new coding_exception('Invalid $fields parameter in enrol_get_my_courses()');
    }
    if (in_array('*', $fields)) {
        $fields = array('*');
    }

    $orderby = "";
    $sort    = trim($sort);
    if (!empty($sort)) {
        $rawsorts = explode(',', $sort);
        $sorts = array();
        foreach ($rawsorts as $rawsort) {
            $rawsort = trim($rawsort);
            if (strpos($rawsort, 'c.') === 0) {
                $rawsort = substr($rawsort, 2);
            }
            $sorts[] = trim($rawsort);
        }
        $sort = 'c.'.implode(',c.', $sorts);
        $orderby = "ORDER BY $sort";
    }

    $wheres = array("c.id <> :siteid", 'cd.course IS NOT NULL');
    $params = array('siteid'=>SITEID);

    if (isset($USER->loginascontext) and $USER->loginascontext->contextlevel == CONTEXT_COURSE) {
        // list _only_ this course - anything else is asking for trouble...
        $wheres[] = "courseid = :loginas";
        $params['loginas'] = $USER->loginascontext->instanceid;
    }

    $coursefields = 'c.' .join(',c.', $fields);
    list($ccselect, $ccjoin) = context_instance_preload_sql('c.id', CONTEXT_COURSE, 'ctx');
    $wheres = implode(" AND ", $wheres);

    //note: we can not use DISTINCT + text fields due to Oracle and MS limitations, that is why we have the subselect there

    $sql = "SELECT $coursefields $ccselect
              FROM {course} c
              LEFT JOIN {connect_course_dets} AS cd ON c.id = cd.course
              JOIN (SELECT DISTINCT e.courseid
                      FROM {enrol} e
                      JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = :userid)
                     WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)
                   ) en ON (en.courseid = c.id)
           $ccjoin
             WHERE $wheres
          $orderby";
    $params['userid']  = $USER->id;
    $params['active']  = ENROL_USER_ACTIVE;
    $params['enabled'] = ENROL_INSTANCE_ENABLED;
    $params['now1']    = round(time(), -2); // improves db caching
    $params['now2']    = $params['now1'];

    //$totalcourses = count($DB->get_records_sql($sql, $params));
    //$courses = $DB->get_records_sql($sql, $params, $page, $perpage);
    $courses = $DB->get_records_sql($sql, $params);

    return $courses;
}

/**
 * Returns TRUE or FALSE depending on if a user has any edit course access at all.
 */
function kent_meta_has_edit_course_access(){

    global $CFG, $USER, $DB;

    $params['userid'] = (int)$USER->id;
    $params['capability'] = 'moodle/course:update';

    $sql = "SELECT COUNT(ra.id) as assignments
            FROM {$CFG->prefix}role_assignments ra
            WHERE userid=:userid
            AND ra.roleid IN (SELECT DISTINCT roleid FROM {$CFG->prefix}role_capabilities rc WHERE rc.capability=:capability AND rc.permission=1 ORDER BY rc.roleid ASC)";

    //Pull out an amount of assignments a user has of module update in total.  Acts as a check to see if a user should ever hit the rollover list page.
    if ($courses = $DB->get_record_sql($sql, $params)) {
        $assignments = (int)$courses->assignments;
        if($assignments > 0){
            return true;
        }

    }

    return false;

}