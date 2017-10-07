<?php

namespace Mp\Lib\Helper;

class Paginator
{
    public function paginate($option = [], $model = null, $pager = true, &$pageInfo = [])
    {
        $default = [
            'page' => 1,
            'limit' => 20,
        ];

        $option = array_merge($default, $option);

        $return = [];
        $results = $model->find($option, 'all');

        extract($option);

        if (!$results) {
            $count = 0;
        } elseif ($page === 1 && count($results) < $limit) {
            $count = count($results);
        } else {
            $count = $model->find($option, 'count');
        }

        $pageCount = (int)ceil($count / $limit);
        $requestedPage = $page;
        $page = max(min($page, $pageCount), 1);

        if ($requestedPage > $page) {
            abort('NotFoundException');
        }

        $return['list'] = $results;

        $return['pager'] = [];

        if ($pager && $pageCount > 1) {
            $params = ['current' => $page, 'count' => $pageCount];
            if (isset($paginator)) {
                $params = array_merge($params, $paginator);
            }

            $return['pager'] = $this->pager($params);
        }

        $pageInfo = [
            'current' => $page,
            'total' => $pageCount
        ];

        return $return;
    }

    private function pager($params)
    {
        $result = [];
        $modulus = 4;
        $start = 1;
        $end = $params['count'];

        $half = 0;
        if ($params['count'] > $modulus) {
            $half = (int)($modulus / 2);
            $end = $params['current'] + $half;

            if ($end > $params['count']) {
                $end = $params['count'];
            }

            $start = $params['current'] - ($modulus - ($end - $params['current']));
            if ($start <= 1) {
                $start = 1;
                $end = $params['current'] + ($modulus - $params['current']) + 1;
            }
        }

        $navigator = isset($params['navigator']) ? $params['navigator'] : true;

        if ($navigator && $params['current'] >= $modulus) {
            if (isset($params['prev'])) {
                $display = $params['prev'];
            } else {
                $display = '<i class="fa fa-chevron-left"></i>';
            }

            $result[] = ['display' => $display, 'page' => 1, 'target' => false];
        }

        while ($start <= $end) {
            $result[] = ['display' => $start, 'page' => $start, 'target' => ($start == $params['current'])];

            $start++;
        }

        if ($navigator && $params['current'] < ($params['count'] - $half)) {
            if (isset($params['next'])) {
                $display = $params['next'];
            } else {
                $display = '<i class="fa fa-chevron-right"></i>';
            }

            $result[] = ['display' => $display, 'page' => $params['count'], 'target' => false];
        }

        return $result;
    }
}
