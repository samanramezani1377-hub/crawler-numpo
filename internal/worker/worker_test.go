package worker

import (
 "testing"
 "github.com/samanramezani1377-hub/crawler-numpo/internal/crawl"
)

func TestShouldEscalateToBrowserForJSShell(t *testing.T) {
 p:=crawl.Page{Status:200,ContentType:"text/html",Body:"<html><body><div id=\"root\"></div><script src=\"/app.js\"></script></body></html>"}
 if !shouldEscalateToBrowser(p){t.Fatal("expected browser escalation")}
}

func TestShouldNotEscalateForNormalHTML(t *testing.T) {
 p:=crawl.Page{Status:200,ContentType:"text/html",Body:"<html><body><h1>Store</h1><a href=\"/about\">About</a><script type=\"application/ld+json\">{}</script></body></html>"}
 if shouldEscalateToBrowser(p){t.Fatal("did not expect browser escalation")}
}
