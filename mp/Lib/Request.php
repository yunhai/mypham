<?php

namespace Mp\Lib;

use Mp\App;
use Mp\Lib\Utility\Sanitize;

class Request
{
    public function baseUrl()
    {
        return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/';
    }

    public function home()
    {
        return self::branchUrl();
    }

    public function branchUrl()
    {
        $result = self::baseUrl();

        $locale = self::getLocale(false);
        if (empty($this->locale) === false && $locale) {
            $result .= $locale . '/';
        }

        if (empty($this->prefix) === false) {
            $result .= $this->prefix . '/';
        }

        return trim($result, '/') . '/';
    }

    public function getLocale($id = true)
    {
        if ($id) {
            return $this->locale;
        }

        $config = App::mp('config');
        $locale = $config->get('locale.available.' . $this->locale);

        if ($config->get('locale.default') != $locale) {
            return $locale;
        }

        return '';
    }

    public function conduct($output = array())
    {
        if (empty($output)) {
            $output = $_GET;
        }

        if (empty($output['request'])) {
            $output['request'] = '';
        } else {
            $output['request'] = strtolower($output['request']);
        }

        $output = $this->formatGet($output);
        if (!empty($_POST)) {
            $output['data'] = $_POST;
        }

        $output = Sanitize::clean($output);

        foreach ($output as $key => $info) {
            $this->$key = $info;
        }

        return true;
    }

    private function formatGet($input)
    {
        $config = App::mp('config');

        $query = $name = $param = $locales = $prefixes = [];
        $tenant = $app = $prefix = $channel = $locale = '';

        $appService = new \Mp\Service\Apps();
        $target = $appService->getByDomain($this->host());

        if (empty($target)) {
            abort('invalid tenant', 500);
        }

        $tenant = $target['app']['tenant_id'];
        $app = $target['app']['code'];
        $domain = $target['app']['domain'];

        //$config->basic($tenant, $app);
        $prefixes = $config->get('app.prefix');

        $locales = $config->get('locale.available');

        foreach ($input as $key => $value) {
            if ($key == 'request') {
                $value = trim($value, '/');

                if (strpos($value, '/') !== false) {
                    $query = array_merge($query, explode('/', $value));

                    if (in_array($query[0], $locales)) {
                        $locale = $query[0];
                        unset($query[0]);

                        $query = array_values($query);
                    }

                    foreach ($query as $k => $v) {
                        if (in_array($v, $prefixes)) {
                            $prefix = $v;
                            unset($query[$k]);

                            continue;
                        }
                        if (strpos($v, ':') !== false) {
                            $tmp = explode(':', $v);

                            if (count($tmp) > 1) {
                                $name[$tmp[0]] = $tmp[1];
                                unset($query[$k]);
                            }
                        }
                    }
                } else {
                    if (in_array($value, $locales)) {
                        $locale = $value;
                    } elseif (in_array($value, $prefixes)) {
                        $prefix = $value;
                    } elseif (!empty($value)) {
                        $query[] = $value;
                    }
                }
            }

            if (strpos($key, ':') !== false) {
                $tmp = explode(':', $key);
                if (count($tmp) > 1) {
                    $param[$tmp[0]] = $tmp[1];
                    continue;
                }
            }

            $param[$key] = $value;
        }

        $channel = empty($prefix) ? 'frontend' : $prefix;

        $url = '';
        if (empty($locale)) {
            $locale = $config->get('locale.default');
        } else {
            $url = $locale . '/';
        }

        if ($prefix) {
            $url .= $prefix . '/';
        }

        $target['locale'] = array_search($locale, $locales);
        if (is_null($target['locale'])) {
            abort('invalid locale', 500);
        }

        Session::delete('target');
        Session::write('target', $target);

        $config->option($tenant, $target['app'], $locale);

        $query = array_values($query);
        if ($query) {
            $url .= trim(implode('/', $query));
        }

        $url = trim($url, '/');
        if (!$url) {
            $url = '/';
        }
        $meta = [];
        $url = App::mp('seo')->search($url, $meta);
        if ($url) {
            $query = explode('/', $url);
        }

        if (!$query) {
            abort('page not found', 404);
        }

        $view = App::mp('view');
        $view->variable(['meta' => $meta]);

        $query['action'] = $query['module'] = '';

        if (empty($query[0]) === false && strpos($query[0], ':') === false) {
            $query['module'] = $query[0];
        }

        if (empty($query[1]) === false && strpos($query[1], ':') === false) {
            $query['action'] = $query[1];
        }

        $target['locale'] = $locale;

        return compact('request', 'query', 'name', 'param', 'prefix', 'channel', 'app', 'tenant', 'locale', 'domain');
    }

    /**
     * Get the IP the client is using, or says they are using.
     *
     * @param boolean $safe Use safe = false when you think the user might manipulate their HTTP_CLIENT_IP
     *   header. Setting $safe = false will will also look at HTTP_X_FORWARDED_FOR
     * @return string The client IP.
     */
    public function clientIp($safe = true)
    {
        if (!$safe && env('HTTP_X_FORWARDED_FOR')) {
            $ipaddr = preg_replace('/(?:,.*)/', '', env('HTTP_X_FORWARDED_FOR'));
        } else {
            if (env('HTTP_CLIENT_IP')) {
                $ipaddr = env('HTTP_CLIENT_IP');
            } else {
                $ipaddr = env('REMOTE_ADDR');
            }
        }

        if (env('HTTP_CLIENTADDRESS')) {
            $tmpipaddr = env('HTTP_CLIENTADDRESS');

            if (!empty($tmpipaddr)) {
                $ipaddr = preg_replace('/(?:,.*)/', '', $tmpipaddr);
            }
        }

        return trim($ipaddr);
    }

    /**
     * Returns the referer that referred this request.
     *
     * @param boolean $local Attempt to return a local address. Local addresses do not contain hostnames.
     * @return string The referring address for this request.
     */
    public function referer()
    {
        $ref = env('HTTP_REFERER');
        $forwarded = env('HTTP_X_FORWARDED_HOST');
        if ($forwarded) {
            $ref = $forwarded;
        }

        return $ref;
    }

    /**
     * Check whether or not a Request is a certain type. Uses the built in detection rules
     * as well as additional rules defined with CakeRequest::addDetector(). Any detector can be called
     * as `is($type)` or `is$Type()`.
     *
     * @param string $type The type of request you want to check.
     * @return bool Whether or not the request is the type you are checking.
     */
    public function is($type)
    {
        $type = strtolower($type);

        $detect = $this->__detectors($type);

        if (!isset($detect)) {
            return false;
        }

        if (isset($detect['env'])) {
            if (isset($detect['value'])) {
                return env($detect['env']) == $detect['value'];
            }
            if (isset($detect['pattern'])) {
                return (bool)preg_match($detect['pattern'], env($detect['env']));
            }
            if (isset($detect['options'])) {
                $pattern = '/' . implode('|', $detect['options']) . '/i';

                return (bool)preg_match($pattern, env($detect['env']));
            }
        }

        return false;
    }

    /**
     * Read an HTTP header from the Request information.
     *
     * @param string $name Name of the header you want.
     * @return mixed Either false on no header being set or the value of the header.
     */
    public static function header($name)
    {
        $name = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (!empty($_SERVER[$name])) {
            return $_SERVER[$name];
        }

        return false;
    }

    /**
     * Get the HTTP method used for this request.
     * There are a few ways to specify a method.
     *
     * - If your client supports it you can use native HTTP methods.
     * - You can set the HTTP-X-Method-Override header.
     * - You can submit an input with the name `_method`
     *
     * Any of these 3 approaches can be used to set the HTTP method used
     * by CakePHP internally, and will effect the result of this method.
     *
     * @return string The name of the HTTP method used.
     */
    public function method()
    {
        return env('REQUEST_METHOD');
    }

    /**
     * Get the host that the request was handled on.
     *
     * @return string
     */
    public function host()
    {
        return env('HTTP_HOST');
    }

    /**
     * Get the domain name and include $tldLength segments of the tld.
     *
     * @param integer $tldLength Number of segments your tld contains. For example: `example.com` contains 1 tld.
     *   While `example.co.uk` contains 2.
     * @return string Domain name without subdomains.
     */
    public function domain($tldLength = 1)
    {
        $segments = explode('.', $this->host());
        $domain = array_slice($segments, -1 * ($tldLength + 1));

        return implode('.', $domain);
    }

    /**
     * Get the subdomains for a host.
     *
     * @param integer $tldLength Number of segments your tld contains. For example: `example.com` contains 1 tld.
     *   While `example.co.uk` contains 2.
     * @return array of subdomains.
     */
    public function subdomains($tldLength = 1)
    {
        $segments = explode('.', $this->host());

        return array_slice($segments, 0, -1 * ($tldLength + 1));
    }

    private function __detectors($type = '')
    {
        $detectors = [
            'get' => ['env' => 'REQUEST_METHOD', 'value' => 'GET'],
            'post' => ['env' => 'REQUEST_METHOD', 'value' => 'POST'],
            'put' => ['env' => 'REQUEST_METHOD', 'value' => 'PUT'],
            'delete' => ['env' => 'REQUEST_METHOD', 'value' => 'DELETE'],
            'head' => ['env' => 'REQUEST_METHOD', 'value' => 'HEAD'],
            'options' => ['env' => 'REQUEST_METHOD', 'value' => 'OPTIONS'],
            'ssl' => ['env' => 'HTTPS', 'value' => 1],
            'ajax' => ['env' => 'HTTP_X_REQUESTED_WITH', 'value' => 'XMLHttpRequest'],
            'flash' => ['env' => 'HTTP_USER_AGENT', 'pattern' => '/^(Shockwave|Adobe) Flash/'],
            'mobile' => [
                'env' => 'HTTP_USER_AGENT',
                'options' => [
                    'Android', 'AvantGo', 'BlackBerry', 'DoCoMo', 'Fennec', 'iPod', 'iPhone', 'iPad',
                    'J2ME', 'MIDP', 'NetFront', 'Nokia', 'Opera Mini', 'Opera Mobi', 'PalmOS', 'PalmSource',
                    'portalmmm', 'Plucker', 'ReqwirelessWeb', 'SonyEricsson', 'Symbian', 'UP\\.Browser',
                    'webOS', 'Windows CE', 'Windows Phone OS', 'Xiino'
                ]
            ],
            'json' => ['env' => 'HTTP_ACCEPT', 'pattern' => '/application\/json/i']
        ];

        return empty($detectors[$type]) ? null : $detectors[$type];
    }

    public function get()
    {
        return $_GET;
    }

    public function post()
    {
        return $_POST;
    }

    public function branch()
    {
        return array_search($this->channel, App::mp('config')->get('app.channel'));
    }
}
