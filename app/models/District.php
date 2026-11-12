<?php
class District extends CatalogModel
{
    protected string $table = 'districts';
    protected array $fillable = ['province_id', 'name'];
    protected ?string $parentCol = 'province_id';
}
