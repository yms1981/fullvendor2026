<?php
/**
 * Miniatura de producto en el listado CRUD.
 *
 * @var object $listItem the product item from the list for a given record
 */
$this->load->view('company/product/partials/thumbnail', ['listItem' => $listItem ?? null]);
