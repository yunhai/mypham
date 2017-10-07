<?php

use Mp\Model\Apps;

class AppModel extends Apps {

    public function error($error = null) {
        if (is_null($error)) {
            $errors = parent::error();
            if (empty($errors)) {
                return [];
            }

            $result = [];
            foreach ($errors as $field => $criteria) {
                foreach ($criteria as $error ) {
                    $result[$field][] = $error;
                }
            }

            return $result;
        }

        return parent::error($error);
    }
}