package routing

type Rule struct{RequireActive bool;RequireWordPress bool;RequireWooCommerce bool;DeepCrawl bool;Name string}
type Decision struct{Probe bool;DeepCrawl bool;Reasons []string}

func Route(active,wordpress,woocommerce bool)Decision{
 return RouteWithRules(active,wordpress,woocommerce,[]Rule{
  {RequireActive:true,RequireWordPress:true,DeepCrawl:true,Name:"active_wordpress"},
  {RequireActive:true,RequireWooCommerce:true,DeepCrawl:true,Name:"active_woocommerce"},
 })
}
func RouteWithRules(active,wordpress,woocommerce bool,rules []Rule)Decision{
 d:=Decision{Probe:active}
 if active{d.Reasons=append(d.Reasons,"active")}
 for _,r:=range rules{
  if r.RequireActive&&!active{continue}
  if r.RequireWordPress&&!wordpress{continue}
  if r.RequireWooCommerce&&!woocommerce{continue}
  if r.DeepCrawl{d.DeepCrawl=true}
  if r.Name!=""{d.Reasons=append(d.Reasons,r.Name)}
 }
 return d
}
