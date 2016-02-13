go to https://dev.twitter.com/apps/.../oauth?nid=...
request query:
track=e&language=it
click buttom
copy cURL command ($cURL)
execute in bash shell
$cURL | sed -n 's/.*\(text\".*\"\,\"source\).*/\1/p'  | awk '{print substr($0, 8, length($0) - 16)}'  > destination.txt
where destination.txt should be a non existing file(or empty)