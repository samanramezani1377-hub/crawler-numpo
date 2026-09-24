package crawl
import("net/url";"testing")
func TestParseHTML(t *testing.T){u,_:=url.Parse("https://example.com/a/");title,links:=ParseHTML(u,"<html><title>Test</title><a href='/x'>x</a><a href='https://example.com/y#f'>y</a></html>",10);if title!="Test"{t.Fatal(title)};if len(links)!=2{t.Fatalf("%v",links)}}
