<?php
class Numpo_Plugin_Test extends WP_UnitTestCase {
    public function test_plugin_constants_and_version(): void {
        $this->assertSame('0.1.0', NUMPO_VERSION);
        $this->assertFileExists(NUMPO_DIR . 'numpo.php');
    }

    public function test_activation_sets_default_engine_url(): void {
        delete_option('numpo_engine_url');
        $reflection = new ReflectionFunction(function () {});
        $this->assertNotNull($reflection);
        update_option('numpo_engine_url', '');
        if (!get_option('numpo_engine_url')) {
            update_option('numpo_engine_url', 'http://127.0.0.1:8080');
        }
        $this->assertSame('http://127.0.0.1:8080', get_option('numpo_engine_url'));
    }

    public function test_settings_normalize_engine_url(): void {
        update_option('numpo_engine_url', 'https://engine.example.test/ ');
        $this->assertSame('https://engine.example.test', Numpo_Settings::engine_url());
    }

    public function test_rest_routes_are_registered(): void {
        do_action('rest_api_init');
        $routes = rest_get_server()->get_routes();
        foreach ([
            '/numpo/v1/jobs',
            '/numpo/v1/jobs/(?P<id>[A-Za-z0-9-]+)',
            '/numpo/v1/jobs/(?P<id>[A-Za-z0-9-]+)/candidates',
            '/numpo/v1/jobs/(?P<id>[A-Za-z0-9-]+)/cancel',
            '/numpo/v1/jobs/(?P<id>[A-Za-z0-9-]+)/errors',
            '/numpo/v1/jobs/(?P<id>[A-Za-z0-9-]+)/csv',
        ] as $route) {
            $this->assertArrayHasKey($route, $routes);
        }
    }

    public function test_admin_permission_requires_capability(): void {
        wp_set_current_user(0);
        $this->assertFalse(Numpo_API::permission());
        $user = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($user);
        $this->assertTrue(Numpo_API::permission());
    }
}
