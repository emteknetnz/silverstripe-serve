<?php

namespace SilverStripe\Serve\Tests;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use SilverStripe\Serve\ServerFactory;
use SilverStripe\Serve\PortChecker;

class ServerTest extends TestCase
{
    public function testStartStop()
    {
        $factory = new ServerFactory(BASE_PATH);
        $server = $factory->launchServer([
            'bootstrapFile' => $this->getBootstrapPath(),
            'host' => 'localhost',
            'preferredPort' => '3000',
        ]);

        // Server is immediately started
        $this->assertTrue(PortChecker::isPortOpen('localhost', $server->getPort()));

        var_dump($server->getURL());
        $content = file_get_contents($server->getURL());
        var_dump($content);
        
        // Test a "stable" URL available via the framework module, that isn't tied to an environment type
        $content = file_get_contents($server->getURL() . 'Security/login');

        // Check that the login form exists on the displayed page
        $this->assertStringContainsString('MemberLoginForm_LoginForm', $content);

        // When it stops, it stops listening
        $server->stop();
        $this->assertFalse(PortChecker::isPortOpen('localhost', $server->getPort()));
    }

    public function testStartTwiceFails()
    {
        $factory = new ServerFactory(realpath(__DIR__ . '/..'));
        $server = $factory->launchServer([
            'host' => 'localhost',
            'preferredPort' => '3000',
        ]);

        // Start fails because the server is already started
        $this->expectException(\LogicException::class);
        $server->start();
    }

    public function testStopTwiceFails()
    {
        $factory = new ServerFactory(BASE_PATH);
        $server = $factory->launchServer([
            'host' => 'localhost',
            'preferredPort' => '3000',
        ]);

        $server->stop();

        // Stop a 2nd fails because the server is already stopped
        $this->expectException(\LogicException::class);
        $server->stop();
    }

    public function testPreferredPortFindsAnOpenPort()
    {
        $factory = new ServerFactory(BASE_PATH);
        $server1 = $factory->launchServer([
            'host' => 'localhost',
            'preferredPort' => '3000',
        ]);

        $server2 = $factory->launchServer([
            'host' => 'localhost',
            'preferredPort' => '3000',
        ]);

        $this->assertNotEquals($server1->getPort(), $server2->getPort());

        $this->assertTrue(PortChecker::isPortOpen('localhost', $server1->getPort()));
        $this->assertTrue(PortChecker::isPortOpen('localhost', $server2->getPort()));
    }

    /**
     * Get relative path to serve-bootstrap.php from cwd
     *
     * @return string
     */
    protected function getBootstrapPath()
    {
        $parents = [
            'vendor/silverstripe/framework/', // framework in vendor
            'framework/', // old ss4 root module
            '' // framework root
        ];
        $path = 'tests/behat/serve-bootstrap.php';
        foreach ($parents as $parent) {
            if (file_exists(BASE_PATH . '/' . $parent . $path)) {
                return $parent . $path;
            }
        }
        throw new BadMethodCallException("serve-bootstrap.php could not be found");
    }
}
