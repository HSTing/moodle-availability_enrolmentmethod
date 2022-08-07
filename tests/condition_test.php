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
 * Unit tests for the condition.
 *
 * @package availability_enrolmentmethod
 * @copyright 2022 Jorge C.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/enrol/locallib.php');

use availability_enrolmentmethod\condition;

/**
 * Unit tests for the condition.
 *
 * @package availability_enrolmentmethod
 * @copyright 2022 Jorge C.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class availability_enrolmentmethod_condition_testcase extends advanced_testcase {
    /**
     * Load required classes.
     */
    public function setUp(): void {
        // Load the mock info class so that it can be used.
        global $CFG;
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
    }

    /**
     * Tests constructing and using condition.
     *
     * @covers \availability_enrolmentmethod\condition::is_available()
     * @covers \availability_enrolmentmethod\condition::get_description()
     * @throws coding_exception
     */
    public function test_usage() {
        global $CFG, $USER, $PAGE;
        $this->resetAfterTest();
        $CFG->enableavailability = true;
        $generator = self::getDataGenerator();

        // Generate course
        $course = $generator->create_course();

        // Generate user and enrol with manual enrolment plugin. 
        $manualuser = $generator->create_user();
        $generator->enrol_user($manualuser->id, $course->id, 'student', 'manual');

        // Generate user and enrol with self enrolment plugin.
        $selfuser = $generator->create_user();
        $generator->enrol_user($selfuser->id, $course->id, 'student', 'self');

        // Get users enrolments
        $manager = new course_enrolment_manager($PAGE, $course);
        $manualuserenrolments = $manager->get_user_enrolments($manualuser->id);
        $selfuserenrolments = $manager->get_user_enrolments($selfuser->id);

        $info = new \core_availability\mock_info($course, $manualuser->id);

        // Get enrolment instances
        $manualenrolinstance = reset($manualuserenrolments)->enrolmentinstance;
        $selfenrolinstance = reset($selfuserenrolments)->enrolmentinstance;

        // Ensure both enrolment plugins are enabled.
        $manualplugin = enrol_get_plugin('manual');
        $manualplugin->update_status($manualenrolinstance, ENROL_INSTANCE_ENABLED);

        $selfplugin = enrol_get_plugin('self');
        $selfplugin->update_status($selfenrolinstance, ENROL_INSTANCE_ENABLED);

        // Create condition: enrolment method must be manual.
        $cond = new condition((object) array('id' => (int) $manualenrolinstance->id));

        // Check if available for a manual enrolled user when the condition is manual enrolment.
        $this->assertTrue($cond->is_available(false, $info, true, $manualuser->id));
        $this->assertFalse($cond->is_available(true, $info, true, $manualuser->id));

        // Check description
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertEquals(get_string('requires_enrolmentmethod',
                'availability_enrolmentmethod', reset($manualuserenrolments)->enrolmentinstancename), $information);

        // Check description with inverse condition.
        $information = $cond->get_description(true, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $this->assertEquals(get_string('requires_notenrolmentmethod',
                'availability_enrolmentmethod', reset($manualuserenrolments)->enrolmentinstancename), $information);

        // Check if available for a self enrolled user when the condition is manual enrolment.
        $info = new \core_availability\mock_info($course, $selfuser->id);
        $this->assertFalse($cond->is_available(false, $info, true, $selfuser->id));
        $this->assertTrue($cond->is_available(true, $info, true, $selfuser->id));

        /*
         * Now enrol self enrolled user with manual enrolment to check that is available when the condition is manual enrolment 
         * and the user with multiple enrolments is enrolled with manual enrolment.
         */

        $generator->enrol_user($selfuser->id, $course->id, 'student', 'manual');
        $info = new \core_availability\mock_info($course, $selfuser->id);
        $this->assertTrue($cond->is_available(false, $info, true, $selfuser->id));
        $this->assertFalse($cond->is_available(true, $info, true, $selfuser->id));
    }

    /**
     * Tests the constructor including error conditions. Also tests the
     * string conversion feature (intended for debugging only).
     */
    public function test_constructor() {
        // Invalid id (not int).
        $structure = (object) array('id' => 'bourne');
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertStringContainsString('Invalid ->id', $e->getMessage());
        }

        // Valid (with id).
        $structure->id = 123;
        $cond = new condition($structure);
        $this->assertEquals('{enrolmentmethod:#123}', (string) $cond);
    }

    /**
     * Tests the save() function.
     */
    public function test_save() {
        $structure = (object) array('id' => 123);
        $cond = new condition($structure);
        $structure->type = 'enrolmentmethod';
        $this->assertEquals($structure, $cond->save());

        $structure = (object) array();
        $cond = new condition($structure);
        $structure->type = 'enrolmentmethod';
        $this->assertEquals($structure, $cond->save());
    }

    /**
     * Tests the filter_users (bulk checking) function. Also tests the SQL
     * variant get_user_list_sql.
     */
    public function test_filter_users() {
        global $DB;
        $this->resetAfterTest();

        // Erase static cache before test.
        condition::wipe_static_cache();

        // Make a test course and some users.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, $roleids['editingteacher']);
        $allusers = array($teacher->id => $teacher);
        $students = array();
        for ($i = 0; $i < 3; $i++) {
            $student = $generator->create_user();
            $students[$i] = $student;
            $generator->enrol_user($student->id, $course->id, $roleids['student']);
            $allusers[$student->id] = $student;
        }
        $info = new \core_availability\mock_info($course);

        // Make test groups.
        $group1 = $generator->create_group(array('courseid' => $course->id));
        $group2 = $generator->create_group(array('courseid' => $course->id));

        // Assign students to groups as follows (teacher is not in a group):
        // 0: no groups.
        // 1: in group 1.
        // 2: in group 2.
        groups_add_member($group1, $students[1]);
        groups_add_member($group2, $students[2]);

        // Test 'any group' condition.
        $checker = new \core_availability\capability_checker($info->get_context());
        $cond = new condition((object) array());
        $result = array_keys($cond->filter_user_list($allusers, false, $info, $checker));
        ksort($result);
        $expected = array($teacher->id, $students[1]->id, $students[2]->id);
        $this->assertEquals($expected, $result);

        // Test it with get_user_list_sql.
        list ($sql, $params) = $cond->get_user_list_sql(false, $info, true);
        $result = $DB->get_fieldset_sql($sql, $params);
        sort($result);
        $this->assertEquals($expected, $result);

        // Test NOT version (note that teacher can still access because AAG works
        // both ways).
        $result = array_keys($cond->filter_user_list($allusers, true, $info, $checker));
        ksort($result);
        $expected = array($teacher->id, $students[0]->id);
        $this->assertEquals($expected, $result);

        // Test with get_user_list_sql.
        list ($sql, $params) = $cond->get_user_list_sql(true, $info, true);
        $result = $DB->get_fieldset_sql($sql, $params);
        sort($result);
        $this->assertEquals($expected, $result);

        // Test specific group.
        $cond = new condition((object) array('id' => (int) $group1->id));
        $result = array_keys($cond->filter_user_list($allusers, false, $info, $checker));
        ksort($result);
        $expected = array($teacher->id, $students[1]->id);
        $this->assertEquals($expected, $result);

        list ($sql, $params) = $cond->get_user_list_sql(false, $info, true);
        $result = $DB->get_fieldset_sql($sql, $params);
        sort($result);
        $this->assertEquals($expected, $result);

        $result = array_keys($cond->filter_user_list($allusers, true, $info, $checker));
        ksort($result);
        $expected = array($teacher->id, $students[0]->id, $students[2]->id);
        $this->assertEquals($expected, $result);

        list ($sql, $params) = $cond->get_user_list_sql(true, $info, true);
        $result = $DB->get_fieldset_sql($sql, $params);
        sort($result);
        $this->assertEquals($expected, $result);
    }
}
