<?php

/**
 * Trait for handling pagination functionality
 *
 * @property CI_Loader $load
 * @property CI_Pagination $pagination
 */
trait HasPagination
{
    /**
     * Gets the pagination base Url, which should be the same of the list url of the crud
     * @param $url
     * @return string
     */
    protected function paginationUrl($url = '')
    {
        return get_app_url($url);
    }

    /**
     * Creates the configuration for codeigniter paginator
     * @param $totalItems
     * @param $perPage
     * @param $langMethod
     * @return array
     */
    protected function getCodeIgniterPaginationConfig($totalItems, $perPage, string $url = null, $langMethod = 'lang_products')
    {
        $langPagination = $this->HelperLanguage->$langMethod();
        return [
            'base_url' => $this->paginationUrl($url),
            'num_links' => 2,
            'uri_segment' => 4,
            'total_rows' => $totalItems,
            'per_page' => $perPage,
            'full_tag_open' => '<ul class="pagination pagination-sm">',
            'full_tag_close' => '</ul>',
            'num_tag_open' => '<li>',
            'num_tag_close' => '</li>',
            'first_link' => $langPagination['pagination_first'],
            'first_tag_open' => '<li class="next">',
            'first_tag_close' => '</li>',
            'last_link' => $langPagination['pagination_last'],
            'last_tag_open' => '<li class="next">',
            'last_tag_close' => '</li>',
            'prev_link' => $langPagination['pagination_prev'],
            'prev_tag_open' => '<li class="next">',
            'prev_tag_close' => '</li>',
            'next_link' => $langPagination['pagination_next'],
            'next_tag_open' => '<li class="next">',
            'next_tag_close' => '</li>',
            'cur_tag_open' => '<li class="current"><span>',
            'cur_tag_close' => '</span></li>',
            'use_page_numbers' => true,
            'page_query_string' => true,
            'query_string_segment' => 'page',
            'reuse_query_string' => true
        ];
    }

    /**
     * @param array $filter
     * @param null $page
     * @param null $perPage
     * @return object
     */
    protected function getPaginatedList(string $resorse, array $filter = [], $page = null, $perPage = null): object
    {
        $params = array_merge($filter, ['page' => $page, 'per_page' => $perPage]);
        $resourcePath = rtrim($resorse, '/') . '/';
        $result = $this->getObjectFromApi($resourcePath, $params);

        $normalized = $this->normalizePaginatedListResponse($result, $page, $perPage);
        if ($normalized !== null) {
            return $normalized;
        }

        if (! is_object($result)) {
            if (is_array($result) && method_exists($this, 'hasError') && $this->hasError($result)) {
                log_message('error', 'getPaginatedList API error for ' . $resorse . ': ' . json_encode($result));
            }

            $pageNum = max(1, (int) ($page ?? 1));
            $perPageNum = max(1, (int) ($perPage ?? 25));

            return (object) [
                'data' => [],
                'total' => 0,
                'per_page' => $perPageNum,
                'current_page' => $pageNum,
                'last_page' => 1,
            ];
        }

        return $result;
    }

    /**
     * Normaliza respuestas API: paginadas `{data,total,...}` o listas planas con `all=true`.
     *
     * @param mixed $result
     */
    protected function normalizePaginatedListResponse($result, $page = null, $perPage = null): ?object
    {
        $pageNum = max(1, (int) ($page ?? 1));
        $perPageNum = max(1, (int) ($perPage ?? 25));

        if (is_object($result)) {
            if (method_exists($this, 'hasError') && $this->hasError($result)) {
                return null;
            }
            if (isset($result->data) && is_array($result->data)) {
                $count = count($result->data);
                $total = isset($result->total) ? (int) $result->total : $count;
                if (!isset($result->total)) {
                    $result->total = $total;
                }
                if (!isset($result->per_page)) {
                    $result->per_page = $total > 0 ? max($total, $perPageNum) : $perPageNum;
                }
                if (!isset($result->current_page)) {
                    $result->current_page = $pageNum;
                }
                if (!isset($result->last_page)) {
                    $result->last_page = max(1, (int) ceil($total / max(1, (int) $result->per_page)));
                }
                // Listas all=true suelen omitir from/to → "Showing X to of N"
                if (!isset($result->from) || $result->from === '' || $result->from === null) {
                    $result->from = $total > 0 ? 1 : 0;
                }
                if (!isset($result->to) || $result->to === '' || $result->to === null) {
                    $result->to = $total > 0 ? min($total, (int) $result->per_page) : 0;
                }

                return $result;
            }
            if ($result instanceof \Traversable || is_array($result)) {
                $rows = is_array($result) ? $result : iterator_to_array($result);
                $count = count($rows);

                return (object) [
                    'data' => array_values($rows),
                    'total' => $count,
                    'per_page' => $count > 0 ? $count : $perPageNum,
                    'current_page' => 1,
                    'last_page' => 1,
                ];
            }

            return $result;
        }

        if (! is_array($result)) {
            return null;
        }

        if (method_exists($this, 'hasError') && $this->hasError($result)) {
            return null;
        }

        if (isset($result['data']) && is_array($result['data'])) {
            $count = count($result['data']);
            $total = (int) ($result['total'] ?? $count);
            $perPageResolved = (int) ($result['per_page'] ?? ($total > 0 ? max($total, $perPageNum) : $perPageNum));
            $currentPage = (int) ($result['current_page'] ?? $pageNum);

            return (object) [
                'data' => $result['data'],
                'total' => $total,
                'per_page' => $perPageResolved,
                'current_page' => $currentPage,
                'last_page' => (int) ($result['last_page'] ?? max(1, (int) ceil($total / max(1, $perPageResolved)))),
                'from' => (int) ($result['from'] ?? ($total > 0 ? (($currentPage - 1) * $perPageResolved) + 1 : 0)),
                'to' => (int) ($result['to'] ?? ($total > 0 ? min($total, $currentPage * $perPageResolved) : 0)),
            ];
        }

        if ($result === []) {
            return (object) [
                'data' => [],
                'total' => 0,
                'per_page' => $perPageNum,
                'current_page' => $pageNum,
                'last_page' => 1,
            ];
        }

        if (array_keys($result) === range(0, count($result) - 1)) {
            $count = count($result);

            return (object) [
                'data' => $result,
                'total' => $count,
                'per_page' => $count > 0 ? $count : $perPageNum,
                'current_page' => 1,
                'last_page' => 1,
            ];
        }

        return null;
    }
}
