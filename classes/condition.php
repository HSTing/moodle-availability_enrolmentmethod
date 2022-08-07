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
 * Condition main class.
 *
 * @package availability_enrolmentmethod
 * @copyright 2022 Jorge C.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_enrolmentmethod;
defined('MOODLE_INTERNAL') || die();
use course_enrolment_manager;
require_once($CFG->dirroot . '/enrol/locallib.php');


/**
 * Condition main class.
 *
 * @package availability_enrolmentmethod
 * @copyright 2022 Jorge C.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var int ID of enrolment method that this condition requires */
    protected $enrolmentmethodid;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        // Get enrolment method id.
        if (!property_exists($structure, 'id')) {
            $this->enrolmentmethodid = 0;
        } else if (is_int($structure->id)) {
            $this->enrolmentmethodid = $structure->id;
        } else {
            throw new \coding_exception('Invalid ->id for enrolment method condition');
        }
    }

    public function save() {
        $result = (object) array('type' => 'enrolmentmethod');
        if ($this->enrolmentmethodid) {
            $result->id = $this->enrolmentmethodid;
        }
        return $result;
    }

    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $PAGE;
        $course = $info->get_course();

        $allow = true;
        $manager = new course_enrolment_manager($PAGE, $course);
        $userenrolments = $manager->get_user_enrolments($userid);
        $userenrolids = array_column($userenrolments , 'enrolid');
        if (!in_array($this->enrolmentmethodid, $userenrolids)) {
            $allow = false;
        }
        if ($not) {
            $allow = !$allow;
        }
        return $allow;
    }

    public function get_description($full, $not, \core_availability\info $info) {
        global $PAGE;
        if ($this->enrolmentmethodid) {
            $course = $info->get_course();
            $manager = new course_enrolment_manager($PAGE, $course);
            $enrolmentmethodnames = $manager->get_enrolment_instance_names(true);

            // If it still doesn't exist, it must have been misplaced.
            if (!array_key_exists($this->enrolmentmethodid, $enrolmentmethodnames)) {
                $name = get_string('missing', 'availability_enrolmentmethod');
            } else {
                // Not safe to call format_string here; use the special function to call it later.
                $name = self::description_format_string($enrolmentmethodnames[$this->enrolmentmethodid]);
            }
        }

        return get_string($not ? 'requires_notenrolmentmethod' : 'requires_enrolmentmethod',
                'availability_enrolmentmethod', $name);
    }

    protected function get_debug_string() {
        return $this->enrolmentmethodid ? '#' . $this->enrolmentmethodid : 'any';
    }

    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name) {
        global $DB;
        if (!$this->enrolmentmethodid) {
            return false;
        }
        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'enrol', $this->enrolmentmethodid);
        if (!$rec || !$rec->newitemid) {
            // If we are on the same course (e.g. duplicate) then we can just
            // use the existing one.
            if ($DB->record_exists('enrol',
                    array('id' => $this->enrolmentmethodid, 'courseid' => $courseid))) {
                return false;
            }
            // Otherwise it's a warning.
            $this->enrolmentmethodid = -1;
            $logger->process('Restored item (' . $name .
                    ') has availability condition on enrolment method that was not restored',
                    \backup::LOG_WARNING);
        } else {
            $this->enrolmentmethodid = (int) $rec->newitemid;
        }
        return true;
    }

    public function is_applied_to_user_lists() {
        // Enrolment method conditions are assumed to be 'permanent', so they affect the
        // display of user lists for activities.
        return true;
    }

    public function filter_user_list(array $users, $not, \core_availability\info $info,
            \core_availability\capability_checker $checker) {
        global $PAGE;

        // If the array is empty already, just return it.
        if (!$users) {
            return $users;
        }

        $course = $info->get_course();
        // List users for this course who match the condition.

        $manager = new course_enrolment_manager($PAGE, $course);

        // Filter the user list.
        $result = array();
        foreach ($users as $id => $user) {
            $userenrolments = $manager->get_user_enrolments($id);
            $allow = false;

            foreach ($userenrolments as $userenrolment) {
                if ($this->enrolmentmethodid === (int) $userenrolment->enrolid) {
                    $allow = true;
                    break;
                }
            }

            if ($not) {
                $allow = !$allow;
            }
            if ($allow) {
                $result[$id] = $user;
            }
        }
        return $result;
    }
}
