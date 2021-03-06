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
 * @package    mod_feedback
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_feedback_activity_task
 */

/**
 * Structure step to restore one feedback activity
 */
class restore_feedback_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('feedback', '/activity/feedback');
        $paths[] = new restore_path_element('feedback_item', '/activity/feedback/items/item');
        if ($userinfo) {
            $paths[] = new restore_path_element('feedback_completed', '/activity/feedback/completeds/completed');
            $paths[] = new restore_path_element('feedback_completed_history', '/activity/feedback/completeds_history/completed_history');
            $paths[] = new restore_path_element('feedback_value', '/activity/feedback/completeds/completed/values/value');
            $paths[] = new restore_path_element('feedback_value_history', '/activity/feedback/completeds_history/completed_history/values_history/value_history');
            $paths[] = new restore_path_element('feedback_tracking', '/activity/feedback/trackings/tracking');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_feedback($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the feedback record
        $newitemid = $DB->insert_record('feedback', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_feedback_item($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->feedback = $this->get_new_parentid('feedback');

        //dependitem
        $data->dependitem = $this->get_mappingid('feedback_item', $data->dependitem);

        $newitemid = $DB->insert_record('feedback_item', $data);
        $this->set_mapping('feedback_item', $oldid, $newitemid, true); // Can have files
    }

    protected function process_feedback_completed($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->feedback = $this->get_new_parentid('feedback');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('feedback_completed', $data);
        $this->set_mapping('feedback_completed', $oldid, $newitemid);
    }

    protected function process_feedback_completed_history($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->feedback = $this->get_new_parentid('feedback');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('feedback_completed_history', $data);
        $this->set_mapping('feedback_completed_history', $oldid, $newitemid);
    }

    protected function process_feedback_value($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->completed = $this->get_new_parentid('feedback_completed');
        $data->item = $this->get_mappingid('feedback_item', $data->item);
        $data->course_id = $this->get_courseid();

        $newitemid = $DB->insert_record('feedback_value', $data);
        $this->set_mapping('feedback_value', $oldid, $newitemid);
    }

    protected function process_feedback_value_history($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->completed = $this->get_new_parentid('feedback_completed_history');
        $data->item = $this->get_mappingid('feedback_item', $data->item);
        $data->course_id = $this->get_courseid();

        $newitemid = $DB->insert_record('feedback_value_history', $data);
        $this->set_mapping('feedback_value_history', $oldid, $newitemid);
    }

    protected function process_feedback_tracking($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->feedback = $this->get_new_parentid('feedback');
        $data->completed = $this->get_mappingid('feedback_completed', $data->completed);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('feedback_tracking', $data);
    }


    protected function after_execute() {
        // Add feedback related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_feedback', 'intro', null);
        $this->add_related_files('mod_feedback', 'page_after_submit', null);
        $this->add_related_files('mod_feedback', 'item', 'feedback_item');
    }
}
