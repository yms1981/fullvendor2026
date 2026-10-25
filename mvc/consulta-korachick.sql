-- prodSeparados

SELECT pro_id, category_value, company_id
FROM (
    SELECT 
        pro_id,
        company_id,
        SUBSTRING_INDEX(SUBSTRING_INDEX(category_id, ',', numbers.n), ',', -1) AS category_value
    FROM 
        products
    CROSS JOIN (
        SELECT (a.n + b.n * 10) + 1 AS n
        FROM 
            (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
            CROSS JOIN 
            (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b
        ORDER BY n
    ) AS numbers
    WHERE
        numbers.n <= 1 + (LENGTH(category_id) - LENGTH(REPLACE(category_id, ',', '')))
) AS subquery
GROUP BY company_id, pro_id, category_value;



--- category_count

SELECT company_id, COUNT(pro_id) AS items, category_value FROM prodSeparados
GROUP BY company_id, category_value