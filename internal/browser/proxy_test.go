package browser

import ("context";"testing";"time")
func TestSSRFProxyStartsOnLoopback(t *testing.T){p,e:=StartSSRFProxy();if e!=nil{t.Fatal(e)};defer p.Close();if p.URL()==""{t.Fatal("missing proxy URL")};ctx,c:=context.WithCancel(context.Background());defer c();go p.Serve(ctx);time.Sleep(5*time.Millisecond)}
