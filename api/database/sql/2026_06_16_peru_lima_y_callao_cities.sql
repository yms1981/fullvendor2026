-- Provincias de Lima y Callao (Perú) como ciudades del estado "Lima y Callao".
-- Ejecutar solo si la migración 2026_06_16_120000_seed_peru_lima_y_callao_cities no se aplicó.

SET @country_id := (SELECT id FROM countries WHERE sortname = 'PE' LIMIT 1);
SET @lima_state_id := (
    SELECT id FROM states WHERE country_id = @country_id AND name = 'Lima y Callao' LIMIT 1
);
SET @loreto_state_id := (
    SELECT id FROM states WHERE country_id = @country_id AND name = 'Loreto' LIMIT 1
);

UPDATE cities
SET state_id = @lima_state_id, updated_at = NOW()
WHERE state_id = @loreto_state_id
  AND name = 'Barranca'
  AND deleted_at IS NULL;

INSERT INTO cities (name, state_id, created_at, updated_at)
SELECT province.name, @lima_state_id, NOW(), NOW()
FROM (
    SELECT 'Lima' AS name UNION ALL
    SELECT 'Barranca' UNION ALL
    SELECT 'Cajatambo' UNION ALL
    SELECT 'Canta' UNION ALL
    SELECT 'Canete' UNION ALL
    SELECT 'Huaral' UNION ALL
    SELECT 'Huarochiri' UNION ALL
    SELECT 'Huaura' UNION ALL
    SELECT 'Oyon' UNION ALL
    SELECT 'Yauyos' UNION ALL
    SELECT 'Callao'
) AS province
WHERE @lima_state_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM cities c
      WHERE c.state_id = @lima_state_id
        AND c.name = province.name
        AND c.deleted_at IS NULL
  );
