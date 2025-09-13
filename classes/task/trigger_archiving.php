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
 * Scheduled task for triggering new activities to archive.
 *
 * @package     archivingtrigger_cron
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace archivingtrigger_cron\task;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Scheduled task for triggering new activities to archive.
 */
class trigger_archiving extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for the task (shown to admins)
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('task_trigger_archiving', 'archivingtrigger_cron');
    }

    /**
     * Triggers targeted activities to be archived.
     */
    public function execute() {
        mtrace('Checking for activities to archive in selected course categories ...');

        // Determine course modules to archive.
        $trigger = new \archivingtrigger_cron\archivingtrigger();
        $cmstoarchivemeta = $trigger->get_cms_to_archive(
            includeunchanged: get_config('archivingtrigger_cron', 'archive_unchanged')
        );

        // Quit early if nothing to archive was found.
        if (empty($cmstoarchivemeta)) {
            mtrace('No activities found to archive.');
            return;
        }

        // Trigger archiving for all dirty course modules.
        $dryrun = get_config('archivingtrigger_cron', 'dryrun');
        foreach ($cmstoarchivemeta as $cmmeta) {
            $course = $cmmeta->cm->get_course();
            $prettyname = "{$course->fullname} (ID: {$course->id}) > {$cmmeta->cm->name} (ID: {$cmmeta->cm->id})";

            // Handle dry-run.
            if ($dryrun) {
                mtrace("→ [DRY-RUN] Would archive: {$prettyname}");
                continue;
            }

            // Handle actual archive job creation.
            mtrace("→ Trigger archiving: {$prettyname}");
            try {
                $trigger->create_archive_job($cmmeta->cm);
            } catch (\Exception $e) {
                mtrace("Failed to create archive job: {$e->getMessage()}");
            }
        }
    }

}
