<?php
/**
 * Miniatura de categoría en el listado CRUD.
 *
 * @var object $listItem the category item from the list for a given record
 */
$this->load->view('company/categorias/partials/thumbnail', ['listItem' => $listItem ?? null]);
