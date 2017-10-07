<?php

use Mp\Controller\Backend\Post;

class PostController extends Post {

    public function validate($alias = '', $data = [], &$error = [], $level = 1, $rule = 'def') {
        $validator = $this->validator('post')->rule($rule);
        return $validator->validate($data, $error);
    }
}
