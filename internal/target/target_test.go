package target

import "testing"

func TestMatchTechnologiesAndPhone(t *testing.T) {
	phone := true
	c := Config{Technologies: []string{"wordpress", "woocommerce"}, HasPublicPhone: &phone}
	if !c.Match("https://shop.example.com", "<html>", []string{"WordPress", "WooCommerce"}, true) {
		t.Fatal("expected match")
	}
	if c.Match("https://shop.example.com", "<html>", []string{"WordPress"}, true) {
		t.Fatal("missing technology should not match")
	}
	if c.Match("https://shop.example.com", "<html>", []string{"WordPress", "WooCommerce"}, false) {
		t.Fatal("phone target should not match")
	}
}

func TestMatchCountryFromSignals(t *testing.T) {
	c := Config{Countries: []string{"ir"}}
	if !c.Match("https://example.ir", "", nil, false) {
		t.Fatal("expected ccTLD match")
	}
	if !c.Match("https://example.com", `<html lang="fa">`, nil, false) {
		t.Fatal("expected language signal match")
	}
	if c.Match("https://example.com", `<html lang="en">`, nil, false) {
		t.Fatal("unexpected country match")
	}

	cases := []struct {
		language string
		country  string
	}{
		{language: "nl", country: "nl"},
		{language: "de", country: "de"},
		{language: "fr", country: "fr"},
		{language: "tr", country: "tr"},
		{language: "en-US", country: "us"},
	}
	for _, tc := range cases {
		if got := inferCountry("https://example.com", `<html lang="`+tc.language+`">`); got != tc.country {
			t.Fatalf("language %q: expected country %q, got %q", tc.language, tc.country, got)
		}
	}
}

func TestEmptyTarget(t *testing.T) {
	if !(Config{}).Match("https://example.com", "", nil, false) {
		t.Fatal("empty target should match")
	}
}
