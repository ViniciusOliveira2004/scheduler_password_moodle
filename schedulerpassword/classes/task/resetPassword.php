<?php
/**
 * @package    local_schedulerpassword
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

        $sql = "SELECT DISTINCT *
                    FROM {user} u
                    JOIN {role_assignments} ra ON ra.userid = u.id
                    JOIN {role} r ON r.id = ra.roleid
                    WHERE u.suspended = 0 
                        AND u.deleted = 0
                        AND r.shortname = 'student';";
        $usuarios = $DB->get_records_sql($sql);

        $nova_senha = $this->generate_random_password();
        //foreach ($usuarios as $usuario) {//update_user_password($usuario, $nova_senha) }

        $papeis = $DB->get_records_sql("SELECT * FROM {role} r;");

        \mtrace("Papeis (name): " . implode(', ', array_column($papeis, 'name')));
        \mtrace("Papeis (shortname): " . implode(', ', array_column($papeis, 'shortname')));
        \mtrace("Nova senha: {$nova_senha}");
    }
}