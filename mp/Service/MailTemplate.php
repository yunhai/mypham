<?php

namespace Mp\Service;

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Core\Service\Service;

class MailTemplate extends Service {
    public function __construct($model = 'mailTemplate', $table = 'mail_template', $alias = 'mailTemplate') {
        $this->model(new \Mp\Model\MailTemplate($table, $alias));
    }

    public function code($code = 0, $option = []) {
        $alias = $this->model()->alias();

        $default = [
            'select' => 'id, _from, _to, cc, bcc, title, content, status',
            'where' => 'code = "' . $code . '"'
        ];

        $default = array_merge($default, $option);
        $result = $this->model()->find($default, 'first');

        if ($result) {
            $result = current($result);
            return $this->format($result);
        }

        return [];
    }

    private function format($target = []) {
        $target['to'] = $target['_to'];
        $target['from'] = $target['_from'];

        unset($target['_to']);
        unset($target['_from']);

        return $target;
    }
}