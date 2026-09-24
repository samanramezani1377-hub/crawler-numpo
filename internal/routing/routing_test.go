package routing
import "testing"
func TestRoute(t *testing.T){d:=Route(true,true,false);if !d.DeepCrawl{t.Fatal("wordpress site should route to deep crawl")};d=Route(true,false,true);if !d.DeepCrawl{t.Fatal("woocommerce site should route to deep crawl")};d=Route(false,false,false);if d.DeepCrawl{t.Fatal("inactive site should not deep crawl")}}
func TestRouteWithRules(t *testing.T){d:=RouteWithRules(true,true,false,[]Rule{{RequireActive:true,RequireWordPress:true,DeepCrawl:true,Name:"wp"}});if !d.Probe||!d.DeepCrawl{t.Fatal("expected probe and deep crawl")};d=RouteWithRules(true,false,false,[]Rule{{RequireActive:true,RequireWordPress:true,DeepCrawl:true}});if d.DeepCrawl{t.Fatal("unexpected deep crawl")}}
