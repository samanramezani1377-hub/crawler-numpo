package httpapi

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"github.com/samanramezani1377-hub/crawler-numpo/internal/config"
)

func TestServeHTTPRequiresAPIKey(t *testing.T) {
	s := &Server{Cfg: config.Config{APIKey: "secret", AllowAnonymousAPI: false}}
	req := httptest.NewRequest(http.MethodGet, "/api/v1/discovery/jobs", nil)
	rr := httptest.NewRecorder()
	s.ServeHTTP(rr, req)
	if rr.Code != http.StatusUnauthorized {
		t.Fatalf("status=%d, want 401", rr.Code)
	}
}

func TestServeHTTPRejectsWrongAPIKey(t *testing.T) {
	s := &Server{Cfg: config.Config{APIKey: "secret", AllowAnonymousAPI: false}}
	req := httptest.NewRequest(http.MethodGet, "/api/v1/discovery/jobs", nil)
	req.Header.Set("Authorization", "Bearer wrong")
	rr := httptest.NewRecorder()
	s.ServeHTTP(rr, req)
	if rr.Code != http.StatusUnauthorized {
		t.Fatalf("status=%d, want 401", rr.Code)
	}
}

func TestCreateJobReturnsJSONDecoderDetails(t *testing.T) {
	s := &Server{Cfg: config.Config{
		APIKey: "secret",
		AllowAnonymousAPI: false,
		MaxBodyBytes: 2 << 20,
	}}
	body := strings.NewReader("{\"project_id\":\"p\",\"mode\":\"manual\",\"seeds\":[\"https://example.com\"]} trailing")
	req := httptest.NewRequest(http.MethodPost, "/api/v1/discovery/jobs", body)
	req.Header.Set("Authorization", "Bearer secret")
	rr := httptest.NewRecorder()
	s.ServeHTTP(rr, req)
	if rr.Code != http.StatusBadRequest {
		t.Fatalf("status=%d, want 400", rr.Code)
	}
	var got map[string]any
	if err := json.Unmarshal(rr.Body.Bytes(), &got); err != nil {
		t.Fatalf("response is not JSON: %v", err)
	}
	e, ok := got["error"].(map[string]any)
	if !ok {
		t.Fatalf("missing error object: %#v", got)
	}
	if e["code"] != "invalid_json" {
		t.Fatalf("code=%v, want invalid_json", e["code"])
	}
	if !strings.Contains(e["message"].(string), "trailing") {
		t.Fatalf("message=%v, want decoder detail", e["message"])
	}
}
