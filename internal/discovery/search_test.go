package discovery

import ("context";"io";"net/http";"testing";"strings";"time")

type roundTripFunc func(*http.Request)(*http.Response,error)
func(f roundTripFunc)RoundTrip(r *http.Request)(*http.Response,error){return f(r)}

func TestHTTPSearchProviderPaginationAndDedup(t *testing.T){
 calls:=0
 p:=NewSearchProvider(time.Second,"https://example.com/search?q={query}&page={page}")
 p.Client=&http.Client{Transport:roundTripFunc(func(r *http.Request)(*http.Response,error){calls++;body:="https://example.com/a\nhttps://example.com/a\n";if r.URL.Query().Get("page")=="2"{body="https://example.com/b\n"};return &http.Response{StatusCode:200,Body:io.NopCloser(strings.NewReader(body)),Header:make(http.Header),Request:r},nil})}
 p.MaxPages=2
 got,e:=p.Discover(context.Background(),"foo");if e!=nil{t.Fatal(e)}
 if calls!=2||len(got)!=2{t.Fatalf("calls=%d got=%v",calls,got)}
}
func TestParseSearchJSON(t *testing.T){got:=parseSearchResults(`[{"url":"https://example.com/a","title":"A","snippet":"x"}]`,1,"test");if len(got)!=1||got[0].Title!="A"{t.Fatalf("unexpected %+v",got)}}
