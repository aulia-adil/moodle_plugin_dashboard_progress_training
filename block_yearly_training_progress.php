<?php
require_once(__DIR__ . '/utils.php');
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
 * Block yearly_training_progress is defined here.
 *
 * @package     block_yearly_training_progress
 * @copyright   2025 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_yearly_training_progress extends block_base
{

    /**
     * Initializes class member variables.
     */
    public function init()
    {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_yearly_training_progress');
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content()
    {
        global $DB, $USER, $CFG, $OUTPUT, $PAGE;
        $PAGE->requires->js('/blocks/yearly_training_progress/main.js');
        $PAGE->requires->css('/blocks/yearly_training_progress/styles.css');
        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $text = '';

        // Insert myid into $text
        $myId = $USER->id;
        error_log("myid: {$myId}");

        global $CFG;

        $activityData = get_attendance_sessions($myId, "attendance", $DB);
        error_log("activityData: " . print_r($activityData, true));
        $activityData = array_merge($activityData, getInteractiveVideoData($myId, "hvp", $DB));
        // $dummyData = [];

        // for ($i = 0; $i < 20; $i++) {
        //     $dummyData[] = [
        //         'Nama Aktivitas' => 'Activity ' . ($i + 1),
        //         'Durasi' => rand(1, 10600), // Random duration or null
        //         'Tanggal' => time() - rand(0, 1000000), // Random past timestamp
        //         'link' => new moodle_url('/mod/hvp/view.php', ['id' => rand(1, 20)])
        //     ];
        // }
        // $activityData = $dummyData;

        // // Example usage
        // foreach ($dummyData as $activity) {
        //     print_r($activity);
        // }
        // error_log("activityData: " . print_r($activityData, true));

        // Sort activities by date in descending order
        usort($activityData, function ($a, $b) {
            return strtotime($b['Tanggal']) - strtotime($a['Tanggal']);
        });

        $courseOverviewTable = "
        <table class='simple-table' id='course-overview-table'>
            <thead style='position: sticky; top: -1px; background-color: #fff; z-index: 1;'>
            <tr data-month='all'>
                <th>Nama Aktivitas</th>
                <th>Durasi</th>
            </tr>
            </thead>
            <tbody>
        ";

        $durationTotal = 0;
        foreach ($activityData as $activity) {
            $duration = $activity['Durasi'];
            if ($duration !== null) {
                $durationTotal += $duration;
            }
        }
        $durationTotalCopy = $durationTotal;

        $PROGRESS_BAR_COLOR = "#007bff";
        $PROGRESS_BAR_BACKGROUND_COLOR = "#E9ECEF";

        foreach ($activityData as $activity) {
            $description = $activity['Nama Aktivitas'];
            $duration = $activity['Durasi'];
            $link = $activity['link'];
            $month = date('m', strtotime($activity['Tanggal']));
            $description = str_replace(array('<p>', '</p>'), '', $description);

            if ($duration === null) {
                // Handle the case where duration is null
                // For example, you can set a default value or skip processing
                $duration_text = 'N/A'; // Set a default text for null duration
                $progress = calculate_progress($durationTotalCopy);
                // Cap the progress at 100%
                if ($progress > 100) {
                    $progress = 100;
                }
                $gradient_style = "background:linear-gradient(to right, {$PROGRESS_BAR_COLOR} 0%, {$PROGRESS_BAR_COLOR} {$progress}%, {$PROGRESS_BAR_BACKGROUND_COLOR} {$progress}%, {$PROGRESS_BAR_BACKGROUND_COLOR} 100%) bottom no-repeat; background-size:100% 3px;";
            } else {
                $duration_text = formatTimeDurationForCourseOverviewTable($duration);
                $progress = calculate_progress($durationTotalCopy);
                if ($progress > 100) {
                    $progress = 100;
                }
                $gradient_style = "background:linear-gradient(to right, {$PROGRESS_BAR_COLOR} 0%, {$PROGRESS_BAR_COLOR} {$progress}%, {$PROGRESS_BAR_BACKGROUND_COLOR} {$progress}%, {$PROGRESS_BAR_BACKGROUND_COLOR} 100%) bottom no-repeat; background-size:100% 3px;";
                $durationTotalCopy -= $duration;
            }

            // $courseOverviewTable .= "
            //     <tr style='{$gradient_style}; background-color:hsla(224, 70.40%, 94.70%, 0.10);' data-month='{$month}'>
            //         <td><a href='{$link}' class='description' data-full-text='{$description}'>{$description}</a></td>
            //         <td>{$duration_text}</td>
            //     </tr>
            // ";

            // <a href='#' class='description' data-full-text='{$description}' data-activity-name='{$description}' data-activity-time='{$waktuPencatatanAktivitas}' data-activity-duration='{$durationOverlayText}' data-activity-link='{$link}'>

            $waktuPencatatanAktivitas = $activity['Waktu Pencatatan Aktivitas'];
            $durationOverlayText = "N/A";
            if ($duration) {
                $durationOverlayText = formatDuration($duration);
            }
            $activityType = $activity['Tipe Aktivitas'];
            $courseOverviewTable .= "
                <tr style='{$gradient_style}; background-color:hsla(224, 70.40%, 94.70%, 0.10);' data-month='{$month}'>
                    <td>
                    <a href='#' class='description' data-full-text='{$description}' data-activity-name='{$description}' data-activity-time='{$waktuPencatatanAktivitas}' data-activity-duration='{$durationOverlayText}' data-activity-link='{$link}'
                    data-activity-type='{$activityType}'></a></td>
                    <td>{$duration_text}</td>
                </tr>
            ";
        }
        $courseOverviewTable .= "
                </tbody>
            </table>";

        // Example progress bar
        $maxTrainingHoursAccreditation = 20;
        $progress = ($durationTotal / 3600) * (100 / $maxTrainingHoursAccreditation); // Scale to 100% for 20 hours

        if ($progress > 100) {
            $progress = 100; // Cap the progress at 100%
        }


        $text .= "
            <div class='progress' style='height: 40px;'>
                <div class='progress-bar' role='progressbar' style='width: {$progress}%; height: 40px;' aria-valuenow='{$progress}' aria-valuemin='0' aria-valuemax='100'>
                </div>
            </div>
        ";

        $duration_text = formatDuration($durationTotal);
        $text .= "<h5 class='d-flex justify-content-between' style='margin-top: 10px;'>
                    <span style='color: {$PROGRESS_BAR_COLOR};'>{$duration_text}</span>
                    <span>20 Jam</span>
                </h5>";
        $text .= "
        <style>
        .table-container {
        display: block;
        overflow-y: auto;
        height: 300px;
        scrollbar-width: none;
        
        }
        .gradient-table-container {
            position: relative;
            overflow: hidden;
    }
        
        .gradient-table-container-top,
.gradient-table-container-bottom {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    height: 30px; /* Height of the gradient */
    pointer-events: none; /* Allow clicks to pass through */
    z-index: 1;
    transition: opacity 1s ease;
}

.gradient-table-container-top {
    top: 38px;
    background: linear-gradient(to bottom, rgba(255, 255, 255, 1), rgba(255, 255, 255, 0));
}

.gradient-table-container-bottom {
    bottom: -1px;
    background: linear-gradient(to top, rgba(255, 255, 255, 1), rgba(255, 255, 255, 0));
}

.month-btn {
    display: flex;
    height: 27px;
    padding: 5px 11px;
    justify-content: center;
    align-items: center;
    
    background: var(--Neutral-50, #F7F8F9);
    border: 2px solid #F7F8F9;
    cursor: pointer;
    box-sizing: border-box; /* Ensure the button size includes border */
}
.month-btn:hover {
    background: #e0e0e0;
    border: 2px solid #e0e0e0;
    box-sizing: border-box; /* Ensure the button size includes border */
}

.month-btn.persistent-focus {
    border: 2px solid #e0e0e0; /* Add border when focused */
    box-sizing: border-box; /* Ensure the button size includes border */
}

.month-btn:active {
    background-color: #e0e0e0; /* Change background color when clicked */
    border: 2px solid #e0e0e0; /* Add border when focused */
    color: black; /* Change text color */
    box-sizing: border-box; /* Ensure the button size includes border */
}

.tooltip-text {
    visibility: hidden;
    width: 250px;
    background-color: #fff;
    color: black;
    text-align: center;
    border-radius: 5px;
    border: 1px solid #CED4DA;
    padding: 15px;
    position: absolute;
    z-index: 2;
    top: -4px;
    left: -256px;
    opacity: 0;
    transition: opacity 0.3s;
    font-size: 16px;
    font-weight: 400;
    text-align: left;
}

.tooltip-text::before {
    content: '';
    position: absolute;
    top: 10px;
    right: -10.5px;
    margin-top: 0;
    border-width: 6px;
    border-style: solid;
    border-color: transparent transparent transparent #FFFF;
    z-index: 1;
}

.tooltip-text::after {
    content: '';
    position: absolute;
    top: 10px;
    right: -12px;
    margin-top: 0;
    border-width: 6px;
    border-style: solid;
    border-color: transparent transparent transparent #CED4DA;
}

.help-icon:hover .tooltip-text {
    visibility: visible;
    opacity: 1;
}

.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.overlay-content {
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    width: 80%;
    max-width: 500px;
    text-align: left;
    position: relative;
    top: -5%;
}

.close-btn {
    position: absolute;
    top: 0px;
    right: 20px;
    font-size: 30px;
    cursor: pointer;
}
        </style>
        ";

        $text .= "
<div class='text-center mt-4'>
    <div class='triangle d-flex justify-content-center align-items-center'>
        <a href='#' class='show-more-link' id='show-more-link' style='color: #007bff; display: inline; text-decoration: none;'>
            Lihat detail
        </a>
        <span style='display: inline;  margin-left: 5px; padding-top: 0.5px; box-sizing: border-box; height: 24px; width: 20px;'>
            <i class='fa-solid fa-angle-right' id='triangle' style='display: inline;color: #007bff;'></i>
        </span>
        
    </div>
    <div id='more-content' style='display: block; border-top: 1px solid #ccc; margin-top: 16px;'>
        
        <div class='months-container-wrapper'>
            <div class='gradient-left' id='left-most'></div>
            <div class='months-container'>
                <button class='month-btn persistent-focus' data-month='all'>Semua</button>
                <button class='month-btn' data-month='01'>January</button>
                <button class='month-btn' data-month='02'>February</button>
                <button class='month-btn' data-month='03'>March</button>
                <button class='month-btn' data-month='04'>April</button>
                <button class='month-btn' data-month='05'>May</button>
                <button class='month-btn' data-month='06'>June</button>
                <button class='month-btn' data-month='07'>July</button>
                <button class='month-btn' data-month='08'>August</button>
                <button class='month-btn' data-month='09'>September</button>
                <button class='month-btn' data-month='10'>October</button>
                <button class='month-btn' data-month='11'>November</button>
                <button class='month-btn' data-month='12'>December</button>
            </div>
            <div class='gradient-right'></div>
        </div>
        <div class='gradient-table-container'>
        <div class='table-container'>
        <div class='gradient-table-container-top'></div>
            {$courseOverviewTable}
        <div class='gradient-table-container-bottom'></div>
        <div id='overlay' class='overlay'>
    <div class='overlay-content'>
        <span class='close-btn'>&times;</span>
        <h6>Nama Aktivitas: </h6>
        <p id='overlay-activity-name'></p>
        <h6>Waktu Pencatatan Aktivitas: </h6>
        <p id='overlay-activity-time'></p>
        <h6>Durasi: </h6>
        <p id='overlay-activity-duration'></p>
        <h6>Tipe Aktivitas: </h6>
        <p id='overlay-activity-type'></p>
        <h6>Link: </h6>
        <a href='#' id='overlay-activity-link' target='_blank'>Klik ini
        <i class='fa fa-external-link-alt' id='new-tab-icon' style='margin-left: 5px; cursor: pointer;'></i>
        </a>
        
    </div>
</div>
        </div>
        </div>
        </div>
    </div>
</div>
";

        $this->content->text = $text;

        return $this->content;
    }

    /**
     * Defines configuration data.
     *
     * The function is called immediately after init().
     */
    public function specialization()
    {

        // Load user defined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_yearly_training_progress');
            $this->title = "<div class='d-flex justify-content-between'
            ><span>{$this->title}</span><span 
        class='help-icon' title='Help' style='cursor: pointer; color:#008196; position: relative;'>
            <i class='fa fa-question-circle'></i>
            <span class='tooltip-text'>Sesuai dengan standar akreditasi rumah sakit, setiap staf diwajibkan mengikuti minimal <strong>20 jam pelatihan dalam setahun</strong>. Grafik dashboard ini akan membantu Anda memantau pelatihan Anda. Setiap kali Anda menyelesaikan aktivitas pelatihan, seperti <strong>menonton video</strong> atau <strong>melakukan presensi</strong>, sistem akan mencatatnya di dashboard ini. </span>
        </span></div>";
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Enables global configuration of the block in settings.php.
     *
     * @return bool True if the global configuration is enabled.
     */
    public function has_config()
    {
        return true;
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return string[] Array of pages and permissions.
     */
    public function applicable_formats()
    {
        return array(
            "all" => true
        );
    }

    function _self_test()
    {
        return true;
    }
}
