<?php
/**
 * Alta de usuario de depósito (rol warehouse) — reutiliza el formulario de usuarios.
 *
 * @var array $pageTexts
 * @var object|null $company
 * @var array $userTypes
 * @var array $planLimitBlock
 */
$formAction = company_url('warehouses/save');
$usersListUrl = $usersListUrl ?? company_url('warehouses');
$addFlowType = $addFlowType ?? 'warehouse';
$userTypes = $userTypes ?? ['warehouse'];
$pageTitleKey = 'add_new_deposit';
$this->load->view('company/users/companyuser_add', get_defined_vars());
