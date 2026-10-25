<?php
/**
 * @var object $listItem the customer item from the list
 * @var mixed $value the value of customer_id field
 * @var array|null $pageTexts textos de página (cmp-clientes / listado)
 */

$customerId = (string) ($listItem->customer_id ?? '');
$pageTexts = $pageTexts ?? [];
$editTooltip = htmlspecialchars((string) ($pageTexts['edit'] ?? ''), ENT_QUOTES, 'UTF-8');

if ($customerId !== '') {
    $editUrl = company_url('customers/edit/' . $customerId);
    $escapedUrl = htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8');
    $linkStyle = 'color: inherit; text-decoration: underline; cursor: pointer;';

    echo '<a href="javascript:void(0)" class="customer-name-link" data-edit-url="' . $escapedUrl . '" data-toggle="tooltip" title="' . $editTooltip . '" style="' . $linkStyle . '">' . htmlspecialchars($customerId) . '</a>';
}

