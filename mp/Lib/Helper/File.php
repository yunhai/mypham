<?php

namespace Mp\Lib\Helper;

class File {

    public function upload($data = [], $destination = '', &$error = '') {
        $destination = $destination . '/' . $data['filename'];
        return move_uploaded_file($data['tmp_name'], $destination);
    }

    public function delete($path = '') {
        @unlink($path);
        return true;
    }
}