package discovery
import("context";"strings";"github.com/google/uuid";"github.com/samanramezani1377-hub/crawler-numpo/internal/policy";"github.com/samanramezani1377-hub/crawler-numpo/internal/store";"github.com/samanramezani1377-hub/crawler-numpo/internal/urlnorm")
type Service struct{Store *store.Store}
func(s *Service)Seed(ctx context.Context,job string,seeds []string)error{for _,raw:=range seeds{if e:=policy.ValidateURL(raw);e!=nil{return e};u,e:=urlnorm.URL(raw);if e!=nil{return e};d,e:=urlnorm.Domain(raw);if e!=nil{return e};if e=s.Store.UpsertCandidate(ctx,uuid.NewString(),job,raw,u.String(),d,strings.ToLower(u.Hostname()),"manual_seed","",100,1);e!=nil{return e}};return nil}
