package retry

import (
 "math/rand"
 "strconv"
 "strings"
 "time"
 "github.com/samanramezani1377-hub/crawler-numpo/internal/crawl"
)

type Decision struct { Retry bool; Delay time.Duration; Code string }

type Policy struct {
 MaxAttempts int
 BaseDelay time.Duration
 MaxDelay time.Duration
 Jitter time.Duration
}

func New(maxAttempts int, base, max, jitter time.Duration) Policy {
 if maxAttempts < 1 { maxAttempts = 3 }
 if base <= 0 { base = time.Second }
 if max <= 0 { max = 30 * time.Second }
 if jitter < 0 { jitter = 0 }
 return Policy{maxAttempts,base,max,jitter}
}

func (p Policy) Decide(err error, attempt int) Decision {
 if err == nil || attempt >= p.MaxAttempts { return Decision{Code:"final"} }
 d := p.BaseDelay
 for i:=1; i<attempt; i++ {
  if d >= p.MaxDelay/2 { d=p.MaxDelay; break }
  d*=2
 }
 if d>p.MaxDelay { d=p.MaxDelay }
 if he,ok:=err.(*crawl.HTTPError); ok {
  if he.Status!=429 && he.Status<500 { return Decision{Code:"final"} }
  if s:=strings.TrimSpace(he.RetryAfter); s!="" {
   if n,e:=strconv.Atoi(s); e==nil && n>=0 { d=time.Duration(n)*time.Second }
   if t,e:=httpDate(s); e==nil { d=time.Until(t); if d<0 { d=0 } }
  }
 }
 if p.Jitter>0 { d += time.Duration(rand.Int63n(int64(p.Jitter)+1)) }
 if d>p.MaxDelay { d=p.MaxDelay }
 return Decision{Retry:true,Delay:d,Code:"retry"}
}

func httpDate(s string)(time.Time,error){ return time.Parse(time.RFC1123,s) }
