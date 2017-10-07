<?php

use Mp\Controller\Frontend\Contact;

class ContactController extends Contact {

    public function __construct($model = 'contact', $table = 'contact', $alias = 'contact', $template = 'contact') {
        parent::__construct($model, $table, $alias, $template);
    }
}