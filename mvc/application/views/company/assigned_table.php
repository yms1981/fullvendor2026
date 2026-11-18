<?php
/**
 * @var object $entity
 * @var string $entityType
 * @var array $pageTexts
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
if (function_exists('fv_lang_json_flat_section') && function_exists('fv_resolve_company_language_id')) {
    $langId = (int) fv_resolve_company_language_id();
    $moduleKey = (($entityType ?? '') === 'warehouse') ? 'cmp-warehouse' : 'cmp-cliente';
    $moduleLang = fv_lang_json_flat_section($langId, $moduleKey);
    if ($moduleLang !== []) {
        $pageTexts = array_merge($moduleLang, $pageTexts);
    }
}
$t = static function (string $key, string $fallback = '') use ($pageTexts): string {
    $value = $pageTexts[$key] ?? '';
    if (! is_string($value) || trim($value) === '') {
        $value = $fallback;
    }

    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};

$users = $entity->users ?? [];
if (! is_array($users)) {
    $users = is_object($users) ? (array) $users : [];
}

$assignUsersPending = !empty($assignUsersPending);
$isWarehouseEntity = ($entityType ?? '') === 'warehouse';
$isCustomerEntity = ($entityType ?? '') === 'customer';
$entityId = $isCustomerEntity
    ? (int) ($entity->customer_id ?? $entity->id ?? 0)
    : (int) ($entity->id ?? $entity->{$entityType . '_id'} ?? 0);
$warehouseRoleUsersList = is_array($warehouseRoleUsers ?? null) ? $warehouseRoleUsers : [];
$canAssignUsers = $entityId > 0 && ! $assignUsersPending;
$canOpenAssignModal = $isWarehouseEntity
    ? ($canAssignUsers || ($assignUsersPending && count($warehouseRoleUsersList) > 0))
    : $canAssignUsers;
$canUnassign = ($isWarehouseEntity || $isCustomerEntity) && $entityId > 0;
$useEcommercePortalUi = $isCustomerEntity;

$confirmUnassignMsg = trim((string) ($pageTexts['confirm_unassign_user'] ?? ''));
if ($confirmUnassignMsg === '' && function_exists('fv_lang_json_flat_section') && function_exists('fv_resolve_company_language_id')) {
    $whLang = fv_lang_json_flat_section((int) fv_resolve_company_language_id(), 'cmp-warehouse');
    $confirmUnassignMsg = trim((string) ($whLang['confirm_unassign_user'] ?? ''));
}
$confirmUnassignAttr = htmlspecialchars($confirmUnassignMsg, ENT_QUOTES, 'UTF-8');

$sectionTitle = $useEcommercePortalUi
    ? $t('ecommerce_portal_users', 'ECommerce Portal Users')
    : $t('usuarios_asociados', 'Associated Users');
$emptyMsg = $useEcommercePortalUi
    ? $t('ecommerce_portal_users_empty', $t('sin_usuarios', 'No associated users'))
    : $t('sin_usuarios', 'No associated users');
$colspan = $useEcommercePortalUi ? 4 : 3;
$addModalTarget = $useEcommercePortalUi ? '#modalAddUser' : '#addUserModal';
$customerFallbackPhone = '';
$customerPhonecode = '';
if ($useEcommercePortalUi) {
    $customerFallbackPhone = trim((string) ($entity->cell_phone ?? ''));
    if ($customerFallbackPhone === '') {
        $customerFallbackPhone = trim((string) ($entity->phone ?? ''));
    }

    $fvPrefixDigits = static function (string $phone, string $code): string {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        $codeDigits = preg_replace('/\D+/', '', $code) ?: '';
        if ($digits === '') {
            return '';
        }
        if ($codeDigits === '') {
            return $digits;
        }
        if (strpos($digits, $codeDigits) === 0 && strlen($digits) > strlen($codeDigits)) {
            return $digits;
        }

        return $codeDigits . $digits;
    };

    $countriesList = is_array($countries ?? null) ? $countries : [];
    $fvMatchCountryPhonecode = static function (string $countryKey, array $countriesList): string {
        $countryKey = trim($countryKey);
        if ($countryKey === '' || $countriesList === []) {
            return '';
        }
        $keyUpper = function_exists('mb_strtoupper')
            ? mb_strtoupper($countryKey, 'UTF-8')
            : strtoupper($countryKey);
        foreach ($countriesList as $countryRow) {
            $countryRow = is_object($countryRow) ? $countryRow : (object) $countryRow;
            $id = (string) ($countryRow->id ?? '');
            $name = trim((string) ($countryRow->name ?? ''));
            $sort = trim((string) ($countryRow->sortname ?? ''));
            $nameUpper = $name !== ''
                ? (function_exists('mb_strtoupper') ? mb_strtoupper($name, 'UTF-8') : strtoupper($name))
                : '';
            $sortUpper = $sort !== ''
                ? (function_exists('mb_strtoupper') ? mb_strtoupper($sort, 'UTF-8') : strtoupper($sort))
                : '';
            $match = ($id !== '' && $id === $countryKey)
                || ($nameUpper !== '' && $nameUpper === $keyUpper)
                || ($sortUpper !== '' && $sortUpper === $keyUpper);
            if ($match) {
                $pc = (int) ($countryRow->phonecode ?? 0);

                return $pc > 0 ? (string) $pc : '';
            }
        }

        return '';
    };

    // 1) Fuente de verdad: customers.commercial_country (p. ej. PERU → 51)
    $countryKey = trim((string) ($entity->commercial_country ?? ''));
    $customerPhonecode = $fvMatchCountryPhonecode($countryKey, $countriesList);

    // 2) Dirección comercial cuyo país coincida con commercial_country
    if ($customerPhonecode === '') {
        $addresses = $entity->addresses ?? [];
        if (! is_array($addresses)) {
            $addresses = is_object($addresses) ? (array) $addresses : [];
        }
        $fallbackAddrPc = '';
        foreach ($addresses as $addr) {
            $addr = is_object($addr) ? $addr : (object) $addr;
            $pivotType = (string) ($addr->pivot->type ?? $addr->type ?? '');
            if (strcasecmp($pivotType, 'commercial') !== 0) {
                continue;
            }
            $countryObj = $addr->country ?? null;
            if (! is_object($countryObj)) {
                continue;
            }
            $pc = (int) ($countryObj->phonecode ?? 0);
            if ($pc < 1) {
                continue;
            }
            $addrName = trim((string) ($countryObj->name ?? ''));
            $addrSort = trim((string) ($countryObj->sortname ?? ''));
            if ($countryKey !== '' && (
                strcasecmp($addrName, $countryKey) === 0
                || strcasecmp($addrSort, $countryKey) === 0
            )) {
                $customerPhonecode = (string) $pc;
                break;
            }
            if ($fallbackAddrPc === '') {
                $fallbackAddrPc = (string) $pc;
            }
        }
        if ($customerPhonecode === '' && $countryKey === '') {
            $customerPhonecode = $fallbackAddrPc;
        }
    }

    // 3) Solo si el cliente no tiene país: default compañía (último recurso)
    if ($customerPhonecode === '' && $countryKey === '') {
        $metaPc = (int) (($companyPhoneCountryMeta['phonecode'] ?? 0));
        if ($metaPc > 0) {
            $customerPhonecode = (string) $metaPc;
        } elseif (! empty($companyDefaultCountryId) && $countriesList !== []) {
            foreach ($countriesList as $countryRow) {
                $countryRow = is_object($countryRow) ? $countryRow : (object) $countryRow;
                if ((int) ($countryRow->id ?? 0) === (int) $companyDefaultCountryId) {
                    $pc = (int) ($countryRow->phonecode ?? 0);
                    if ($pc > 0) {
                        $customerPhonecode = (string) $pc;
                    }
                    break;
                }
            }
        }
    }

    // Prefijo E.164 para SMS/WhatsApp (p. ej. 51 + 900947238 → 51900947238).
    if ($customerFallbackPhone !== '' && $customerPhonecode !== '') {
        $customerFallbackPhone = $fvPrefixDigits($customerFallbackPhone, $customerPhonecode);
    }
}
?>
<script>
window.FV_ASSIGNED_USERS_I18N = <?= json_encode([
    'confirm_unassign_user' => $confirmUnassignMsg,
    'portal_password_show' => (string) ($pageTexts['portal_password_show'] ?? ''),
    'portal_password_hide' => (string) ($pageTexts['portal_password_hide'] ?? ''),
    'portal_credentials_send' => (string) ($pageTexts['portal_credentials_send'] ?? ''),
    'portal_credentials_send_sms' => (string) ($pageTexts['portal_credentials_send_sms'] ?? ''),
    'portal_credentials_send_whatsapp' => (string) ($pageTexts['portal_credentials_send_whatsapp'] ?? ''),
    'portal_credentials_confirm' => (string) ($pageTexts['portal_credentials_confirm'] ?? ''),
    'portal_credentials_confirm_sms' => (string) ($pageTexts['portal_credentials_confirm_sms'] ?? ''),
    'portal_credentials_confirm_whatsapp' => (string) ($pageTexts['portal_credentials_confirm_whatsapp'] ?? ''),
    'portal_credentials_sent' => (string) ($pageTexts['portal_credentials_sent'] ?? ''),
    'portal_credentials_sent_sms' => (string) ($pageTexts['portal_credentials_sent_sms'] ?? ''),
    'portal_credentials_sent_whatsapp' => (string) ($pageTexts['portal_credentials_sent_whatsapp'] ?? ''),
    'portal_credentials_send_failed' => (string) ($pageTexts['portal_credentials_send_failed'] ?? ''),
    'portal_credentials_sending' => (string) ($pageTexts['portal_credentials_sending'] ?? ''),
    'portal_credentials_sending_sms' => (string) ($pageTexts['portal_credentials_sending_sms'] ?? ''),
    'portal_credentials_sending_whatsapp' => (string) ($pageTexts['portal_credentials_sending_whatsapp'] ?? ''),
    'portal_credentials_no_phone' => (string) ($pageTexts['portal_credentials_no_phone'] ?? ''),
    'portal_credentials_no_phonecode' => (string) ($pageTexts['portal_credentials_no_phonecode'] ?? ''),
    'btn_accept' => (string) ($pageTexts['btn_accept'] ?? $pageTexts['aceptar'] ?? ''),
    'cancel' => (string) ($pageTexts['cancelar'] ?? $pageTexts['cancel'] ?? ''),
    'cancelar' => (string) ($pageTexts['cancelar'] ?? $pageTexts['cancel'] ?? ''),
    'error' => (string) ($pageTexts['error_occurred'] ?? ''),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/company/fv-assigned-users.css') ?>" />
<div class="card fv-assigned-users-card<?= $useEcommercePortalUi ? ' fv-assigned-users-card--portal' : ' mt-4' ?>"
     data-confirm-unassign="<?= $confirmUnassignAttr ?>"
     data-assign-pending="<?= $assignUsersPending ? '1' : '0' ?>"
     data-available-users="<?= (int) count($warehouseRoleUsersList) ?>"
     data-label-delete="<?= $t('eliminar', 'Delete') ?>">
    <div class="card-header fv-assigned-users-card__header d-flex justify-content-between align-items-center py-2">
        <span class="fv-assigned-users-card__title"><?= $sectionTitle ?></span>
        <button type="button"
                class="btn btn-primary btn-sm fv-assigned-users-card__add-btn"
                data-toggle="modal"
                data-target="<?= htmlspecialchars($addModalTarget, ENT_QUOTES, 'UTF-8') ?>"
            <?php if ($useEcommercePortalUi) : ?>
                data-default-role="customer"
                data-related-id="<?= $entityId ?>"
            <?php endif; ?>
                title="<?= $t('agregar_usuario', 'Add User') ?>"
                aria-label="<?= $t('agregar_usuario', 'Add User') ?>"
            <?= ($useEcommercePortalUi ? $canAssignUsers : $canOpenAssignModal) ? '' : ' disabled' ?>>
            <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
        </button>
    </div>
    <?php if ($assignUsersPending) : ?>
        <div class="card-body border-bottom py-2">
            <p class="text-muted small mb-0"><?= $t('assign_users_pending_hint', '') ?></p>
        </div>
    <?php endif; ?>
    <div class="card-body p-0">
        <div class="table-responsive fv-assigned-users-table-wrap">
            <table class="table table-striped table-sm mb-0 fv-assigned-users-table<?= $useEcommercePortalUi ? ' fv-assigned-users-table--portal' : '' ?>">
                <thead>
                <tr>
                    <th><?= $t('nombre', 'Name') ?></th>
                    <th><?= $t('email', 'Email') ?></th>
                    <?php if ($useEcommercePortalUi) : ?>
                        <th><?= $t('password', 'Password') ?></th>
                    <?php endif; ?>
                    <th class="fv-assigned-users-table__actions-col text-center"><?= $t('acciones', 'Actions') ?></th>
                </tr>
                </thead>
                <tbody id="fv-assigned-users-tbody">
                <?php if (count($users) <= 0) : ?>
                    <tr class="fv-assigned-users-empty-row">
                        <td colspan="<?= $colspan ?>" class="text-center text-muted py-3"><?= $emptyMsg ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($users as $user) : ?>
                        <?php
                        $rowUserId = (int) ($user->id ?? 0);
                        $rowEmail = (string) ($user->email ?? '');
                        $rowName = (string) ($user->name ?? '');
                        $rowPhone = trim((string) ($user->cellphone ?? $user->phone ?? $user->phone_number ?? ''));
                        if ($rowPhone === '' && $useEcommercePortalUi) {
                            $rowPhone = $customerFallbackPhone;
                        } elseif ($rowPhone !== '' && $useEcommercePortalUi && $customerPhonecode !== '') {
                            $rowPhone = $fvPrefixDigits($rowPhone, $customerPhonecode);
                        } else {
                            $rowPhone = preg_replace('/\D+/', '', $rowPhone) ?: $rowPhone;
                        }
                        $rowPhoneDisplay = $rowPhone !== '' ? ('+' . ltrim($rowPhone, '+')) : '';
                        $rowPlain = trim((string) ($user->password_plain ?? ''));
                        $canSendEmail = $rowEmail !== '';
                        $canSendPhone = $rowPhone !== '';
                        ?>
                        <tr class="fv-assigned-users-data-row">
                            <td><?= htmlspecialchars($rowName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($rowEmail, ENT_QUOTES, 'UTF-8') ?></td>
                            <?php if ($useEcommercePortalUi) : ?>
                                <td class="fv-assigned-users-table__password-col">
                                    <div class="input-group input-group-sm fv-portal-row-password">
                                        <input type="password"
                                               class="form-control form-control-sm bg-light js-portal-row-password"
                                               value="<?= htmlspecialchars($rowPlain, ENT_QUOTES, 'UTF-8') ?>"
                                               readonly
                                               autocomplete="off"
                                               <?= $rowPlain === '' ? 'disabled' : '' ?>>
                                        <div class="input-group-append">
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-sm js-portal-row-password-toggle"
                                                    title="<?= $t('portal_password_show', 'Show') ?>"
                                                    aria-label="<?= $t('portal_password_show', 'Show') ?>"
                                                <?= $rowPlain === '' ? 'disabled' : '' ?>>
                                                <i class="fa fa-eye" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            <?php endif; ?>
                            <td class="fv-assigned-users-table__actions-col text-center">
                                <div class="fv-assigned-users-table__actions fv-crud-row-actions">
                                    <?php if ($useEcommercePortalUi) : ?>
                                    <button type="button"
                                            class="btn fv-crud-btn fv-crud-btn--row btn-info btn-sm js-send-portal-credentials"
                                            title="<?= $t('portal_credentials_send', 'Send email') ?>"
                                            aria-label="<?= $t('portal_credentials_send', 'Send email') ?>"
                                            data-channel="email"
                                            data-user-id="<?= $rowUserId ?>"
                                            data-email="<?= htmlspecialchars($rowEmail, ENT_QUOTES, 'UTF-8') ?>"
                                            data-phone="<?= htmlspecialchars($rowPhone, ENT_QUOTES, 'UTF-8') ?>"
                                            data-phone-display="<?= htmlspecialchars($rowPhoneDisplay, ENT_QUOTES, 'UTF-8') ?>"
                                            data-phonecode="<?= htmlspecialchars($customerPhonecode, ENT_QUOTES, 'UTF-8') ?>"
                                            data-customer-id="<?= $entityId ?>"
                                        <?= $canSendEmail ? '' : 'disabled' ?>>
                                        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                                    </button>
                                    <button type="button"
                                            class="btn fv-crud-btn fv-crud-btn--row btn-secondary btn-sm js-send-portal-credentials"
                                            title="<?= $t('portal_credentials_send_sms', 'Send SMS') ?>"
                                            aria-label="<?= $t('portal_credentials_send_sms', 'Send SMS') ?>"
                                            data-channel="sms"
                                            data-user-id="<?= $rowUserId ?>"
                                            data-email="<?= htmlspecialchars($rowEmail, ENT_QUOTES, 'UTF-8') ?>"
                                            data-phone="<?= htmlspecialchars($rowPhone, ENT_QUOTES, 'UTF-8') ?>"
                                            data-phone-display="<?= htmlspecialchars($rowPhoneDisplay, ENT_QUOTES, 'UTF-8') ?>"
                                            data-phonecode="<?= htmlspecialchars($customerPhonecode, ENT_QUOTES, 'UTF-8') ?>"
                                            data-customer-id="<?= $entityId ?>"
                                        <?= ($canSendEmail && $canSendPhone) ? '' : 'disabled' ?>>
                                        <i class="fa-solid fa-comment-sms" aria-hidden="true"></i>
                                    </button>
                                    <button type="button"
                                            class="btn fv-crud-btn fv-crud-btn--row btn-success btn-sm js-send-portal-credentials"
                                            title="<?= $t('portal_credentials_send_whatsapp', 'Send WhatsApp') ?>"
                                            aria-label="<?= $t('portal_credentials_send_whatsapp', 'Send WhatsApp') ?>"
                                            data-channel="whatsapp"
                                            data-user-id="<?= $rowUserId ?>"
                                            data-email="<?= htmlspecialchars($rowEmail, ENT_QUOTES, 'UTF-8') ?>"
                                            data-phone="<?= htmlspecialchars($rowPhone, ENT_QUOTES, 'UTF-8') ?>"
                                            data-phone-display="<?= htmlspecialchars($rowPhoneDisplay, ENT_QUOTES, 'UTF-8') ?>"
                                            data-phonecode="<?= htmlspecialchars($customerPhonecode, ENT_QUOTES, 'UTF-8') ?>"
                                            data-customer-id="<?= $entityId ?>"
                                        <?= ($canSendEmail && $canSendPhone) ? '' : 'disabled' ?>>
                                        <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                                    </button>
                                    <button type="button"
                                            class="btn fv-crud-btn fv-crud-btn--row btn-warning btn-sm js-user-edit-modal"
                                            title="<?= $t('editar', 'Edit') ?>"
                                            aria-label="<?= $t('editar', 'Edit') ?>"
                                            data-toggle="modal"
                                            data-target="#modalEditUser"
                                            data-user-id="<?= $rowUserId ?>">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </button>
                                    <?php else : ?>
                                    <button type="button"
                                            class="btn fv-crud-btn fv-crud-btn--row btn-warning btn-sm js-edit-assigned-user"
                                            title="<?= $t('editar', 'Edit') ?>"
                                            aria-label="<?= $t('editar', 'Edit') ?>"
                                            data-user-id="<?= $rowUserId ?>"
                                            data-name="<?= htmlspecialchars($rowName, ENT_QUOTES, 'UTF-8') ?>"
                                            data-email="<?= htmlspecialchars($rowEmail, ENT_QUOTES, 'UTF-8') ?>"
                                            data-phone="<?= htmlspecialchars($rowPhone, ENT_QUOTES, 'UTF-8') ?>"
                                            data-password-plain="<?= htmlspecialchars($rowPlain, ENT_QUOTES, 'UTF-8') ?>"
                                            data-update-url="<?= htmlspecialchars(company_url('users/update/' . $rowUserId), ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($canUnassign) : ?>
                                    <?php
                                        $unassignUrl = $isCustomerEntity
                                            ? company_url('customers/unassignUser')
                                            : company_url('warehouses/unassignUser');
                                        $entityIdAttr = $isCustomerEntity ? 'data-customer-id' : 'data-warehouse-id';
                                    ?>
                                    <button type="button"
                                            class="btn fv-crud-btn fv-crud-btn--row btn-danger btn-sm js-unassign-assigned-user"
                                            title="<?= $t('eliminar', 'Delete') ?>"
                                            aria-label="<?= $t('eliminar', 'Delete') ?>"
                                            data-user-id="<?= $rowUserId ?>"
                                            <?= $entityIdAttr ?>="<?= $entityId ?>"
                                            data-confirm-msg="<?= $confirmUnassignAttr ?>"
                                            data-unassign-url="<?= htmlspecialchars($unassignUrl, ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (! $useEcommercePortalUi && $canOpenAssignModal) : ?>
<?php $this->load->view('company/assign_user', [
    'entity' => $entity,
    'entityType' => $entityType,
    'pageTexts' => $pageTexts,
    'warehouseRoleUsers' => $warehouseRoleUsersList,
    'assignUsersPending' => $assignUsersPending,
]); ?>
<?php endif; ?>
<?php if ($canAssignUsers && ! $useEcommercePortalUi) : ?>
<?php $this->load->view('company/edit_assigned_user_modal', ['pageTexts' => $pageTexts]); ?>
<?php endif; ?>
<script>
(function ($) {
    $(function () {
        var i18n = window.FV_ASSIGNED_USERS_I18N || {};
        $(document).on('click', '.js-portal-row-password-toggle', function () {
            var $btn = $(this);
            var $input = $btn.closest('.fv-portal-row-password').find('.js-portal-row-password');
            if (!$input.length || $input.prop('disabled')) {
                return;
            }
            var show = $input.attr('type') === 'password';
            $input.attr('type', show ? 'text' : 'password');
            $btn.find('i').toggleClass('fa-eye', !show).toggleClass('fa-eye-slash', show);
            var label = show ? (i18n.portal_password_hide || '') : (i18n.portal_password_show || '');
            if (label) {
                $btn.attr('title', label).attr('aria-label', label);
            }
        });
    });
}(jQuery));
</script>
