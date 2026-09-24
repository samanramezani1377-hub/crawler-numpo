package policy

import("context";"fmt";"net";"net/http";"strings";"time";"github.com/samanramezani1377-hub/crawler-numpo/internal/urlnorm")

type Error struct{Code,Message string}
func(e *Error)Error()string{return e.Code+": "+e.Message}
func ValidateURL(raw string) error {u,err:=urlnorm.URL(raw);if err!=nil{return err};h:=u.Hostname();if urlnorm.IsPrivateHost(h){return &Error{"ssrf_blocked","private or local network target is blocked"}};ctx,cancel:=context.WithTimeout(context.Background(),3*time.Second);defer cancel();ips,err:=net.DefaultResolver.LookupIPAddr(ctx,h);if err==nil{for _,ip:=range ips{if urlnorm.IsPrivateHost(ip.IP.String()){return &Error{"ssrf_blocked","host resolves to a private or local address"}}}};return nil}

func SafeTransport() *http.Transport{return &http.Transport{Proxy:nil,ForceAttemptHTTP2:true,DialContext:func(ctx context.Context,network,address string)(net.Conn,error){host,port,err:=net.SplitHostPort(address);if err!=nil{return nil,err};if urlnorm.IsPrivateHost(host){return nil,&Error{"ssrf_blocked","private or local network target is blocked"}};rctx,cancel:=context.WithTimeout(ctx,3*time.Second);defer cancel();ips,err:=net.DefaultResolver.LookupIPAddr(rctx,host);if err!=nil{return nil,err};d:=net.Dialer{Timeout:10*time.Second};var last error;for _,ip:=range ips{if urlnorm.IsPrivateHost(ip.IP.String()){last=&Error{"ssrf_blocked",fmt.Sprintf("resolved private address %s",ip.IP.String())};continue};conn,err:=d.DialContext(ctx,network,net.JoinHostPort(ip.IP.String(),port));if err==nil{return conn,nil};last=err};if last!=nil{return nil,last};return nil,fmt.Errorf("no usable address for %s",host)}}}

func ValidateHost(host string)error{host=strings.TrimSpace(host);if host==""||urlnorm.IsPrivateHost(host){return &Error{"ssrf_blocked","private or local host is blocked"}};return nil}
