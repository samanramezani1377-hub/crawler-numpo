package urlnorm
import "testing"
func TestURLNormalization(t *testing.T){u,e:=URL("HTTP://Example.COM/a#frag");if e!=nil{t.Fatal(e)};if u.String()!="http://example.com/a"{t.Fatalf("got %s",u.String())}}
func TestDomain(t *testing.T){d,e:=Domain("https://Shop.Example.com/x");if e!=nil{t.Fatal(e)};if d!="shop.example.com"{t.Fatalf("got %s",d)}}
