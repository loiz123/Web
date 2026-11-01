<?php
// [C] Tìm kiếm & duyệt tin đăng - Hà Ngọc Thiện (@hntrhunter) phụ trách
class PropertyFinder extends Model
{
    protected string $table = 'properties';

    private const LIST_SQL = "SELECT p.id, p.title, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
            p.is_verified, t.name AS type_name, w.name AS ward_name, d.name AS district_name,
            (SELECT image_url FROM property_images i WHERE i.property_id = p.id ORDER BY i.is_cover DESC, i.id LIMIT 1) AS cover_url
        FROM properties p
        JOIN property_types t ON t.id = p.type_id
        JOIN wards w ON w.id = p.ward_id
        JOIN districts d ON d.id = w.district_id
        WHERE p.status = 'approved'";

    public function latest(int $limit = 8): array
    {
        if (config('use_routines')) {
            return $this->call('sp_latest_properties', [$limit]);
        }
        return $this->query(self::LIST_SQL . ' ORDER BY p.created_at DESC, p.id DESC LIMIT ' . (int)$limit)->fetchAll();
    }

    /** $f: keyword, listing_type, district_id, min_price, max_price */
    public function search(array $f, int $limit = 12, int $offset = 0): array
    {
        $kw   = trim($f['keyword'] ?? '') ?: null;
        $type = in_array($f['listing_type'] ?? '', ['sale', 'rent'], true) ? $f['listing_type'] : null;
        $dist = ($f['district_id'] ?? '') !== '' ? (int)$f['district_id'] : null;
        $min  = ($f['min_price'] ?? '') !== '' ? (float)$f['min_price'] : null;
        $max  = ($f['max_price'] ?? '') !== '' ? (float)$f['max_price'] : null;

        if (config('use_routines')) {
            return $this->call('sp_search_properties', [$kw, $type, $dist, $min, $max, $limit, $offset]);
        }
        $sql = self::LIST_SQL;
        $args = [];
        if ($kw) {
            $sql .= ' AND (p.title LIKE ? OR p.address_detail LIKE ?)';
            $args[] = "%$kw%";
            $args[] = "%$kw%";
        }
        if ($type) {
            $sql .= ' AND p.listing_type = ?';
            $args[] = $type;
        }
        if ($dist) {
            $sql .= ' AND d.id = ?';
            $args[] = $dist;
        }
        if ($min !== null) {
            $sql .= ' AND p.price >= ?';
            $args[] = $min;
        }
        if ($max !== null) {
            $sql .= ' AND p.price <= ?';
            $args[] = $max;
        }
        $sql .= ' ORDER BY p.created_at DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        return $this->query($sql, $args)->fetchAll();
    }

    /** Lấy chi tiết đầy đủ 1 tin BĐS (kèm loại, địa chỉ, môi giới) */
    public function findDetail(int $id): ?array
    {
        $sql = "SELECT p.*,
                       t.name AS type_name,
                       w.name AS ward_name,
                       d.id AS district_id, d.name AS district_name,
                       pr.name AS province_name,
                       u.full_name AS agent_name,
                       u.phone AS agent_phone,
                       u.email AS agent_email,
                       u.avatar AS agent_avatar
                FROM properties p
                JOIN property_types t ON t.id = p.type_id
                JOIN wards w ON w.id = p.ward_id
                JOIN districts d ON d.id = w.district_id
                JOIN provinces pr ON pr.id = d.province_id
                JOIN users u ON u.id = p.user_id
                WHERE p.id = ? LIMIT 1";
        $prop = $this->query($sql, [$id])->fetch();
        return $prop ?: null;
    }

    /** Danh sách hình ảnh của tin */
    public function getImages(int $propertyId): array
    {
        return $this->query(
            "SELECT id, image_url, is_cover FROM property_images WHERE property_id = ? ORDER BY is_cover DESC, id ASC",
            [$propertyId]
        )->fetchAll();
    }

    /** Danh sách tiện ích của tin */
    public function getAmenities(int $propertyId): array
    {
        return $this->query(
            "SELECT a.id, a.name
             FROM property_amenities pa
             JOIN amenities a ON a.id = pa.amenity_id
             WHERE pa.property_id = ?
             ORDER BY a.name ASC",
            [$propertyId]
        )->fetchAll();
    }

    /** Tăng lượt xem tin */
    public function incrementViews(int $propertyId): void
    {
        $this->query("UPDATE properties SET views = views + 1 WHERE id = ?", [$propertyId]);
    }

    /** Lấy 3 tin cùng loại tương tự */
    public function getSimilar(int $propertyId, int $typeId, int $limit = 3): array
    {
        $sql = "SELECT p.id, p.title, p.listing_type, p.price, p.area, p.bedrooms, p.bathrooms,
                       p.is_verified, t.name AS type_name, w.name AS ward_name, d.name AS district_name,
                       (SELECT image_url FROM property_images i WHERE i.property_id = p.id ORDER BY i.is_cover DESC, i.id LIMIT 1) AS cover_url
                FROM properties p
                JOIN property_types t ON t.id = p.type_id
                JOIN wards w ON w.id = p.ward_id
                JOIN districts d ON d.id = w.district_id
                WHERE p.status = 'approved' AND p.id != ? AND p.type_id = ?
                ORDER BY p.created_at DESC LIMIT " . (int)$limit;
        return $this->query($sql, [$propertyId, $typeId])->fetchAll();
    }
}
