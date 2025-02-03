<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Unit tests for block_listallcourses.
 *
 * @package     block_listallcourses
 * @category    test
 * @copyright   2025 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/listallcourses/block_listallcourses.php');

class block_listallcoursesTest extends advanced_testcase {

    /**
     * Test block initialization.
     */
    public function test_block_initialization() {
        $block = new block_listallcourses();
        $block->init();
        $this->assertEquals(get_string('pluginname', 'block_listallcourses'), $block->title);
    }

    /**
     * Test block content generation.
     */
    public function test_block_content_generation() {
        global $DB, $USER;

        // Create a test user.
        $this->resetAfterTest(true);
        $USER = $this->getDataGenerator()->create_user();

        // Create test data for attendance sessions.
        $course = $this->getDataGenerator()->create_course();
        $attendance = $this->getDataGenerator()->create_module('attendance', ['course' => $course->id]);
        $session = $DB->insert_record('attendance_sessions', [
            'attendanceid' => $attendance->id,
            'description' => 'Test Session',
            'duration' => 3600
        ]);
        $DB->insert_record('attendance_log', [
            'sessionid' => $session,
            'studentid' => $USER->id,
            'statusid' => 1,
            'timetaken' => time(),
            'statusset' => '1'
        ]);

        // Create test data for interactive videos.
        $hvp = $this->getDataGenerator()->create_module('hvp', ['course' => $course->id]);
        $DB->insert_record('course_modules_completion', [
            'coursemoduleid' => $hvp->cmid,
            'userid' => $USER->id,
            'timemodified' => time()
        ]);

        $block = new block_listallcourses();
        $block->instance = (object) ['id' => 1];
        $content = $block->get_content();

        $this->assertNotEmpty($content->text);
        $this->assertStringContainsString('Test Session', $content->text);
    }

    /**
     * Test SQL queries used in the block.
     */
    public function test_sql_queries() {
        global $DB, $USER;

        // Create a test user.
        $this->resetAfterTest(true);
        $USER = $this->getDataGenerator()->create_user();

        // Create test data for attendance sessions.
        $course = $this->getDataGenerator()->create_course();
        $attendance = $this->getDataGenerator()->create_module('attendance', ['course' => $course->id]);
        $session = $DB->insert_record('attendance_sessions', [
            'attendanceid' => $attendance->id,
            'description' => 'Test Session',
            'duration' => 3600
        ]);
        $DB->insert_record('attendance_log', [
            'sessionid' => $session,
            'studentid' => $USER->id,
            'statusid' => 1,
            'timetaken' => time(),
            'statusset' => '1'
        ]);

        // Create test data for interactive videos.
        $hvp = $this->getDataGenerator()->create_module('hvp', ['course' => $course->id]);
        $DB->insert_record('course_modules_completion', [
            'coursemoduleid' => $hvp->cmid,
            'userid' => $USER->id,
            'timemodified' => time()
        ]);

        // SQL query to get attendance sessions based on myid
        $sqlQueryAttendance = "
            SELECT s.description, s.duration, cm.id, l.timetaken
            FROM {attendance_sessions} s
            JOIN {attendance_log} l ON s.id = l.sessionid
            JOIN {attendance} a ON s.attendanceid = a.id
            JOIN {course_modules} cm ON cm.instance = a.id
            WHERE l.studentid = :myid
            AND cm.module = :moduleid
            AND l.statusid = (
                SELECT MIN(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(l.statusset, ',', n.n), ',', -1) AS UNSIGNED))
                FROM (SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) n
                WHERE n.n <= 1 + LENGTH(l.statusset) - LENGTH(REPLACE(l.statusset, ',', ''))
            )
        ";
        $paramsAttendance = [
            'myid' => $USER->id,
            'moduleid' => 24,
        ];

        $recordsAttendance = $DB->get_records_sql($sqlQueryAttendance, $paramsAttendance);
        $this->assertNotEmpty($recordsAttendance);

        // SQL query to get interactive videos based on myid
        $sqlQueryInteractiveVideo = "
            SELECT h.name, h.json_content, cm.id, cmc.timemodified
            FROM {course_modules_completion} cmc
            JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id
            JOIN {hvp} h ON cm.instance = h.id
            WHERE cmc.userid = :userid
            AND cm.module = :moduleid
        ";
        $paramsInteractiveVideo = [
            'userid' => $USER->id,
            'moduleid' => 25,
        ];

        $recordsInteractiveVideo = $DB->get_records_sql($sqlQueryInteractiveVideo, $paramsInteractiveVideo);
        $this->assertNotEmpty($recordsInteractiveVideo);
    }
}