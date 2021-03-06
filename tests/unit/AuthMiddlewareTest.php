<?php

namespace Tests\Unit;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuthMiddlewareTest extends BaseTestCase
{

    public function testInvoke()
    {
        $edit = $this->authManager->createPermission('edit');
        $this->authManager->addPermission($edit);

        $write = $this->authManager->createPermission('write');
        $this->authManager->addPermission($write);

        $moderator = $this->authManager->createRole('moderator');
        $this->authManager->addRole($moderator);

        $admin = $this->authManager->createRole('admin');
        $this->authManager->addRole($admin);

        $this->authManager->addChildPermission($moderator, $edit);
        $this->authManager->addChildPermission($admin, $write);
        $this->authManager->addChildRole($admin, $moderator);

        $this->authManager->assign($moderator, self::MODERATOR_USER_ID);
        $this->authManager->assign($admin, self::ADMIN_USER_ID);

        $middleware = $this->authMiddleware;
        $callable = function (Request $request, Response $response) { return $response; };

        $request = new ServerRequest('GET', 'write');
        $response = new Response();

        $request = $request->withAttribute('userId', self::ADMIN_USER_ID);
        $response = $middleware($request, $response, $callable);
        $this->assertEquals(200, $response->getStatusCode());

        $request = $request->withAttribute('userId', self::MODERATOR_USER_ID);
        $response = $middleware($request, $response, $callable);
        $this->assertEquals(403, $response->getStatusCode());
    }
}