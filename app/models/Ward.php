<?php
class Ward extends CatalogModel
{
    protected string $table = 'wards';
    protected array $fillable = ['district_id', 'name'];
    protected ?string $parentCol = 'district_id';
}
