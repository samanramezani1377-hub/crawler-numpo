<?php
class Numpo_Plugin_Test extends WP_UnitTestCase {
 public function test_plugin_constants_and_version(): void {
  $this->assertSame('0.3.0', NUMPO_VERSION);
  $this->assertFileExists(NUMPO_DIR . 'numpo.php');
 }
 public function test_settings_normalize_engine_url(): void {
  update_option('numpo_engine_url','https://engine.example.test/ ');
  $this->assertSame('https://engine.example.test',Numpo_Settings::engine_url());
 }
 public function test_rest_routes_are_registered(): void {
  do_action('rest_api_init');$routes=rest_get_server()->get_routes();
  foreach(['/numpo/v1/jobs','/numpo/v1/jobs/(?P<id>[A-Za-z0-9-]+)','/numpo/v1/jobs/(?P<id>[A-Za-z0-9-]+)/candidates','/numpo/v1/jobs/(?P<id>[A-Za-z0-9-]+)/cancel','/numpo/v1/jobs/(?P<id>[A-Za-z0-9-]+)/facts',
   '/numpo/v1/jobs/(?P<id>[A-Za-z0-9-]+)/errors','/numpo/v1/jobs/(?P<id>[A-Za-z0-9-]+)/(?P<resource>domains|pages|technologies|contacts)'] as $route)$this->assertArrayHasKey($route,$routes);
 }
 public function test_admin_permission_requires_capability(): void {
  wp_set_current_user(0);$this->assertFalse(Numpo_API::permission());$user=self::factory()->user->create(['role'=>'administrator']);wp_set_current_user($user);$this->assertTrue(Numpo_API::permission());
 }
 public function test_php_crawler_normalizes_http_urls(): void {
  $this->assertSame('https://example.com/path?q=1',Numpo_Crawler::normalize_url('https://EXAMPLE.com/path?q=1#fragment'));
  $this->assertNull(Numpo_Crawler::normalize_url('file:///etc/passwd'));
 }
 public function test_php_runtime_diagnostics_report_php_components(): void {
  $d=Numpo_Diagnostics::check();
  $this->assertArrayHasKey('php',$d['checks']);
  $this->assertArrayHasKey('curl',$d['checks']);
  $this->assertArrayHasKey('database',$d['checks']);
  $this->assertArrayHasKey('schema',$d['checks']);
 }
}
