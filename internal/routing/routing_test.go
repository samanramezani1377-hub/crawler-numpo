package routing
import "testing"
func TestRoute(t *testing.T){d:=Route(true,true,false);if !d.DeepCrawl{t.Fatal("wordpress site should route to deep crawl")};d=Route(true,false,true);if !d.DeepCrawl{t.Fatal("woocommerce site should route to deep crawl")};d=Route(false,false,false);if d.DeepCrawl{t.Fatal("inactive site should not deep crawl")}}
func TestRouteWithRules(t *testing.T){d:=RouteWithRules(true,true,false,[]Rule{{RequireActive:true,RequireWordPress:true,DeepCrawl:true,Name:"wp"}});if !d.Probe||!d.DeepCrawl{t.Fatal("expected probe and deep crawl")};d=RouteWithRules(true,false,false,[]Rule{{RequireActive:true,RequireWordPress:true,DeepCrawl:true}});if d.DeepCrawl{t.Fatal("unexpected deep crawl")}}

func TestPriority(t *testing.T){d:=RouteWithRules(true,true,true,[]Rule{{RequireActive:true,DeepCrawl:true,Priority:90,Name:"high"},{RequireActive:true,DeepCrawl:true,Priority:20,Name:"low"}});if d.Priority!=90{t.Fatalf("priority=%d",d.Priority)}}
