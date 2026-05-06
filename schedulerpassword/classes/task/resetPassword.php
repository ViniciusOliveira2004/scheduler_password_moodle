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
 * Plugin functions for the local_schedulerpassword plugin.
 *
 * @package   local_schedulerpassword
 * @copyright 2026, Vinicius Oliveira
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_schedulerpassword\task;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/schedulerpassword/lib.php');

class resetPassword extends \core\task\scheduled_task {

    public function get_name() {
        return "Reset diário de senhas dos usuários";
    }


    public function execute() {
        global $DB;

        $sql = "SELECT DISTINCT *
                    FROM {user} u
                    JOIN {role_assignments} ra ON ra.userid = u.id
                    JOIN {role} r ON r.id = ra.roleid
                    WHERE u.suspended = 0 
                        AND u.deleted = 0
                        AND r.shortname = 'student';";
        $usuarios = $DB->get_records_sql($sql);

        $nova_senha = local_schedulerpassword_generate_random_password();
        //foreach ($usuarios as $usuario) { update_user_password($usuario, $nova_senha) }

        $papeis = $DB->get_records_sql("SELECT * FROM {role} r;");

        \mtrace("Papeis (name): " . implode(', ', array_column($papeis, 'name')));
        \mtrace("Papeis (shortname): " . implode(', ', array_column($papeis, 'shortname')));
        \mtrace("Nova senha: {$nova_senha}");
    }
}