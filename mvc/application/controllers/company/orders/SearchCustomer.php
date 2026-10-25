<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class SearchCustomer extends Company_Controller
{
    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');

        $companyId = (int) $this->getCompanyId();
        if ($companyId < 1) {
            echo json_encode([]);

            return;
        }

        $term = trim((string) $this->input->get('term'));
        $response = $this->getObjectFromApi("company/{$companyId}/customers");
        if ($this->hasError($response)) {
            echo json_encode([]);

            return;
        }

        $list = $this->filterCustomersByTerm($this->extractApiListItems($response), $term);
        echo json_encode(array_values($list));
    }

    /**
     * @param array<int, mixed> $list
     * @return array<int, mixed>
     */
    protected function filterCustomersByTerm(array $list, string $term): array
    {
        if ($term === '') {
            return $list;
        }

        $needle = mb_strtolower($term);

        return array_values(array_filter($list, function ($customer) use ($needle) {
            $row = is_object($customer) ? (array) $customer : $customer;
            $haystack = mb_strtolower(implode(' ', array_filter([
                $row['customer_name'] ?? '',
                $row['business_name'] ?? '',
                $row['name'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $row['cell_phone'] ?? '',
                $row['tax_id'] ?? '',
                $row['cust_id_kor'] ?? '',
                $row['commercial_address'] ?? '',
                $row['commercial_city'] ?? '',
                $row['commercial_state'] ?? '',
                $row['commercial_country'] ?? '',
                $row['dispatch_address'] ?? '',
                $row['dispatch_city'] ?? '',
                $row['dispatch_country'] ?? '',
                (string) ($row['customer_id'] ?? ''),
            ])));

            return mb_strpos($haystack, $needle) !== false;
        }));
    }
}
