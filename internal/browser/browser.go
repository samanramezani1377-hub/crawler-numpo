package browser

import "context"

// Result is the normalized output of a rendered page. The crawler core consumes
// this shape just like HTTP-fetched pages, so browser execution remains an
// optional escalation layer.
type Result struct {
 URL         string
 Status      int
 ContentType string
 HTML        string
 DurationMS  int64
}

// Renderer is intentionally transport-agnostic. A future Playwright/Chromium
// worker can implement it without coupling the crawl core to a browser SDK.
type Renderer interface {
 Render(context.Context, string) (Result, error)
}

// DisabledRenderer makes browser escalation explicit when no browser worker
// is configured.
type DisabledRenderer struct{}

func (DisabledRenderer) Render(context.Context, string) (Result, error) {
 return Result{}, ErrDisabled
}

var ErrDisabled = disabledError{}

type disabledError struct{}
func (disabledError) Error() string { return "browser rendering is disabled" }
