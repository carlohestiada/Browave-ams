BEGIN;

CREATE OR REPLACE FUNCTION prevent_duplicate_employee_names()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    normalized_english TEXT;
    normalized_chinese TEXT;
    previous_english TEXT;
    previous_chinese TEXT;
    lock_key BIGINT;
BEGIN
    normalized_english := NULLIF(
        LOWER(BTRIM(NEW.english_name, CHR(9) || CHR(10) || CHR(11) || CHR(12) || CHR(13) || ' ')),
        ''
    );
    normalized_chinese := NULLIF(
        BTRIM(NEW.chinese_name, CHR(9) || CHR(10) || CHR(11) || CHR(12) || CHR(13) || ' '),
        ''
    );

    IF TG_OP = 'UPDATE' THEN
        previous_english := NULLIF(
            LOWER(BTRIM(OLD.english_name, CHR(9) || CHR(10) || CHR(11) || CHR(12) || CHR(13) || ' ')),
            ''
        );
        previous_chinese := NULLIF(
            BTRIM(OLD.chinese_name, CHR(9) || CHR(10) || CHR(11) || CHR(12) || CHR(13) || ' '),
            ''
        );

        IF normalized_english IS NOT DISTINCT FROM previous_english THEN
            normalized_english := NULL;
        END IF;
        IF normalized_chinese IS NOT DISTINCT FROM previous_chinese THEN
            normalized_chinese := NULL;
        END IF;
    END IF;

    FOR lock_key IN
        SELECT key_value
        FROM UNNEST(ARRAY[
            CASE
                WHEN normalized_english IS NOT NULL
                    THEN HASHTEXTEXTENDED('employees:english:' || normalized_english, 0::BIGINT)
            END,
            CASE
                WHEN normalized_chinese IS NOT NULL
                    THEN HASHTEXTEXTENDED('employees:chinese:' || normalized_chinese, 0::BIGINT)
            END
        ]) AS keys(key_value)
        WHERE key_value IS NOT NULL
        ORDER BY key_value
    LOOP
        PERFORM PG_ADVISORY_XACT_LOCK(lock_key);
    END LOOP;

    IF normalized_english IS NOT NULL AND EXISTS (
        SELECT 1
        FROM employees e
        WHERE e.id IS DISTINCT FROM NEW.id
          AND LOWER(BTRIM(e.english_name, CHR(9) || CHR(10) || CHR(11) || CHR(12) || CHR(13) || ' '))
              = normalized_english
    ) THEN
        RAISE EXCEPTION USING
            ERRCODE = '23505',
            CONSTRAINT = 'uq_employees_english_name_ci',
            MESSAGE = 'An employee with this English name already exists.';
    END IF;

    IF normalized_chinese IS NOT NULL AND EXISTS (
        SELECT 1
        FROM employees e
        WHERE e.id IS DISTINCT FROM NEW.id
          AND BTRIM(e.chinese_name, CHR(9) || CHR(10) || CHR(11) || CHR(12) || CHR(13) || ' ')
              = normalized_chinese
    ) THEN
        RAISE EXCEPTION USING
            ERRCODE = '23505',
            CONSTRAINT = 'uq_employees_chinese_name',
            MESSAGE = 'An employee with this Chinese name already exists.';
    END IF;

    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS employees_prevent_duplicate_names ON employees;

CREATE TRIGGER employees_prevent_duplicate_names
    BEFORE INSERT OR UPDATE OF english_name, chinese_name
    ON employees
    FOR EACH ROW
    EXECUTE FUNCTION prevent_duplicate_employee_names();

COMMIT;
