package httpapi

import (
 "net/http"
 "net/http/httptest"
 "testing"

 "github.com/samanramezani1377-hub/crawler-numpo/internal/config"
)

func TestServeHTTPRequiresAPIKey(t *testing.T) {
 s := &Server{Cfg: config.Config{APIKey:"secret", AllowAnonymousAPI:false}}
 req := httptest.NewRequest(http.MethodGet, "/api/v1/discovery/jobs", nil)
 rr := httptest.NewRecorder()
 s.ServeHTTP(rr, req)
 if rr.Code != http.StatusUnauthorized { t.Fatalf("status=%d, want 401", rr.Code) }
}

func TestServeHTTPRejectsWrongAPIKey(t *testing.T) {
 s := &Server{Cfg: config.Config{APIKey:"secret", AllowAnonymousAPI:false}}
 req := httptest.NewRequest(http.MethodGet, "/api/v1/discovery/jobs", nil)
 req.Header.Set("Authorization", "Bearer wrong")
 rr := httptest.NewRecorder()
 s.ServeHTTP(rr, req)
 if rr.Code != http.StatusUnauthorized { t.Fatalf("status=%d, want 401", rr.Code) }
}
