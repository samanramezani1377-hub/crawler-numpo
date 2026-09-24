package discovery

import (
 "context"
 "net/http"
 "net/http/httptest"
 "testing"
 "time"
)
func TestHTTPSearchProviderPaginationAndDedup(t *testing.T){
 srv:=httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter,r *http.Request){
  if r.URL.Query().Get("q")!="foo"{t.Errorf("query not escaped/passed: %s",r.URL.RawQuery)}
  if r.URL.Query().Get("page")=="1"{_,_=w.Write([]byte("https://example.com/a\nhttps://example.com/a\n"))}else{_,_=w.Write([]byte("https://example.com/b\n"))}
 }));defer srv.Close()
 p:=NewSearchProvider(time.Second,srv.URL+"?q={query}&page={page}");p.MaxPages=2
 got,e:=p.Discover(context.Background(),"foo");if e!=nil{t.Fatal(e)}
 if len(got)!=2{t.Fatalf("got %v",got)}
}
func TestParseSearchJSON(t *testing.T){
 got:=parseSearchResults(`[{"url":"https://example.com/a","title":"A","snippet":"x"}]`,1,"test")
 if len(got)!=1||got[0].Title!="A"{t.Fatalf("unexpected %+v",got)}
}
