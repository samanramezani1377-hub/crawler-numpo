package browser

import (
 "context"
 "fmt"
 "time"

 "github.com/chromedp/chromedp"
)

type Result struct {
 URL string
 Status int
 ContentType string
 HTML string
 DurationMS int64
}

type Renderer interface {
 Render(context.Context,string)(Result,error)
}

type DisabledRenderer struct{}
func (DisabledRenderer) Render(context.Context,string)(Result,error){return Result{},ErrDisabled}
var ErrDisabled=disabledError{}
type disabledError struct{}
func(disabledError)Error()string{return "browser rendering is disabled"}

// ChromiumRenderer renders a page with a real Chromium/Chrome process.
// It is optional and only used when browser escalation is explicitly enabled.
type ChromiumRenderer struct{BrowserBinary string;Timeout time.Duration}
func NewChromiumRenderer(binary string,timeout time.Duration) *ChromiumRenderer { if timeout<=0 {timeout=30*time.Second}; return &ChromiumRenderer{BrowserBinary:binary,Timeout:timeout} }
func(r *ChromiumRenderer) Render(parent context.Context,rawURL string)(Result,error){
 ctx,cancel:=context.WithTimeout(parent,r.Timeout);defer cancel()
 opts:=append(chromedp.DefaultExecAllocatorOptions[:],chromedp.NoFirstRun,chromedp.NoDefaultBrowserCheck)
 if r.BrowserBinary!="" {opts=append(opts,chromedp.ExecPath(r.BrowserBinary))}
 alloc,acancel:=chromedp.NewExecAllocator(ctx,opts...);defer acancel()
 tab,tcancel:=chromedp.NewContext(alloc);defer tcancel()
 start:=time.Now();var html string;var status int
 if err:=chromedp.Run(tab,chromedp.Navigate(rawURL),chromedp.WaitReady("body"),chromedp.OuterHTML("html",&html));err!=nil{return Result{},err}
 status=200
 if html=="" {return Result{},fmt.Errorf("browser returned empty html")}
 return Result{URL:rawURL,Status:status,ContentType:"text/html",HTML:html,DurationMS:time.Since(start).Milliseconds()},nil
}
