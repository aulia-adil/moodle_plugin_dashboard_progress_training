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
 * Unit tests for block_listallcourses utils.
 *
 * @package     block_listallcourses
 * @category    test
 * @copyright   2025 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use PHPUnit\Framework\TestCase;
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/listallcourses/utils.php');

class Interactive_video_test extends \advanced_testcase {
    protected $course;

    protected function setUp(): void {
        $this->resetAfterTest(true);
        global $CFG, $DB;
        require_once($CFG->dirroot . '/blocks/listallcourses/utils.php');

        // Create test data for attendance sessions.
        $this->course = $this->getDataGenerator()->create_course();

        
    }

    public function test_get_interactive_video_data() {
        global $DB, $CFG;

        $student = $this->getDataGenerator()->create_user();

        # Create get module id of hvp
        $moduleId = $DB->get_record('modules', ['name' => 'hvp'], 'id', MUST_EXIST);
        
        # Create hvp activity
        $hvpId = $DB->insert_record('hvp', [
            'course' => $this->course->id,
            'name' => 'testing (copy) (copy)',
            'intro' => '<p>AHAHSDALKSJD</p>',
            'introformat' => 1,
            'json_content' => '{"interactiveVideo":{"video":{"files":[{"path":"https://www.youtube.com/watch?v=MOoIs-fth9k&ab_channel=DailyDoseOfInternet"}]}}}',
            'embed_type' => 'div',
            'disable' => 0,
            'main_library_id' => 31,
            'content_type' => NULL,
            'authors' => '[]',
            'source' => NULL,
            'year_from' => NULL,
            'year_to' => NULL,
            'license' => 'U',
            'license_version' => NULL,
            'changes' => '[]',
            'license_extras' => NULL,
            'author_comments' => NULL,
            'default_language' => NULL,
            'filtered' => '{"interactiveVideo":{"video":{"files":[{"path":"https://www.youtube.com/watch?v=MOoIs-fth9k&ab_channel=DailyDoseOfInternet"}]}}}',
            'slug' => 'testing-2',
            'timecreated' => 1738132408,
            'timemodified' => 1738405724,
            'completionpass' => 0,
            'shared' => 0,
            'synced' => NULL,
            'hub_id' => NULL,
            'a11y_title' => NULL
        ]);

        # Create course module of hvp
        $cmId = $DB->insert_record('course_modules', [
            'course' => $this->course->id,
            'module' => $moduleId->id,
            'instance' => $hvpId, 
            'section' => 0,
            'idnumber' => '',
            'added' => time(),
            'score' => 0,
            'indent' => 0,
            'visible' => 1,
            'visibleold' => 1,
            'groupmode' => 0,
            'groupingid' => 0,
            'completion' => 0,
            'completiongradeitemnumber' => NULL,
            'completionview' => 0,
            'completionexpected' => 0,
            'showdescription' => 0,
            'availability' => NULL,
            'deletioninprogress' => 0
        ]);        
            
        $result = getInteractiveVideoData($student->id, 'hvp',$DB, $CFG->phpunit_prefix);
        $this->assertCount(0, $result, 'Expected exactly zero interactive video record');

        $DB->insert_record('course_modules_completion', [
            'coursemoduleid' => $cmId,
            'userid' => $student->id,
            'completionstate' => 1,
            'timemodified' => time() + 0,
        ]);

        $result = getInteractiveVideoData($student->id, 'hvp',$DB, $CFG->phpunit_prefix);
        $this->assertCount(1, $result, 'Expected exactly 1 interactive video record because it has been completed');
        $this->assertEquals('testing (copy) (copy)', $result[0]['Nama Aktivitas']);
    }

    public function test_get_two_data_video_but_only_one_completed() {
        global $DB, $CFG;
        
        $student = $this->getDataGenerator()->create_user();
        
        # Create get module id of hvp
        $moduleId = $DB->get_record('modules', ['name' => 'hvp'], 'id', MUST_EXIST);
        
        # Create hvp activity
        $hvpId = $DB->insert_record('hvp', [
            'course' => $this->course->id,
            'name' => 'testing (copy) (copy)',
            'intro' => '<p>AHAHSDALKSJD</p>',
            'introformat' => 1,
            'json_content' => '{"interactiveVideo":{"video":{"files":[{"path":"https://www.youtube.com/watch?v=MOoIs-fth9k&ab_channel=DailyDoseOfInternet"}]}}}',
            'embed_type' => 'div',
            'disable' => 0,
            'main_library_id' => 31,
            'content_type' => NULL,
            'authors' => '[]',
            'source' => NULL,
            'year_from' => NULL,
            'year_to' => NULL,
            'license' => 'U',
            'license_version' => NULL,
            'changes' => '[]',
            'license_extras' => NULL,
            'author_comments' => NULL,
            'default_language' => NULL,
            'filtered' => '{"interactiveVideo":{"video":{"files":[{"path":"https://www.youtube.com/watch?v=MOoIs-fth9k&ab_channel=DailyDoseOfInternet"}]}}}',
            'slug' => 'testing-2',
            'timecreated' => 1738132408,
            'timemodified' => 1738405724,
            'completionpass' => 0,
            'shared' => 0,
            'synced' => NULL,
            'hub_id' => NULL,
            'a11y_title' => NULL
        ]);

        # Create course module of hvp
        $cmId = $DB->insert_record('course_modules', [
            'course' => $this->course->id,
            'module' => $moduleId->id,
            'instance' => $hvpId, 
            'section' => 0,
            'idnumber' => '',
            'added' => time(),
            'score' => 0,
            'indent' => 0,
            'visible' => 1,
            'visibleold' => 1,
            'groupmode' => 0,
            'groupingid' => 0,
            'completion' => 0,
            'completiongradeitemnumber' => NULL,
            'completionview' => 0,
            'completionexpected' => 0,
            'showdescription' => 0,
            'availability' => NULL,
            'deletioninprogress' => 0
        ]);        
            
        
        $DB->insert_record('course_modules_completion', [
            'coursemoduleid' => $cmId,
            'userid' => $student->id,
            'completionstate' => 1,
            'timemodified' => time() + 0,
        ]);

        $result = getInteractiveVideoData($student->id, 'hvp',$DB, $CFG->phpunit_prefix);
        $this->assertCount(1, $result, 'Expected exactly zero interactive video record');
        $this->assertEquals('testing (copy) (copy)', $result[0]['Nama Aktivitas']);


        # Create hvp activity
        $hvpId = $DB->insert_record('hvp', [
            'course' => $this->course->id,
            'name' => 'testing name',
            'intro' => '<p>AHAHSDALKSJD</p>',
            'introformat' => 1,
            'json_content' => '{"interactiveVideo":{"video":{"files":[{"path":"https://www.youtube.com/watch?v=JWP13GHMcnU&ab_channel=SISITERANG"}]}}}',
            'embed_type' => 'div',
            'disable' => 0,
            'main_library_id' => 31,
            'content_type' => NULL,
            'authors' => '[]',
            'source' => NULL,
            'year_from' => NULL,
            'year_to' => NULL,
            'license' => 'U',
            'license_version' => NULL,
            'changes' => '[]',
            'license_extras' => NULL,
            'author_comments' => NULL,
            'default_language' => NULL,
            'filtered' => '{"interactiveVideo":{"video":{"files":[{"path":"https://www.youtube.com/watch?v=JWP13GHMcnU&ab_channel=SISITERANG"}]}}}',
            'slug' => 'testing-2',
            'timecreated' => 1738132408,
            'timemodified' => 1738405724,
            'completionpass' => 0,
            'shared' => 0,
            'synced' => NULL,
            'hub_id' => NULL,
            'a11y_title' => NULL
        ]);

        # Create course module of hvp
        $cmId = $DB->insert_record('course_modules', [
            'course' => $this->course->id,
            'module' => $moduleId->id,
            'instance' => $hvpId, 
            'section' => 0,
            'idnumber' => '',
            'added' => time(),
            'score' => 0,
            'indent' => 0,
            'visible' => 1,
            'visibleold' => 1,
            'groupmode' => 0,
            'groupingid' => 0,
            'completion' => 0,
            'completiongradeitemnumber' => NULL,
            'completionview' => 0,
            'completionexpected' => 0,
            'showdescription' => 0,
            'availability' => NULL,
            'deletioninprogress' => 0
        ]);        
            
        
        $result = getInteractiveVideoData($student->id, 'hvp',$DB, $CFG->phpunit_prefix);
        $this->assertCount(1, $result, 'Expected exactly zero interactive video record');
    }

    public function test_create_one_video_data_and_then_delete_it() {
        global $DB, $CFG;
        
        $student = $this->getDataGenerator()->create_user();
        
        # Create get module id of hvp
        $moduleId = $DB->get_record('modules', ['name' => 'hvp'], 'id', MUST_EXIST);
        
        # Create hvp activity
        $hvpId = $DB->insert_record('hvp', [
            'course' => $this->course->id,
            'name' => 'testing (copy) (copy)',
            'intro' => '<p>AHAHSDALKSJD</p>',
            'introformat' => 1,
            'json_content' => '{"interactiveVideo":{"video":{"files":[{"path":"https://www.youtube.com/watch?v=MOoIs-fth9k&ab_channel=DailyDoseOfInternet"}]}}}',
            'embed_type' => 'div',
            'disable' => 0,
            'main_library_id' => 31,
            'content_type' => NULL,
            'authors' => '[]',
            'source' => NULL,
            'year_from' => NULL,
            'year_to' => NULL,
            'license' => 'U',
            'license_version' => NULL,
            'changes' => '[]',
            'license_extras' => NULL,
            'author_comments' => NULL,
            'default_language' => NULL,
            'filtered' => '{"interactiveVideo":{"video":{"files":[{"path":"https://www.youtube.com/watch?v=MOoIs-fth9k&ab_channel=DailyDoseOfInternet"}]}}}',
            'slug' => 'testing-2',
            'timecreated' => 1738132408,
            'timemodified' => 1738405724,
            'completionpass' => 0,
            'shared' => 0,
            'synced' => NULL,
            'hub_id' => NULL,
            'a11y_title' => NULL
        ]);

        # Create course module of hvp
        $cmId = $DB->insert_record('course_modules', [
            'course' => $this->course->id,
            'module' => $moduleId->id,
            'instance' => $hvpId, 
            'section' => 0,
            'idnumber' => '',
            'added' => time(),
            'score' => 0,
            'indent' => 0,
            'visible' => 1,
            'visibleold' => 1,
            'groupmode' => 0,
            'groupingid' => 0,
            'completion' => 0,
            'completiongradeitemnumber' => NULL,
            'completionview' => 0,
            'completionexpected' => 0,
            'showdescription' => 0,
            'availability' => NULL,
            'deletioninprogress' => 0
        ]);        
            
        
        $DB->insert_record('course_modules_completion', [
            'coursemoduleid' => $cmId,
            'userid' => $student->id,
            'completionstate' => 1,
            'timemodified' => time() + 0,
        ]);

        $result = getInteractiveVideoData($student->id, 'hvp',$DB, $CFG->phpunit_prefix);
        $this->assertCount(1, $result, 'Expected exactly zero interactive video record');
        $this->assertEquals('testing (copy) (copy)', $result[0]['Nama Aktivitas']);


        # Delete hvp activity
        $DB->delete_records('hvp', ['id' => $hvpId]);
        $DB->delete_records('course_modules', ['id' => $cmId]);
        $DB->delete_records('course_modules_completion', ['coursemoduleid' => $cmId]);  
        
        $result = getInteractiveVideoData($student->id, 'hvp',$DB, $CFG->phpunit_prefix);
        $this->assertCount(0, $result, 'Expected exactly zero interactive video record');   
    }

    // use PHPMock;

    public function test_get_interactive_video_duration_the_video_not_exist_in_youtube() {
        global $CFG, $DB;
        $student = $this->getDataGenerator()->create_user();
        
        # Create get module id of hvp
        $moduleId = $DB->get_record('modules', ['name' => 'hvp'], 'id', MUST_EXIST);
        
        # Create hvp activity
        $hvpId = $DB->insert_record('hvp', [
            'course' => $this->course->id,
            'name' => 'testing (copy) (copy)',
            'intro' => '<p>AHAHSDALKSJD</p>',
            'introformat' => 1,
            'json_content' => '{"interactiveVideo":{"video":{"files":[{"path":"https://youtu.be/bX1djYUCJ88"}]}}}',
            'embed_type' => 'div',
            'disable' => 0,
            'main_library_id' => 31,
            'content_type' => NULL,
            'authors' => '[]',
            'source' => NULL,
            'year_from' => NULL,
            'year_to' => NULL,
            'license' => 'U',
            'license_version' => NULL,
            'changes' => '[]',
            'license_extras' => NULL,
            'author_comments' => NULL,
            'default_language' => NULL,
            'filtered' => '{"interactiveVideo":{"video":{"files":[{"path":"https://www.youtube.com/watch?v=MOoIs-fth9k&ab_channel=DailyDoseOfInternet"}]}}}',
            'slug' => 'testing-2',
            'timecreated' => 1738132408,
            'timemodified' => 1738405724,
            'completionpass' => 0,
            'shared' => 0,
            'synced' => NULL,
            'hub_id' => NULL,
            'a11y_title' => NULL
        ]);

        # Create course module of hvp
        $cmId = $DB->insert_record('course_modules', [
            'course' => $this->course->id,
            'module' => $moduleId->id,
            'instance' => $hvpId, 
            'section' => 0,
            'idnumber' => '',
            'added' => time(),
            'score' => 0,
            'indent' => 0,
            'visible' => 1,
            'visibleold' => 1,
            'groupmode' => 0,
            'groupingid' => 0,
            'completion' => 0,
            'completiongradeitemnumber' => NULL,
            'completionview' => 0,
            'completionexpected' => 0,
            'showdescription' => 0,
            'availability' => NULL,
            'deletioninprogress' => 0
        ]);        
            
        
        $DB->insert_record('course_modules_completion', [
            'coursemoduleid' => $cmId,
            'userid' => $student->id,
            'completionstate' => 1,
            'timemodified' => time() + 0,
        ]);

        $result = getInteractiveVideoData($student->id, 'hvp',$DB, $CFG->phpunit_prefix);
        $this->assertEquals(null, $result[0]['Durasi'], 'Expected null because the video not exist in youtube');
    }

    public function test_video_data_next_year_and_last_year_not_counted() {
        global $DB, $CFG;

        $student = $this->getDataGenerator()->create_user();

        # Create get module id of hvp
        $moduleId = $DB->get_record('modules', ['name' => 'hvp'], 'id', MUST_EXIST);
        
        # FOR THIS YEAR
        # Create hvp activity
        $hvpId = $DB->insert_record('hvp', [
            'course' => $this->course->id,
            'name' => 'testing 1',
            'intro' => '<p>AHAHSDALKSJD</p>',
            'introformat' => 1,
            'json_content' => '{"interactiveVideo":{"video":{"files":[{"path":"https://www.youtube.com/watch?v=MOoIs-fth9k&ab_channel=DailyDoseOfInternet"}]}}}',
            'embed_type' => 'div',
            'disable' => 0,
            'main_library_id' => 31,
            'content_type' => NULL,
            'authors' => '[]',
            'source' => NULL,
            'year_from' => NULL,
            'year_to' => NULL,
            'license' => 'U',
            'license_version' => NULL,
            'changes' => '[]',
            'license_extras' => NULL,
            'author_comments' => NULL,
            'default_language' => NULL,
            'filtered' => '{"interactiveVideo":{"video":{"files":[{"path":"https://www.youtube.com/watch?v=MOoIs-fth9k&ab_channel=DailyDoseOfInternet"}]}}}',
            'slug' => 'testing-2',
            'timecreated' => time(),
            'timemodified' => 1738405724,
            'completionpass' => 0,
            'shared' => 0,
            'synced' => NULL,
            'hub_id' => NULL,
            'a11y_title' => NULL
        ]);

        # Create course module of hvp
        $cmId = $DB->insert_record('course_modules', [
            'course' => $this->course->id,
            'module' => $moduleId->id,
            'instance' => $hvpId, 
            'section' => 0,
            'idnumber' => '',
            'added' => time(),
            'score' => 0,
            'indent' => 0,
            'visible' => 1,
            'visibleold' => 1,
            'groupmode' => 0,
            'groupingid' => 0,
            'completion' => 0,
            'completiongradeitemnumber' => NULL,
            'completionview' => 0,
            'completionexpected' => 0,
            'showdescription' => 0,
            'availability' => NULL,
            'deletioninprogress' => 0
        ]);        
            
        
        $DB->insert_record('course_modules_completion', [
            'coursemoduleid' => $cmId,
            'userid' => $student->id,
            'completionstate' => 1,
            'timemodified' => time() + 0,
        ]);

        # FOR LAST YEAR
        # Create hvp activity
        $hvpId = $DB->insert_record('hvp', [
            'course' => $this->course->id,
            'name' => 'testing 2',
            'intro' => '<p>AHAHSDALKSJD</p>',
            'introformat' => 1,
            'json_content' => '{"interactiveVideo":{"video":{"files":[{"path":"https://www.youtube.com/watch?v=MOoIs-fth9k&ab_channel=DailyDoseOfInternet"}]}}}',
            'embed_type' => 'div',
            'disable' => 0,
            'main_library_id' => 31,
            'content_type' => NULL,
            'authors' => '[]',
            'source' => NULL,
            'year_from' => NULL,
            'year_to' => NULL,
            'license' => 'U',
            'license_version' => NULL,
            'changes' => '[]',
            'license_extras' => NULL,
            'author_comments' => NULL,
            'default_language' => NULL,
            'filtered' => '{"interactiveVideo":{"video":{"files":[{"path":"https://www.youtube.com/watch?v=MOoIs-fth9k&ab_channel=DailyDoseOfInternet"}]}}}',
            'slug' => 'testing-2',
            'timecreated' => strtotime('-1 year'),
            'timemodified' => 1738405724,
            'completionpass' => 0,
            'shared' => 0,
            'synced' => NULL,
            'hub_id' => NULL,
            'a11y_title' => NULL
        ]);

        # Create course module of hvp
        $cmId = $DB->insert_record('course_modules', [
            'course' => $this->course->id,
            'module' => $moduleId->id,
            'instance' => $hvpId, 
            'section' => 0,
            'idnumber' => '',
            'added' => time(),
            'score' => 0,
            'indent' => 0,
            'visible' => 1,
            'visibleold' => 1,
            'groupmode' => 0,
            'groupingid' => 0,
            'completion' => 0,
            'completiongradeitemnumber' => NULL,
            'completionview' => 0,
            'completionexpected' => 0,
            'showdescription' => 0,
            'availability' => NULL,
            'deletioninprogress' => 0
        ]);        
            
        
        $DB->insert_record('course_modules_completion', [
            'coursemoduleid' => $cmId,
            'userid' => $student->id,
            'completionstate' => 1,
            'timemodified' => time() + 0,
        ]);

        # FOR NEXT YEAR
        # Create hvp activity
        $hvpId = $DB->insert_record('hvp', [
            'course' => $this->course->id,
            'name' => 'testing 3',
            'intro' => '<p>AHAHSDALKSJD</p>',
            'introformat' => 1,
            'json_content' => '{"interactiveVideo":{"video":{"files":[{"path":"https://www.youtube.com/watch?v=MOoIs-fth9k&ab_channel=DailyDoseOfInternet"}]}}}',
            'embed_type' => 'div',
            'disable' => 0,
            'main_library_id' => 31,
            'content_type' => NULL,
            'authors' => '[]',
            'source' => NULL,
            'year_from' => NULL,
            'year_to' => NULL,
            'license' => 'U',
            'license_version' => NULL,
            'changes' => '[]',
            'license_extras' => NULL,
            'author_comments' => NULL,
            'default_language' => NULL,
            'filtered' => '{"interactiveVideo":{"video":{"files":[{"path":"https://www.youtube.com/watch?v=MOoIs-fth9k&ab_channel=DailyDoseOfInternet"}]}}}',
            'slug' => 'testing-2',
            'timecreated' => strtotime('+1 year'),
            'timemodified' => 1738405724,
            'completionpass' => 0,
            'shared' => 0,
            'synced' => NULL,
            'hub_id' => NULL,
            'a11y_title' => NULL
        ]);

        # Create course module of hvp
        $cmId = $DB->insert_record('course_modules', [
            'course' => $this->course->id,
            'module' => $moduleId->id,
            'instance' => $hvpId, 
            'section' => 0,
            'idnumber' => '',
            'added' => time(),
            'score' => 0,
            'indent' => 0,
            'visible' => 1,
            'visibleold' => 1,
            'groupmode' => 0,
            'groupingid' => 0,
            'completion' => 0,
            'completiongradeitemnumber' => NULL,
            'completionview' => 0,
            'completionexpected' => 0,
            'showdescription' => 0,
            'availability' => NULL,
            'deletioninprogress' => 0
        ]);        
            
        
        $DB->insert_record('course_modules_completion', [
            'coursemoduleid' => $cmId,
            'userid' => $student->id,
            'completionstate' => 1,
            'timemodified' => time() + 0,
        ]);

        $result = getInteractiveVideoData($student->id, 'hvp',$DB, $CFG->phpunit_prefix);
        $this->assertEquals(1, count($result));
        $this->assertEquals("testing 1", $result[0]["Nama Aktivitas"]);
    }
}
