<?php
/**
 * @var object $listItem item from the list for a given record
 */
?>
<?php
    $companyPivots = $listItem->company_users ?? [];

    if (count($companyPivots) > 1) {
        echo implode(', ', array_map(function ($company) {
            $profileable = $company->profileable ?? null;
            return $profileable->profile_name ?? $profileable->name ?? ' - ';
        }, $companyPivots));
    } else {
        $profileable = $companyPivots[0]->profileable ?? null;
        echo $profileable->profile_name ?? $profileable->name ?? ' - ';
    }
?>
