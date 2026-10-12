<?php
// [C] Trang chủ
class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', [
            'title'      => 'Trang chủ',
            'properties' => (new Property())->latest(8),
            'provinces'  => (new Location())->provinces(),
        ]);
    }
}
