package browser

import (
 "context"
 "fmt"
 "os/exec"
 "strings"
 "time"
 "github.com/samanramezani1377-hub/crawler-numpo/internal/policy"
)

type Result struct{URL string;Status int;ContentType string;HTML string;DurationMS int64}
type Renderer interface{Render(context.Context,string)(Result,error)}
type DisabledRenderer struct{}
func(DisabledRenderer)Render(context.Context,string)(Result,error){return Result{},ErrDisabled}
var ErrDisabled=disabledError{}
type disabledError struct{}
func(disabledError)Error()string{return "browser rendering is disabled"}

// ChromiumRenderer uses a locally installed Chromium/Chrome executable.
// It is deliberately process-isolated: the crawl core does not embed a browser SDK.
type ChromiumRenderer struct{Binary string;Timeout time.Duration;MaxOutput int;slots chan struct{}}
func NewChromiumRenderer(binary string,timeout time.Duration,maxOutput int)*ChromiumRenderer{if timeout<=0{timeout=30*time.Second};if maxOutput<=0{maxOutput=8<<20};return &ChromiumRenderer{Binary:binary,Timeout:timeout,MaxOutput:maxOutput,slots:make(chan struct{},2)}}
func(r *ChromiumRenderer)Render(parent context.Context,rawURL string)(Result,error){
 if e:=policy.ValidateURL(rawURL);e!=nil{return Result{},e}
 ctx,cancel:=context.WithTimeout(parent,r.Timeout);defer cancel()
 if r.slots==nil { r.slots=make(chan struct{},2) }
 select{case r.slots<-struct{}{}:defer func(){<-r.slots}();case <-ctx.Done():return Result{},ctx.Err()}
 if strings.TrimSpace(r.Binary)==""{return Result{},ErrDisabled}
 start:=time.Now()
 cmd:=exec.CommandContext(ctx,r.Binary,"--headless","--disable-gpu","--disable-extensions","--no-first-run","--no-default-browser-check","--dump-dom",rawURL)
 out,err:=cmd.Output()
 if err!=nil{return Result{},err}
 if len(out)>r.MaxOutput{out=out[:r.MaxOutput]}
 html:=string(out)
 if strings.TrimSpace(html)==""{return Result{},fmt.Errorf("browser returned empty html")}
 return Result{URL:rawURL,Status:200,ContentType:"text/html",HTML:html,DurationMS:time.Since(start).Milliseconds()},nil
}
