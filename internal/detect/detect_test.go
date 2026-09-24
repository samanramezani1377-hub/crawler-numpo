package detect

import "testing"

func TestContactsAndSocialExtraction(t *testing.T){
 body:=`<html><head><title>Example Shop</title></head><body>
 <a href="mailto:Sales@Example.com">email</a>
 <div>+98 912 123 4567</div>
 <a href="https://instagram.com/example">Instagram</a>
 <a href="https://t.me/example">Telegram</a>
 </body></html>`
 c:=Contacts(body,"https://example.com/contact")
 if len(c)!=2{t.Fatalf("expected 2 contacts, got %d",len(c))}
 s:=Social(body,"https://example.com/contact")
 if len(s)!=2{t.Fatalf("expected 2 social profiles, got %d",len(s))}
}

func TestClassifyPage(t *testing.T){
 got:=ClassifyPage("<html>تماس با ما</html>","https://example.com/contact","Contact")
 found:=false
 for _,v:=range got{if v.Class=="contact"{found=true}}
 if !found{t.Fatal("expected contact classification")}
}

func TestBusinessExtraction(t *testing.T){
 got:=Business("<div>آدرس: تهران، خیابان نمونه، پلاک ۱</div>","https://example.com","Example Shop")
 if len(got)!=1||got[0].Name!="Example Shop"||got[0].Address==""{t.Fatalf("unexpected business extraction: %#v",got)}
}
