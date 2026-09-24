package target

import (
	"net/url"
	"regexp"
	"strings"
)

type Config struct {
	Technologies  []string
	Countries     []string
	HasPublicPhone *bool
}

func FromMap(v map[string]any) Config {
	c := Config{}
	if xs, ok := v["technologies"].([]any); ok {
		for _, x := range xs { if s, ok := x.(string); ok && strings.TrimSpace(s) != "" { c.Technologies = append(c.Technologies, strings.ToLower(strings.TrimSpace(s))) } }
	}
	if xs, ok := v["technologies"].([]string); ok {
		for _, s := range xs { if strings.TrimSpace(s) != "" { c.Technologies = append(c.Technologies, strings.ToLower(strings.TrimSpace(s))) } }
	}
	if xs, ok := v["country"].([]any); ok {
		for _, x := range xs { if s, ok := x.(string); ok && strings.TrimSpace(s) != "" { c.Countries = append(c.Countries, strings.ToLower(strings.TrimSpace(s))) } }
	}
	if xs, ok := v["countries"].([]any); ok {
		for _, x := range xs { if s, ok := x.(string); ok && strings.TrimSpace(s) != "" { c.Countries = append(c.Countries, strings.ToLower(strings.TrimSpace(s))) } }
	}
	if b, ok := v["has_public_phone"].(bool); ok { c.HasPublicPhone = &b }
	return c
}

func (c Config) Empty() bool {
	return len(c.Technologies) == 0 && len(c.Countries) == 0 && c.HasPublicPhone == nil
}

func (c Config) Match(pageURL, body string, technologies []string, hasPublicPhone bool) bool {
	for _, wanted := range c.Technologies {
		found := false
		for _, got := range technologies {
			if strings.EqualFold(strings.TrimSpace(got), wanted) { found = true; break }
		}
		if !found { return false }
	}
	if c.HasPublicPhone != nil && *c.HasPublicPhone != hasPublicPhone { return false }
	if len(c.Countries) > 0 {
		country := inferCountry(pageURL, body)
		if country == "" { return false }
		found := false
		for _, wanted := range c.Countries { if strings.EqualFold(country, wanted) { found = true; break } }
		if !found { return false }
	}
	return true
}

// Country is a routing signal, not proof of the business's legal location.
// It uses ccTLD first and then the document's html[lang] value.
func inferCountry(rawURL, body string) string {
	if u, err := url.Parse(rawURL); err == nil {
		host := strings.ToLower(u.Hostname())
		parts := strings.Split(host, ".")
		if len(parts) >= 2 {
			tld := parts[len(parts)-1]
			if len(tld) == 2 && tld != "uk" { return tld }
			if len(parts) >= 3 && tld == "uk" && parts[len(parts)-2] == "co" { return "gb" }
		}
	}
	re := regexp.MustCompile(`(?i)<html[^>]*lang=["']([a-z]{2})(?:-[a-z]{2})?["']`)
	if m := re.FindStringSubmatch(body); len(m) == 2 { return strings.ToLower(m[1]) }
	return ""
}
