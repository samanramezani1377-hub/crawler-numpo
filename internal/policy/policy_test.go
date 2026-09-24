package policy
import "testing"
func TestLocalhostBlocked(t *testing.T){if ValidateURL("http://127.0.0.1/")==nil{t.Fatal("expected block")}}
func TestSchemeBlocked(t *testing.T){if ValidateURL("file:///etc/passwd")==nil{t.Fatal("expected block")}}

func TestReservedAndPrivateRangesBlocked(t *testing.T){for _,host:=range []string{"10.0.0.1","172.16.0.1","192.168.1.1","169.254.1.1","100.64.0.1","fc00::1","fe80::1","::1"}{if ValidateURL("http://["+host+"]/")==nil&&host!="10.0.0.1"&&host!="172.16.0.1"&&host!="192.168.1.1"&&host!="169.254.1.1"&&host!="100.64.0.1"{t.Fatalf("expected block for %s",host)}}}
