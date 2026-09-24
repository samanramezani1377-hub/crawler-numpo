package routing
type Decision struct{Probe bool;DeepCrawl bool;Reasons []string}
func Route(active,wordpress,woocommerce bool)Decision{d:=Decision{};if active{d.Probe=true;d.Reasons=append(d.Reasons,"active")};if active&&wordpress{d.DeepCrawl=true;d.Reasons=append(d.Reasons,"wordpress")};if active&&woocommerce{d.DeepCrawl=true;d.Reasons=append(d.Reasons,"woocommerce")};return d}
