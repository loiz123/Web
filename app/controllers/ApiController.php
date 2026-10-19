<?php
// [A] Endpoint JSON cho AJAX. Dạng trả về: {success, message, data}
//   GET api/provinces          -> [{id,name}]
//   GET api/districts/{tinhId} -> [{id,name}]
//   GET api/wards/{quanId}     -> [{id,name}]
class ApiController extends Controller
{
    public function provinces(): void
    {
        $this->json((new Location())->provinces());
    }

    public function districts($provinceId = 0): void
    {
        $this->json((new Location())->districtsByProvince((int)$provinceId));
    }

    public function wards($districtId = 0): void
    {
        $this->json((new Location())->wardsByDistrict((int)$districtId));
    }
}
