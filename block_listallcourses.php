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
 * Block listallcourses is defined here.
 *
 * @package     block_listallcourses
 * @copyright   2025 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_listallcourses extends block_base
{

    /**
     * Initializes class member variables.
     */
    public function init()
    {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_listallcourses');
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content()
    {
        global $DB, $USER, $CFG, $OUTPUT;
        if ($this->content !== null) {
            return $this->content;
        }

        error_log("TESTING 123");

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
        $myid = $USER->id;

        // SQL query to get attendance sessions based on myid
        $sqlquery = "
            SELECT s.description, s.duration
            FROM mdl_attendance_sessions s
            JOIN mdl_attendance_log l ON s.id = l.sessionid
            WHERE l.studentid = :myid
        ";
        $params = array('myid' => $myid);

        // Execute the query
        $records = $DB->get_records_sql($sqlquery, $params);
        $durationTotal = 0;
        $table = "
<style>
    .simple-table {
        width: 100%;
        border-collapse: collapse;
    }
    .simple-table th, .simple-table td {
        padding: 8px;
        text-align: left;
    }
    .simple-table th {
        background-color: #E8EDFB;
        font-weight: bold;
    }
</style>

<table class='simple-table'>
    <thead>
        <tr>
            <th>Nama Training</th>
            <th>Durasi</th>
        </tr>
    </thead>
    <tbody>
";


if ($records) {
    foreach ($records as $record) {
        $duration = $record->duration;
        $durationTotal .= $duration;
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);

        $hours = sprintf("%02d", $hours);
        $minutes = sprintf("%02d", $minutes);

        if ($hours > 0) {
            if ($minutes > 0) {
                $duration_text = "{$hours} Jam {$minutes} Menit";
            } else {
                $duration_text = "{$hours} Jam";
            }
        } else {
            $duration_text = "{$minutes} Menit";
        }
        $max_hours = 20;
        $progress = ($durationTotal / 3600) * (100 / $max_hours); // Scale to 100% for 20 hours

        $description = $record->description;
        $description = str_replace(array('<p>', '</p>'), '', $description);
        $gradient_style = $progress < 100 ? "background:linear-gradient(to right, #007bff 0%, #007bff {$progress}%, #E9ECEF {$progress}%, #E9ECEF 100%) bottom no-repeat; background-size:100% 3px;" : "";
        $table .= "
        <tr style='{$gradient_style}'>
            <td>{$description}</td>
            <td>{$duration_text}</td>
        </tr>
        ";
    }
} else {
    $table .= "
    <tr>
        <td colspan='2'>No attendance sessions found.</td>
    </tr>
    ";
}

$table .= "
    </tbody>
</table>
";
$durationTotal = 0;

        // Append the results to $text
        if ($records) {
            foreach ($records as $record) {
                $duration = $record->duration;
                $hours = floor($duration / 3600);
                $minutes = floor(($duration % 3600) / 60);

                $hours = sprintf("%02d", $hours);
                $minutes = sprintf("%02d", $minutes);

                if ($hours > 0) {
                    if ($minutes > 0) {
                        $duration_text = "{$hours} Jam {$minutes} Menit";
                    } else {
                        $duration_text = "{$hours} Jam";
                    }
                } else {
                    $duration_text = "{$minutes} Menit";
                }
                $durationTotal += $duration;
            }
        } else {
            $text .= "<p>No attendance sessions found.</p>";
        }

        // Example progress bar
        $max_hours = 20;
        $progress = ($durationTotal / 3600) * (100 / $max_hours); // Scale to 100% for 20 hours

        if ($progress > 100) {
            $progress = 100; // Cap the progress at 100%
        }

        $text .= "
            <div class='progress' style='height: 40px;'>
                <div class='progress-bar' role='progressbar' style='width: {$progress}%; height: 40px;' aria-valuenow='{$progress}' aria-valuemin='0' aria-valuemax='100'>
                </div>
            </div>
        ";

        $hours = floor($durationTotal / 3600);
        $minutes = floor(($durationTotal % 3600) / 60);

        $hours = sprintf("%02d", $hours);
        $minutes = sprintf("%02d", $minutes);

        if ($hours > 0) {
            if ($minutes > 0) {
                $duration_text = "{$hours} Jam {$minutes} Menit";
            } else {
                $duration_text = "{$hours} Jam";
            }
        } else {
            $duration_text = "{$minutes} Menit";
        }

        $text .= "<h4 class='text-center mt-3'>{$duration_text}</h4>";

        $text .= "
<div class='text-center mt-4'>
    <a href='#' class='show-more-link' id='show-more-link' style='color: #007bff; display: inline; text-decoration: none;'>
        Lihat progress training-mu
        </a>
    <div class='triangle' style='text-align: center; display: block; color: #007bff;'>
        <span style='display: inline; margin-top: 5px;'>
        <i class='fa-solid fa-angle-down' id='triangle' style='display: inline; margin-top: 5px;'></i>
        </span>
        
    </div>
    <div id='more-content' style='display: block; border-top: 1px solid #ccc; margin-top: 16px;'>
        
        <div class='months-container-wrapper'>
            <div class='gradient-left' id='left-most'></div>
            <div class='months-container'>
            <button class='month-btn'>Semua</button>
                <button class='month-btn'>January</button>
                <button class='month-btn'>February</button>
                <button class='month-btn'>March</button>
                <button class='month-btn'>April</button>
                <button class='month-btn'>May</button>
                <button class='month-btn'>June</button>
                <button class='month-btn'>July</button>
                <button class='month-btn'>August</button>
                <button class='month-btn'>September</button>
                <button class='month-btn'>October</button>
                <button class='month-btn'>November</button>
                <button class='month-btn'>December</button>
            </div>
            <div class='gradient-right'></div>
        </div>
        <div style='height: 300px; overflow-y: auto;'>
            {$table}
        </div>
    </div>
</div>
";

// Add JavaScript to handle the show more link click
$text .= "
<script>
    function toggleMoreContent(event) {
        event.preventDefault();
        var moreContent = document.getElementById('more-content');
        var icon = document.querySelector('.triangle i');
        if (moreContent.style.display === 'none') {
            moreContent.style.display = 'block';
            icon.className = 'fa-solid fa-angle-up';
        } else {
            moreContent.style.display = 'none';
            icon.className = 'fa-solid fa-angle-down';
        }
    }
    function updateGradientVisibility() {
        var container = document.getElementsByClassName('months-container')[0];
        var gradientLeft = document.querySelector('.gradient-left');
        var gradientRight = document.querySelector('.gradient-right');
        console.log
        // console.log('TESTING' + container.scrollLeft);
        // console.log(gradientLeft);

        if (container.scrollLeft === 0) {
            gradientLeft.style.opacity = '0';
        } else {
            gradientLeft.style.opacity = '1';
        }

        if (container.scrollLeft + container.offsetWidth >= container.scrollWidth) {
            gradientRight.style.opacity = '0';
        } else {
            gradientRight.style.opacity = '1';
        }
    }
    document.getElementById('triangle').addEventListener('click', toggleMoreContent);
    document.getElementById('show-more-link').addEventListener('click', toggleMoreContent);
    var test = document.getElementsByClassName('months-container')[0];
    console.log(test);
    // console.log('TESTING 123' + test.scrollLeft);
    test.addEventListener('scroll', updateGradientVisibility);
</script>
";

// Add CSS for the month buttons
$text .= "
<style>
    .months-container-wrapper {
        position: relative;
    }
    .months-container {
        display: flex;
        overflow-x: auto;
        padding: 10px 0;
        gap: 10px;
        scrollbar-width: none; /* For Firefox */
        -ms-overflow-style: none;  /* For Internet Explorer and Edge */
    }
    .months-container::-webkit-scrollbar {
        display: none; /* For Chrome, Safari, and Opera */
    }
    .gradient-left,
    .gradient-right {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 50px;
        pointer-events: none;
        transition: opacity 1s ease;
    }
    .gradient-left {
        left: 0;
        background: linear-gradient(to right, rgba(255, 255, 255, 1), rgba(255, 255, 255, 0));
    }
    .gradient-right {
        right: 0;
        background: linear-gradient(to left, rgba(255, 255, 255, 1), rgba(255, 255, 255, 0));
    }
    .month-btn {
        display: flex;
        height: 27px;
        padding: 5px 11px;
        justify-content: center;
        align-items: center;
        border-radius: 8px;
        background: var(--Neutral-50, #F7F8F9);
        border: none;
        cursor: pointer;
    }
    .month-btn:hover {
        background: #e0e0e0;
    }
</style>";

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
            $this->title = get_string('pluginname', 'block_listallcourses');
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
            "all" => true,
        );
    }

    function _self_test()
    {
        return true;
    }
}
