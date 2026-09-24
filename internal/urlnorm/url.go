package urlnorm

import("fmt";"net";"net/url";"strings")
func URL(raw string)(*url.URL,error){ raw=strings.TrimSpace(raw); if raw=="" {return nil,fmt.Errorf("empty url")}; if !strings.Contains(raw,"://"){raw="https://"+raw}; u,err:=url.Parse(raw); if err!=nil{return nil,fmt.Errorf("invalid url: %w",err)}; if u.Scheme!="http"&&u.Scheme!="https"{return nil,fmt.Errorf("unsupported scheme")}; if u.Hostname()==""{return nil,fmt.Errorf("missing host")}; u.Fragment=""; u.Host=strings.ToLower(u.Host); if u.Path==""{u.Path="/"}; return u,nil }
func Domain(raw string)(string,error){u,err:=URL(raw);if err!=nil{return "",err};return strings.ToLower(strings.TrimSuffix(u.Hostname(),".")),nil}
func IsPrivateHost(host string)bool{ip:=net.ParseIP(host);if ip==nil{return strings.EqualFold(host,"localhost")||strings.HasSuffix(strings.ToLower(host),".localhost")};return ip.IsLoopback()||ip.IsPrivate()||ip.IsLinkLocalUnicast()||ip.IsUnspecified()||ip.IsMulticast()}
