<?php

if (!function_exists('get_youtube_duration')) {
    function get_youtube_duration($video_url)
    {
        // Use youtube-dl to fetch video info
        $command = "PYTHONPATH=/bitnami/youtube-dl python3 -m youtube_dl --get-duration \"$video_url\"";
        $duration = shell_exec($command);

        if ($duration) {
            // Convert duration to seconds (e.g., "1:23" -> 83)
            $parts = explode(':', trim($duration));
            if (count($parts) == 2) {
                return (int) $parts[0] * 60 + (int) $parts[1];
            } elseif (count($parts) == 3) {
                return (int) $parts[0] * 3600 + (int) $parts[1] * 60 + (int) $parts[2];
            }
        }
        return null;
    }
}

function get_interactive_video_duration($json_content, $DB) {
    
    $data = json_decode($json_content, true);

    // Get the path
    $youtubeUrl = $data['interactiveVideo']['video']['files'][0]['path'];

    $video = $DB->get_record('block_yearly_training_progress_videos', ['video_url' => $youtubeUrl]);

    if ($video) {
        $duration = $video->video_duration;
    } else {
        $duration = get_youtube_duration($youtubeUrl);
        if ($duration !== null) {
            $DB->insert_record('block_yearly_training_progress_videos', [
                'video_url' => $youtubeUrl,
                'video_duration' => $duration,
            ]);
        }
    }
    return $duration;
}

/**
 * Converts total duration in seconds to a formatted duration text.
 *
 * @param int $durationTotal Total duration in seconds.
 * @return string Formatted duration text.
 */
function formatDuration($durationTotal) {
    $hours = floor($durationTotal / 3600);
    $minutes = floor(($durationTotal % 3600) / 60);

    $hours = sprintf("%02d", $hours);
    $minutes = sprintf("%02d", $minutes);

    if ($hours > 0) {
        if ($minutes > 0) {
            return "{$hours} Jam {$minutes} Menit";
        } else {
            return "{$hours} Jam";
        }
    } else {
        return "{$minutes} Menit";
    }
}

/**
 * Format duration from seconds to hh:mm:ss.
 *
 * @param int $seconds Duration in seconds.
 * @return string Formatted duration in hh:mm:ss.
 */
function formatTimeDurationForCourseOverviewTable($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}

function get_attendance_sessions($userId, $moduleName, $DB, $dbPrefix = null) {
    global $CFG, $USER, $DB;
    $dbPrefix = $dbPrefix ?? $CFG->prefix;
    $currentYear = date('Y'); // Get the current year

$sqlQueryAttendance = "
    WITH RECURSIVE numbers AS (
        SELECT 1 AS n
        UNION ALL
        SELECT n + 1
        FROM numbers
        WHERE n < (SELECT MAX(1 + LENGTH(l.statusset) - LENGTH(REPLACE(l.statusset, ',', ''))) FROM {$dbPrefix}attendance_log l)
    )
    SELECT 
        ROW_NUMBER() OVER (ORDER BY cm.id) AS rownum,
        s.description, s.duration, cm.id, s.sessdate, l.timetaken
    FROM {$dbPrefix}attendance_sessions s
    JOIN {$dbPrefix}attendance_log l ON s.id = l.sessionid
    JOIN {$dbPrefix}attendance a ON s.attendanceid = a.id
    JOIN {$dbPrefix}course_modules cm ON cm.instance = a.id
    JOIN {$dbPrefix}modules m ON cm.module = m.id
    WHERE l.studentid = :userid
    AND m.name = :modulename
    AND YEAR(FROM_UNIXTIME(s.sessdate)) = :currentyear
    AND l.statusid = (
        SELECT MIN(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(l.statusset, ',', n.n), ',', -1) AS UNSIGNED))
        FROM numbers n
        WHERE n.n <= 1 + LENGTH(l.statusset) - LENGTH(REPLACE(l.statusset, ',', ''))
    )
";


    $paramsAttendance = [
        'userid' => $userId,
        'modulename' => $moduleName,
        'currentyear' => $currentYear
    ];

    $activityData = [];

    $recordsAttendance = $DB->get_records_sql($sqlQueryAttendance, $paramsAttendance);

    
    
    if ($recordsAttendance) {
        foreach ($recordsAttendance as $recordAttendance) {
            $datetimeTimetaken = null;
            if ($recordAttendance->timetaken) {
                $datetime = new DateTime();
                $datetime->setTimestamp($recordAttendance->timetaken);
                $formatter = new IntlDateFormatter('id_ID', IntlDateFormatter::FULL, IntlDateFormatter::FULL);
                $formatter->setPattern('HH:mm, dd MMMM yyyy');
                $datetimeTimetaken = $formatter->format($datetime);
            }
            $activityData[] = [
                'Nama Aktivitas' => $recordAttendance->description,
                'Durasi' => $recordAttendance->duration,
                'Tanggal' => $recordAttendance->sessdate,
                'link' => new moodle_url('/mod/attendance/view.php', ['id' => $recordAttendance->id, "view" => 5]),
                'Waktu Pencatatan Aktivitas' => $datetimeTimetaken,
                'Tipe Aktivitas' => 'Presensi Kehadiran'
            ];
        }
    }

    return $activityData;
}

function getInteractiveVideoData($userId, $moduleName, $DB, $dbPrefix = null) {
    global $CFG;
    $dbPrefix = $dbPrefix ?? $CFG->prefix;

    $sqlQueryInteractiveVideo = "
        SELECT 
            ROW_NUMBER() OVER (ORDER BY cm.id) AS rownum,
            h.name, 
            h.json_content, 
            cm.id, 
            cmc.timemodified
        FROM {$dbPrefix}course_modules_completion cmc
        JOIN {$dbPrefix}course_modules cm ON cmc.coursemoduleid = cm.id
        JOIN {$dbPrefix}hvp h ON cm.instance = h.id
        JOIN {$dbPrefix}modules m ON cm.module = m.id
        WHERE cmc.userid = :userid
        AND YEAR(FROM_UNIXTIME(h.timecreated)) = :currentyear
        AND m.name = :modulename
        AND (cmc.completionstate = :completionstate1 OR cmc.completionstate = :completionstate2)
    ";
    $COMPLETION_STATUS_COMPLETE = 1;
    $COMPLETION_STATUS_COMPLETE_WITH_PASS = 2;
    $paramsInteractiveVideo = [
        'userid' => $userId,
        'modulename' => $moduleName,
        'completionstate1' => $COMPLETION_STATUS_COMPLETE,
        'completionstate2' => $COMPLETION_STATUS_COMPLETE_WITH_PASS,
        'currentyear' => date('Y')
    ];

    $recordsInteractiveVideo = $DB->get_records_sql($sqlQueryInteractiveVideo, $paramsInteractiveVideo);

    $activityData = [];

    if ($recordsInteractiveVideo) {
        foreach ($recordsInteractiveVideo as $recordInteractiveVideo) {
            $datetime = new DateTime();
            $datetime->setTimestamp($recordInteractiveVideo->timemodified);
            $formatter = new IntlDateFormatter('id_ID', IntlDateFormatter::FULL, IntlDateFormatter::FULL);
            $formatter->setPattern('HH:mm, dd MMMM yyyy');
            $datetimeTimetaken = $formatter->format($datetime);
            $activityData[] = [
                'Nama Aktivitas' => $recordInteractiveVideo->name,
                'Durasi' => get_interactive_video_duration($recordInteractiveVideo->json_content, $DB),
                'Tanggal' => $recordInteractiveVideo->timemodified,
                'link' => new moodle_url('/mod/hvp/view.php', ['id' => $recordInteractiveVideo->id]),
                'Waktu Pencatatan Aktivitas' => $datetimeTimetaken,
                'Tipe Aktivitas' => 'Video Interaktif'
            ];
        }
    }

    return $activityData;
}

/**
 * Calculate the progress percentage based on the total duration and maximum training hours.
 *
 * @param int $durationTotal Total duration in seconds.
 * @param int $maxTrainingHoursAccreditation Maximum training hours for accreditation.
 * @return float Progress percentage.
 */
function calculate_progress($durationTotal, $maxTrainingHoursAccreditation = 20) {
    return ($durationTotal / 3600) * (100 / $maxTrainingHoursAccreditation); // Scale to 100% for max training hours
}

/**
 * Get quiz attempt data for a user based on a specific tag.
 *
 * @param int $userId User ID.
 * @param string $tagName Tag name.
 * @param object $DB Moodle database object.
 * @return array Quiz attempt data.
 */
function get_quiz_attempt_data($userId, $tagName, $moduleName, $DB) {
    global $CFG;

    $sqlQuery = "
    SELECT 
        ROW_NUMBER() OVER (ORDER BY cm.id) AS rownum,
        qn.questiontext AS questiontext,
        qat.responsesummary,
        q.sumgrades AS thresholdgrade,
        qat.questionusageid,
        qa.sumgrades,
        qat.timemodified,
        qasd.value AS feedback,
        qa.id AS quizattemptid,
        cm.id AS cmid
    FROM 
        {quiz_attempts} qa
    JOIN 
        {quiz} q ON qa.quiz = q.id
    JOIN
        {course_modules} cm ON cm.instance = q.id
    JOIN
        {modules} m ON cm.module = m.id
    JOIN 
        {tag_instance} ti ON ti.itemid = cm.id
    JOIN 
        {tag} t ON t.id = ti.tagid
    JOIN 
        {question_attempts} qat ON qat.questionusageid = qa.uniqueid
    JOIN
        {question} qn ON qn.id = qat.questionid
    JOIN
        (
        SELECT 
            qas_inner.questionattemptid,
            qas_inner.id
        FROM
            {question_attempt_steps} qas_inner
        WHERE
            qas_inner.sequencenumber = (
                SELECT MAX(qas_inner2.sequencenumber)
                FROM {question_attempt_steps} qas_inner2
                WHERE qas_inner2.questionattemptid = qas_inner.questionattemptid
            )
        ) qas ON qas.questionattemptid = qat.id
    LEFT JOIN
        {question_attempt_step_data} qasd ON qasd.id = (
            SELECT MAX(qasd_inner.id)
            FROM {question_attempt_step_data} qasd_inner
            WHERE qasd_inner.attemptstepid = qas.id
            AND (qasd_inner.name = '-comment' OR qasd_inner.name IS NULL)
        )
    WHERE 
        t.name = :tagname
        AND qa.userid = :userid
        AND qa.state = 'finished'
        AND (qasd.name = '-comment' OR qasd.name IS NULL)
        AND m.name = :modulename
";

    
    $params = [
        'tagname' => $tagName,
        'userid' => $userId,
        'modulename' => $moduleName
    ];
    
    $records = $DB->get_records_sql($sqlQuery, $params);

    $activityData = [];
    $groupedRecords = [];

    // error_log('records = ' . print_r($records, true));
    // Group records by questionusageid
    foreach ($records as $record) {
        $questionusageid = $record->questionusageid;
        if (!isset($groupedRecords[$questionusageid])) {
            $groupedRecords[$questionusageid] = [];
        }
        $groupedRecords[$questionusageid][] = $record;
    }

    // Process grouped records
    foreach ($groupedRecords as $questionusageid => $group) {
        $activityDatum = [];
        $timeModifiedArray = [];
        
        foreach ($group as $record) {
            $parsedData = parse_html_for_data_parse($record->questiontext);
            if (empty($parsedData)) {
                continue;
            }
            if (isset($parsedData['nama-aktivitas'])) {
                $activityDatum['Nama Aktivitas'] = $record->responsesummary;
                if ($record->feedback !== null && $record->feedback !== '') {
                    $cleanedFeedback = strip_tags($record->feedback);
                    $cleanedFeedback = str_replace(["\r", "\n", ' '], '', $cleanedFeedback);
                    $activityDatum['Nama Aktivitas'] = $cleanedFeedback;
                }
            }
            if (isset($parsedData['durasi'])) {
                // error_log('parse_duration_to_seconds = ' . parse_duration_to_seconds($record->responsesummary));
                $activityDatum['Durasi'] = parse_duration_to_seconds($record->responsesummary);
                // error_log('[DURASI] record-responsesummary = ' . $record->responsesummary);
            
                if ($record->feedback) {
                    // error_log('[DURASI-FEEDBACK] record-feedback = ' . $record->feedback);
                    
                    $cleanedFeedback = strip_tags($record->feedback);
                    $cleanedFeedback = str_replace(["\r", "\n", ' '], '', $cleanedFeedback);
                   
                    $activityDatum['Durasi'] = parse_duration_to_seconds($cleanedFeedback);
                }
            }
            if (isset($parsedData['tanggal-awal'])) {
                $activityDatum['Tanggal'] = parse_date_to_unixtime($record->responsesummary);
                if ($record->feedback) {
                    $cleanedFeedback = strip_tags($record->feedback);
                    $cleanedFeedback = str_replace(["\r", "\n", ' '], '', $cleanedFeedback);
                    $activityDatum['Tanggal'] = parse_date_to_unixtime($cleanedFeedback);
                }
            }
            $timeModifiedArray[] = $record->timemodified;
        }
        
        if ($record->sumgrades < $record->thresholdgrade) {
            continue;
        }
        # If tanggal isn't this year, then skip
        if (isset($activityDatum['Tanggal']) && is_numeric($activityDatum['Tanggal']) && (int)$activityDatum['Tanggal'] > 0) {
            if (date('Y', $activityDatum['Tanggal']) != date('Y')) {
                continue;
            }
        } 
        
        $activityDatum ['link'] = new moodle_url('/mod/quiz/review.php', ['attempt' => $record->quizattemptid, 'cmid' => $record->cmid]);
        $activityDatum ['Waktu Pencatatan Aktivitas'] = format_timestamp(max($timeModifiedArray));
        $activityDatum ['Tipe Aktivitas'] = 'Manual Input';

        $activityData[] = $activityDatum;
    }
    
    // error_log('activityData = ' . print_r($activityData, true));
    

    return $activityData;
}
function parse_html_for_data_parse($htmlContent) {
    $dom = new DOMDocument();
    @$dom->loadHTML($htmlContent); // Suppress warnings for invalid HTML

    $xpath = new DOMXPath($dom);
    $elements = $xpath->query('//*[@data-parse]');

    $parsedData = [];
    foreach ($elements as $element) {
        $dataParseValue = $element->getAttribute('data-parse');
        $parsedData[$dataParseValue] = $element->nodeValue;
    }

    return $parsedData;
}
/**
 * Parse a duration string in the format hh:mm into seconds.
 *
 * @param string $durationString Duration string in the format hh:mm:ss.
 * @return int|null Duration in seconds or null if the format is incorrect.
 */
function parse_duration_to_seconds($durationString) {
    if (preg_match('/^(\d+):(\d+)$/', $durationString, $matches)) {
        $hours = (int)$matches[1];
        $minutes = (int)$matches[2];

        return ($hours * 3600) + ($minutes * 60);
    } else {
        return null;
    }
}

/**
 * Parse a date string in the format dd/mm/yyyy into Unix time with 00:00 as the HH:mm.
 *
 * @param string|null $dateString Date string in the format dd/mm/yyyy.
 * @return int|null Unix time or null if the format is incorrect.
 */
function parse_date_to_unixtime($dateString) {
    if ($dateString === null) {
        return null;
    }

    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateString, $matches)) {
        $day = (int)$matches[1];
        $month = (int)$matches[2];
        $year = (int)$matches[3];

        // Validate the date
        if (!checkdate($month, $day, $year)) {
            return null;
        }

        $dateTime = new DateTime();
        $dateTime->setDate($year, $month, $day);
        $dateTime->setTime(0, 0);

        return $dateTime->getTimestamp();
    } else {
        return null;
    }
}

    /**
 * Format a timestamp into a specific date and time format.
 *
 * @param int $timestamp Unix timestamp.
 * @param string $locale Locale for formatting.
 * @param string $pattern Date and time pattern.
 * @return string Formatted date and time.
 */
function format_timestamp($timestamp, $locale = 'id_ID', $pattern = 'HH:mm, dd MMMM yyyy') {
    $datetime = new DateTime();
    $datetime->setTimestamp($timestamp);
    $formatter = new IntlDateFormatter($locale, IntlDateFormatter::FULL, IntlDateFormatter::FULL);
    $formatter->setPattern($pattern);
    return $formatter->format($datetime);
}