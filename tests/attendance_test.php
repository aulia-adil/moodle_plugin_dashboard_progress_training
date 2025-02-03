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
 * External functions test for attendance plugin.
 *
 * @package    mod_attendance
 * @category   test
 * @copyright  2015 Caio Bressan Doneda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace mod_attendance\external;

use externallib_advanced_testcase;
use mod_attendance_structure;
use stdClass;
use attendance_handler;
use external_api;
use mod_attendance_external;

defined('MOODLE_INTERNAL') || die();




/**
 * This class contains the test cases for webservices.
 *
 * @package    mod_attendance
 * @category   test
 * @copyright  2015 Caio Bressan Doneda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      mod_attendance
 * @runTestsInSeparateProcesses
 */
class Attendance_test extends \advanced_testcase {

    protected $attendance;

    protected function setUp(): void {
        $this->resetAfterTest(true);
        global $CFG;
        require_once($CFG->dirroot . '/blocks/listallcourses/utils.php');

        // Create test data for attendance sessions.
        $course = $this->getDataGenerator()->create_course();
        $this->attendance = $this->getDataGenerator()->create_module('attendance', ['course' => $course->id]);
        
    }
    

    public function test_one_attendance_record_for_one_student() {
        global $DB, $CFG;
        

        // Create a test user.
        $student = $this->getDataGenerator()->create_user();

        $session = $DB->insert_record('attendance_sessions', [
            'attendanceid' => $this->attendance->id,
            'description' => 'Test Session',
            'duration' => 3600
        ]);
        
        $DB->insert_record('attendance_log', [
            'sessionid' => $session,
            'studentid' => $student->id,
            'statusid' => 5,
            'statusset' => '5,6,7,8',
            'timetaken' => time(),
            'takenby' => 2,
            'remarks' => '',
            'ipaddress' => ''
        ]);
        
        $result = get_attendance_sessions($student->id, "attendance", $DB, $CFG->phpunit_prefix);
        $this->assertCount(1, $result, 'Expected exactly one attendance record');
        $this->assertEquals('Test Session', $result[0]['Nama Aktivitas'], 'Expected description to match');
    }

    public function test_one_attendance_record_for_one_student_but_no_come() {
        global $DB, $CFG;
        

        // Create a test user.
        $student = $this->getDataGenerator()->create_user();

        $session = $DB->insert_record('attendance_sessions', [
            'attendanceid' => $this->attendance->id,
            'description' => 'Test Session',
            'duration' => 3600
        ]);
        
        $DB->insert_record('attendance_log', [
            'sessionid' => $session,
            'studentid' => $student->id,
            'statusid' => 6,
            'statusset' => '5,6,7,8',
            'timetaken' => time(),
            'takenby' => 2,
            'remarks' => '',
            'ipaddress' => ''
        ]);
        
        $result = get_attendance_sessions($student->id, "attendance", $DB, $CFG->phpunit_prefix);
        $this->assertCount(0, $result, 'Expected exactly one attendance record');
        
    }

    public function test_two_attendance_records_of_two_students() {
        global $DB, $CFG;
        

        // Create a test user.
        $student = $this->getDataGenerator()->create_user();

        $session = $DB->insert_record('attendance_sessions', [
            'attendanceid' => $this->attendance->id,
            'description' => 'Test Session',
            'duration' => 3600
        ]);
        
        $DB->insert_record('attendance_log', [
            'sessionid' => $session,
            'studentid' => $student->id,
            'statusid' => 5,
            'statusset' => '5,6,7,8',
            'timetaken' => time(),
            'takenby' => 2,
            'remarks' => '',
            'ipaddress' => ''
        ]);

        // Create a test user.
        $student2 = $this->getDataGenerator()->create_user();
        
        $DB->insert_record('attendance_log', [
            'sessionid' => $session,
            'studentid' => $student2->id,
            'statusid' => 5,
            'statusset' => '5,6,7,8',
            'timetaken' => time(),
            'takenby' => 2,
            'remarks' => '',
            'ipaddress' => ''
        ]);
        
        $result = get_attendance_sessions($student->id, "attendance", $DB, $CFG->phpunit_prefix);
        $this->assertCount(1, $result, 'Expected exactly one attendance record');
        
    }

    public function test_two_attendance_records_for_one_student() {
        global $DB, $CFG;

        // Create a test user.
        $student = $this->getDataGenerator()->create_user();

        $session = $DB->insert_record('attendance_sessions', [
            'attendanceid' => $this->attendance->id,
            'description' => 'Test Session',
            'duration' => 3600
        ]);
        
        $DB->insert_record('attendance_log', [
            'sessionid' => $session,
            'studentid' => $student->id,
            'statusid' => 5,
            'statusset' => '5,6,7,8',
            'timetaken' => time(),
            'takenby' => 2,
            'remarks' => '',
            'ipaddress' => ''
        ]);

        $session2 = $DB->insert_record('attendance_sessions', [
            'attendanceid' => $this->attendance->id,
            'description' => 'Test Session 321',
            'duration' => 3600
        ]);
        
        $DB->insert_record('attendance_log', [
            'sessionid' => $session2,
            'studentid' => $student->id,
            'statusid' => 5,
            'statusset' => '5,6,7,8',
            'timetaken' => time(),
            'takenby' => 2,
            'remarks' => '',
            'ipaddress' => ''
        ]);
        
        $result = get_attendance_sessions($student->id, "attendance", $DB, $CFG->phpunit_prefix);
        $this->assertCount(2, $result, 'Expected exactly one attendance record');
        
    }

    public function test_two_attendance_records_for_one_student_but_one_session_not_come() {
        global $DB, $CFG;

        // Create a test user.
        $student = $this->getDataGenerator()->create_user();

        $session = $DB->insert_record('attendance_sessions', [
            'attendanceid' => $this->attendance->id,
            'description' => 'Test Session',
            'duration' => 3600
        ]);
        
        $DB->insert_record('attendance_log', [
            'sessionid' => $session,
            'studentid' => $student->id,
            'statusid' => 5,
            'statusset' => '5,6,7,8',
            'timetaken' => time(),
            'takenby' => 2,
            'remarks' => '',
            'ipaddress' => ''
        ]);

        $session2 = $DB->insert_record('attendance_sessions', [
            'attendanceid' => $this->attendance->id,
            'description' => 'Test Session 321',
            'duration' => 3600
        ]);
        
        $DB->insert_record('attendance_log', [
            'sessionid' => $session2,
            'studentid' => $student->id,
            'statusid' => 6,
            'statusset' => '5,6,7,8',
            'timetaken' => time(),
            'takenby' => 2,
            'remarks' => '',
            'ipaddress' => ''
        ]);
        
        $result = get_attendance_sessions($student->id, "attendance", $DB, $CFG->phpunit_prefix);
        $this->assertCount(1, $result, 'Expected exactly one attendance record');
        
    }

    public function test_create_attendance_record_and_session_and_log_and_then_delete_attendance() {
        global $DB, $CFG;

        // Create a test user.
        $student = $this->getDataGenerator()->create_user();

        $session = $DB->insert_record('attendance_sessions', [
            'attendanceid' => $this->attendance->id,
            'description' => 'Test Session',
            'duration' => 3600
        ]);
        
        $DB->insert_record('attendance_log', [
            'sessionid' => $session,
            'studentid' => $student->id,
            'statusid' => 5,
            'statusset' => '5,6,7,8',
            'timetaken' => time(),
            'takenby' => 2,
            'remarks' => '',
            'ipaddress' => ''
        ]);

        $result = get_attendance_sessions($student->id, "attendance", $DB, $CFG->phpunit_prefix);
        $this->assertCount(1, $result, 'Expected exactly one attendance record');
        $this->assertEquals('Test Session', $result[0]['Nama Aktivitas'], 'Expected description to match');

        $DB->delete_records('attendance', ['id' => $this->attendance->id]);
        $DB->delete_records('attendance_sessions', ['attendanceid' => $this->attendance->id]);
        $DB->delete_records('attendance_log', ['sessionid' => $session]);
        
        $result = get_attendance_sessions($student->id, "attendance", $DB, $CFG->phpunit_prefix);
        $this->assertCount(0, $result, 'Expected exactly one attendance record');
    }

}
