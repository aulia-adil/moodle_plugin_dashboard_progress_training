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

    $video = $DB->get_record('block_listallcourses_videos', ['video_url' => $youtubeUrl]);

    if ($video) {
        $duration = $video->video_duration;
    } else {
        $duration = get_youtube_duration($youtubeUrl);
        if ($duration !== null) {
            $DB->insert_record('block_listallcourses_videos', [
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
    SELECT s.description, s.duration, cm.id, l.timetaken
    FROM {$dbPrefix}attendance_sessions s
    JOIN {$dbPrefix}attendance_log l ON s.id = l.sessionid
    JOIN {$dbPrefix}attendance a ON s.attendanceid = a.id
    JOIN {$dbPrefix}course_modules cm ON cm.instance = a.id
    JOIN {$dbPrefix}modules m ON cm.module = m.id
    WHERE l.studentid = :userid
    AND m.name = :modulename
    AND YEAR(FROM_UNIXTIME(l.timetaken)) = :currentyear
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
            $activityData[] = [
                'Nama Aktivitas' => $recordAttendance->description,
                'Durasi' => $recordAttendance->duration,
                'Tanggal' => date('Y-m-d', $recordAttendance->timetaken),
                'link' => new moodle_url('/mod/attendance/view.php', ['id' => $recordAttendance->id])
            ];
        }
    }

    return $activityData;
}

function getInteractiveVideoData($userId, $moduleName, $DB, $dbPrefix = null) {
    global $CFG;
    $dbPrefix = $dbPrefix ?? $CFG->prefix;

    $sqlQueryInteractiveVideo = "
        SELECT h.name, h.json_content, cm.id, cmc.timemodified
        FROM {$dbPrefix}course_modules_completion cmc
        JOIN {$dbPrefix}course_modules cm ON cmc.coursemoduleid = cm.id
        JOIN {$dbPrefix}hvp h ON cm.instance = h.id
        JOIN {$dbPrefix}modules m ON cm.module = m.id
        WHERE cmc.userid = :userid
        AND m.name = :modulename
        AND cmc.completionstate = :completionstate
    ";
    $COMPLETION_STATUS_COMPLETED = 1;
    $paramsInteractiveVideo = [
        'userid' => $userId,
        'modulename' => $moduleName,
        'completionstate' => $COMPLETION_STATUS_COMPLETED,
    ];

    $recordsInteractiveVideo = $DB->get_records_sql($sqlQueryInteractiveVideo, $paramsInteractiveVideo);

    $activityData = [];

    if ($recordsInteractiveVideo) {
        foreach ($recordsInteractiveVideo as $recordInteractiveVideo) {
            $activityData[] = [
                'Nama Aktivitas' => $recordInteractiveVideo->name,
                'Durasi' => get_interactive_video_duration($recordInteractiveVideo->json_content, $DB),
                'Tanggal' => $recordInteractiveVideo->timemodified,
                'link' => new moodle_url('/mod/hvp/view.php', ['id' => $recordInteractiveVideo->id])
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