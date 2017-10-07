<?php

namespace Mp\Lib;

use Mp\App;

class View
{
    private $content = '';
    private $ext = '.twg';
    private $header = [];
    private $variable = [];

    public function set($key, $value = null)
    {
        $this->variable[$key] = $value;
    }

    public function reference($type = null)
    {
        if (isset($this->variable['reference'][$type])) {
            return $this->variable['reference'][$type];
        }

        return [];
    }

    public function get($key = '')
    {
        if (is_null($key)) {
            return $this->variable;
        }

        return isset($this->variable[$key]) ? $this->variable[$key] : '';
    }

    public function variable($variable = null)
    {
        if (is_null($variable)) {
            return $this->variable;
        }

        $this->variable = array_merge($this->variable, $variable);
    }

    public function render($tpl = '', $option = [])
    {
        $tpl .= $this->ext;

        $this->meta();
        if (empty($this->variable) === false) {
            $option = array_merge($this->variable, $option);
        }

        return $this->content = App::template()->render($tpl, $option);
    }

    private function meta()
    {
        $meta = $this->variable['meta'];

        $title = App::mp('config')->get('app.title');

        if (empty($meta['title'])) {
            $meta['title'] = $title;
        } else {
            $meta['title'] .= ' | ' . $title;
        }

        $this->variable['meta'] = $meta;
    }

    public function renderString($string = '', $variable = [])
    {
        if (empty($this->variable) === false) {
            $variable = array_merge($this->variable, $variable);
        }

        \Twig_Autoloader::register();

        $template = new \Twig_Environment(new \Twig_Loader_String());

        return $template->render($string, $variable);
    }

    public function finalize($runme, $func = 'navigator')
    {
        $runme->$func();

        return $this->content;
    }

    public function header($array = null)
    {
        if (is_null($array)) {
            return $this->header;
        }

        $this->header = $array;
    }

    public function content($content = null)
    {
        if (is_null($content)) {
            return $this->content;
        }

        $this->content = $content;
    }
}
