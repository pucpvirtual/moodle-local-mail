<?php

// Local mail plugin for Moodle
// Copyright © 2012,2013 Institut Obert de Catalunya
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// Ths program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

require_once("{$CFG->dirroot}/user/selector/lib.php");
require_once("{$CFG->dirroot}/local/mail/lib.php");

class mail_recipients_selector extends groups_user_selector_base {

    public function find_users($search) {
        global $DB;

        $context = get_context_instance(CONTEXT_COURSE, $this->courseid);

        list($wherecondition, $params) = $this->search_sql($search, 'u');
        list($enrolledsql, $enrolledparams) = get_enrolled_sql($context, '', $this->groupid, true);

        $params = array_merge($params, $enrolledparams);
        $params['courseid'] = $this->courseid;

        $fields = 'SELECT r.id AS roleid, '
            . 'r.shortname AS roleshortname, '
            . 'r.name AS rolename, '
            . 'u.id AS userid, '
            . $this->required_fields_sql('u');

        $countfields = 'SELECT COUNT(1)';

        $sql = ' FROM {user} u JOIN (' . $enrolledsql . ') e ON e.id = u.id'
            . ' LEFT JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.contextid '
            . get_related_contexts_string($context) . ')'
            . ' LEFT JOIN {role} r ON r.id = ra.roleid'
            . ' WHERE ' . $wherecondition;

        $order = ' ORDER BY r.sortorder, u.lastname ASC, u.firstname ASC';

        if (!$this->is_validating()) {
            $count = $DB->count_records_sql($countfields . $sql, $params);
            if ($count > 100) {
                return $this->too_many_results($search, $count);
            }
        }

        $rs = $DB->get_recordset_sql($fields . $sql . $order, $params);
        $roles = groups_calculate_role_people($rs, $context);

        return $this->convert_array_format($roles, $search);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/mail/recipients_selector.php';
        return $options;
    }
}
