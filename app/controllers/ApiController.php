<?php
// [A] Endpoint JSON cho AJAX. Mẫu: GET api/districts/1  ->  {success, message, data:[{id,name}]}
class ApiController extends Controller
{
    public function districts($provinceId = 0): void
    {
        $this->json((new Location())->districtsByProvince((int)$provinceId));
    }

    public function wards($districtId = 0): void
    {
        $this->json((new Location())->wardsByDistrict((int)$districtId));
    }
}
