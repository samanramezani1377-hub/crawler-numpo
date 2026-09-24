package routing
import "testing"
func TestRoute(t *testing.T){d:=Route(true,true,false);if !d.DeepCrawl{t.Fatal("wordpress site should route to deep crawl")};d=Route(true,false,true);if !d.DeepCrawl{t.Fatal("woocommerce site should route to deep crawl")};d=Route(false,false,false);if d.DeepCrawl{t.Fatal("inactive site should not deep crawl")}}
