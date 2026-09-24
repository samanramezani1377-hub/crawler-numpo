<?php
class Numpo_Plugin_Test extends WP_UnitTestCase {
    public function test_plugin_constants_and_version(): void {
        $this->assertSame('0.2.0', NUMPO_VERSION);
        $this->assertFileExists(NUMPO_DIR . 'numpo.php');
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
            '/numpo/v1/jobs/(?P<id>[A-Za-z0-9-]+)/(?P<resource>domains|hosts|pages|technologies|contacts|business|social|classifications|probes)',
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

    public function test_csv_forwards_raw_csv_content_type(): void {
        update_option('numpo_engine_url', 'https://engine.example.test');
        add_filter('pre_http_request', function ($response, $args, $url) {
            $this->assertSame('https://engine.example.test/api/v1/discovery/jobs/job-1/csv', $url);
            $this->assertSame('text/csv', $args['headers']['Content-Type']);
            $this->assertSame("url\nhttps://example.com\n", $args['body']);
            return ['response' => ['code' => 200], 'body' => '{"imported":1}'];
        }, 10, 3);
        $request = new WP_REST_Request('POST', '/numpo/v1/jobs/job-1/csv');
        $request->set_param('id', 'job-1');
        $request->set_body("url\nhttps://example.com\n");
        $response = Numpo_API::csv($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
    }
    public function test_runtime_diagnostics_report_required_components(): void {
        $d = Numpo_Diagnostics::check();
        $this->assertArrayHasKey('exec', $d['checks']);
        $this->assertArrayHasKey('engine', $d['checks']);
        $this->assertArrayHasKey('postgres', $d['checks']);
        $this->assertArrayHasKey('chromium', $d['checks']);
        $this->assertFalse($d['checks']['chromium']['required']);
    }
}
