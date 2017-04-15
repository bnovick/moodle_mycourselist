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
 * Course list block.
 *
 * @package    block_aoamycourse_list
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include_once($CFG->dirroot . '/course/lib.php');
include_once($CFG->libdir . '/coursecatlib.php');

class block_aoamycourse_list extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_aoamycourse_list');
    }

    function has_config() {
        return true;
    }

    function get_content() {
        global $CFG, $USER, $DB, $OUTPUT;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $icon  = '<img src="' . $OUTPUT->pix_url('i/course') . '" class="icon" alt="" />';

        $adminseesall = true;
        if (isset($CFG->block_aoamycourse_list_adminview)) {
           if ( $CFG->block_aoamycourse_list_adminview == 'own'){
               $adminseesall = false;
           }
        }

        if (empty($CFG->disablemycourses) and isloggedin() and !isguestuser() and
          !(has_capability('moodle/course:update', context_system::instance()) and $adminseesall)) {    // Just print My Courses
            // As this is producing navigation sort order should default to $CFG->navsortmycoursessort instead
            // of using the default.
            if (!empty($CFG->navsortmycoursessort)) {
                $sortorder = 'visible DESC, ' . $CFG->navsortmycoursessort . ' ASC';
            } else {
                $sortorder = 'visible DESC, sortorder ASC';
            }
            if ($courses = enrol_get_my_courses(NULL, $sortorder)) {
                foreach ($courses as $course) {
                	$cinfo = new completion_info($course);
					$iscomplete = $cinfo->is_course_complete($USER->id);
					if(!$iscomplete){
	                    $coursecontext = context_course::instance($course->id);
	                    $linkcss = $course->visible ? " class=\"inprog\" " : " class=\"dimmed\" ";
	                    $this->content->items[]="<a $linkcss title=\"" . format_string($course->shortname, true, array('context' => $coursecontext)) . "\" ".
	                               "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">".format_string($course->fullname). "</a>";
                	}
                } 
                /*foreach ($courses as $course) {
                	$cinfo = new completion_info($course);
					$iscomplete = $cinfo->is_course_complete($USER->id);
					if($iscomplete){
	                    $coursecontext = context_course::instance($course->id);
	                    $linkcss = $course->visible ? " class=\"comp\" " : " class=\"dimmed\" ";
	                    $this->content->items[]="<a $linkcss title=\"" . format_string($course->shortname, true, array('context' => $coursecontext)) . "\" ".
	                               "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">".format_string($course->fullname). "</a>";
                	}

                }*/
                
                
                $this->title = 'COURSES IN PROGRESS';
            
            }
            
            /*if($oldcourses = $DB->get_records('course', array('visible' => 0), 'id')){
            	$info  = '<i class="fa fa-info-circle" aria-hidden="true" title="This course is no longer available."></i>';
				foreach ($oldcourses as $oldcourse) {
					$cinfo = new completion_info($oldcourse);
					if($iscomplete = $cinfo->is_course_complete($USER->id)){
						$linkcss = " class=\"dimmed oldcourse\" ";
	                    $this->content->items[]="<span $linkcss>".format_string($course->fullname).$info. "</span>";
	                }
				}
			}*/
            $this->get_remote_courses();
            if ($this->content->items) { // make sure we don't return an empty list
                return $this->content;
            }
            else{
            	return '';
            }
        }
    }

    function get_remote_courses() {
        global $CFG, $USER, $OUTPUT;

        if (!is_enabled_auth('mnet')) {
            // no need to query anything remote related
            return;
        }

        $icon = '<img src="'.$OUTPUT->pix_url('i/mnethost') . '" class="icon" alt="" />';

        // shortcut - the rest is only for logged in users!
        if (!isloggedin() || isguestuser()) {
            return false;
        }

        if ($courses = get_my_remotecourses()) {
            $this->content->items[] = get_string('remotecourses','mnet');
            $this->content->icons[] = '';
            foreach ($courses as $course) {
                $this->content->items[]="<a title=\"" . format_string($course->shortname, true) . "\" ".
                    "href=\"{$CFG->wwwroot}/auth/mnet/jump.php?hostid={$course->hostid}&amp;wantsurl=/course/view.php?id={$course->remoteid}\">"
                    .$icon. format_string(get_course_display_name_for_list($course)) . "</a>";
            }
            // if we listed courses, we are done
            return true;
        }

        if ($hosts = get_my_remotehosts()) {
            $this->content->items[] = get_string('remotehosts', 'mnet');
            $this->content->icons[] = '';
            foreach($USER->mnet_foreign_host_array as $somehost) {
                $this->content->items[] = $somehost['count'].get_string('courseson','mnet').'<a title="'.$somehost['name'].'" href="'.$somehost['url'].'">'.$icon.$somehost['name'].'</a>';
            }
            // if we listed hosts, done
            return true;
        }

        return false;
    }

    /**
     * Returns the role that best describes the course list block.
     *
     * @return string
     */
    public function get_aria_role() {
        return 'navigation';
    }
}


