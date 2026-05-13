<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$admin = \App\Models\User::where('role', 'admin')->first();
\Illuminate\Support\Facades\Auth::login($admin);

$routes = [
    '/admin',
    '/admin/salons',
    '/admin/kategoris',
    '/admin/kotas', 
    '/admin/services',
    '/admin/orders',
    '/admin/reviews',
    '/admin/promos',
    '/admin/users',
    '/admin/mitra-applications',
];

foreach ($routes as $path) {
    $request = \Illuminate\Http\Request::create('http://localhost' . $path, 'GET');
    $request->setUserResolver(fn() => $admin);
    \Illuminate\Support\Facades\Auth::setUser($admin);
    
    try {
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        
        if ($status === 200) {
            $content = $response->getContent();
            $hasError = str_contains($content, 'Whoops') 
                || str_contains($content, 'ErrorException') 
                || str_contains($content, 'syntax error')
                || str_contains($content, 'Class &quot;');
            echo ($hasError ? 'ERROR' : 'OK   ') . " $status $path\n";
        } else {
            echo "REDIR $status $path\n";
        }
    } catch (\Throwable $e) {
        echo "THROW $path: " . $e->getMessage() . "\n";
    }
}
