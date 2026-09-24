package retry

import ("errors";"testing";"time";"github.com/samanramezani1377-hub/crawler-numpo/internal/crawl")

func TestPolicyRetryable(t *testing.T){
 p:=New(3,time.Second,10*time.Second,0)
 d:=p.Decide(&crawl.HTTPError{Status:503},1)
 if !d.Retry || d.Delay!=time.Second { t.Fatalf("unexpected decision: %+v",d) }
 if p.Decide(errors.New("x"),3).Retry { t.Fatal("max attempts must stop retry") }
}
func TestPolicyDoesNotRetryClientErrors(t *testing.T){
 p:=New(3,time.Second,10*time.Second,0)
 if p.Decide(&crawl.HTTPError{Status:404},1).Retry { t.Fatal("4xx must be final") }
}
