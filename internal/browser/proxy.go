package browser

import (
 "bufio"
 "context"
 "io"
 "net"
 "net/http"
 "strings"
 "time"
 "github.com/samanramezani1377-hub/crawler-numpo/internal/policy"
)

type SSRFProxy struct { ln net.Listener; transport *http.Transport }

func StartSSRFProxy() (*SSRFProxy,error) {
 ln,e:=net.Listen("tcp","127.0.0.1:0"); if e!=nil{return nil,e}
 return &SSRFProxy{ln:ln,transport:policy.SafeTransport()},nil
}
func(p *SSRFProxy) URL()string{return "http://"+p.ln.Addr().String()}
func(p *SSRFProxy) Close()error{if p==nil||p.ln==nil{return nil};return p.ln.Close()}
func(p *SSRFProxy) Serve(ctx context.Context) {
 for { c,e:=p.ln.Accept(); if e!=nil{return}; go p.serveConn(ctx,c) }
}
func(p *SSRFProxy) serveConn(ctx context.Context,c net.Conn){
 defer c.Close(); _=c.SetDeadline(deadline(ctx))
 br:=bufio.NewReader(c); req,e:=http.ReadRequest(br); if e!=nil{return}
 if strings.EqualFold(req.Method,http.MethodConnect) {
  host:=req.Host; if !strings.Contains(host,":"){host+=":443"}
  target:="https://"+host
  if e=policy.ValidateURL(target);e!=nil{_,_=io.WriteString(c,"HTTP/1.1 403 Forbidden\r\nConnection: close\r\n\r\n");return}
  upstream,e:=p.transport.DialContext(ctx,"tcp",host);if e!=nil{_,_=io.WriteString(c,"HTTP/1.1 502 Bad Gateway\r\nConnection: close\r\n\r\n");return}
  defer upstream.Close()
  bw:=bufio.NewWriter(c);_,_=bw.WriteString("HTTP/1.1 200 Connection Established\r\n\r\n");_=bw.Flush()
  go func(){_,_=io.Copy(upstream,br);_=upstream.Close()}()
  _,_=io.Copy(c,upstream); return
 }
 if req.URL==nil||req.URL.Host=="" {return}
 if e=policy.ValidateURL(req.URL.String());e!=nil{_,_=io.WriteString(c,"HTTP/1.1 403 Forbidden\r\nConnection: close\r\n\r\n");return}
 req.RequestURI=""
 resp,e:=p.transport.RoundTrip(req);if e!=nil{_,_=io.WriteString(c,"HTTP/1.1 502 Bad Gateway\r\nConnection: close\r\n\r\n");return}
 defer resp.Body.Close();_ = resp.Write(c)
}
func deadline(ctx context.Context)time.Time{if d,ok:=ctx.Deadline();ok{return d};return time.Now().Add(30*time.Second)}
