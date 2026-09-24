package policy

import ("context";"net";"github.com/samanramezani1377-hub/crawler-numpo/internal/urlnorm")
type Error struct{Code,Message string}
func(e *Error)Error()string{return e.Code+": "+e.Message}
func ValidateURL(raw string) error { u,err:=urlnorm.URL(raw);if err!=nil{return err};h:=u.Hostname();if urlnorm.IsPrivateHost(h){return &Error{"ssrf_blocked","private or local network target is blocked"}};ips,err:=net.DefaultResolver.LookupIPAddr(context.Background(),h);if err==nil{for _,ip:=range ips{if urlnorm.IsPrivateHost(ip.IP.String()){return &Error{"ssrf_blocked","host resolves to a private or local address"}}}};return nil}
