<?php

class TransportationType
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT id, transportation_name
             FROM transportation_types
             WHERE is_active = TRUE
             ORDER BY CASE WHEN LOWER(transportation_name) = 'other' THEN 1 ELSE 0 END,
                      LOWER(transportation_name), transportation_name"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findCanonicalName(string $name): ?string
    {
        $normalized = $this->normalizeName($name);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT transportation_name
             FROM transportation_types
             WHERE LOWER(transportation_name) = LOWER(?)
             ORDER BY id
             LIMIT 1"
        );
        $stmt->execute([$normalized]);
        $canonicalName = $stmt->fetchColumn();

        return $canonicalName === false ? null : (string) $canonicalName;
    }

    public function create(string $name): array
    {
        $normalized = $this->normalizeName($name);
        if ($normalized === null || $normalized === '') {
            return ['success' => false, 'error' => 'Transportation type is required.'];
        }

        $length = preg_match_all('/./us', $normalized);
        if ($length === false) {
            return ['success' => false, 'error' => 'Transportation type contains invalid text.'];
        }
        if ($length > 100) {
            return ['success' => false, 'error' => 'Transportation type must be 100 characters or fewer.'];
        }

        $existingName = $this->findCanonicalName($normalized);
        if ($existingName !== null) {
            return ['success' => true, 'transportation_name' => $existingName, 'created' => false];
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO transportation_types (transportation_name, is_active)
                 VALUES (?, TRUE)
                 RETURNING transportation_name"
            );
            $stmt->execute([$normalized]);

            return [
                'success' => true,
                'transportation_name' => (string) $stmt->fetchColumn(),
                'created' => true,
            ];
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23505') {
                $existingName = $this->findCanonicalName($normalized);
                if ($existingName !== null) {
                    return ['success' => true, 'transportation_name' => $existingName, 'created' => false];
                }
            }

            return ['success' => false, 'error' => 'Unable to add transportation type.'];
        }
    }

    private function normalizeName(string $name): ?string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($name));
        if ($normalized === null) {
            return null;
        }

        return trim($normalized);
    }
}