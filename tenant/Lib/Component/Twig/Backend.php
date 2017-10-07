<?php

use Mp\App;
use Mp\Lib\Package\TemplateEngine\Twig;

class BackendTwigComponent extends Twig{

    public function twig() {
        $option = [
            'cache' => false,
            'debug' => true,
            'strict_variables' => false,
            'autoescape' => false,
            'auto_reload' => true
        ];

        $template = $this->init($option);

        $this->filterError($template);
        $this->filterStatus($template);
        $this->filterNumber($template);
        $this->filterChannel($template);

        return $template;
    }

    private function filterError($template) {
        $filter = new Twig_SimpleFilter('error', function ($errors, $group, $field) {
            if (empty($errors[$group][$field])) {
                return '';
            }

            $result = '';
            foreach ($errors[$group][$field] as $err) {
                $result .= '<span class="error-message">' . $err . '</span>';
            }

            return $result;
        });

        $template->addFilter($filter);
        return $template;
    }

    private function filterStatus($template) {
        $filter = new Twig_SimpleFilter('status', function ($status, $label = [], $class = []) {
            if (empty($class)) {
                $class = [
                    'btn-xs btn-dark',
                    'btn-xs btn-success',
                    'btn-xs btn-danger',
                    'btn-xs btn-primary'
                ];
            }

            $label = isset($label[$status]) ? $label[$status] : '';
            $class = isset($class[$status]) ? $class[$status] : 'btn-default';

            return '<span class="btn ' . $class . '">' . $label . '</span>';
        });

        $template->addFilter($filter);
        return $template;
    }

    private function filterChannel($template) {
        $filter = new Twig_SimpleFilter('channel', function ($channel) {
            $map = App::mp('config')->get('channel');

            return isset($map[$channel]) ? $map[$channel] : '';
        });

        $template->addFilter($filter);
        return $template;
    }

    protected function filterNumber($template) {
        $filter = new Twig_SimpleFilter('number', function ($string) {
            return empty($string) ? 0 : number_format($string);
        });

        $template->addFilter($filter);
        return $template;
    }

    protected function filterUrl($template) {
        $filter = new Twig_SimpleFilter('url', function ($string, $param = '') {
            if (strpos($string, 'http://') === 0 ||
                strpos($string, 'https://') === 0) {
                return $string;
            }

            return App::mp('request')->branchUrl() . $string;
        });

        $template->addFilter($filter);
        return $template;
    }

}