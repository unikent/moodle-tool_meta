<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('reports', new admin_externalpage('reportkentmetacoursereport', get_string('pluginname', 'local_kentmetacourse'), "$CFG->wwwroot/local/kentmetacourse/index.php"));
}