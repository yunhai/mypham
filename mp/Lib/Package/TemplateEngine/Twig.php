<?php

namespace Mp\Lib\Package\TemplateEngine;

use Mp\App;

use Twig_Autoloader as Twig_Autoloader;
use Twig_Loader_String as Twig_Loader_String;
use Twig_Loader_Filesystem as Twig_Loader_Filesystem;
use Twig_Environment as Twig_Environment;

use Twig_SimpleFunction as Twig_SimpleFunction;
use Twig_SimpleFilter as Twig_SimpleFilter;
use Twig_Extension_Debug as Twig_Extension_Debug;

class Twig
{
    public function renderString($string = '')
    {
        Twig_Autoloader::register();

        $template = new Twig_Environment(new Twig_Loader_String());

        return $template->render($string, $option);
    }

    public function init($option = [])
    {
        $config = App::mp('config');
        $request = App::mp('request');

        $base = $config->appLocation();
        $folder = $config->get('app.view.' . $request->channel);
        if ($option['cache']) {
            $option['cache'] = ROOT . "storage/cache/view/{$folder}";
        }

        $loader = new Twig_Loader_Filesystem($base . 'View' . DS . $folder . DS);
        $template = new Twig_Environment($loader, $option);

        $this->filterDecode($template);
        $this->filterUrl($template);

        $this->filterTruncate($template);
        $this->filterMedia($template);
        $this->filterMediaPath($template);

        $this->filterLink($template);
        $this->filterAsset($template);

        $this->functionFile($template);
        $this->functionLocale($template);
        $this->functionLoad($template);
        $this->functionRequest($template);
        $this->functionLogin($template);
        $this->functionMp($template);
        $this->functionConfig($template);

        $this->functionMedia($template); // sap bo
        $this->functionDebug($template); // sap bo
        $this->functionExecute($template); // sap bo

        return $template;
    }

    protected function functionExecute($template)
    {
        $function = new Twig_SimpleFunction('execute', function ($fn = '') {
            return $fn();
        });

        $template->addFunction($function);

        return $template;
    }

    protected function functionDebug($template)
    {
        $function = new Twig_SimpleFunction('dump', function (... $args) {
            ob_start();

            foreach ($args as $arg) {
                print_r('<pre>');
                print_r($arg);
                print_r('</pre>');
            }

            return ob_get_clean();
        }, ['is_safe' => ['html']]);

        $template->addFunction($function);

        return $template;
    }

    protected function functionMp($template)
    {
        $function = new Twig_SimpleFunction('mp', function ($target = '') {
            return App::mp($target);
        });

        $template->addFunction($function);

        return $template;
    }

    protected function functionLocale($template)
    {
        $function = new Twig_SimpleFunction('__', function ($code, $scope, $default = '') {
            return __($code, $scope, $default);
        });

        $template->addFunction($function);

        return $template;
    }

    protected function functionLogin($template)
    {
        $function = new Twig_SimpleFunction('login', function () {
            return App::mp('login');
        });

        $template->addFunction($function);

        return $template;
    }

    protected function functionLoad($template)
    {
        $function = new Twig_SimpleFunction('load', function ($name, $type = 'helper') {
            return App::load($name, $type);
        });

        $template->addFunction($function);

        return $template;
    }

    protected function functionRequest($template)
    {
        $function = new Twig_SimpleFunction('request', function () {
            return App::mp('request');
        });

        $template->addFunction($function);

        return $template;
    }

    protected function filterAsset($template)
    {
        $filter = new Twig_SimpleFilter('asset', function ($string, $option = []) {
            return $this->makeImg(App::load('html')->img() . '/' . $string, $option);
        });

        $template->addFilter($filter);

        return $template;
    }

    protected function filterMedia($template)
    {
        $filter = new Twig_SimpleFilter('media', function ($file, $option = [], $ref = 'file') {
            $map = App::mp('view')->reference($ref);

            if (isset($option['target_id'])) {
                $fileId = $option['target_id'];
            } elseif (isset($option['field'])) {
                $fileId = $file[$option['field']];
            } else {
                $fileId = $file['file_id'];
            }

            if (isset($map[$fileId])) {
                return $this->makeMedia($map[$fileId], $option);
            }

            return $this->makeImg(App::load('html')->img() . '/no-image.png', $option);
        });

        $template->addFilter($filter);

        return $template;
    }

    protected function filterMediaPath($template)
    {
        $filter = new Twig_SimpleFilter('mediaPath', function ($file) {
            return $this->makeMediaPath($file);
        });

        $template->addFilter($filter);

        return $template;
    }

    protected function functionFile($template)
    {
        $function = new Twig_SimpleFunction('file', function ($id, $ref = 'file') {
            $map = App::mp('view')->reference($ref);

            return isset($map[$id]) ? $map[$id] : [];
        });

        $template->addFunction($function);

        return $template;
    }

    protected function functionMedia($template)
    {
        $function = new Twig_SimpleFunction('media', function ($file, $option = []) {
            return $this->makeMedia($file, $option);
        });

        $template->addFunction($function);

        return $template;
    }

    protected function makeMedia($file, $option = [])
    {
        $path = self::makeMediaPath($file);

        return $this->makeImg($path, $option);
    }

    protected function makeMediaPath($file)
    {
        return App::load('html')->media() . $file['directory'] . $file['name'];
    }

    protected function makeImg($path = '', $option = [])
    {
        $attr = '';
        foreach ($option as $key => $value) {
            $attr .= $key . "='" . $value . "' ";
        }

        return "<img src='{$path}' {$attr} />";
    }

    protected function filterDecode($template)
    {
        $filter = new Twig_SimpleFilter('decode', function ($string) {
            return html_entity_decode($string);
        });

        $template->addFilter($filter);

        return $template;
    }

    protected function filterUrl($template)
    {
        $filter = new Twig_SimpleFilter('url', function ($string, $param = '') {
            if (strpos($string, 'http://') === 0 ||
                strpos($string, 'https://') === 0) {
                return $string;
            }

            return App::load('format')->url($string, $param);
        });

        $template->addFilter($filter);

        return $template;
    }

    protected function filterLink($template)
    {
        $filter = new Twig_SimpleFilter('link', function ($target = [], $module = '', $type = 'detail', $param = '') {
            return App::load('format')->link($target, $module, $type, $param);
        });

        $template->addFilter($filter);

        return $template;
    }

    protected function filterTruncate($template)
    {
        $filter = new Twig_SimpleFilter('truncate', function ($string, $length = 100, $option = []) {
            return App::load('format')->truncate($string, $length, $option);
        });

        $template->addFilter($filter);

        return $template;
    }

    protected function functionConfig($template)
    {
        $function = new Twig_SimpleFunction('config', function ($key) {
            return App::mp('config')->get($key);
        });

        $template->addFunction($function);

        return $template;
    }
}
