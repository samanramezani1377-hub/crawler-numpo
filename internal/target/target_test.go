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
}

func TestEmptyTarget(t *testing.T) {
	if !(Config{}).Match("https://example.com", "", nil, false) {
		t.Fatal("empty target should match")
	}
}
