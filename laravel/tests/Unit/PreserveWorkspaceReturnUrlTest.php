<?php

namespace Tests\Unit;

use App\Http\Middleware\PreserveWorkspaceReturnUrl;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class PreserveWorkspaceReturnUrlTest extends TestCase
{
    public function test_it_preserves_a_same_admin_workspace_url_for_post_redirects(): void
    {
        $workspaceUrl = 'http://localhost/private-console/applications/01TEST/card-types';
        $request = Request::create(
            'http://localhost/private-console/card-types',
            'POST',
            server: ['HTTP_X_WORKSPACE_URL' => $workspaceUrl],
        );
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));

        $route = new Route('POST', '/{adminPath}/card-types', fn () => null);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $response = (new PreserveWorkspaceReturnUrl)->handle(
            $request,
            fn () => new Response('', 204),
        );

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame($workspaceUrl, $request->session()->previousUrl());
    }

    public function test_it_ignores_a_workspace_url_outside_the_current_admin_path(): void
    {
        $request = Request::create(
            'http://localhost/private-console/card-types',
            'POST',
            server: ['HTTP_X_WORKSPACE_URL' => 'http://localhost/another-console/dashboard'],
        );
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));

        $route = new Route('POST', '/{adminPath}/card-types', fn () => null);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        (new PreserveWorkspaceReturnUrl)->handle($request, fn () => new Response('', 204));

        $this->assertNull($request->session()->previousUrl());
    }
}
