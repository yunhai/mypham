<?php

use Mp\App;
use Mp\Lib\Package\TemplateEngine\Twig;

class FrontendTwigComponent extends Twig{

    public function twig() {
        $option = [
            'cache' => true,
            'debug' => false,
            'strict_variables' => false,
            'autoescape' => false,
            'auto_reload' => true
        ];

        $template = $this->init($option);

        $this->filterMyDate($template);
        $this->filterPrice($template);

        return $template;
    }

    protected function filterPrice($template) {
        $filter = new Twig_SimpleFilter('price', function ($string) {
            return number_format($string);
        });

        $template->addFilter($filter);
        return $template;
    }

    protected function filterMyDate($template) {
        $filter = new Twig_SimpleFilter('mydate', function ($string) {
            $string = strtotime($string);
            $week = [
                'Chủ nhật',
                'Thứ hai',
                'Thứ ba',
                'Thứ tư',
                'Thứ năm',
                'Thứ sáu',
                'Thứ bảy'
            ];
            $w = date('w', $string);
            $date = date('d/m/Y H:i (P)', $string);
            return sprintf('%s, ngày %s', $week[$w], $date);
        });

        $template->addFilter($filter);
        return $template;
    }
}
