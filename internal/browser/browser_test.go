package browser

import (
 "context"
 "errors"
 "testing"
)

func TestDisabledRendererIsExplicit(t *testing.T) {
 _, err := (DisabledRenderer{}).Render(context.Background(), "https://example.com")
 if !errors.Is(err, ErrDisabled) {
  t.Fatalf("expected ErrDisabled, got %v", err)
 }
}
