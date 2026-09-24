package model

import "time"

type DiscoveryJob struct { ID string `json:"job_id"`; ProjectID string `json:"project_id"`; Mode string `json:"mode"`; Status string `json:"status"`; CreatedAt time.Time `json:"created_at"`; UpdatedAt time.Time `json:"updated_at"` }
type Candidate struct { ID string `json:"id"`; DiscoveryJobID string `json:"discovery_job_id"`; URL string `json:"url"`; NormalizedURL string `json:"normalized_url"`; NormalizedDomain string `json:"normalized_domain"`; NormalizedHost string `json:"normalized_host"`; SourceType string `json:"source_type"`; ParentURL string `json:"parent_url,omitempty"`; Priority int `json:"priority"`; Confidence float64 `json:"confidence"`; Status string `json:"status"`; AttemptCount int `json:"attempt_count"`; LastError string `json:"last_error,omitempty"`; DiscoveredAt time.Time `json:"discovered_at"` }
type ProbeResult struct { Host string `json:"host"`; Status string `json:"status"`; DNSStatus string `json:"dns_status,omitempty"`; HTTPStatus int `json:"http_status,omitempty"`; HTTPSStatus int `json:"https_status,omitempty"`; RedirectTarget string `json:"redirect_target,omitempty"`; ResponseTimeMS int64 `json:"response_time_ms,omitempty"`; ErrorCode string `json:"error_code,omitempty"`; ErrorMessage string `json:"error_message,omitempty"` }

type Technology struct { Name string; Version string; Confidence float64; Evidence []string; URL string }
type Contact struct { Type string; Value string; NormalizedValue string; URL string }
type PageClassification struct { Class string; Confidence float64; Evidence []string; URL string }
type BusinessProfile struct { Name string; Description string; Address string; Confidence float64; URL string }
type SocialProfile struct { Network string; URL string; NormalizedURL string; Confidence float64; SourceURL string }
