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

class resetPassword extends \core\task\scheduled_task {

    public function get_name() {
        return "Reset diário de senhas dos usuários";
    }

    private function generate_random_password() {
        $prefixo = '2025';

        $letras = 'abcdefghijklmnopqrstuvwxyz';
        $numeros = '0123456789';
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';

        $seed = round(microtime(true) * 1000);
        $buffer = [];

        $letraIndex = $seed % strlen($letras);
        $buffer[] = $letras[$letraIndex];

        for ($i = 0; $i < 5; $i++) {
            $index = ($seed + $i * 37) % strlen($chars);
            $buffer[] = $chars[$index];
        }

        for ($i = count($buffer) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            $temp = $buffer[$i];
            $buffer[$i] = $buffer[$j];
            $buffer[$j] = $temp;
        }

        $senha = $prefixo . implode('', $buffer);
        return $senha;

    }

    public function execute() {
        global $DB;

        \mtrace("Iniciando o reset de senhas...");
        $nova_senha_gerada = $this->generate_random_password();
        $nova_senha_hash = hash_internal_user_password($nova_senha_gerada);

        try {
            // Alteração da senha no Banco de Dados do Moodle
            $sql = "UPDATE {user} u
                SET u.password = :password
                WHERE u.suspended = 0
                AND u.deleted = 0
                AND EXISTS (
                    SELECT 1
                    FROM {role_assignments} ra
                    JOIN {role} r ON r.id = ra.roleid
                    WHERE ra.userid = u.id
                    AND r.shortname = 'student'
                );";
            $DB->execute($sql, ['password' => $nova_senha_hash]);

            \mtrace("SUCESSO: A senha de todos os estudantes foi resetada.");

            // Alteração da senha das escolas no Supabase via API
            $apikey = get_config('local_schedulerpassword', 'api_key');
            $jwt_token = get_config('local_schedulerpassword', 'jwt_token');

            $curl = new \curl();
            $curl->setHeader([
                "Content-Type: application/json",
                "apikey: $apikey",
                "Authorization: Bearer $jwt_token"
            ]);
            $response = $curl->post('https://moodlesupa.inovasee.org/rest/v1/rpc/atualizar_senha_escolas', json_encode(['nova_senha' => $nova_senha_gerada]));

            $info = $curl->get_info();
            if ($info['http_code'] >= 200 && $info['http_code'] < 300) {
                \mtrace("SUCESSO: Senhas das escolas atualizadas.");
            } else {
                \mtrace("ERRO na requisição: Código HTTP " . $info['http_code']);
                \mtrace("Resposta: " . $response);
            }
        } catch (\Exception $e) {
            \mtrace("ERRO ao resetar senhas: " . $e->getMessage());
            throw $e; 
        }
    }
}