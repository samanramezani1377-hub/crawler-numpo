package policy

import (
 "context"
 "net"
 "testing"
 "github.com/samanramezani1377-hub/crawler-numpo/internal/urlnorm"
)

func TestLocalhostBlocked(t *testing.T){if ValidateURL("http://127.0.0.1/")==nil{t.Fatal("expected block")}}
func TestSchemeBlocked(t *testing.T){if ValidateURL("file:///etc/passwd")==nil{t.Fatal("expected block")}}
func TestReservedAndPrivateRangesBlocked(t *testing.T){for _,host:=range []string{"10.0.0.1","172.16.0.1","192.168.1.1","169.254.1.1","100.64.0.1","fc00::1","fe80::1","::1","ff02::1"}{if ValidateURL("http://["+host+"]/")==nil{t.Fatalf("expected block for %s",host)}}}
func TestIsPrivateHostCoversIPv6(t *testing.T){for _,h:=range []string{"::1","fc00::1","fd12::1","fe80::1","ff02::1","100.64.0.1"}{if !urlnorm.IsPrivateHost(h){t.Fatalf("expected private/reserved: %s",h)}}}
func TestSafeTransportRejectsPrivateDial(t *testing.T){
 tr:=SafeTransport()
 ctx,cancel:=context.WithCancel(context.Background());defer cancel()
 _,e:=tr.DialContext(ctx,"tcp","127.0.0.1:80");if e==nil{t.Fatal("expected private dial rejection")}
}
func TestValidateHostRejectsLocalNames(t *testing.T){for _,h:=range []string{"localhost","x.localhost","127.0.0.1"}{if ValidateHost(h)==nil{t.Fatalf("expected rejection for %s",h)}}}
func TestDocumentationNetworkIsNotPrivate(t *testing.T){if urlnorm.IsPrivateHost(net.ParseIP("192.0.2.1").String()){t.Fatal("192.0.2.1 should not be classified as private")}}
