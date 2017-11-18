<?php

use Mp\Model\Post;

class AdvisoryModel extends Post
{
    use \Mp\Lib\Traits\Extension;

    public function __construct($table = 'post', $alias = 'advisory')
    {
        parent::__construct($table, $alias);
        $this->extension();
    }

    public function extension()
    {
        $this->loadExtension(new \Mp\Model\Extension());
        $this->virtualField($this->field());
    }

    public function field()
    {
        return [
            'string_1' => 'question',
            'string_2' => 'email',
            'string_3' => 'fullname',
        ];
    }
}
