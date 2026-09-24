package discovery
import("context";"io";"net/http";"regexp";"strings";"time";"github.com/samanramezani1377-hub/crawler-numpo/internal/policy")
type Provider interface{Name()string;Discover(context.Context,string)([]string,error)}
type HTTPProvider struct{Client *http.Client}
func NewHTTPProvider(t time.Duration)*HTTPProvider{return &HTTPProvider{&http.Client{Timeout:t}}}
func(p *HTTPProvider)get(ctx context.Context,raw string)(string,error){if e:=policy.ValidateURL(raw);e!=nil{return "",e};req,e:=http.NewRequestWithContext(ctx,http.MethodGet,raw,nil);if e!=nil{return "",e};resp,e:=p.Client.Do(req);if e!=nil{return "",e};defer resp.Body.Close();if resp.StatusCode<200||resp.StatusCode>=400{return "",nil};b,e:=io.ReadAll(io.LimitReader(resp.Body,2<<20));return string(b),e}
type SitemapProvider struct{*HTTPProvider}
func(p *SitemapProvider)Name()string{return "sitemap"}
func(p *SitemapProvider)Discover(ctx context.Context,base string)([]string,error){b,e:=p.get(ctx,strings.TrimRight(base,"/")+"/sitemap.xml");if e!=nil{return nil,e};re:=regexp.MustCompile("(?is)<loc>\\s*([^<]+?)\\s*</loc>");m:=re.FindAllStringSubmatch(b,-1);out:=[]string{};for _,v:=range m{if len(v)>1{out=append(out,strings.TrimSpace(v[1]))}};return out,nil}
type RobotsProvider struct{*HTTPProvider}
func(p *RobotsProvider)Name()string{return "robots"}
func(p *RobotsProvider)Discover(ctx context.Context,base string)([]string,error){b,e:=p.get(ctx,strings.TrimRight(base,"/")+"/robots.txt");if e!=nil{return nil,e};var out []string;for _,line:=range strings.Split(b,"\n"){line=strings.TrimSpace(line);if strings.HasPrefix(strings.ToLower(line),"sitemap:"){parts:=strings.SplitN(line,":",2);if len(parts)==2{out=append(out,strings.TrimSpace(parts[1]))}}};return out,nil}
