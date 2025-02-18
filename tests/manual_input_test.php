<?php

defined('MOODLE_INTERNAL') || die();

class Manual_input_test extends \advanced_testcase {

    protected $course;
    protected $quizModule;
    protected $student;
    protected $tag;

    protected function setUp(): void {
        $this->resetAfterTest(true);
        global $CFG, $DB;
        require_once($CFG->dirroot . '/blocks/yearly_training_progress/utils.php');

        // Create test data
        $this->course = $this->getDataGenerator()->create_course();
        $this->quizModule = $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id]);
        $this->student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id);
        $this->tag = $this->getDataGenerator()->create_tag([
            'name' => 'manual-input-progress-training',
            'description' => 'Ini untuk bisa melakukan manual input seperti knowledge sharing sehingga bisa dimasukkan ke dalam progress training tahunan'
        ]);

        $cmid = $DB->get_field_sql('SELECT cm.id FROM {course_modules} cm JOIN {modules} m ON cm.module = m.id WHERE m.name = ? AND cm.instance = ? AND cm.course = ?', ['quiz', $this->quizModule->id, $this->course->id]);
        $DB->insert_record('tag_instance', ['tagid' => $this->tag->id, 'itemid' => $cmid, 'itemtype' => 'course_modules']);
    }

    public function test_data_input_with_formats_got_accepted_by_admin() {
        global $DB;
        $quizAttempt = $DB->insert_record('quiz_attempts', [
            'quiz' => $this->quizModule->id,
            'userid' => $this->student->id,
            'timefinish' => time(),
            'timestart' => time(),
            'sumgrades' => 3,
            'state' => 'finished',
            'layout' => '1,2,3'
        ], true);

        $DB->set_field('quiz_attempts', 'uniqueid', $quizAttempt, ['id' => $quizAttempt]);

        $questions = [
            ['text' => 'nama-aktivitas', 'response' => 'Suatu Judul yang Saya Buat', 'time' => time() + 60],
            ['text' => 'durasi', 'response' => '2 Jam', 'time' => time() + 120],
            ['text' => 'tanggal-awal', 'response' => '2021-01-01', 'time' => time() + 180]
        ];

        foreach ($questions as $index => $question) {
            $questionId = $DB->insert_record('question', [
                'questiontext' => "<p data-parse=\"{$question['text']}\"><span class=\"M7eMe\"><strong>{$question['text']}</strong></span></p>",
                'generalfeedback' => ''
            ]);

            $DB->insert_record('question_attempts', [
                'questionusageid' => $quizAttempt,
                'questionsummary' => "<p data-parse=\"{$question['text']}\"><span class=\"M7eMe\"><strong>{$question['text']}</strong></span></p>",
                'responsesummary' => $question['response'],
                'maxmark' => 1,
                'timemodified' => $question['time'],
                'slot' => $index + 1,
                'questionid' => $questionId,
                'minfraction' => 0,
                'maxfraction' => 1,
                'behaviour' => 'manualgraded',
                'timecreated' => time()
            ]);
        }

        $questionAttemptStep = $DB->insert_record('question_attempt_steps', [
            'questionattemptid' => $quizAttempt,
            'state' => 'mangrright',
            'sequencenumber' => 4,
            'timecreated' => time()
        ]);

        $DB->insert_record('question_attempt_step_data', [
            'attemptstepid' => $questionAttemptStep,
            'name' => '-comment',
            'value' => '<p>Ini adalah komentar saya</p>'
        ]);

        $result = get_quiz_attempt_data($this->student->id, 'manual-input-progress-training', 'quiz', $DB);
        
        $this->assertEquals('Suatu Judul yang Saya Buat', $result[0]['Nama Aktivitas']);
        $this->assertCount(1, $result);
    }

    public function test_data_input_with_formats_not_accepted_by_admin() {
        global $DB;
        $quizAttempt = $DB->insert_record('quiz_attempts', [
            'quiz' => $this->quizModule->id,
            'userid' => $this->student->id,
            'timefinish' => time(),
            'timestart' => time(),
            'sumgrades' => 3,
            'state' => 'finished',
            'layout' => '1,2,3'
        ], true);

        $DB->set_field('quiz_attempts', 'uniqueid', $quizAttempt, ['id' => $quizAttempt]);

        $questions = [
            ['text' => 'nama-aktivitas', 'response' => 'Suatu Judul yang Saya Buat', 'time' => time() + 60],
            ['text' => 'durasi', 'response' => '2 Jam', 'time' => time() + 120],
            ['text' => 'tanggal-awal', 'response' => '2021-01-01', 'time' => time() + 180]
        ];

        foreach ($questions as $index => $question) {
            $questionId = $DB->insert_record('question', [
                'questiontext' => "<p data-parse=\"{$question['text']}\"><span class=\"M7eMe\"><strong>{$question['text']}</strong></span></p>",
                'generalfeedback' => ''
            ]);

            $DB->insert_record('question_attempts', [
                'questionusageid' => $quizAttempt,
                'questionsummary' => "<p data-parse=\"{$question['text']}\"><span class=\"M7eMe\"><strong>{$question['text']}</strong></span></p>",
                'responsesummary' => $question['response'],
                'maxmark' => 1,
                'timemodified' => $question['time'],
                'slot' => $index + 1,
                'questionid' => $questionId,
                'minfraction' => 0,
                'maxfraction' => 1,
                'behaviour' => 'manualgraded',
                'timecreated' => time()
            ]);
        }

        $questionAttemptStep = $DB->insert_record('question_attempt_steps', [
            'questionattemptid' => $quizAttempt,
            'state' => 'mangrright',
            'sequencenumber' => 4,
            'timecreated' => time()
        ]);

        $DB->insert_record('question_attempt_step_data', [
            'attemptstepid' => $questionAttemptStep,
            'name' => '-comment',
            'value' => '<p>Ini adalah komentar saya</p>'
        ]);

        $result = get_quiz_attempt_data($this->student->id, 'manual-input-progress-training', 'quiz', $DB);
        
        $this->assertEquals('Suatu Judul yang Saya Buat', $result[0]['Nama Aktivitas']);
        $this->assertCount(1, $result);
    }
}