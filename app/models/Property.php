<?php
// [B][C] Tin đăng. Có sẵn latest() và search() làm mẫu: gọi stored procedure,
// hoặc SQL thường khi config use_routines = false.
class Property extends Model
{
    protected string $table = 'properties';
    protected array $fillable = [
        'user_id', 'type_id', 'ward_id', 'title', 'description', 'listing_type', 'price', 'deposit', 'area',
        'bedrooms', 'bathrooms', 'direction', 'legal_status', 'furniture', 'pets_allowed', 'address_detail',
        'latitude', 'longitude', 'status', 'reject_reason', 'is_verified',
    ];

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
        if (config('use_routines')) return $this->call('sp_latest_properties', [$limit]);
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
        $sql = self::LIST_SQL; $args = [];
        if ($kw)   { $sql .= ' AND (p.title LIKE ? OR p.address_detail LIKE ?)'; $args[] = "%$kw%"; $args[] = "%$kw%"; }
        if ($type) { $sql .= ' AND p.listing_type = ?'; $args[] = $type; }
        if ($dist) { $sql .= ' AND d.id = ?'; $args[] = $dist; }
        if ($min !== null) { $sql .= ' AND p.price >= ?'; $args[] = $min; }
        if ($max !== null) { $sql .= ' AND p.price <= ?'; $args[] = $max; }
        $sql .= ' ORDER BY p.created_at DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        return $this->query($sql, $args)->fetchAll();
    }
}
