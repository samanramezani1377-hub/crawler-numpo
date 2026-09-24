package discovery
import("context";"io";"net/http";"net/url";"strings";"time")
type SearchProvider struct{Client *http.Client;Template string}
func NewSearchProvider(t time.Duration,template string)*SearchProvider{return &SearchProvider{&http.Client{Timeout:t},template}}
func(p *SearchProvider)Name()string{return "search_provider"}
func(p *SearchProvider)Discover(ctx context.Context,query string)([]string,error){if p.Template==""{return nil,nil};target:=strings.ReplaceAll(p.Template,"{query}",url.QueryEscape(query));req,e:=http.NewRequestWithContext(ctx,http.MethodGet,target,nil);if e!=nil{return nil,e};resp,e:=p.Client.Do(req);if e!=nil{return nil,e};defer resp.Body.Close();b,e:=io.ReadAll(io.LimitReader(resp.Body,1<<20));if e!=nil{return nil,e};var out []string;for _,line:=range strings.Split(string(b),"\n"){v:=strings.TrimSpace(line);if strings.HasPrefix(v,"http://")||strings.HasPrefix(v,"https://"){out=append(out,v)}};return out,nil}
