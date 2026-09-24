package browser

import (
 "context"
 "errors"
 "testing"
 "os"
 "path/filepath"
 "time"
)

func TestDisabledRendererIsExplicit(t *testing.T) {
 _, err := (DisabledRenderer{}).Render(context.Background(), "https://example.com")
 if !errors.Is(err, ErrDisabled) {
  t.Fatalf("expected ErrDisabled, got %v", err)
 }
}


func TestChromiumRendererUsesConfiguredExecutable(t *testing.T) {
 dir:=t.TempDir()
 script:=filepath.Join(dir,"browser.sh")
 if err:=os.WriteFile(script,[]byte("#!/bin/sh\nprintf '<html><title>Rendered</title><body>ready</body></html>\n'\n"),0755);err!=nil{t.Fatal(err)}
 r:=NewChromiumRenderer(script,time.Second,1024)
 got,err:=r.Render(context.Background(),"https://example.com")
 if err!=nil{t.Fatal(err)}
 if got.Status!=200||got.ContentType!="text/html"||got.URL!="https://example.com"{t.Fatalf("unexpected result: %+v",got)}
 if got.HTML!="<html><title>Rendered</title><body>ready</body></html>\n"{t.Fatalf("unexpected html: %q",got.HTML)}
}
