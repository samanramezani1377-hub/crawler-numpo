package policy
import "testing"
func TestLocalhostBlocked(t *testing.T){if ValidateURL("http://127.0.0.1/")==nil{t.Fatal("expected block")}}
func TestSchemeBlocked(t *testing.T){if ValidateURL("file:///etc/passwd")==nil{t.Fatal("expected block")}}
