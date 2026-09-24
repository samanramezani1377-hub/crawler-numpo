package discovery
import("context";"io";"net/http";"regexp";"strings";"time";"github.com/samanramezani1377-hub/crawler-numpo/internal/policy")
type Provider interface{Name()string;Discover(context.Context,string)([]string,error)}
type HTTPProvider struct{Client *http.Client}
func NewHTTPProvider(t time.Duration)*HTTPProvider{return &HTTPProvider{&http.Client{Timeout:t,CheckRedirect:func(r *http.Request,v []*http.Request)error{if len(v)>=3{return http.ErrUseLastResponse};if policy.ValidateURL(r.URL.String())!=nil{return http.ErrUseLastResponse};return nil}}}}
func(p *HTTPProvider)get(ctx context.Context,raw string)(string,error){if e:=policy.ValidateURL(raw);e!=nil{return "",e};req,e:=http.NewRequestWithContext(ctx,http.MethodGet,raw,nil);if e!=nil{return "",e};resp,e:=p.Client.Do(req);if e!=nil{return "",e};defer resp.Body.Close();if resp.StatusCode<200||resp.StatusCode>=400{return "",nil};b,e:=io.ReadAll(io.LimitReader(resp.Body,2<<20));return string(b),e}
func NewSitemapProvider(h *HTTPProvider)*SitemapProvider{return &SitemapProvider{HTTPProvider:h}}
type SitemapProvider struct{*HTTPProvider}
func(p *SitemapProvider)Name()string{return "sitemap"}
func(p *SitemapProvider)Discover(ctx context.Context,base string)([]string,error){candidates:=[]string{strings.TrimRight(base,"/")+"/sitemap.xml",strings.TrimRight(base,"/")+"/sitemap_index.xml"};re:=regexp.MustCompile("(?is)<loc>\\s*([^<]+?)\\s*</loc>");var out []string;for _,src:=range candidates{b,e:=p.get(ctx,src);if e!=nil{continue};for _,v:=range re.FindAllStringSubmatch(b,-1){if len(v)<2{continue};u:=strings.TrimSpace(v[1]);if policy.ValidateURL(u)==nil{out=append(out,u)}}};return out,nil}
func NewRobotsProvider(h *HTTPProvider)*RobotsProvider{return &RobotsProvider{HTTPProvider:h}}
type RobotsProvider struct{*HTTPProvider}
func(p *RobotsProvider)Name()string{return "robots"}
func(p *RobotsProvider)Discover(ctx context.Context,base string)([]string,error){b,e:=p.get(ctx,strings.TrimRight(base,"/")+"/robots.txt");if e!=nil{return nil,e};var out []string;for _,line:=range strings.Split(b,"\n"){line=strings.TrimSpace(line);if strings.HasPrefix(strings.ToLower(line),"sitemap:"){parts:=strings.SplitN(line,":",2);if len(parts)==2{u:=strings.TrimSpace(parts[1]);if policy.ValidateURL(u)==nil{out=append(out,u)}}}};return out,nil}