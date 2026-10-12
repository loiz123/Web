<?php
// Router: /controller/action/param1/param2
//   /                      -> HomeController::index
//   /property/detail/5     -> PropertyController::detail(5)
//   /admin/users/lock/3    -> AdminUsersController::lock(3)  (file controllers/admin/)
class App
{
    public function run(): void
    {
        $segments = array_values(array_filter(explode('/', trim($_GET['url'] ?? '', '/')), 'strlen'));

        $prefix = '';
        if (($segments[0] ?? '') === 'admin') { $prefix = 'Admin'; array_shift($segments); }

        $name = $segments[0] ?? ($prefix ? 'dashboard' : 'home');
        $class = $prefix . str_replace(' ', '', ucwords(str_replace('-', ' ', $name))) . 'Controller';
        $action = $segments[1] ?? 'index';
        $params = array_slice($segments, 2);

        if (!preg_match('/^[A-Za-z0-9]+$/', $class) || !class_exists($class)
            || !is_subclass_of($class, 'Controller')) { $this->notFound(); }

        $controller = new $class();
        $method = str_replace(' ', '', lcfirst(ucwords(str_replace('-', ' ', $action))));
        if (!method_exists($controller, $method)
            || !(new ReflectionMethod($controller, $method))->isPublic()
            || $method[0] === '_') { $this->notFound(); }

        try {
            $controller->$method(...$params);
        } catch (ArgumentCountError $e) {
            $this->notFound();
        }
    }

    private function notFound()
    {
        http_response_code(404);
        $c = new class extends Controller { public function show(): void { $this->view('errors/404'); } };
        $c->show();
        exit;
    }
}
