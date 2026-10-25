create
    definer = `test1-fv`@localhost procedure update_catalogs(IN id_company int)
BEGIN
    DELETE FROM order_categories
    WHERE company_id = id_company
    AND (catalog_id IS NULL
    OR category_id IS NULL
    OR NOT FIND_IN_SET(catalog_id, (
        SELECT GROUP_CONCAT(catalog_id)
        FROM catalog
        WHERE company_id = id_company
    )));
END;

