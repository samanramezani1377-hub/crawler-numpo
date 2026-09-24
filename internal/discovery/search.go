package discovery

import (
 "context"
 "encoding/json"
 "fmt"
 "io"
 "net/http"
 "net/url"
 "strconv"
 "strings"
 "time"
 "golang.org/x/net/html"
 "github.com/samanramezani1377-hub/crawler-numpo/internal/policy"
 "github.com/samanramezani1377-hub/crawler-numpo/internal/urlnorm"
)

type SearchProvider interface { Name() string; Search(context.Context,string,int)([]Result,error) }
type Result struct { URL string; Title string; Snippet string; Page int; Provider string }

type HTTPSearchProvider struct { Client *http.Client; Template string; MaxPages int; PageParam string }

func NewSearchProvider(t time.Duration,template string)*HTTPSearchProvider {
 if t<=0 {t=15*time.Second}
 return &HTTPSearchProvider{Client:&http.Client{Transport:policy.SafeTransport(),Timeout:t,CheckRedirect:func(r *http.Request,v []*http.Request)error{if len(v)>=3||policy.ValidateURL(r.URL.String())!=nil{return http.ErrUseLastResponse};return nil}},Template:template,MaxPages:5,PageParam:"page"}
}
func(p *HTTPSearchProvider)Name()string{return "http"}
func(p *HTTPSearchProvider)Search(ctx context.Context,query string,page int)([]Result,error){
 if p.Template=="" {return nil,fmt.Errorf("search provider template is empty")}
 if page<1 {page=1}
 target:=strings.ReplaceAll(p.Template,"{query}",url.QueryEscape(query))
 target=strings.ReplaceAll(target,"{page}",strconv.Itoa(page))
 if e:=policy.ValidateURL(target);e!=nil{return nil,e}
 req,e:=http.NewRequestWithContext(ctx,http.MethodGet,target,nil);if e!=nil{return nil,e}
 resp,e:=p.Client.Do(req);if e!=nil{return nil,e};defer resp.Body.Close()
 if resp.StatusCode==429||resp.StatusCode>=500{return nil,fmt.Errorf("search provider http status %d",resp.StatusCode)}
 if resp.StatusCode<200||resp.StatusCode>=400{return nil,fmt.Errorf("search provider http status %d",resp.StatusCode)}
 b,e:=io.ReadAll(io.LimitReader(resp.Body,2<<20));if e!=nil{return nil,e}
 return parseSearchResults(string(b),page,p.Name()),nil
}
func(p *HTTPSearchProvider)Discover(ctx context.Context,query string)([]string,error){
 max:=p.MaxPages;if max<1{max=1};seen:=map[string]bool{};var out []string
 for page:=1;page<=max;page++{results,e:=p.Search(ctx,query,page);if e!=nil{return out,e};if len(results)==0{break};for _,r:=range results{if !seen[r.URL]{seen[r.URL]=true;out=append(out,r.URL)}}}
 return out,nil
}
func parseSearchResults(body string,page int,provider string)[]Result{
 var raw []struct{URL string `json:"url"`;Title string `json:"title"`;Snippet string `json:"snippet"`}
 if json.Unmarshal([]byte(body),&raw)==nil{out:=make([]Result,0,len(raw));for _,r:=range raw{if u,e:=normalizeResultURL(r.URL);e==nil{out=append(out,Result{u,r.Title,r.Snippet,page,provider})}};return dedupResults(out)}
 var out []Result
 for _,line:=range strings.Split(body,"\n"){v:=strings.TrimSpace(line);if u,e:=normalizeResultURL(v);e==nil{out=append(out,Result{u,"","",page,provider})}}
 if len(out)>0{return dedupResults(out)}
 doc,e:=html.Parse(strings.NewReader(body));if e!=nil{return nil}
 var walk func(*html.Node);walk=func(n *html.Node){if n.Type==html.ElementNode&&n.Data=="a"{for _,a:=range n.Attr{if a.Key=="href"{if u,e:=normalizeResultURL(strings.TrimSpace(a.Val));e==nil{out=append(out,Result{u,strings.TrimSpace(textContent(n)),"",page,provider})};break}}};for ch:=n.FirstChild;ch!=nil;ch=ch.NextSibling{walk(ch)}}
 walk(doc);return dedupResults(out)
}
func normalizeResultURL(raw string)(string,error){if !strings.HasPrefix(raw,"http://")&&!strings.HasPrefix(raw,"https://"){return "",fmt.Errorf("not an absolute URL")};if e:=policy.ValidateURL(raw);e!=nil{return "",e};u,e:=urlnorm.URL(raw);if e!=nil{return "",e};return u.String(),nil}
func textContent(n *html.Node)string{var b strings.Builder;var walk func(*html.Node);walk=func(x *html.Node){if x.Type==html.TextNode{b.WriteString(x.Data)};for ch:=x.FirstChild;ch!=nil;ch=ch.NextSibling{walk(ch)}};walk(n);return strings.TrimSpace(b.String())}
func dedupResults(in []Result)[]Result{seen:=map[string]bool{};out:=make([]Result,0,len(in));for _,r:=range in{if !seen[r.URL]{seen[r.URL]=true;out=append(out,r)}};return out}
