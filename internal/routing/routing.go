package routing

type Rule struct{RequireActive bool `json:"require_active"`;RequireWordPress bool `json:"require_wordpress"`;RequireWooCommerce bool `json:"require_woocommerce"`;DeepCrawl bool `json:"deep_crawl"`;Probe bool `json:"probe"`;Priority int `json:"priority"`;Name string `json:"name"`}
type Decision struct{Probe bool;DeepCrawl bool;Priority int;Reasons []string}

func Route(active,wordpress,woocommerce bool)Decision{return RouteWithRules(active,wordpress,woocommerce,[]Rule{{RequireActive:true,RequireWordPress:true,DeepCrawl:true,Name:"active_wordpress"},{RequireActive:true,RequireWooCommerce:true,DeepCrawl:true,Name:"active_woocommerce"}})}

func RouteWithRules(active,wordpress,woocommerce bool,rules []Rule)Decision{
 d:=Decision{Probe:active}
 for _,r:=range rules{
  if r.RequireActive&&!active||r.RequireWordPress&&!wordpress||r.RequireWooCommerce&&!woocommerce{continue}
  if r.Probe{d.Probe=true};if r.DeepCrawl{d.DeepCrawl=true};if r.Priority>d.Priority{d.Priority=r.Priority};if r.Name!=""{d.Reasons=append(d.Reasons,r.Name)}
 }
 if active&&len(d.Reasons)==0{d.Reasons=append(d.Reasons,"active")}
 return d
}
