<?php
/**
 * Edición de usuario de depósito (rol warehouse).
 *
 * @var object $editingUser
 * @var array $pageTexts
 */
$eu = is_object($editingUser->data ?? null) ? $editingUser->data : $editingUser;
$editingUser = $eu;
$formAction = company_url('warehouses/update/' . (int) ($eu->id ?? 0));
$usersListUrl = $usersListUrl ?? company_url('warehouses');
$pageTitleKey = 'edit_warehouse';
$this->load->view('company/users/companyuser_edit', get_defined_vars());
