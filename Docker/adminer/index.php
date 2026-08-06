<?php
/** Adminer - Compact database management
* @link https://www.adminer.org/
* @author Jakub Vrana, https://www.vrana.cz/
* @copyright 2007 Jakub Vrana
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @version 5.5.1
*/namespace
Adminer;const
VERSION="5.5.1";error_reporting(24575);set_error_handler(function($Gc,$Ic){return!!preg_match('~^Undefined (array key|offset|index)~',$Ic);},E_WARNING|E_NOTICE);$gd=!preg_match('~^(unsafe_raw)?$~',ini_get("filter.default"));if($gd||ini_get("filter.default_flags")){foreach(array('_GET','_POST','_COOKIE','_SERVER')as$X){$Kj=filter_input_array(constant("INPUT$X"),FILTER_UNSAFE_RAW);if($Kj)$$X=$Kj;}}if(function_exists("mb_internal_encoding"))mb_internal_encoding("8bit");function
connection($g=null){return($g?:Db::$instance);}function
adminer(){return
Adminer::$instance;}function
driver(){return
Driver::$instance;}function
connect(){$Hb=adminer()->credentials();$J=Driver::connect($Hb[0],$Hb[1],$Hb[2]);return(is_object($J)?$J:null);}function
idf_unescape($t){if(!preg_match('~^[`\'"[]~',$t))return$t;$Qe=substr($t,-1);return
str_replace($Qe.$Qe,$Qe,substr($t,1,-1));}function
q($Q){return
connection()->quote($Q);}function
idx($xa,$w,$k=null){return($xa&&array_key_exists($w,$xa)?$xa[$w]:$k);}function
number($X){return
preg_replace('~[^0-9]+~','',$X);}function
number_type(){return'((?<!o)int(?!er)|numeric|real|float|double|decimal|money)';}function
remove_slashes(array$ck,$gd=false){$J=array();foreach($ck
as$w=>$X)$J[stripslashes($w)]=(is_array($X)?remove_slashes($X,$gd):($gd?$X:stripslashes($X)));return$J;}function
bracket_escape($t,$Fa=false){static$uj=array(':'=>':1',']'=>':2','['=>':3','"'=>':4');return
strtr($t,($Fa?array_flip($uj):$uj));}function
min_version($fk,$if="",$g=null){$g=connection($g);$li=$g->server_info;if($if&&preg_match('~([\d.]+)-MariaDB~',$li,$A)){$li=$A[1];$fk=$if;}return$fk&&version_compare($li,$fk)>=0;}function
charset(Db$f){return(min_version("5.5.3",0,$f)?"utf8mb4":"utf8");}function
ini_set($rg,$Y){return(function_exists('ini_set')?\ini_set($rg,$Y):false);}function
ini_bool($re){$X=ini_get($re);return(preg_match('~^(on|true|yes)$~i',$X)||(int)$X);}function
ini_bytes($re){$X=ini_get($re);switch(strtolower(substr($X,-1))){case'g':$X=(int)$X*1024;case'm':$X=(int)$X*1024;case'k':$X=(int)$X*1024;}return$X;}function
sid(){static$J;if($J===null)$J=(SID&&!($_COOKIE&&ini_bool("session.use_cookies")));return$J;}function
set_password($ek,$N,$V,$F){$_SESSION["pwds"][$ek][$N][$V]=($_COOKIE["adminer_key"]&&is_string($F)?array(encrypt_string($F,$_COOKIE["adminer_key"])):$F);}function
get_password(){$J=get_session("pwds");if(is_array($J))$J=($_COOKIE["adminer_key"]?decrypt_string($J[0],$_COOKIE["adminer_key"]):false);return$J;}function
get_val($H,$m=0,$vb=null){$vb=connection($vb);$I=$vb->query($H);if(!is_object($I))return
false;$K=$I->fetch_row();return($K?$K[$m]:false);}function
get_vals($H,$d=0){$J=array();$I=connection()->query($H);if(is_object($I)){while($K=$I->fetch_row())$J[]=$K[$d];}return$J;}function
get_key_vals($H,$g=null,$oi=true){$g=connection($g);$J=array();$I=$g->query($H);if(is_object($I)){while($K=$I->fetch_row()){if($oi)$J[$K[0]]=$K[1];else$J[]=$K[0];}}return$J;}function
get_rows($H,$g=null,$l="<p class='error'>"){$vb=connection($g);$J=array();$I=$vb->query($H);if(is_object($I)){while($K=$I->fetch_assoc())$J[]=$K;}elseif(!$I&&!$g&&$l&&(defined('Adminer\PAGE_HEADER')||$l=="-- "))echo$l.error()."\n";return$J;}function
unique_array($K,array$v){foreach($v
as$u){if(preg_match("~PRIMARY|UNIQUE~",$u["type"])&&!$u["partial"]){$J=array();foreach($u["columns"]as$w){if(!isset($K[$w]))continue
2;$J[$w]=$K[$w];}return$J;}}}function
escape_key($w){if(preg_match('(^([\w(]+)('.str_replace("_",".*",preg_quote(idf_escape("_"))).')([ \w)]+)$)',$w,$A))return$A[1].idf_escape(idf_unescape($A[2])).$A[3];return
idf_escape($w);}function
where(array$Z,array$n=array()){$J=array();foreach((array)$Z["where"]as$w=>$X){$w=bracket_escape($w,true);$d=escape_key($w);$m=idx($n,$w,array());$cd=$m["type"];$J[]=$d.(JUSH=="sql"&&$cd=="json"?" = CAST(".q($X)." AS JSON)":(JUSH=="pgsql"&&preg_match('~^jsonb?$~',$m["full_type"])?"::jsonb = ".q($X)."::jsonb":(JUSH=="sql"&&is_numeric($X)&&preg_match('~\.~',$X)?" LIKE ".q($X):(JUSH=="mssql"&&strpos($cd,"datetime")===false?" LIKE ".q(preg_replace('~[_%[]~','[\0]',$X)):" = ".unconvert_field($m,q($X))))));if(JUSH=="sql"&&preg_match('~char|text~',$cd)&&preg_match("~[^ -@]~",$X))$J[]="$d = ".q($X)." COLLATE ".charset(connection())."_bin";}foreach((array)$Z["null"]as$w)$J[]=escape_key($w)." IS NULL";return
implode(" AND ",$J);}function
where_check($X,array$n=array()){parse_str($X,$Za);remove_slashes(array(&$Za));return
where($Za,$n);}function
where_link($r,$d,$Y,$og="="){return"&where%5B$r%5D%5Bcol%5D=".urlencode($d)."&where%5B$r%5D%5Bop%5D=".urlencode(($Y!==null?$og:"IS NULL"))."&where%5B$r%5D%5Bval%5D=".urlencode($Y);}function
convert_fields(array$e,array$n,array$M=array()){$J="";foreach($e
as$w=>$X){if($M&&!in_array(idf_escape($w),$M))continue;$ya=convert_field($n[$w]);if($ya)$J
.=", $ya AS ".idf_escape($w);}return$J;}function
cookie_path(){return
strtr(preg_replace('~\?.*~','',$_SERVER["REQUEST_URI"]),array(";"=>"%3B",","=>"%2C"));}function
cookie($B,$Y,$Ze=2592000){header("Set-Cookie: $B=".rawurlencode($Y).($Ze?"; expires=".gmdate("D, d M Y H:i:s",time()+$Ze)." GMT":"")."; path=".cookie_path().(HTTPS?"; secure":"")."; HttpOnly; SameSite=lax",false);}function
get_url($Rj,$Bb){$J=@file_get_contents($Rj,false,$Bb);if(function_exists('http_get_last_response_headers'))$http_response_header=http_get_last_response_headers();return
array($J,(preg_match('~^HTTP/[\d.]+ (\d+)~',$http_response_header[0],$A)?$A[1]:''),);}function
get_settings($Db){parse_str($_COOKIE[$Db],$pi);return$pi;}function
get_setting($w,$Db="adminer_settings",$k=null){return
idx(get_settings($Db),$w,$k);}function
save_settings(array$pi,$Db="adminer_settings"){$Y=http_build_query($pi+get_settings($Db));cookie($Db,$Y);$_COOKIE[$Db]=$Y;}function
restart_session(){if(!ini_bool("session.use_cookies")&&(!function_exists('session_status')||session_status()==1))session_start();}function
stop_session($pd=false){$Uj=ini_bool("session.use_cookies");if(!$Uj||$pd){session_write_close();if($Uj&&ini_set("session.use_cookies",'0')===false)session_start();}}function&get_session($w){return$_SESSION[$w][DRIVER][SERVER][$_GET["username"]];}function
set_session($w,$X){$_SESSION[$w][DRIVER][SERVER][$_GET["username"]]=$X;}function
auth_url($ek,$N,$V,$j=null){$Qj=remove_from_uri(implode("|",array_keys(SqlDriver::$drivers))."|username|ext|".($j!==null?"db|":"").($ek=='mssql'||$ek=='pgsql'?"":"ns|").session_name());preg_match('~([^?]*)\??(.*)~',$Qj,$A);return"$A[1]?".(sid()?SID."&":"").($ek!="server"||$N!=""?urlencode($ek)."=".urlencode($N)."&":"").($_GET["ext"]?"ext=".urlencode($_GET["ext"])."&":"")."username=".urlencode($V).($j!=""?"&db=".urlencode($j):"").($A[2]?"&$A[2]":"");}function
is_ajax(){return($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");}function
redirect($_,$yf=null){if($yf!==null){restart_session();$_SESSION["messages"][preg_replace('~^[^?]*~','',($_!==null?$_:$_SERVER["REQUEST_URI"]))][]=$yf;}if($_!==null){if($_=="")$_=".";header("Location: $_");exit;}}function
query_redirect($H,$_,$yf,$zh=true,$Nc=true,$Wc=false,$hj=""){if($Nc){$Di=microtime(true);$Wc=!connection()->query($H);$hj=format_time($Di);}$yi=($H?adminer()->messageQuery($H,$hj,$Wc):"");if($Wc){adminer()->error
.=error().$yi.script("messagesPrint();")."<br>";return
false;}if($zh)redirect($_,$yf.$yi);return
true;}class
Queries{static$queries=array();static$start=0;}function
queries($H){if(!Queries::$start)Queries::$start=microtime(true);Queries::$queries[]=(driver()->delimiter!=';'?$H:(preg_match('~;$~',$H)?"DELIMITER ;;\n$H;\nDELIMITER ":$H).";");return
connection()->query($H);}function
apply_queries($H,array$T,$Jc='Adminer\table'){foreach($T
as$R){if(!queries("$H ".$Jc($R)))return
false;}return
true;}function
queries_redirect($_,$yf,$zh){$uh=implode("\n",Queries::$queries);$hj=format_time(Queries::$start);return
query_redirect($uh,$_,$yf,$zh,false,!$zh,$hj);}function
format_time($Di){return
sprintf('%.3f s',max(0,microtime(true)-$Di));}function
relative_uri(){return
str_replace(":","%3a",preg_replace('~^[^?]*/([^?]*)~','\1',$_SERVER["REQUEST_URI"]));}function
remove_from_uri($Kg=""){return
substr(preg_replace("~(?<=[?&])($Kg".(SID?"":"|".session_name()).")=[^&]*&~",'',relative_uri()."&"),0,-1);}function
get_file($w,$Tb=false,$Zb=""){$ed=$_FILES[$w];if(!$ed)return
null;foreach($ed
as$w=>$X)$ed[$w]=(array)$X;$J='';foreach($ed["error"]as$w=>$l){if($l)return$l;$B=$ed["name"][$w];$pj=$ed["tmp_name"][$w];$_b=file_get_contents($Tb&&preg_match('~\.gz$~',$B)?"compress.zlib://$pj":$pj);if($Tb){$Di=substr($_b,0,3);if(function_exists("iconv")&&preg_match("~^\xFE\xFF|^\xFF\xFE~",$Di))$_b=iconv("utf-16","utf-8",$_b);elseif($Di=="\xEF\xBB\xBF")$_b=substr($_b,3);}$J
.=$_b;if($Zb)$J
.=(preg_match("($Zb\\s*\$)",$_b)?"":$Zb)."\n\n";}return$J;}function
upload_error($l){$sf=($l==UPLOAD_ERR_INI_SIZE?ini_get("upload_max_filesize"):0);return($l?'Unable to upload a file.'.($sf?" ".sprintf('Maximum allowed file size is %sB.',$sf):""):'File does not exist.');}function
repeat_pattern($Xg,$x){return
str_repeat("$Xg{0,65535}",$x/65535)."$Xg{0,".($x%65535)."}";}function
is_utf8($X){return(preg_match('~~u',$X)&&!preg_match('~[\0-\x8\xB\xC\xE-\x1F]~',$X));}function
format_number($X){return
strtr(number_format($X,0,".",','),preg_split('~~u','0123456789',-1,PREG_SPLIT_NO_EMPTY));}function
friendly_url($X){return
preg_replace('~\W~i','-',$X);}function
table_status1($R,$Xc=false){$J=table_status($R,$Xc);return($J?reset($J):array("Name"=>$R));}function
column_foreign_keys($R){$J=array();foreach(adminer()->foreignKeys($R)as$o){foreach($o["source"]as$X)$J[$X][]=$o;}return$J;}function
fields_from_edit(){$J=array();foreach((array)$_POST["field_keys"]as$w=>$X){if($X!=""){$X=bracket_escape($X);$_POST["function"][$X]=$_POST["field_funs"][$w];$_POST["fields"][$X]=$_POST["field_vals"][$w];}}foreach((array)$_POST["fields"]as$w=>$X){$B=bracket_escape($w,true);$J[$B]=array("field"=>$B,"privileges"=>array("insert"=>1,"update"=>1,"where"=>1,"order"=>1),"null"=>1,"auto_increment"=>($w==driver()->primary),);}return$J;}function
dump_headers($Xd,$Lf=false){$J=adminer()->dumpHeaders($Xd,$Lf);$Gg=$_POST["output"];if($Gg!="text")header("Content-Disposition: attachment; filename=".adminer()->dumpFilename($Xd).".$J".($Gg!="file"&&preg_match('~^[0-9a-z]+$~',$Gg)?".$Gg":""));session_write_close();if(!ob_get_level())ob_start(null,4096);ob_flush();flush();return$J;}function
dump_csv(array$K){$Cj=$_POST["format"]=="tsv";foreach($K
as$w=>$X){if(preg_match('~["\n]|^0[^.]|\.\d*0$|'.($Cj?'\t':'[,;]|^$').'~',$X))$K[$w]='"'.str_replace('"','""',$X).'"';}echo
implode(($_POST["format"]=="csv"?",":($Cj?"\t":";")),$K)."\r\n";}function
apply_sql_function($q,$d){return($q?($q=="unixepoch"?"DATETIME($d, '$q')":($q=="count distinct"?"COUNT(DISTINCT ":strtoupper("$q("))."$d)"):$d);}function
get_temp_dir(){return
ini_get("upload_tmp_dir")?:sys_get_temp_dir();}function
file_open_lock($fd){if(is_link($fd))return;$p=@fopen($fd,"c+");if(!$p)return;@chmod($fd,0660);if(!flock($p,LOCK_EX)){fclose($p);return;}return$p;}function
file_write_unlock($p,$Nb){rewind($p);fwrite($p,$Nb);ftruncate($p,strlen($Nb));file_unlock($p);}function
file_unlock($p){flock($p,LOCK_UN);fclose($p);}function
first(array$xa){return
reset($xa);}function
password_file($h){$fd=get_temp_dir()."/adminer.key";if(!$h&&!file_exists($fd))return'';$p=file_open_lock($fd);if(!$p)return'';$J=stream_get_contents($p);if(!$J){$J=rand_string();file_write_unlock($p,$J);}else
file_unlock($p);return$J;}function
rand_string(){return(function_exists('random_bytes')?bin2hex(random_bytes(16)):md5(uniqid(strval(mt_rand()),true)));}function
select_value($X,$z,array$m,$gj){if(is_array($X)){$J="";if(array_filter($X,'is_array')==array_values($X)){$Ke=array();foreach($X
as$W)$Ke+=array_fill_keys(array_keys($W),null);foreach(array_keys($Ke)as$Ie)$J
.="<th>".h($Ie);foreach($X
as$W){$J
.="<tr>";foreach(array_merge($Ke,$W)as$Yj)$J
.="<td>".select_value($Yj,$z,$m,$gj);}}else{foreach($X
as$Ie=>$W)$J
.="<tr>".($X!=array_values($X)?"<th>".h($Ie):"")."<td>".select_value($W,$z,$m,$gj);}return"<table>$J</table>";}if(!$z)$z=adminer()->selectLink($X,$m);if($z===null){if(is_mail($X))$z="mailto:$X";if(is_url($X))$z=$X;}$X=driver()->value($X,$m);$J=adminer()->editVal($X,$m);if($J!==null){if(!is_utf8($J))$J="\0";elseif($gj!=""&&is_shortable($m))$J=shorten_utf8($J,max(0,+$gj));else$J=h($J);}return
adminer()->selectVal($J,$z,$m,$X);}function
is_blob(array$m){return
preg_match('~blob|bytea|raw|file~',$m["type"])&&!in_array($m["type"],idx(driver()->structuredTypes(),'User types',array()));}function
is_mail($xc){$za='[-a-z0-9!#$%&\'*+/=?^_`{|}~]';$lc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';$Xg="$za+(\\.$za+)*@($lc?\\.)+$lc";return
is_string($xc)&&preg_match("(^$Xg(,\\s*$Xg)*\$)i",$xc);}function
is_url($Q){$lc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';return
preg_match("~^((https?):)?//($lc?\\.)+$lc(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i",$Q);}function
is_shortable(array$m){return!preg_match('~'.number_type().'|date|time|year~',$m["type"]);}function
host_port($N){return(preg_match('~^(:([^:].*)|(\[(.+)\]|(([^:]+://)?[^:]+))(:(\d+))?)$~',$N,$A)?array($A[4].$A[5],$A[2].$A[8]):array($N,''));}function
count_rows($R,array$Z,$Ae,array$Cd){$H=" FROM ".table($R).($Z?" WHERE ".implode(" AND ",$Z):"");return($Ae&&(JUSH=="sql"||count($Cd)==1)?"SELECT COUNT(DISTINCT ".implode(", ",$Cd).")$H":"SELECT COUNT(*)".($Ae?" FROM (SELECT 1$H GROUP BY ".implode(", ",$Cd).") x":$H));}function
slow_query($H){$j=adminer()->database();$ij=adminer()->queryTimeout();$ti=driver()->slowQuery($H,$ij);$g=null;if(!$ti&&support("kill")){$g=connect();if($g&&($j==""||$g->select_db($j))){$Le=get_val(connection_id(),0,$g);echo
script("const timeout = setTimeout(() => { ajax('".js_escape(ME)."script=kill', function () {}, 'kill=$Le&token=".get_token()."'); }, 1000 * $ij);");}}ob_flush();flush();$J=@get_key_vals(($ti?:$H),$g,false);if($g){echo
script("clearTimeout(timeout);");ob_flush();flush();}return$J;}function
get_token(){$xh=rand(1,1e6);return($xh^$_SESSION["token"]).":$xh";}function
verify_token(){list($qj,$xh)=explode(":",$_POST["token"]);return($xh^$_SESSION["token"])==$qj&&in_array($_SERVER["HTTP_SEC_FETCH_SITE"],array("","same-origin"));}function
compress_alphabet(){return
strtr(implode(range('"','~')),"'\\","!\n");}function
decompress_string($Q){$ua=array_flip(str_split(compress_alphabet()));$x=strlen($Q);$bk=($x?13*($x-1)/2-$ua[$Q[0]]:0);$La="";$Jh=0;$Kh=0;for($r=1;$r<$x;$r+=2){$Jh=($Jh<<13)+$ua[$Q[$r]]*93+$ua[$Q[$r+1]];$Kh+=13;while($Kh>=8&&$bk>=8){$Kh-=8;$bk-=8;$La
.=chr($Jh>>$Kh);$Jh&=(1<<$Kh)-1;}}if($La=="")return"";return(function_exists('gzinflate')?gzinflate($La):inflate($La));}function
inflate($La){$We=array(3,4,5,6,7,8,9,10,11,13,15,17,19,23,27,31,35,43,51,59,67,83,99,115,131,163,195,227,258);$Xe=array(0,0,0,0,0,0,0,0,1,1,1,1,2,2,2,2,3,3,3,3,4,4,4,4,5,5,5,5,0);$fc=array(1,2,3,4,5,7,9,13,17,25,33,49,65,97,129,193,257,385,513,769,1025,1537,2049,3073,4097,6145,8193,12289,16385,24577);$hc=array(0,0,0,0,1,1,2,2,3,3,4,4,5,5,6,6,7,7,8,8,9,9,10,10,11,11,12,12,13,13);$J="";$G=0;do{$hd=inflate_bits($La,$G,1);$U=inflate_bits($La,$G,2);if(!$U){$G=($G+7)&~7;$x=inflate_bits($La,$G,16);$G+=16;$J
.=substr($La,$G>>3,$x);$G+=$x<<3;}else{if($U==1){$df=array_merge(array_fill(0,144,8),array_fill(0,112,9),array_fill(0,24,7),array_fill(0,8,8));$ic=array_fill(0,30,5);}else{$cf=inflate_bits($La,$G,5)+257;$gc=inflate_bits($La,$G,5)+1;$ug=array(16,17,18,0,8,7,9,6,10,5,11,4,12,3,13,2,14,1,15);$Df=array_fill(0,19,0);$Cf=inflate_bits($La,$G,4)+4;for($r=0;$r<$Cf;$r++)$Df[$ug[$r]]=inflate_bits($La,$G,3);$Ef=inflate_table($Df);$Ye=array();while(count($Ye)<$cf+$gc){$Ni=inflate_symbol($La,$G,$Ef);if($Ni==16)$Ye=array_merge($Ye,array_fill(0,inflate_bits($La,$G,2)+3,end($Ye)));elseif($Ni==17)$Ye=array_merge($Ye,array_fill(0,inflate_bits($La,$G,3)+3,0));elseif($Ni==18)$Ye=array_merge($Ye,array_fill(0,inflate_bits($La,$G,7)+11,0));else$Ye[]=$Ni;}$df=array_slice($Ye,0,$cf);$ic=array_slice($Ye,$cf);}$ef=inflate_table($df);$kc=inflate_table($ic);while(($Ni=inflate_symbol($La,$G,$ef))!=256){if($Ni<256)$J
.=chr($Ni);else{$x=$We[$Ni-257]+inflate_bits($La,$G,$Xe[$Ni-257]);$jc=inflate_symbol($La,$G,$kc);$C=strlen($J)-$fc[$jc]-inflate_bits($La,$G,$hc[$jc]);for($r=0;$r<$x;$r++)$J
.=$J[$C+$r];}}}}while(!$hd);return$J;}function
inflate_bits($La,&$G,$Eb){$J=0;for($r=0;$r<$Eb;$r++){$J+=((ord($La[$G>>3])>>($G&7))&1)<<$r;$G++;}return$J;}function
inflate_table(array$Ye){$R=array();$ib=0;for($Ma=1;$Ma<=max($Ye);$Ma++){foreach($Ye
as$Ni=>$x){if($x==$Ma){$R[$Ma][$ib]=$Ni;$ib++;}}$ib<<=1;}return$R;}function
inflate_symbol($La,&$G,array$R){$ib=0;$Ma=0;do{$ib=($ib<<1)+inflate_bits($La,$G,1);$Ma++;}while(!isset($R[$Ma][$ib]));return$R[$Ma][$ib];}function
script($vi,$tj="\n"){return"<script".nonce().">$vi</script>$tj";}function
script_src($Rj,$Wb=false){return"<script src='".h($Rj)."'".nonce().($Wb?" defer":"")."></script>\n";}function
nonce(){return' nonce="'.get_nonce().'"';}function
input_hidden($B,$Y=""){return"<input type='hidden' name='".h($B)."' value='".h($Y)."'>\n";}function
input_token(){return
input_hidden("token",get_token());}function
target_blank(){return' target="_blank" rel="noreferrer noopener"';}function
h($Q){return
str_replace(array('&','<','"',"'","\0"),array('&amp;','&lt;','&quot;','&#039;','&#0;'),$Q);}function
nl_br($Q){return
str_replace("\n","<br>",$Q);}function
checkbox($B,$Y,$cb,$Ne="",$ng="",$gb="",$Pe=""){$J="<input type='checkbox' name='$B' value='".h($Y)."'".($cb?" checked":"").($Pe?" aria-labelledby='$Pe'":"").">".($ng?script("qsl('input').onclick = function () { $ng };",""):"");return($Ne!=""||$gb?"<label".($gb?" class='$gb'":"").">$J".h($Ne)."</label>":$J);}function
optionlist($sg,$di=null,$Vj=false){$J="";foreach($sg
as$Ie=>$W){$tg=array($Ie=>$W);if(is_array($W)){$J
.='<optgroup label="'.h($Ie).'">';$tg=$W;}foreach($tg
as$w=>$X)$J
.='<option'.($Vj||is_string($w)?' value="'.h($w).'"':'').($di!==null&&($Vj||is_string($w)?(string)$w:$X)===$di?' selected':'').'>'.h($X);if(is_array($W))$J
.='</optgroup>';}return$J;}function
html_select($B,array$sg,$Y="",$mg="",$Pe=""){static$Ne=0;$Oe="";if(!$Pe&&substr($sg[""],0,1)=="("){$Ne++;$Pe="label-$Ne";$Oe="<option value='' id='$Pe'>".h($sg[""]);unset($sg[""]);}return"<select name='".h($B)."'".($Pe?" aria-labelledby='$Pe'":"").">".$Oe.optionlist($sg,$Y)."</select>".($mg?script("qsl('select').onchange = function () { $mg };",""):"");}function
html_radios($B,array$sg,$Y="",$hi=""){$J="";foreach($sg
as$w=>$X)$J
.="<label><input type='radio' name='".h($B)."' value='".h($w)."'".($w==$Y?" checked":"").">".h($X)."</label>$hi";return$J;}function
confirm($yf="",$ei="qsl('input')"){return
script("$ei.onclick = () => confirm('".($yf?js_escape($yf):'Are you sure?')."');","");}function
print_fieldset($s,$Ve,$ik=false){echo"<fieldset><legend>","<a href='#fieldset-$s'>$Ve</a>",script("qsl('a').onclick = partial(toggle, 'fieldset-$s');",""),"</legend>","<div id='fieldset-$s'".($ik?"":" class='hidden'").">\n";}function
bold($Oa,$gb=""){return($Oa?" class='active $gb'":($gb?" class='$gb'":""));}function
js_escape($Q){return
addcslashes($Q,"\r\n'\\/");}function
pagination($D,$Kb){return" ".($D==$Kb?$D+1:'<a href="'.h(remove_from_uri("page|next").($D?"&page=$D".($_GET["next"]?"&next=".urlencode($_GET["next"]):""):"")).'">'.($D+1)."</a>");}function
hidden_fields(array$rh,array$be=array(),$ih=''){$J=false;foreach($rh
as$w=>$X){if(!in_array($w,$be)){if(is_array($X))hidden_fields($X,array(),$w);else{$J=true;echo
input_hidden(($ih?$ih."[$w]":$w),$X);}}}return$J;}function
hidden_fields_get(){echo(sid()?input_hidden(session_name(),session_id()):''),(SERVER!==null?input_hidden(DRIVER,SERVER):""),input_hidden("username",$_GET["username"]);}function
file_input($te){$nf="max_file_uploads";$of=ini_get($nf);$Oj="upload_max_filesize";$Pj=ini_get($Oj);return(ini_bool("file_uploads")?$te.script("qsl('input[type=\"file\"]').onchange = partialArg(fileChange, "."$of, '".sprintf('Increase %s.',"$nf = $of")."', ".ini_bytes("upload_max_filesize").", '".sprintf('Increase %s.',"$Oj = $Pj")."')"):'File uploads are disabled.');}function
enum_input($U,$_a,array$m,$Y,$_c=""){preg_match_all("~'((?:[^']|'')*)'~",$m["length"],$lf);$ih=($m["type"]=="enum"?"val-":"");$cb=(is_array($Y)?in_array("null",$Y):$Y===null);$J=($m["null"]&&$ih?"<label><input type='$U'$_a value='null'".($cb?" checked":"")."><i>$_c</i></label>":"");foreach($lf[1]as$X){$X=stripcslashes(str_replace("''","'",$X));$cb=(is_array($Y)?in_array($ih.$X,$Y):$Y===$X);$J
.=" <label><input type='$U'$_a value='".h($ih.$X)."'".($cb?' checked':'').'>'.h(adminer()->editVal($X,$m)).'</label>';}return$J;}function
input(array$m,$Y,$q,$Da=false){$B=h(bracket_escape($m["field"]));echo"<td class='function'>";if(is_array($Y)&&!$q)$q="json";$Ge=($q=="json"||preg_match('~^jsonb?$~',$m["full_type"]));if($Ge&&$Y!=''&&(JUSH!="pgsql"||$m["type"]!="json"))$Y=json_encode(is_array($Y)?$Y:json_decode($Y),128|64|256);$Ih=(JUSH=="mssql"&&$m["auto_increment"]);if($Ih&&!$_POST["save"])$q=null;$yd=(isset($_GET["select"])||$Ih?array("orig"=>'original'):array())+adminer()->editFunctions($m);$Fc=driver()->enumLength($m);if($Fc){$m["type"]="enum";$m["length"]=$Fc;}$_a=" name='fields[$B]".($m["type"]=="enum"||$m["type"]=="set"?"[]":"")."'".($Da?" autofocus":"");echo
driver()->unconvertFunction($m)." ";$R=$_GET["edit"]?:$_GET["select"];if($m["type"]=="enum")echo
h($yd[""])."<td>".adminer()->editInput($R,$m,$_a,$Y);else{$Kd=(in_array($q,$yd)||isset($yd[$q]));echo(count($yd)>1?"<select name='function[$B]'>".optionlist($yd,$q===null||$Kd?$q:"")."</select>".on_help("event.target.value.replace(/^SQL\$/, '')",1).script("qsl('select').onchange = functionChange;",""):h(reset($yd))).'<td>';$te=adminer()->editInput($R,$m,$_a,$Y);if($te!="")echo$te;elseif(preg_match('~bool~',$m["type"]))echo"<input type='hidden'$_a value='0'>"."<input type='checkbox'".(preg_match('~^(1|t|true|y|yes|on)$~i',$Y)?" checked='checked'":"")."$_a value='1'>";elseif($m["type"]=="set")echo
enum_input("checkbox",$_a,$m,(is_string($Y)?explode(",",$Y):$Y));elseif(is_blob($m)&&ini_bool("file_uploads"))echo"<input type='file' name='fields-$B'>";elseif($Ge)echo"<textarea$_a cols='50' rows='12' class='jush-js'>".h($Y).'</textarea>';elseif(($ej=preg_match('~text|lob|memo~i',$m["type"]))||preg_match("~\n~",$Y)){if($ej&&JUSH!="sqlite")$_a
.=" cols='50' rows='12'";else{$L=min(12,substr_count($Y,"\n")+1);$_a
.=" cols='30' rows='$L'";}echo"<textarea$_a>".h($Y).'</textarea>';}else{$Ej=driver()->types();$uf=(!preg_match('~int~',$m["type"])&&preg_match('~^(\d+)(,(\d+))?$~',$m["length"],$A)?((preg_match("~binary~",$m["type"])?2:1)*$A[1]+($A[3]?1:0)+($A[2]&&!$m["unsigned"]?1:0)):($Ej[$m["type"]]?$Ej[$m["type"]]+($m["unsigned"]?0:1):0));if(JUSH=='sql'&&min_version(5.6)&&preg_match('~time~',$m["type"]))$uf+=7;echo"<input".((!$Kd||$q==="")&&preg_match('~(?<!o)int(?!er)~',$m["type"])&&!preg_match('~\[\]~',$m["full_type"])?" type='number'":"")." value='".h($Y)."'".($uf?" data-maxlength='$uf'":"").(preg_match('~char|binary~',$m["type"])&&$uf>20?" size='".($uf>99?60:40)."'":"")."$_a>";}echo
adminer()->editHint($R,$m,$Y);$id=0;foreach($yd
as$w=>$X){if($w===""||!$X)break;$id++;}if($id&&count($yd)>1)echo
script("qsl('td').oninput = partial(skipOriginal, $id);");}}function
process_input(array$m){$t=bracket_escape($m["field"]);$q=idx($_POST["function"],$t);if($q=="orig")return(preg_match('~^CURRENT_TIMESTAMP~i',$m["on_update"])?idf_escape($m["field"]):false);if($q=="NULL")return"NULL";if(is_blob($m)&&ini_bool("file_uploads")){$ed=get_file("fields-$t");if(!is_string($ed))return
false;return
driver()->quoteBinary($ed);}$Y=idx($_POST["fields"],$t);if($Y===null)return
false;if($m["type"]=="enum"||driver()->enumLength($m)){$Y=idx($Y,0);if($Y=="orig"||!$Y)return
false;if($Y=="null")return"NULL";$Y=substr($Y,4);}if($m["auto_increment"]&&$Y=="")return
null;if($m["type"]=="set")$Y=implode(",",(array)$Y);if($q=="json"){$q="";$Y=json_decode($Y,true);if(!is_array($Y))return
false;return$Y;}return
adminer()->processInput($m,$Y,$q);}function
search_tables(){$_GET["where"][0]["val"]=$_POST["query"];$gi="<ul>\n";foreach(table_status('',true)as$R=>$S){$B=adminer()->tableName($S);if(isset($S["Engine"])&&$B!=""&&(!$_POST["tables"]||in_array($R,$_POST["tables"]))){$I=connection()->query("SELECT".limit("1 FROM ".table($R)," WHERE ".implode(" AND ",adminer()->selectSearchProcess(fields($R),array())),1));if(!$I||$I->fetch_row()){$nh="<a href='".h(ME."select=".urlencode($R)."&where[0][op]=".urlencode($_GET["where"][0]["op"])."&where[0][val]=".urlencode($_GET["where"][0]["val"]))."'>$B</a>";echo"$gi<li>".($I?$nh:"<p class='error'>$nh: ".error())."\n";$gi="";}}}echo($gi?"<p class='message'>".'No tables.':"</ul>")."\n";}function
on_help($ob,$ri=0){return
script("mixin(qsl('select, input'), {onmouseover: function (event) { helpMouseover.call(this, event, $ob, $ri) }, onmouseout: helpMouseout});","");}function
edit_form($R,array$n,$K,$Nj,$l=''){$Ri=adminer()->tableName(table_status1($R,true));page_header(($Nj?'Edit':'Insert'),$l,array("select"=>array($R,$Ri)),$Ri);adminer()->editRowPrint($R,$n,$K,$Nj);if($K===false){echo"<p class='error'>".'No rows.'."\n";return;}echo"<form action='' method='post' enctype='multipart/form-data' id='form'>\n";$vc=false;if(!$n)echo"<p class='error'>".'You have no privileges to update this table.'."\n";else{echo"<table class='layout nowrap'>".script("qsl('table').onkeydown = editingKeydown;");$Da=!$_POST;foreach($n
as$B=>$m){echo"<tr><th>".adminer()->fieldName($m);$k=idx($_GET["set"],bracket_escape($B));if($k===null){$k=$m["default"];if($m["type"]=="bit"&&preg_match("~^b'([01]*)'\$~",$k,$Fh))$k=$Fh[1];if(JUSH=="sql"&&preg_match('~binary~',$m["type"]))$k=bin2hex($k);}$Y=($K!==null?($K[$B]!=""&&JUSH=="sql"&&preg_match("~enum|set~",$m["type"])&&is_array($K[$B])?implode(",",$K[$B]):(is_bool($K[$B])?+$K[$B]:$K[$B])):(!$Nj&&$m["auto_increment"]?"":(isset($_GET["select"])?false:$k)));if(!$_POST["save"]&&is_string($Y))$Y=adminer()->editVal($Y,$m);if(($Nj&&!isset($m["privileges"]["update"]))||$m["generated"])echo"<td class='function'><td>".select_value($Y,'',$m,null);else{$vc=true;$q=($_POST["save"]?idx($_POST["function"],$B,""):($Nj&&preg_match('~^CURRENT_TIMESTAMP~i',$m["on_update"])?"now":($Y===false?null:($Y!==null?'':'NULL'))));if(!$_POST&&!$Nj&&$Y==$m["default"]&&preg_match('~^[\w.]+\(~',$Y))$q="SQL";if(preg_match("~time~",$m["type"])&&preg_match('~^CURRENT_TIMESTAMP~i',$Y)){$Y="";$q="now";}if($m["type"]=="uuid"&&$Y=="uuid()"){$Y="";$q="uuid";}if($Da!==false)$Da=($m["auto_increment"]||$q=="now"||$q=="uuid"?null:true);input($m,$Y,$q,$Da);if($Da)$Da=false;}}if(!support("table")&&!fields($R))echo"<tr>"."<th><input name='field_keys[]'>".script("qsl('input').oninput = fieldChange;","")."<td class='function'>".html_select("field_funs[]",adminer()->editFunctions(array("null"=>isset($_GET["select"]))))."<td><input name='field_vals[]'>";echo"</table>\n";}echo"<p>\n";if($vc){echo"<input type='submit' value='".'Save'."'>\n";if(!isset($_GET["select"]))echo"<input type='submit' name='insert' value='".($Nj?'Save and continue edit':'Save and insert next')."' title='Ctrl+Shift+Enter'>\n",($Nj?script("qsl('input').onclick = function () { return !ajaxForm(this.form, '".'Saving'."…', this); };"):"");}echo($Nj?"<input type='submit' name='delete' value='".'Delete'."'>".confirm()."\n":"");if(isset($_GET["select"]))hidden_fields(array("check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]));echo
input_hidden("referer",(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"])),input_hidden("save",1),input_token(),"</form>\n";}function
shorten_utf8($Q,$x=80,$Ji=""){if(!preg_match("(^(".repeat_pattern("[\t\r\n -\x{10FFFF}]",$x).")($)?)u",$Q,$A))preg_match("(^(".repeat_pattern("[\t\r\n -~]",$x).")($)?)",$Q,$A);return
h($A[1]).$Ji.(isset($A[2])?"":"<i>…</i>");}function
icon($Wd,$B,$Vd,$kj){return"<button type='submit' ".($B?"name='$B'":"draggable='true'")." title='".h($kj)."' class='icon icon-$Wd".($B?"":" jsonly")."'><span>$Vd</span></button>";}if(isset($_GET["file"])){if(substr(VERSION,-4)!='-dev'){if($_SERVER["HTTP_IF_MODIFIED_SINCE"]){header("HTTP/1.1 304 Not Modified");exit;}header("Expires: ".gmdate("D, d M Y H:i:s",time()+365*24*60*60)." GMT");header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");header("Cache-Control: immutable");}ini_set("zlib.output_compression",'1');if($_GET["file"]=="default.css"){header("Content-Type: text/css; charset=utf-8");echo
decompress_string('+c0=@iDWB2P?H+.Sh=;^l:{,zS(WKASq!V)jBmWb?2$th#Z-Ku]_;>Lan<jnKeCU;:~t5y5*eC7ehsBn^yDY2;h.o6kG`I;s9C
)r`$=4hC#.fpJw%?YwL)mj-qNj1#L>,CO1y.amH0(RpCZ1G"yjmkq-_R-.iBdAq:6sKYqdsMi9p{
;Dh5b-]C[00Vuu~-r].#d<y3??SZOYfAs^[*sI*i3FAx("`^,&jKhl`PXm8rvI:]pZ^n40+0sDIxK.O?g3[wmh9(BpHB:oy4*3yMo!0/G2+c<KpnP[2^zCJYKEq/M@<I
d91xW})*U(1VbQ>hF{sZ:v;;4p*/Y6kxKmE%fSxhy
WvupXRo985$6.$t1.o`SEgxt?2]J_t&!m
?E]Zt/S7j_,HS[kyX/]xM0Ej8@GVFnKOjGSk
y[%aV/THZsqO)Z"R71/qZCM!|l~@kQwFTHQ%UU{"/q}>~(V$z
plAor[*UnR,dC4&L;+e?"]_a0o|d1GWOSa]03EO8`.lu`8SUn,(V2:CW"D@1.[&&R???Wl)G;rzhl_bkj*s.TL%l%I~?)=eys$D;!u^;)t!opm^d=bYQ2gf_=Ua!m
BU*,bQ)=omlZD&@eU6vVX?b%0.n%VS]GbVb;VJYrsB>Si9H;.He[#,n8Lg`K!`<WU;!GCGmP)&:>b))?bYiB$YGoJkl*#$`3+owpU)$(OE[VgI2*+(5Mgi(D%lQt3YS..0~By#tL6it7ta>SC..k{uX3e!U
b-.V"Fs/xILp&5s*9+U1#;Wtt0lPX]d=tunSSW[3+ql+{$RV|l
`fci"x]k!I.;Y7hehmY1?ss%ox4PVnT>%6ttbriZGLE2*Vum36g@4(-Q6GQ
?Ann.}2Pc8T0GwD;Y|l*UyML,N-x4Lg/)9YL<e&,518>!DL.Op[(HB$WhkyuQ6pP#oNIr>o-4<i,:/ds8sS
?}Z)wyE46!*Y
[*:up.)%Z(W;#ttO@Sws~L8,9DT6?O&+pF>+eIz4+t|(m]8E2ODd1"sPQMp,pFXd0aa:AXYfQ=0K$gm8[4--76%e!x"yy#|3TQu%nk(E%;{>g`ABN^r[CEK]GU_-F,bSnA:>yO?X+%
n#_Da(KuaFo1KSVZ[5M9YgfdGF0bN-)j`$E7v3RRGO#C-I@J$k-c]P1,mc?d0lm,"AWQf#>&$Ty0$x"LMKEDVb%Chy&<p{?lBx<8F:?[uNA$b41&;*EP.w18
"O1ep*n3Q+7>/0}x)y[wj<FWXOS7qi<ry,Nn(&>UV_*D4*i#/A#m"5*#/B&4E;[g<tAenG5,gZfDx5|F36NFnN?6oWK]"F}p0S7xHER;:Tk8{y2irFYxs7S-a>AfC+0Z6]DAAk80k*b<;-}P7OI#+oL^<D7$u/Sk-E%6[dw4j;pKLJ<6v#"?@]28Gm-pQn:#L;DGIqneW
P>kk,rBE|anI%2g%Ih6%KO0[CqzV2y(R+E!-&Z<&{l<[8gxP;-[T#=~Jo6c
&:9qs?*i%?7[j/,&=[)gR,31a461N4`sC)L9lUuh"3`;N;Go}s@6Jf`%ocVd<1bc
<2(jIBa*lo<SOL.W>UI2s<gfY8*xoboIM~RC!#,AS,FoR/p]3wEkm;Ga,9G)Pf+k
>7/VN!<tBdkcHodco#>/,[+DU93C+yJ&qoW(vkA$PNG6c(]X
/U(`dMKoW?*BBndz<2yS)n@ot:kFsH&kD<7S^%syOf$5ASlCW[vNq&CW8,lF0L:tHVWKNAr1A9I`]O`MJjNaP}?_3k"V[u6*/0
;1"I=RRs~F,5PnVxv8]?[9t:bG3:M>bT^/E.d*b*KV"h4p`9Ynl?j2WJ&u6)(JqcC.IM&"BX}@FTmA@A%#q$#AJlwv*#Pw3tf
X)]aNskV4H#6Bku5iZjHj^OdWH:n~N3-!7AK-H-6`h%tfV$t9vh=)o}%:=(?Qq^W{k)_#x?su7Ej,7ml7AEj6"y3#4v9nHC-?Hqm8raoE7g"Y(jE)Q/KwT:2{-G:b_uFDRl2bNK@:^{X^$OGv(4+DK{^#TF/;
a
#:yRz(<TRXj+ZbRV">7*,O
@UAr8E@=[`3#oD-?2Rt@n6u8$)&X^APDeZ_[;!EKqKswXFWo_i*4rKxA*E,/N7oD
xSxqj;";X,>Ak=p&9JgTTgVkaq!D%9U@#,?X[jmxZTRyDweAIx)W,qMm:WTy()UQ%jgZ-x37ue7kkq.@{[jq1P^uhnSnhy6WNyEmpPgJQm^Go23m_tO,Gn:L_B^wUx"a[oFtLM/n]c|ecn*yVF}v[by,ap5x{5.9F1%]EEj27ypKkK&KlFQ_,b[yve0XsP"cf2_*/qiuyLrIMM/7_lI]zz)0;MCFubqBp_bQU7MwVy)-tr%Q
l8WUIbF6)"c?SFKIXwIZZ|SKm`A.</ancccTY"w<.wi2l:_T&lww){p5CN>wGo_~Z"_$([qgb8A=H:XwJS!Kji[ke|D&+fTN(aS$xayFy=0/D=mXXgeDh2]ne=>JH3ATi]fj:;=N++#W]<4S`mXsd<Fhw]b1)aMt("Iv)&^6xo/@R%De
40s.d8V2$0u>*
5LS5BrbEvJB:G
QrG=bQyo2hKiSagWBC*[03iq@
;8AG{CI[vgMbo=&]CUH<2J#$I)1Opt;
6T_jLmdf=2?`o;ceXMwP>-gZ#179]@=tG1y:#fDl~DPi3@0Pjer:?BMi#3O-$sf6Y-10c<%b.Y<N]q+EwBy*!.u)_J/:$1ZjZmVc;jAJz]imwg,Qtb/S)/lX$Re17Kkyi`5%p*2)Bc3C72~KdCcBLgteSrJdei-TeF-_jdE>im]H"6GkaM;`]=iIA1dp`5NaSeBn%PUU.T$cQSlj3E8A8X#K;/ZT*D?H|Z3_vxvQ&?&OEDYaz0f8PA@]U/
HrU3]~c7hieBb{7/crTVx%/+5-*@S12x^^
Tv
NI5,KYeq_~a8coh/]c#A&jg"
&,b-vH%!vWf)Cd?u$.4!t;luAGj,?6:4#c9tp+AX9^K-Tsh(]mx9)5m[0[6`Y[f0AU(j(G;#Lu7;hN1E^x<Jw.H?@^wF*
E+>WNXA0zOIN%9NBtef`lb4:]RLqoc4u4UX7}S9M**i;G,WqWJil4oFtPVS%6d&,IlU.BB&6v0|ojAX#bG/)sV6mX<7,(YbjMSVB?Y;@XTx@>Rj_x9I4yB-4pjwniDT2``8K1q$fi>RudNs1(kY8VU]/x..C=[7:.o]KW?L^j^^^5*>BHxaY<2bRZ4,SiA:c
cb"61L;(Y^&fSkA,:+o;TAcVL-8ihCI^:Mm@@nB2.o4mYJK.Keb:h=@a?=V(&@/|rn>})es]IsX)Ijwn3hT24#Y@j%5x?:o"m@b}V#ncaM@*Kt)86t.ocwm(K5H=0XfdV"&vit9HOnYfYxKT8k936(jg`1`G%SH83}bA_s,&>u=ATHaA^]*/`W8D&&*<Zi@K]%lo"S^TQY%&yL%~("8.r{QK3QqW7e+y%(=)3`<CbS(mFiXzcj<i?6ql@nEHXf/eFxM)>)a~Nc2=)LnV^?4J+T-T-vw#5vt-RvC_*MD"d$?u]S6sJM2^-=0;xuAOQ>P
?0v|T/LfA,1P[ZK^[F3o]m,1LPkT`@,+R}%<RG&/l7iwR@$/lPC,]9sw9+6L<c%k2S4}PJUULEfX[kQn<"8gEn("A1v}M:;,tCka]2w1"q]]wj2Nd[x^TIA)G<(yw.MTtX');}elseif($_GET["file"]=="dark.css"){header("Content-Type: text/css; charset=utf-8");echo
decompress_string('%OsbOb3V?!K0U*/_K8!!_q-f@v$o[s#eo
DBus<$lON[d8].,qN*m@-poH?R.tyR)O-"TS%KHdM%X3Y.^#Y_hHfaP;q*c?klD(H<QI%Lx,N=.GQ1$T#bV4tq#Nz%
?3w,vbJXqxP3M+9rv`E3"+&ta#-z:q#uH,gsfr;30Y
YD](8VM$vfC0N0c@>"2)GKk6Or*glU2;_e]
lG[qCHj,)N{dgvz5Bxo,St/",-!<m&YguCEOcf=QW&YqSe0y
4tMFl.S5
Q&<3MG&3I$EKa#w5d>?0G#ziI.mcH[Y^xh]n`F<v_9TD-S)yGMvrG^6L=^u]hu~T)XX@%sH2|^MUMM~_O=Lb/j<P[#8jzJ=&?+Lf&PJqQq.7RL#R!3,OUtS?m?2iGs<Z53YxESS/kZ&u!9n9m4#Z~@-oU.[]>QKtIKdd<Hl)Jt[fL#S_x"K+ANa$N"-vrNv

s;AjH"K_hHtyTN0yfA1K;/;F1cD>Dq?!D}QS[!.vMbxL7Yt*f95&ALDq5fB7EhPixM9U-DAhYDVZvb&*VNnYm(ws!LkSX1hT*rf?L?8pLXxiM@vwMc5+L:2N1cn6*pvi+]b-Aj?Ky>:w8"9"eJEDH7YPC/H4sp2BTlYJ4)>xqL$7MHqrU^F
Mq,+cY]Z4{i1WT_a+CI<OH2!jmJU5RI>@LSbVBv>m0_UI3wJw,DVQw*|?w%hHQT(fAdA:}Pu?y<E7VZ;o&F!SfCnKXXmJG3b-0(S1ciBQRFKE$/KyBZ-x~Y&F6),Vt<HA&pAezJiGbD]=9MElur7.Zf,Qp^{f?%4w+^N,&@4A@$CRbNWa
BpG&xQXj#<V0ho]GUh(F0y)N*XAG09Hi!8MGrt3p7rNw=qLDJ7BG1f8rSCi^;*wx=3-B=cetZY63!qW$uGUnB%^t^=7NDGe@tP');}elseif($_GET["file"]=="functions.js"){header("Content-Type: text/javascript; charset=utf-8");echo
decompress_string('*hcbOg~ZSGrK,tnTpUC"nSgx@i@Au14)aq,?AJwvi&hL-=aQ=P|#W=sd(@=1d0#u
.Dh9L{3$vyM[!L&}1
o)Z&TjD5G9jK5-IUAr/2utCtE`FD<N4e_mc2W<c60>hiMM/`qL$1QlWFAYW#hIG_m1*ub?EHk)ZQMp4m_SG[
{GtgNjd`{cWocVqWla-"!N>PM:dEf/.(cs/Tvc.V
t}/;D)c~39?dv{Bsj282IuM8,{G;"WQl/W_eX&aD)k5_y
U$czc4T3;7hcQj>yJ$-s;U%xdq6+SzkhO-Hnk]%Qq<5QZx]eb[O4Z6C~,~Q~s.K6Q}H;)71&lMM{o-Hqy:^9SK@+uAB"OZJL3B`Q3RE!6N+4TV,fIeY#Wu<^oGnRYUYlh@M
y?`|><Qvv/DQYtSWb&;LyPgjDR%gXw"j+mB*!>q:Qp60w&<WL~r;gk;_J.B/`FcQRZ:6EU(jN<l*tn@n)5A&d0i
rUm3.+_nPLoa88x-LN_OVr0~Z_JC+&U"(M?Dt&_Y*%Uf_R8qH7:["om(P&iLTuxk,H/P!HZc0wrd:^cw!bvAM)wnlI"SO,ZoETvlFH^LDa@MvlN?yPFmWlmZsNk^q*X=[zLwGNC%`-E]_NEjvNO8(7)76+JLJy-8<e]jBhDO@2xC=7R8TM@6J?u9Qb#%j{^7;<IK#0W<G|pVKBWgQ;yLg]wWoB*^@%6VxLb;3M$6L^)^RRB2eSa=n3,E,i<L>4@^3v=q,^Lp0x=k5^b}n=Ie8?v3r8fR_D2wqj6mKFyyJ.yQ`3E1GWk@v_BXHBL&`}U1g~>-f;La*{%g($PUor+!%UXQxN_)lMc`b1LGmX`uP+g!,S=n>s^yp~0"MR/6PhTCn(<ei+Wk[:Map~q.c0"A
A=WJ:JnwJVR
;PN<(H^&J"{4(+a%A_>_iZk(7<>twY{!>_5uvn`)zn7"..@y2U,#Y*ri^D3*4rwgjvsG=G
K
KU?CiYQhtubWZ
mCZo
h;D-BobX^u}Ce]Hs^iGfYZ_(DR/ndSj<"#iMgoIdP!8Qv#!7f)rT}NF*"NGc=EW<SxQ?YTP!{QkFpZsO$--(!!z*?R=&M]D"A(nL^h%eXO?MVb)xb,P_G2>EE
}A+T*s`hRd[No=]@>e"PsIw_3-}UNK#p{A+x8V"Og>tQ#>NYnEh&_4^Z2jUvVgqmPVwB>kW$lUOt:$qVvi?xY/k(65OPqG)dQ<@3xR,&P)+=[FN.Y]3/=P}dW4ruq>L(q>->9tS/eDEDlLyp}Gb)317pN5iApal[o-2&][jcnfv]/mYbbSN4gVhSU"q9!]+R#g`oNn$<e64^W$7IRmjWMN3+B/jo}eC:$fi:hPbIS>R:_oO!;IB[Q?=PYO0MceXC+khRL0!`$;"_vtXqx)q&dCB<k#[!IS2]n8uC&$|Lc$/4e2Y$H2M(*X$)r/Y2E#n9b*eK]v!3@lL@|yxPpGa=oi1r9JM(LYDR8md7-"5cIMyxRupBVm7l5->FpSVGsx%5T3aB,
"=)&785(~IP([%Q(MF8]<*5e:lD9Z6yGN-WlD?""i-|
Pj[MpVT_;[Wavge?K1}&>3uOI&_
]:SS_K{-n@Cw7u_uV!lw:"}65q3f8taY:sp6]-pT$f~L!2`3H8=i|T}KuBVTH[jS2n6"Y+Ru>.l*U7RQ/L&bNcAbOlHiSsS1b
Iv(j#Uk=Gi:":$pHNLb0LgWviMY0J%.15D]]h%,b[8q5fOO`8Vs?XF-pbs7gvl!8?]mI~io8/:Go635o/Pek:Zr5bGhVdD3>v:ZInby5+^n4uFTkcE7M{<R6<3F8^7-jJGXfDw_lTY2JsY["XWX
!OFHqdDwHkle%PL`j))S]oO1@n-,h7RkpF&p{oDj5vluaWZ+i`EBIw|+@wB0V3kJ8X9%K&ko2<egaHP8I&x5AOt63:"bbNufQPfv]#C[VQuio(MQ6[e5dYamQ^:.L-ctoH/[AN(1JN.DUx?E6Rs=<w@25$)#V
Rb!17>UXQ^&ZSL*]OAgch/phl*)oEiuwE#PR/>[NyOdC?p?oml,:"v:0y=]VUyjZH)*ZqVr(I2a2_*PH<<t*8J0I?uoT@/$GKJZ.]fO-60`@G@O;lDjXA-bym
OH)usO_ZsVQtL=!.d9?<_CQqyh~HI9q1",u.st#Tb!7rHGg4"1kZ=n}vTh_riA_i?c5K>G5[E^`,gDr3EDy&#+GkS3Zd,E&Q`<1J[XT+s7E(?5|Pl2?X`VWVgwkKQdZ#IFJ_Th[y#nwmOWRX~mceTF-<T,S_j#n:?V6N
c=LhNYgREbxRKDP^Kscc]@d0b0uyQbcZ$*[qL@hc-Ai_nocNGLmvbq`O3YQ_=cu6j$xKg8[_%sP$Z@rC&[Vg5IJX"H&ow;p:mO`9%YDK&r-1-d$ompw0T&IZtswB8!W1C&f&6?:<na?y7>lUViBZJm3CFF$,kUV#UzO,9n"j1dk<I%m$sda$!Ta1%w&4YAc%])mQwgoh6xl@FUGb6&
p_ZjaCj)t0:5$!1PkoQ(T%zu}A?Rd
/dLn8!_JS56"U%;avgyU&fu#K#$7zCyk1n7KOhq7AyW@Lj[$q(noA,VYWfo#w%7b&TJ8r1_3V>)PCP3-dt>X!A|sdMBOI1odddY;v_F^
C%!L^kYV4`u#I])b[u(,?gLp
EQNN/D6I/22OA?u!FnJ[KL%ZH#HHICmia7lk|@)9--I3f6xL.I
whcR_g[t^|S)2h!;P2R-_i)CWVxM@?OCdMEvIqADhOJQkb6k=9e_de&1-~2nu]d~XN1BZ.QX-{]Y$OIJ[@2!h,R_">Clt}X>,#b`Kmpy:p?8bBN}Eaq7dKCoPVgwxP/8*gTDfL1z*edbM^<Xsj.G$x=YcFxZUQh-D0)]?$rL2DBLh5/F*7Sjke/sTdWR8Qfz<S90z!VR1AyQg(]n`UoLobr@w]HPVU5=HD-0=z#vwcMb$7k>/YSH%&]U5{jFfu<4fFYLu[2P8mI:"]qJD~Gbj_nV2h<j$ud*e8q*OT8Ww2V1L
Hr/?Jj>UR?VZ7:ve&e#0ssVN!)Y`1ZQRl6)Hx
oU:%6Vsg-Id.?va?A7x`k(aCu+EE&3%w8
p[Wa`m?G?hm9JIyi#A,-qokLI9AQ%*[V
=EAm|kVKu]|IL+:6
UVSum?[c,@GOsJB`_$"t/{@Z@{r6409|)Z1#l9RSax72ob4^k@[IKq$cb<?C=7NcKe<,CAnr
%rtr%>>%v.]v:^QtZ-ACm5v
&>&`Iaf:nF7heJs454xBFBHt2`f53:.od>W
ns>AIwDM"dNJ3y&!]DVi6C90xsme:c;Gu3"9)2,qMJ8r1TSHV<k[}C>Wuu!7iw">^UA_-prB#:ew"
q@FEjt#r|flmYy8j!CMZel#(t6%lvF4o]GVxqn&HRp|4RGn*$<d$;jD`fa3c+x]x9-b:W95[G6^p4o1U7`n.Q_pZ1P_5awJZtNLd2IlTbBYnly!okK|ctT{%O>z"Re08tAM&qOv<n@X`@oH9kq[R47/?u6l_d=a+n<W57G`^Kl
Y7D8(N?>]:%$`SL
51g|"LgKMMw3v_9JtB0Ly[;C9&8QT^-px)Q|4<yWO-N/[qWAba6`jiC5-69LrZx?H*i4iCPYiFHJ!&rZ,qj~@AX)3xKwtjq=](?UEu%XO|_Ww8?cMr`(1/w%XmvPkBQXLw(0r]@,w0Hx.v;5_.^bw"KVxK514>2yy|y:It9{dC"U4=H/!6$zbG*=^P%+s9:Vwck_<1&3dT>|T~UoJsM
P*bgL`yS79s>h"=`oAxd/YAo^tUsju*.-f1(Yywbb%lJA3!Rc4%Y+}QMax(#_eW<FZYqLa<409s*6]w5eG!Gtnt.;@FgF)DsHd@78,)9gA
7dcS3Z{d+;@B3_yL
k
c$M()qNM
W91Sqg4)LL%##W5U$U<rJrqr*QA%!9UOSPkUR0qY(lQK>6FP~h6?%w#e%H+?s7#.n(k_C]He*lGWY=J6vq}cM,(MtT~K&"9#*:R>xd3u)kgJ$fn2V[YpwTBZ65S(B@[v!ti!~^aW+IRy;3m2Sm?@>#_$]72nU#aR~mJ8B]P0R(Dvhr*`/I0fY[eY0p?_Qe<5"`T:aD=mcE2tFtce|yz^"%t/DftQ0*/[G-58uN9hpwqh;RLk2@?!4I(8r*R+u;Uj^LTwI7^?,pfh!A4S
"8[Jt<R[0(o|pv$a9V:-H7UNxJQAdS&!nT"H6]3JkfR8nAp36"XbH[8coaHzZ
%-dU=0gii7EE4~"j6I6:ql."BLcYG61|.2eMu%wZHomT7c#rFZWX@
&ky$2>3$D7GI(ayW3).XbnpW))@~,Qi,KnCh@96K)J9!p!+a8%/1r[/2Mx#m(H/F&.H]r_CYf:KI,u2P*w!yl1.)eCIFS6fyEZv?aVr.)GXR*WUKV]ha,*5[(~Kk.@A$E2"8lZF{I(yKOvc3"
<XCsIObKs#:~"(y5Q3oU,jHNhs/g&ZlYE?CD>%;S"<KUdIkf.Kq/wB:}Nnj>Dd[Q^ZK9utoZ)"70=)Z,q?R"._JNWO4an][GgJ<
V@)Q)sf=#l8xbgQ[[|6MG"_cgc;l@*Z"=ofpo-<B$V<DKM9lcEV/4gt4i#r7.NV$DqRb_R:xJN&>Vl,K=.X2I?Y=*BT9lE>{<v@#q@Z7w*0N)1Y/DEG-)X-^#AA["d05;tfrbd+?.Q8XuW@@<8q^lMiq5v>X>G/^hlbFO=v+
9+xw`-FxpS->2+?KsC=<t)/=EB373&wA^@Fd,O{H2Cvse7<!ZsGlB03@dx1<@ie<}.)QVmEMg8"jHG@4L`~qyJ`O5*g^"@$"ngdYr?I+d>.5Tea$Y,*E}JH.%7m&lNXjjVJ1X8P>u!90xce"xQgokpN/Hqar6WU<GiscV%^0No,:R-Yo8YD9m7}a_2A.SBn:L9I31tu^rha8;]._G&h("Reei=?>}6;QFGQ0Z[@7pyGe6?UZ:R~%[!h0O3E8P^Xc53za-P@VW_e!<#<gB(gmW_!4*_Et+mo8RYs)<tyt?0yABXwyZo+UZVkb94~EC22R|OHh=dhJLqo*GNbIhPH!}<V#8REnu(ntxX-_|c[ELBghCB7/S2YY5(n
9f$8caxoNFWNmIrZZ;@+/7#t}ICS^Y1G/U$
F<yu04AOJaJJ>=Dj22n,[.KWZ$BG8t&LRj[$5s;QEN@--.%evg!-Nm_1y
fdpTaNo6W*i,%($b[lz<Z7Z>1()ubPQ+CjdQ:9F&1mBAp4no,ru:kJBaG%WtiRD
jkg[5Fe]0m]v)X4p4AxOPBbOL!?q.oLc}@2xNWrV1jgD,@zfSp+P`qk.Aq,#w2H@!6S>l@FO0Ftu"WJP-P<fW2r+et_#>4EU
@/o%A,!v$3D:!Q
Q6<@|My6C&T4jtN)Kb1o:utHI56Rr>VnldMJ=en=A_*8]Sbh0W6ssEZ^~4C=]0s)+blaXMl5mSQy2t`S3mGf*^,N-h;W6>?m8.-goKWio+O["xoQk/8.So[@zj;&RE0<VtYAv)Zc9q<&a5Psuqi#kj{_dVb0Xq,Tp
gW!K5kMM+UVoo(g`a6>fW#abE!6#GWIHuXvku474|u0-K9-2B81Gnp7huLUf;JR#uB>P2pTQ<ZE;#G@k#.9$:,q3@BLo?`C7m8RkL@p@GFXiOz!l9QQUn#]i8`_jc;i0*mm-MfcXD`-26Q6/OopY{
ocv3ewLIz/#
jL1*C7u,9VTt4;^e[3GgteoLvZJy2VPi92_YWjSp=Z*=I1t-X+En*
oLx=9fjbM<#6N+GVLfb7)D/6o^ktdRx3?ggF|=Vkq2=G/cr8=%w4iN^`p_}!:Cy?JSxWDOkfMCZVpws<)R9*MazkYgn=&g?_klr`|M4V8Cu@Mw<B5<b[f%ZQA7RdJ^F@E!Fd!y$yzwIw7`RANZIyaz)o`U>Z>KL6Bvhb:h):*UZw<66yyTBz%*-chD[]}J8qA"p
ihF8>IISe:P/[JPHd<d%ggDGnVae|^~HP>.i.C=>"`)a=s3oES2`ce*6e%~DF_85N>`KnYk3;tWUOIlCktBF]0@GDt(uDPf6q0*3fyZJxN%7(5"M&DOPt^xw)OjdK3%LBl#doK%E"^a$3bKC!SJSw@C(UT`P5q,c[riVzBovSW$13+`8RX]I9)m^8d`vmOU&>SOAG$X5Ie-/rrZk?el^qs[w`3.V+m?]8/ZsKOvdM7$g2<UXarO^Wod(
Q`kG^Ygvy4
ig}e$L
h>Ag*w?_iqYCqX*;h)f>2WCwR*x*4xxIC0!iO|*ji%_<M5Dd&DtPV?WMF!c3me:n>nFn!B
bkL>~>c`4?,RY,JH+=t>^bU<EC03FS0ll1~(fH*WxyvYV2HyDL_L~>Tnc<SWn
0$)rh@=-[8$"5Iv>ExP*}SnN_yiD[32;jICSa3`aJsM."d,GGOu%,O+(X-#cH6f!eAsxgDaLvA6DO$!=;.0#=myh#F*[h$RY;)pNAa;+z^c[G][_m1335CEc^RO.PHciYQk*~eyDrO|O~&8r<oE,x9Ae&NG&"waNbDVKKW1#T<C]Qk0TK9v9t!F[rYPDdX>9|*!F4M#ZVcGI}ot[m&^CA#5`fQvHU9v7[FDF,XMG.i#gfPBD._~RfB*cKT-p-_J?pyxu|n!`,-,J!h}s}BM+AZQkB:FwQ*6IFguV?FL#$kjA#C)iUv%+GwtFg-:^9D^g4:D>TQ5EqTgjk,us*s-8$P_e[+jH
HodDUf:VXZFtjJo+rVqgk<Twib/*CnP%@lP2/6/1%7cu?`qA;,i-H+HCtSb%!YCKfH(eXdQ"]9l.
b@HtkN_PdQ1`n12_dlR[6nURroCMzCBhH".XS0/`2BiVia^E}FpRrP=N!>L[n4^X0NO5e*hEXLU]W_DIc5N;x(@!1qhH8$p3Kd,oU9#ftJHHx(KXe[OsyFcW.hl6KQ
OE6h;3/NH<IlEJFp">]24pFu23,GJu^trfRi#X=)>"1U?kr!k#X<ITK-c[5w+l&TWu4s<XJ8_W]w+LFJ<IZACA(f[Z&J+g%CV^xf!xR>TKZKH&@T<V:@^KBGR(J(;_FJXMW(Gjg=:Tm@h;>JI"L5nr"fL|$~-P%;8$-:-0RTHOR|v")69D"B%E;:SEgNtUmYY_g9NpA:JS1X&qAJ]xnIa#,y.mgW0LWrF:s}9-M!JTy/^f3-II9UWpW__2L7^t@(tHv;n5s2!/]H
V]>,+b[:&t)eu.yqgpU1Y@2TTw*8|MXvL5"/7b,VVF_AotmU!3hRC!`-Z(Ym7BOsu8*C*I|HR#`?}WBSCL#wWd_4r(`E`ApcYZHZLi?+!YbLn(0.Z4xLxZw7KAe/)6Y7Tvm/8B;KHJJ"}=,mwS4##kCJW)50f(.vqk2Ev,1cxIowrE]]*Cf7;_gBlZ"+jm0iiQ$q{1A2DoA>tjTZRs4+-:1W}"d.U)d-AX1
~WL!}j@vUpM0q0XInBObxUWM:CU#,D7#(6ieqiyiCd(,?#twIoZ"XfQ">c$5S3&Qi*q;N!2nQCgXGou:OIV4>Vn)[A@_H8z;h$Ho1k~:&KSh}aJv)O~&!&ng;^lYsv9-Wa[0#`<=73J6Fso:)*X6LX^5}K&/1YUE{E;+`*/Hxc)4xHlBlYte<5V!
^22pNMYU>3<;oxU35#7<uMAN_wG!wt/7HYNYl}0Zj5-{J$z&^de!,2%Jh|[{^dyE(b+Q2%P];SNZ7eZw?2n8s`!K;GYBS/T>Eqk2FE)1FjL}ZSaiR$9w;;pqiswJgc2zYvDNa2wW46tq%inPQM"nG:u7(Owy@.C`-"_T-SWIfF>AIFHQpt@
@j2[-
0V9*D
l0CR6d/hLMyDN97~r}/Lt-VdEtFLvhG)Csoe?`[V/AHjVic0!7:TC$+DAV6<)PG|0msR08&Qmo1/``A[,$qp^WE6dN[0eYH=lX!o/+*lfOh)$&tfVHg4=(4*ZoMObJy#.<dVjHy~41tpogC-iMq7k*JebE[Z&E-2V3K?+!D,$Iz)5Kw70-=)78vU[s:z*U=|gp9tJI)P&^2i`<P;rB^"@t)K)Ru_Z<KJ3`U"gk*-7e/6EPuvfR!u@sr
<Xi+pdmFkb[eKS!3Tik`mLUDbF1$yM<3jch#8"Z:gBVG!EtLU}fBY*,<C->ZQ8lOfF*<3f:[mkKn50ue"E#,>;>sNfAq*7Bf[o3%&T%=dFqkb+Lm:rrO;G+T&4xfVGm(cX7fEA]igLAf6=1MsK;t#u3).jD1e7x!G|,Uq4C!8zFvYKiFA!fw_I*dZ~v<F;U0`//;vpvw.v[aNSktLX$t0)+S`*=ai|7[!Tu;uZ<lq[jAW#=,<_Hthx,A4
qa&p$oo@s&6]rr`=,BjobTx7N1.2S+xR8O)9lKh4ANWKe7j3r8gDBj+]0gz(nMfCwn4dW>a1*B*S<ne_clj$g@S|V^pWq]"]1tT+(!cH;?1C[.mxl?*{am?zA:!4+~1X$c/cG{a]tUu@w;oZ(Ag$8wNmTJJAKzX*-"EZ.!jAl0l[9]&fX#j-9*LhW~h~StZ{U]E{VG6F]!39?_&|9Gi+-#`a<y<!T8N`Z/uo;9;&cSudlc5cboHLNgHcqRls0u_`qQE
1-#B&0p)4.lT@lFzC>b0VO#Qan$w+-HYu{8E_}r{VIN/MQH@vy7DT4f)X5V"<sk{*Qr-jOY/:j[9H^`+kSJ!WD&KP6uCVX;wCnaG!j2Cks?T)?4yMB)d<rTdej&RH9MqQo@Ksje6^!u92To4:u:#,%@]HcfAwAJamddgmMMsOe;4G_lQ6(-[CUxS`3mQZD+zu/8~f"^hJdt/S{O=#u4jUtdMa,qE7;z%x)E,0._O_HV@>Cc9hAv?@&$MK+Des8&8whAeJjWaL8w:VGKla`i`fdlK]DHjG;+LSMnQj}!!XL,s
xtpSFyVfYb]I1p|`?rR7GTvxaxbItM:2Qnoy7e;=)5d[Gm{;e,(=ly8w=sgk2XgjxUgO/>i*sw/k8WbP2Lf[$GQi_g@Vr?9A.".cL1@4`M}!exr])@[=wMF=dK^l!oNWEYvW&8Uq{92E7Tu3mx5[$Ev)~c,#/_rcfHB3Y^M;s`[iFQo,%qAc-p-lag|ls[#:L(r.tS,/P,X$cjVFZ"(Uu
2*~yYCpY>8,X,uXKhBZ99m444B1tvvpwjCB1W&.LPy:uqcYMN@*s.(f_uy}mX#zd%nMD(dGu6^TM^Z/r:F-wM=.F0cs2_$.z%HtEEAvUiTe]F-fciw]n4rLC%i@p*hfc.NrvJFu<Ir&1JOp]y,!y|e*7kQmx((-C%sXxTP,GJ_&f0I6teBy&%=)nV8XgrXaLd2[2~=T_U7F16aM>;k{AH(%p2#%(7P;ofRCY..CN,,v]G<gi[vfBYY&=:tSz"ghtW?kaqc?yA4BoA+JH~A&b9-q>pNSHrM?7!eiT
cw0Bx_A=G/>p@%bz"!L!_E(1JiW[OED[H/M>an8#svP_WSXQUdGrq}L[e&@*H>c%vdIQ?6toIq=YuzB,c]w_m;Qqj##:$Itb
qAIx%Q|XA8n60;eo/s/0Mn&H4,ISo
(v=7;.A
{x&<.asvm/q#5;YJ)UV?%]xsYvIl}WoB!$_XmRiW:O]jY6aS)^A.e`&Ln9a.%*)LzC~SSu4V,sCqPvQn"YA2lDfn^XdY&gjcUZ.z&HSb|dd>0@l=v)NWwMh9r@4up"I-?q-E:Lkb-8X"CE]7;"^dVFt)Q4o[i+|dae>ds%~*Al]z$TzqTc3vh#w;s*7oj>9hpgmQU.}C2N>L2ujH{7F.pBDT`)Z$<n%]3P23h(vw:Bh3:@cqtxj$d$5gZD+!dM{d@X1O*WcBLb>r#pScX"bHAh$]rl7[VWTv6ALR1hN0lr%H)jBkz9]wy=V2nZtP2;++KwQ&_M2v#Edg7X
r0C8hK&UC1fKgPE.J4yMGOFpXc3dJWL?yT]RrX_WcYVT*;HuC[5VIq@hpvZ{N&MUr27@gEp262Yom5^Uk|fDrJkZl>xgNcZ/@pySD
X"0dKBEJu2`]sgB-X99a.:3=-TXw!R_^SPkaXgcm?b+GNx,Hk;CotP5:"Q
-Hj31l?>-qyZ{TiIZqgv{?2D2K%?uG7t;f
7|tzSWx|EyV-N~,"B*ZYSSW%>i%(4ysr:_p`PylAoJKbxlMP]Q$_p%hWYj!W9DTydG*j"i&1jAP!ZvwR6[98KM"|3B:_;yZ_5Zz)0n*o!HXs72jR4Wt0,""Q*X?9f_aFLneWK
TM&S$cZR=>"6B;2kNC*1#tL0IWM{;B2t"1Y1ZO/GZ*=oLBwIK4JLu97!k<FqAnyD?VDSvar!-n-_@mLyNiU^T_wleJ983aIRd:-1bEdE%P"H"CGA!N,Z,m17jZH2M/($={H|$L+xEV]yX>h,kSJ!8lYoe,"W1v:7lQ+Qxtt:e8OQ%)&u$Hx|l6izXeiy;A.Ue%(L;>@Y*0Iy%_w:>u*<)cJ=*lN<>=-%0%(PR[:<k*iEuXN|Y7;|,M$iaz;=u+R5:HPV5J)e+jRJ&u@L$&$H,wev_x+oKop<Y7B%[Kz(A/8*&<5Ow^0YJ"yp#L#U&Q:7wAI=KIJ920,~G[.+z):(U%6q[h.Q7tv}"v%"UE?s,yy7^|K8I}n3rVEbD69|:;R(_L!R(Tz&ZQ74T!EGFegQhia%lmxh(5@X@/P=e*]XKiNmxaPeKdE**El.Y-L$a=hby|&5.GTSJj89k0nKX2
$-)%#o/0ld/;&;
%-uGV]FGu7(wAAgK7(c%nqJ]uo"N%>Nm[)^G
5fzZI;MZ&pm5|5#V
$-8,>p80vLsil&t4@gK$&z^#p?eH7*Iuw4f_*1r/e?6;:bt:M!=P[Av{j:gT]940n*MuwA');}elseif($_GET["file"]=="jush.js"){header("Content-Type: text/javascript; charset=utf-8");echo
decompress_string(')stc2G>ZI.5*iw>yIlyYsO<(8o5Je*2&`]`o+f7@J3`+zQ/F>_-QSjC8;SNMRy4<Z7ZPpC8cBxccgw~jfk(bY=6vH^+kWb4MDpYw@4a<on%L2:,E.hx#.vD=7uJEG,Hide:$AvM1Dv
d#_eTa6xWt70gy4{%2xrHLw|;KAbe{MBb%8^Aj$aoOgK:yr;qx`!eEVeO=(EvSU{65Q@yujiwlf)p{2@r2MtJ>fwb#y^."b)_u7Ii|^Qw6M2sE`IAnS7r
0;mH*8Cb6u
PeBpdBWdP(^e)x#A~&tPd"D[9/@oda"pk&e>}@6.i^mTs7]Hz(j,IPwIp3lOG,g@2FObop&6i4*BGYd&m;Ofhxu0$&Lt&xsLV<0ov@(
a
QPw+g%T%|E(dAy/ScJ
v~e=>^dv*%g"BG]Sr`qM/hyEwY8e>PfauYl~JY%_%todcroH?KC(#}bPd|*8eE7pmik)$
7$ya0@jn*40%;Rl<_hZ!Ic$fiRZg2b@0v$K7J17jhfO{5|3BYZ&cnbAj25OJ-"Q?"oe(1_g>LbT*!9gI*76D+dd!]zRG_T<
rv;YI7_X+X@5dBV~$.O="~b92&p(RsmAcP%}v,)iuYy3x{H8f|>k:RUi.PM,:%vX`EO^1fWj,/K&e4hb[ki7a<<>y.DiZVo,15or2"J`dT(pNQOrxY/ZHec|d!nU&uq!K;ame@/*^D"X`{4^x+2=cBHZNPI[Jg$VSIc_CbsmXye4rp?DQ4yoTTBM;w:vj7)ueft!z%%caXi0B^ok5xc
;8$|Y?[7A^bivu)zuuwq1}KB8#X#;6)D^rNr[VFXR$k3JlEPB}[l>"9tKCZ~u);fif1(x6?Zg.e]z$1L^C70crQ].H;:s;gJr`%bPX1Rs25q1FZlx,,?uFgr^6M@z(tZCYpfo$e*jwjYiE"1uT4}l.1A9jwBvAUAeNr%
arm@P3RKSB>It*Igtpr.@2k?FGP;rI&fWFJh`S<
bZ>Z)vV$ls<M
GFqguK
/:?Vqa0DX/`N~ls$D$tj<?at&(PS:FH,7HwMaVYdc,@V,2;nE,0cT>e@N>zI@4/<(o}/KG4x^XDTGD<UWW"j{L=sU^a$YCJ:q)-)jmqcvRO=ch/8[W24t/Xw?p|iu@Y,N`.f<tn9Lifcny_8qFQKk@0sgLFmq;8txHz>eb)ofW_6")yjWLN%2Si2:ql9Z60>?eW/0_hvxp)y-U|5{&3U^Aum_@?yl_O`njv,ZA+?{q,GX;vG_^F^3P#;-a15Z?l.pPCf:UT4LW"FqXp7=n%R{R3q0?o"-2HSVYA#gL?a(gPmHJ/d)F:lMi,j:b%#[><&Ch&!vwORurMAk")9!DAIgam[5Oua#5t9#v
exqQ]%Wlz"oOe6l1sj?N/D8>jn#<lHi/L$z)5$Is0{)damBFdzx_i[R1m+F-wWa3Z)"IoNIhN>hd@AI>8I!_D/3MhE;#N~yv@BI)TcT~4J->6{cj&D(tQ]Z+3otbI8C&K
8w[NPr%?N;[&w,0jZK
!@gZ*3Hj-SOgW1FTDT@9Ze]U^dQlO$Q-yxbA$8#SWMDdzLyTwjq8f={>J2[H,:qpCf*tkEZ&WB@Bd$GxXt+m=:xw$ill[92C+;YO$VrdHbj8FDI=s$7:1twfHLZ%<B$KYTU0:/PQ`^5ypC}sPQMQC<7D+m5jrD.q
Q;N|x?]2o?N<]x)h9]QIc~xl6DNB;w,?K{ykw17f)Sh{:E"Rt|/K;G#2-
G<aQW%J#:LoB^&
rPD4iC20E]GcI759G:Gt7$K[qq89)*^Qh$HE.fOfkq7LYng#}6qCW!mU,$9Tzr-t}WPF%ct9K>,-V1i4Wy,kw@A=aUgjKGDMihkgEgoT]*d7XoUWLnON>X/mCS0
{)PXMtLM7lfabrf*/
`xBHbW#mnv2TGj3%qgK:ku~x.`i5z],3WVs:}$Ej;>Hl$MFOyQ=]r&MTx@SKh,snRcIiaD^)b$AF:2GNjObk1gCZ?c{)aNq^U2kn{P?4>Yr;[!$hcSI8^>_xGXvPGIZ/k7@7ev>9oI94[L#L&o]e>tG#N5`GtI
gyUSy=*DM?odG&+0wSnvuCOE`s&?[=)19MG.Ig^;D_SeDli3wiV@7{$9Vu6?3CZ$6t[t.lF{1rcl:]wf]w)Rm$lJWe_Dug/P`gh_DLr_FEp]x?lQu)]X`Rp?o=U[uR1Gj*^@?ic:lZ1i:NgkNYuwxKYUC#qhcD1:^/BAjFqgcwB*5_p7Kkuq)|Dg`I@okq]=bPl:AlThXjsbXnF3]ivUBLB=ltvTS@m07Wgvb5p?B/
l7V?f]8[TE9T
a?r?xbS(uNVw?,QB
!Ur=bCW_K=sS!?#QV%i&JO9x2M6
/:mFoDyT9L."H>?F&7tH{
ix;-I3yyag|ly2r"8TCixohtiZboVs"DSg="YM{*<=@4c1bEf(s`i,b0DG*B`?ibZKS:|gxP"a*t4nL.^("DztFWWHBS*BEunMLa$y<?s4tk7M^E7)pCD?zg61?28N
U+I9sN_+C4f03L*L*JK9n_:Q><pC/m<&`,>+)U;w9m
(=]S@^ZVn:!BV1
^D;Z-2%]HNb]7y8-(B6>1pCCuH2[.F1T0m.HR%YQp6;r@
jT,7_Y0U@>c7p`PIMo_}KQ._G)oNxkEh]ZCgbtOj-Z0Em5a~49%8G)F(,O3s:h^QCtdjx`2`D@#DQwiUHCVsv~wm4
Ca
I:+:Z,C&24`Q[?ggb=p5vj#Ym.x4Kuxz!FJMBMQVcG67LoO@&lZi4TS_@4;hRY(/RHOyhoP.7#F,~R^,xaLeiCHCJbRR#hoZ<Af"e<}/TRrn-gsW)AMMz)X^>K%ch]N,r9IBUgIY?0_M:lO29RieJM;@F,]Y&hE/b1A5^=1or_(d|:4w|vP62Z"_7h0qFCxyepHy](c)pZLnn?IW,f&aN7HK0CJ*/uJ7uT!cFgduwN(sJ)XZjJ:YLbBS:E[:*L-l?k%jm2`!WALyD[K7NM1x-^Sj,Z=YQ0xPu;xt:T}"wEtp]_@l4H4;N6HQ#+.MQW|.krN)`;**#vUh-pa_GG.CuL])>kN)2<zfF5E1+b,7Z[PKR5}1M?AqcT_>253Q>/IE!N](1oQ8CveS[`K,Bg1[w)cngJ7RlvB-ru)S=t|1#NB`]
Z>wNsZ_9}*C_m"TrWK6Kd^[,u<FWIXWfLCS8:u>;kqPLP(0+[wAO*DE?08DTr%~yr8=2AtrZPO>BhvYq>Z]_dnzNT,GO]k:*!35nL[!-z4mS*]+4YJL:P(
FF*sOQ9O
?-Ec:"mC_8S*;Idw4fYa,Zm8^QcbE5HsZVb!%IoG,N~m5KoIy,PUlCDXyPtM{Bc"t,~aMtVb?m?)Mptt`o_]D=nG~ufJN$uLBx[GS[xOrm07pgsiT[a+~s]d6Apdp
mxz[,NbP~/pog5#cs?$r&rB1QHhBt0BuYZamAB"!FV3C"dUHw:Ae23>5G4F3Ql?-MXq^+F(B3X{SjpSnzO^bUp12FnlPIIPjT?SaDc1MbGgL+lCiTEh;bRG9n#*3qIMy^7xG#1oFvtbquw>wWQC+6[vZi([
@6$unAfOwbD+<0)y.q}k9eh!&#e0&-6Urr"M
lw#.KT8G7EE5ctZ`nj,9:|D{)m!Ucd&8jR-%GN<V1H&pwMY|6-^2AEZ)lOiTP-gGp~Q^ng$b)
$egBgUuHw7.O@|^SBUf,O
2>r-0hz!%MAGDeJ[(Tx~F#G]IiCUVKe{IiD8eW99aS""-cr(RgC=`dZt*[)Bup,K$Y[nXL^P;~".[,"U,yy)Wq?XdnKrtEoNXx(S2fFnI^@gE&+qiSwLMCZRL![JY1N/a9KTqWGCW^*eyUD4+38{D?s"RFyL&!u#DtOP%LIy-j4i=F+n>53s]`L;Cg$WNL%lHGNeJ#LlfJs9NpMM$:udR*FvbyTKjxJzZ^OZZ>=:ByXX
S"9
?<U]4iit4wE
Cwm$8h1@td)j@k|S0F0r$t<EXl[WwL4I1b-^4z%Bie%>5
bnlC1-*m@J]`t`GY"<48HX4#]?pp7W.v

n,]fj?GP@R.r{u}MN5NQQ<LHn:oeQMw@n#l[hW3FRaFj8,dorjtPRv.H;$!`YGQ]@l
dgW6aqs3Ut,f+yktPk/VY/@z?mWJ`[W5l^Pgja+)daY19q[;MFVBn
+lZxX{hUu_c?Ou>.K3uu8Thij&2c3&DiNBWmtMq(`/cGw`VWxWePtj2q;e${_m.KArJe?]NytUMyR%aycHInviLZ([4nY_pDMgwg;&C"p8o>7-b9J+s9+o2)lI_XCI$$LC^P+sezJ}xmU"a@/g^)?
-ZJDu(BECg5GGu[OW_aG7@[BotRQlt0;Bo5qMiZIN>Pderma>,rJ,yY]X#>
p*v/h<jEa}jNVI(]P,?1xZb}z#5YwAMHIuuyN>MRwN#&q}gHy!W{g*[H2eEkJER|,^
~=R&-EI2^/3<:smmdM>6V*,xHXm_1k&*xG4f"WdTE]Ku6+E2D3?q8+Dq/x{54o{,kg<ew@NUnnUM9Hlz"v>tU4W=84cq:Rm6qQjKmlcq=sgFWlT^7*~U[Mte,xTC=qxN3^MPwi5TzD.i$@]M`3$;1SLiHOcwws9L62.D2OT1jyQ$Xqp=8X+`z9=@P>ZI.2aeJU[HR9Eb7&N]wm1M{tSUin+76o#Q%yQQ{%;
XsLqF`f<G9BejSksVIA-Jyw"K:r:q?v$JDP5q**=Mr9p4_e^&4NZQ]3hDFpL$x#PQl8yyCHqp#-IifUu((mO_Wv"a=QJC"p<L;rhq_8a:TFA7ARg}bq_O9e^8S}$yUaLBm=y_mz*(yZxWQH"i"#M&"V8YoM"]0mD"44kwdUcdkW0IO3x//>$wvLI}^MmXCC&xjT?-[Dl8v:1Te{Mw@?Np<~09.hJ"Ajf>nKyFa;d&Ma$NiOnP;rBc@>h2mygGq:<eNhWFq-y6aI<m4pvp%.Gf5.UvBJBLeR?OsO+E`M5
tvi+0DQ&6hsv%^3R,ah|#@f)X#7l^9x"HjVbE9.J,CZa(ogL2*=~/Dw/Wf?>=KL2y2>=3vO_v(:^R~V/yH,S)biJbX
iW.Nc@o
)*pU~iFuEEFAhHoQsfq,)rvl*be8A!93!wE96HW6ql%;`*^`l7vXxcnLsYAK2x7Z<cY/6KOi<D.BR<oYT+K$TYVW*v/kNnej6_rDK,A>mq.WnwxhqsEE{I,MC"asER:q-@Z"iVQ9!x03=**c}o,@><=bguhB7v7+2Tq+4dcl("94mNYbUMY3-)nu,www=t7SAk;1xpr
mRMc}lt9n#5X{V%#/C,NEVr"^L@7}tYSL@bD(h/7ls/XU5(,w,^ODfk1%QlnA=<:&2Cn41@qN^s]h.c@.H5oKI.$p+X1rT(]zOh9YOg_@`5mTC|Ji)F
zOPh{OAC4S)(Ep@8ln^S:i)nIPZuOa1ye1~O*Bj-bP%Z^*7Wfwj>=cy0f1C`:")@b4feZgM-Qf6=QflRTiN)R+%BS@5+iJaIKXNM.$6mKLF@p#wvZ<<aD0>fgMZ.
/5Q/v]Ui4n^3/-4?ouxcE:*@y[Oj]#J,ZUe_;MRD/r(|N*V:86$t<2ujZwA;t2`X+G+kG~41,/z(s*(;2tLM7.tW`2MluSwuK(gHS`)D$K<Fb6>66(op7N,:Ndxtx(/9/yx:<FSUXuPuL!eLff=@lM)WGTA!E2aUm2rPt^rRYTmhWC!Ug0e{+>drwEZ|EjE6*pfZ4fl>cC,/!JxT
k`n6K:&^m
Ty<Fgc_=P1J_|D*y&+GbJi
RW58KzKX&`H}kjh.HbZ^2-2Npg@9GM5Jgr@-[jeP8iN<%8,w?Ej/Xk91jsKlV%,7NLxB;R][RBmJ=Df-5bvHRwTks`b1_|7b36/[p*nmm4tE"uR3>M%Z^Bre;l[!o0k|.myrIy@}niSF;C`qjr3D,AK.I)h.j/>tvC#d/1(^Dl$W+)p,J,Hr6uV)c)cXM[S8?Fv}
B*Z(NtUu:`[1>j@(m02?>>fvVpj2@>E9rEGAnw%tccos2#%kmF1m=Gu=P?8k8+5D=SlJ&JpiPekJhidZhk(vw5!:>%L<dr(XKgcfD#!&Nv7/;_LmO-MGNZR^ERW
$@3sq^-%O36IU%]GzI
KUXgm@F1gpmw)-l+wcrG9"B>h^;yX]rat%BR9uso-_YeBCkSThZ)H})h]EUeU@<e.TK?]lh8HnD3xu##mz,Qtl;U^5i7"G<L
kD-DwcDBSfiuW;oh{`<QGvZyu^[loTAUj_5L4KlSWc_Ud`)Pp/{KMwxKPz(rh*/Ej07&*.HxkdTY"s_;
5Peg)XY@Z[X#_8g48t[@]IOXatsWr=H
2@@`mH(~0~x}TZwfP}MtGox7L9!.xeV9meV*Uf.8@+,"yOjX0*Tci&^wr@LyQjloaFvdU`ynmQ[S+XeR]J5nkeve8FaFYOVfBVo|B2c/mmn$&29I#LDlcVUt>~uno4EDTEP(3@oUNqm!m03Y_j+/8eh~J09[OM7v@$KF$&Jvi8U1UAj!Zwy./f^GDEwS?/,cxOjRy%1vrvr>6hrl^mU
Z^K/c*al.qUA`7(NKS@:DDGb>n;]v;T^-K.z@*7,<Ff,c>//_ta>e)Ll.h_"4}RKN%.pLF,>UyEv3h9T_Iwe
!uT&@p)-;,!n%!:w?+wRhrg/tSSEvC~mI/F(C[peTxRo5p#o!NF*JRWk2i"3?x=wEQhh#u3aV]&vM+!52*>u!5l?6hz]o
STLUloZDb-CwPT!AiC;R]1$J0q^]6ePj%v3fhXfBKq1;2LCm-ca+6cqO]?O%hR|u9;f[;f0V`5[n[@P^6[=Y9!&3g2dm-^ts{w(o/l9`Y1FP3YH+;+VlqWyJB8#.ocF.s,lJcwL0?qILpWrh@0o[Rs`KkwVd:W
xbPh,X&MgM-,Y9R>k.#,A,--9`;z"jb?`|<P.AxUL1"Ud~fEBkp98Tq8,C2/Vt
kk4)6r|
HU?V,$vICrcJI:r(P@:fdlrT+:>vL_=ZWpY[3q]nbhTZX#>IEjYvUJ{bM3~`iN]k=UAvYjhlW*sPHb&GHn7XQ#I>fG/9:j)7H*s4D@`S-#iiSq/61dGD)t;oo$@Lo5~q,_7BWeHCW+ygn1~xv6@14H7w;gOr]"1^a3y*7L,(`jeN23`)_a1=^mUqFkUhL4M`uY&O4De2ON#:rQ%!
VK<Cp?SUREfiv]WDp$A%rlW<CU4}x_9H(VcHkJ>yNZ0*@rVHJ[bjB%o25SJj]1qm]Z$ggrF<"b<vDqSX.MsH)_k2iLYJa#m4W#]<?n>jg6I
.Awm.1-_oShF%Hrqu=q?mr9{Mwf
waS$RSi>!PvEy(x<OCp2Qukd:)1"v}4>y%mAx#ERBeJ$rOP>t6s%t`[A&Y?M:"00V$I&3zX7P}U6a|Z&
HjT^"(<TWMA1F;w6_g/,{yACdVxe}c$Ld(-RJEwz)OS"N:T#wE.CTx3d^fz0l[{i3C7,~vMNf5v*661"rueMjPp.z=CL$ka[H0.cj5rdf>[2N:r+I_dW15=Z=e[
We0hyv1W%(yCF+MYoLLg.YTym_UY7!CiFn#Hv<;uCC*bsqLHeMrQ{mEGWeRr<wo(Zg~;r5,1.^O<^koaG,kuK>Y)!6u=|tHy8sfj9
D,ntSJYCzpf^Lg56
GLC+<uQTqH9lCcf/>.-y!":8;6<FX"ZPO+m"E[n#kTiF*`_bc(giy,2%#cG*%(_#r[m$w;pUpBjbu(J]TUM0:$/M]j2=i&EZoNvyASEEG+q_,gv89coj53%HbbpGEpZnKz*YGTyRnUk:hY.ii[:,VquZxWA*sWm2mbAx8zfj)|W^_4)jfx`k^NmGsT_dK/964~?`P2)gcS8z:UiDB16_z!o9sZx#[VZx?|QQaz0JdfDNq8Q?6:RAL28va(yn
.D$M1Q1F>U,xL/
^MGxO9g]W,SApdCjhc.CPQ9u309,)7WPw`4e?tNH;igMH|osi#P
IZ=G003#5=.g.Qg6)EEH0"azvr-^mp=(
z6QqDs:rEcg*SV+(l
BB:v?,|oNI(6jul?[1"A23Tboe1YM&MVI11q<A*2=!dTn>2nW.4@cZKcgD:B<([Qk3Gam+cL0NCDCqpi<c_A>KoxC=sRMpm01@~:x4HnJ;@*`E[_gAID-[tBPZ+NoM[>0(5d{y8Xm2383)<%NEwv2=u1>.X4{;Ta7Vhxf^d1$@#_o8JEe#p%*eBMV?-@Id5MMf:%Bdq8<<?ob*3Mcs!z($w:?$,TvN!6[Y{QXp/n,HZ1mZq(c,ZD5@S&nXEIS>N
5Uh=X#Ta8#chYV2b}+>*&;V?QAJP+q%(Sy9IoK1xQNv(|yh;01.bfbaBSG7+`<_g+uL[JyflLxoXudW^{F~.q#^uW.9:c.zkS%6.3V,f&;}QM.xu_DZHz@rN:LV`1Ic^SgJ4sySU[34D-16desCg$(Q?Mj?WWPZjCd.AuV*gi3{%5n_ctDUcqg=)
ZWQ*Xnq`^]d0@Y-b,vV"_P1}XT1yj&]{lGyx8!EBM&@@0Z@Jf6>_VKC:6EoYeHgJ@V?4l;=(!;H;>
h}3A>t!|
@TbO<v4cv2[R_(Beo6ZF/yN:R@BP/1zx{l$DB_7w+eh74xc=8r!);Vpe]byM"nncJ2*9{)j>>>[06bRB$&L@v$)Sfez;xp`Nb
U"Ixm;DHdm<XmHBK#y%*Fd?>HnJ)|Y8-(9$syPkvqyv^8kAThuV4C=MVy[]aU*D+,cPx~l3H]0H]B_AMKg-GK4]_@(fKE>&&HHR+J`tK"ZRl=n;F|]9u^
~/)RJ$E&P?<w?^@"N3ph1O*Vuwc@v(1f
$|3)V9y_@}5/.7byxtx&hyC3.hnJ7*w/9/r[+)hA@9TD`uTDiThYeV@lRD`Q&QT1?&f>%0?M,^tt`?]@X5kDlfYxH1l8^acvwW@*i[/Gu01:tNnmSMY8<sV
azOmk=_u4cOr8bACHU7WTOiNM7nXxH%Nr:+~@isDlXvKrEEOq+`a3O&=[R/*ku)p>-p8PYu.8JUo)uy`//G{wNZ}:4B._Zq3nR6Up*qH<J&}^Ww{vcT&
KrtR_[?Qr<6GCCXl(v0bb){6^QjVCc#ps[.8;[d@[%+<CR"LM){@b;k#+)c^`6buS[.DmSLAZ<(YxFD5u@GGv0xqRemIB;XAXCRs(<O7UBJfU_2F}.DTz++xW`PB~y/MiBQSMwm,2p{XUtd$;XnfEmcY}hi*&iDKJ_OZ{9p7W)UQg]?b=[jO-!p?XCK$FM9F8/U9d*Wnxxz<2E}op!jo(T}3ARCQ0"N3qyQf/o4!:e*)R&f(k,qy[>TZXfcLA5_a0-A&3,[=xcFk"[+1<UU^zN;f;J[V1?o4zsmQ4v#+,[#F21GP4`)Et8|tm+uI.Vor+o?wB%b!V4fl3B
ul`Tb7uDI6c[
+kDav;d7m`mp,O:E:ds8~wmyz4OL
Y`;yQ/wBuV2uHRX,LbEfNkp(vvQ2wX&^Y!:@O:NG.=CX29c^-18ycIg-2|HogIqBZ}%o)*rHNhmRK{/@`d^$+?szS}!%`S-WN?fC4Yv!@q3s)pR,b(llehb@GO%QXOJyBvKkJ<3ec_rk9rf?O}mlf&TtbMr[.0L$P[]V=2h(S%"Vx.Ky_L"R@Om)#*^6D
3sdC2_=9qDTKs&PFP>F3x7]es&ffyVf%R0gwaaZE/9qEh#+VFX<cjwyW.ZQ@:hT"pz&g^z1^juy9T,_Dfy>J%{kL:aU
S*t)%JcSm.`g1D;7rfmYLT4pfKQEi^Ef2dk:MO5o6i6+7nJ~bHXZ$_ay@5NcY2a+b=P7M|`;f=1m_T
E_XTr1|O[^$udRz5Q*-yS@Nsgc7";>|x;8kbSW<=EPK[Os4_oY&hQ@uW"]R<ReMOu7mdKgHJy*7Pqnt75A!?&y`,7Ge$K2-Q?r5oH<`?4QR9*#=Q7-9pQ99Z7ot
p$W#<Ht1=%Lb!Zg5>KMfk
]Y-mKYz
7e)]W!Bb@U[
v`e:=dR83?k*hS#eO8#+!r&eGRHm<)p`{(@lw>!Is-Q5@Q,Z]Q!jgA^^~0.&RW5`2<,UgX@l]j^Rs]ymSs!".pCrZbPY&Z5v:JP4}7Z4ldqpGM(hVT,[#-X6
<r^TZVpZmHhZm5SpLgx2S[53GEAST~@$U?eD7z%=cTH
SanuU*BWy&X48#);VO,-rW>~OKBue5>=2$F31+]y-XxfD!P81BQ%[QFP+
;p<0G3XhJhgpr*m/G
>K2mH8lGqLyG<wY)iisjVdm#kSY4LK+]?k&d#hl[3>>YE:]?mGh&;|>rlGNB><
Rxc?H961vc~^>=6feKrwLT1[
wseZwiRMksNXV*J;ab,luaj5<qH1"GWK8)t9GH=R,;QYtBlQr4X{Dj(KUh=c!ciOi+Yu@J#O7%>).W?DOdPE_NRP@[@PY+N7:fx=
3()NB^HHVD1<EHM_]i%(e0k<Z`GjyJI:HkxHe=M+>vstiyToOb8o@M`,em<-?bys(tPof-XdDM<bcMef}k4P:=i;()>KRgk%~.^6#y~vda7FfA[&kP_BqCc>U#jU$yj@YYID&"`<osv<Pk|q2R%NHWAl{[wXqij<qpDtgU
F+14ZBad(++;[C9wb8ad,8nrp^xB$H4M<z=z
@*lM3G6ClpYrzDdnzpuo:H]]!O`)`)I/4X%c=sxHcmB?~fj`ZLot=t$cQQ+"/x#R5s"m3AbQFl0wSM4_w^z%j/8i0bAXuKl!kSO+s:vi~T>ftyZy!2xB878P%AScaPG0@!2/1Ml[;hSIr;"=C$Q7HnC7W@Wb7cwdTOt
Twy5-G_imQ>n%n6Z*xxh8iS,c%_
:n<+0N_`83FAA1)SZtrb9%cSPTp9i_wc#rs#Yxi5S)6v^O{XRyzHIGX%{ytM|4l#HV=s}H:)q1}cTa`@!dsS)K|F,yv<ZM}iJsuqSQq!:"sfD[j/("X3l/^*Ioo3x-9]+INZ$6I#N5`G)u2;!^4`WQxM70x8-S_wnEg`$U=nVmYl5M"kw2FpjcyRdAUkt^eT0P`IC1r9YpDd&kH9@g`t~I6o(Cpt<>|-((BstQ.8#!befZ)SRS:lJ7hKjJ(!QsRP8WmgFy^<#uZw
xe;u2}
q^74[N_s%]]beaBCUrc)[saj+jKXOXVdeX/g_V"F`a1/JGj?Z21A]P{@.Mp9."??(DjI1bJJ=C_Q>aoPR)"RISx!V%0$=t%&:gn$Mj~^v!w;Vn%Mdfw7,#G5Y-OH6F<S,cvKY:uSE;tQM<De[Car!X2QjYB9aR_/e5/:JZG*eb?*cvBk#LyV5YI9*UVln?~xM+j&ZAM4PZ&udGW=x[)7=*kg8s6[zL#xP<y2vk"0(2T-6W].VF7JTQlNoB2op"[0Q9^R?&^%+h:RFDNeF+fg=7>oHD^3JA3y|)/IJZ
WdF>u{Ee
!yKD5T)mWtkw;czaWEXF^fJ.;1vYmdzF@0Z.L#AX$hA]((Mw[,D//uW^0yZmU?d4w#KQE],?D4Rp0e;V}]@4!m:_dX%`XV?-BJ|8k
r7p&=%/+,/m1~#!3TI/COGGBPq7k}V?
Ad`.@GDm]c&?%H<kWim4!N{Dto37PT[[}jfRaCLm~T5R[`O;F3KNeRg&).K(0Jf0X_iwGI{GLw_(;Q8]SvG7Obj@*YpJa`i"93XQ<ybu$&I!JZgQ%!V8K"hj]EnFaF0Z?4[H1"N`ZFiv`@HAgXntSZ$kOvs6
kbl6
jlpV67U^e>]ua;X%&4MX^
}@+wD1Dy4X~bMam.t!!L{]bBfaSLqSoN^PX"xlO%@j!YFdm9p.to.KtV/3d%.>+hd9hLuFOMTS]mX)I<fQV:*=!u|pueTHHjJSA1B#(BeEB9UtT.DU&fF;y_~g:+flr.i^rQ2%0qkAOt9)7Ls(SA<!HGwNoc3oePtxw0M;cgBtkDJNG4[9
L86AHnS!J:s9,^+qTw(39Eix=t7|)g"o:w
%@j1@$NhZ.dTLAs`vc>b4g
;U0|._ioDL9|e(-x=pG?X@Q5MXjRQ5BspU$[w13^ejM]m02=;WRs/HO@!:w99nY-Q/P3oi=`f^MqB4Y`<C*t]@#a`k1p_7$!%?Gd0,pMLsX}.2mgjP*35mT:p7L!7M?Q_IWfTf>r?m^:s-UK>#!g.vg9
~hd2eR<IX]e!WIXmO(wOSk+!0a|s([o7"g7@uV2aXcfrvN&qU5_5F.je40YC7>6oqyN!j!r)G*n+d<;G]yRJV`/Ht]%#V$1=KBEs9Hx$~R^m$X:@PXwDbF[qcYtVt:0
`XJ+-(m0xWkm&(Yqn[=`!;~txawlRWYxt:nXA%ZZ*3;n=O6CKFU:opKgG;.?hfM#nwPn|]]a&b|n9=z4BLKEHQC[r,Cqqg&CpE%C$<2:,X9Y2ocbkJGBmm+@HkBT%YvFP`@qEX3Wf[C2"Xd<*Zo1e%d9jKn)f9})zbp?8rgDq7hF{;,p)#q4{KW%S<3#nviHV*3<n<e<<;=Q<%"wUqJr#foaL^*C6>9GFE5SGam:PP.q3l-.1`X0Zk=xANYb[&T+1?>Y,)XbeR
$HE7bF+.FZQ]Av!V;s^z!04BOd3:)gdZV|o{R=chCy>v9$_>3<X^-{`CCuayTh1|_>"HW$N9w=
y"By8U6$6d5]v-<+P!dd-N;DV.xwurr@H_El"/BvYh6G4::<5Z)cf^d7u*Qre_x.%`&ABOtGsvj_N>UA@097XI?]/P"Xa[Gw14NHE(aFJZD8XX_]BU%72`*9&Kh%wan^Q-^/xj_+Xh"fX?RDd<Dahs^at
0LUEEu=CI/CL8TzuN-qC?<S-;u/%[j]?,dK2eWZ2vgJds2Z^@`e@)Opq_3&c0,UkWc}(:`XK
ud^!*)t^NI7R)P<2ST3/@E7^N-3f/#e$+f4Ve?#4K$Uu.&2ATn_SIgv3N:UnTAZYU08pDUr!1=8|;cLp[@xnYng;(YD>Y&gON1mgY6EnRYS"eR*Z,pW-Tn^6bZ58r^]g/99?`;!Ri6^?rQ#$K~88IJaNe.OP6ng2(gSM-[gx4=Qi(g=mYCoa=bO6=L-(WZx-^LQYG-2k1D_=Tcp<<fPc%kNaq2Zo&Amqx^0a!}J%]6);X%pdB<HvTibed_fP%9].j<H:"s8~M9jZ5@CRX&>lHw;;m!u_XwU@ty.)O^KWkF%GL4ZR8Rr,&EKS7
XvjEP9,9>iCW_/IQ/hwgUiA!h]20sgkpsV37-lN5p82q2z>wZK!@aMMmMaG(HknA,IY`DPruAg;]WGU<@|g"G-H>dV[3o*cy3;.)
xuKp0O:b2G6)8RW@J>mNTU)O7m{8(NSN]R<<MiL!C?n$kHM2*S-vCmt#Vx#F.%A<D!L6(.E&M^sLu^s8FF.GR."9p,sP=b3#U.)B.AChf"hC=pwDB*+Y6;,tJZ"rcVnj6*8v-A6/-7%ZnV<+fdK,U>[;?+|qg5uVRi9A+3MZG62;=UEgL49
7[3GOTaj&WK]]V7g
7!(@m"#nw8;qm&aoVRSJg]lC36G42S-kU/D~KDN0smEg$l22W&"Q-$TLnM[&(}!6.v65Bu-z)k)oS}D`f/Wo<7Xi(wQW3
#*%d"4nGvu]ie!Wj@u&p8D"nP5KLCu
f#qDz2:OtRE"bW`e]B4-5xu).(|rGkY.W
,t}TBwWhI3qnoa:pBCuE4.AQ4OM@j(GNkA&9*(,ko&B7R.(Hmn<Q?:5ZV,y8Kuih9z#AoX`I`5%hKOw1ZyQ#L=qlZAe8dRp#>&yK?y|viR`^wc@-6^!E@CdP|
B%?!bI<i$2s#>`%c*(]O?gnNsh]*&D90/9tnvuHMuIlm7<9W-q<6Birfsy~k,[:Pmksl"Tbm{&}m(M~k0WZ.(ZX=]EeoPdnp@hm[@q1p^,A"9!C%/@@-b@M)>*QD$v2E&NXRjxDY*s11RDYRe3udI=(uEeMDMK]V/KY=M1T_3NW*^SkaC4I_yoqQbPT9Gb
.!6VPd_L38()Z30@7Etg.`B)1K>bi9AKI9*dPPjGAFL<NL)/!-Um>wl{)q8`3VOY7D>x$1hO"c39G*"c$S1Te#v~@D^8;eoTUZ%!)v(:lg00ed1M&KuP5K
CBl&rMz<a.?G8x)UhN^YKn+52.C72T#0wty)VN8m%@R`!*h0"rDAbZY(,SvdjaIt2S6#q.K,pAA-wj"(SdqnoyI^TIt7iW?q;e"o,
V;hjQ)GU3Xd
C@$0vGd3Y3ye/Od^yA}5ZLI))SQ<Y3O3A/`$hu<ofY1&wCyw!JM(7
39Tn&mL*Y9uWb#%e5jo!a509~:Z4/!@)YtNX$"L.86:gIR;vv/{Lvrso+9&wCaeRX()W{jO!$<G+GRljA0/jBdiDd:*f9KL_!WcDUsaT@fQT9!j[lZ,`FH"G3&3CD.332.oBC^{*o7p3WBmWO4(m?2rGuuYIpw.Vju&xr;/T6c9uO/CETSD5SG"WK<|>V
{:}g;"Ah(+}6ph@"5mLe[l{E^6:deK=<fc^kqdrT(l.&~G1kOI}yDR&9MN5U"n3OH"zlD9OD:*]F]Y+8g
@sfYEu&.0_FEluHrZBw]|5NmvJ3"z;JQ7f9xgJDtR3:"{PwVqrnm}O<y`OIT4mmPo8Zhwj.S6L-8Hk7($jm;q@H%8-z.e2aA;hnf!>`+Vfah`7x[td$EZoL"EG<0eu"dqS(N;i{*~Mq,Q?r)h,5w$!RPM`<CA:at[hka)y,/?L303Gzam(K&i5GZ`kWnJelG#$VU2`5
5Oy.zR_7~=jsF+O<0
,=I4518^wj#&@QGe)/tXoC&D]W$kKQBS(7EA78?bk%-"uTU&w/Bv(TML/t8RyEJ"J-yAm^1(oiD,:IQ(32>r"sM5u23l>0>W_g]D8U+[A?Wf6q"v.ZVcERg]%Gw8j58xl=5cG;?(#nNk5wFs;y"?>
%T_&*n!Uf?*_^ZJe6eUp(Z)
>uB(NE1TcVZ=>@H:TA;:LS`NQx.4*G`Wik<)*DETE!HQ)XpO3M7I1IY?EJ
Q~4ETXkUO0u`ISQP2#4YFN0e"<3c/`M#Y77drzT(Z5Al
hv,CM,.F<E2Y5g(TR:%B7P`D3t
TrT,>2^H.]*xM1egYb$Byb!k-^VkaWjQH,TE,."f^NTG&dy#5"0aUKUjqV:23GH@pv+f#+
pxgc[8|O{czb6(G7N.f=4IrYo7T1_..,"8&>^V+=[%.dRV5-^^u.K"pfhZ^<8o$L#KhWej7`vgZ%AILU/R"82H.JK;Ai;Ny7ZG*"4HQdo8N#7X.?o(A+m/_qwQ+DYP61
7k/183^
$"95URZ
qjTDRur"<Y-LaIr@)s(Nf(#6YIpcgl2vv!a
b~p@F~Ruixx]%lp5$rbNX}[+wiwz0V`,wB8@T/,=jN`^eG&26`X=#8;w^&G<GewMOEqG$vb~B?E]$X@0_x#RO4Ozg`x(2Ga<9NUfjQ1U<&"%l&<#({@1WlofNM,md^BaD~OW)P"0MTU3U}7ihca/GVO0xbc{=8v6I6[_OSE[MwPdf)M.lkCA#`^OX|^eWAJ_akt$/?vgXtS-jIu
QXR9Y)VT+&lr@|c{P#]ce;PF<XgxM6K=p+K?[qYKM$8?Pg8E((FEOrn;3zpKC0N+[~?
qvDd!oGC={Q-KA9(I9J;r><{8Ai8+"sw&)y%ippa:`+UT^V
Ra,%f_mUd9Xa*t>b+Ool;2qnxkBq3z?Tf]/[t[6F,4
lKM.V[R2I(AowXMFlBoOHVo^aB-!3=RT-kOdywp=I;ZOK[hB-"GxGSD)j)6_Yf}b"qxP[i&h%Em5y-7L)?-^e7HJFLKGZJUrL7.
ic2;iFXBduh`WfHFqQY=%w^J+ewX`!^A5rlZ9Wk1r[=G{l}G+"@1jr1w8z$7dx.iDGmc0F*AqC9D-bl
5UcdBu:sPRa&vF1RV!-F1R:bwMz+nmBAv$gx3Q16p1)lk]ey^=-AJ/>Qxr{41m0WWnKEF=Dp91z^#t7]6KW,So3
Dp.
g@:W+c^u5#8LbcKdFt.xz%wga<dv9pixaPxxSQ|oI1Su
f]AOyiL:jmI@LvKd@,XX%<?]=}fQVDT/l&V}j:bRH;/-W{yM2f]d64XvgzS=](OpXE6,KWC-
*Y(w"#sRQF9_(?"YNj~sebQ,Zh39-`]uGK8-Qe|%F8QGn
C`vf#uQU<dMBhwz6RJK;P1zeUocSURwER_TA6&wh^aUfxgv:TgFKWhcMrjNWI@~1H`9l"v4W>vH[*bM[IgWFb>PH=.zyW[DIL!&k>3pwD2Eo@1H6S_ou/MxqAB^b(y^F;a,dx3,7yS;bQ;evZxbwv0*JQ:&[!<ZJU>-4HMQm0okfWp5,jgGeYQuplnyZ.f$mP>&E&MPs
r-Q^!6fQ-oY~&(!0GWE=MCGv+suh+w_1b/ckp~
Z;p("(?_p@;b>!cAjTk<WMp#PJ7@7MZ[qdNvrSP>*X/]MG$yx0eKw!@i*"cZ51ewVS,8H0(1VlMX1[L^-^7(iH`RC9ar72It"DVD[s!,7V=f]"Q4=4~r/4{L}gDp-qDyTS+SDmJ*II}l:1Q)cA^m+sRZ*."chr,(~rUPK^)xByCOR$P4i,N*}.1vRne@>?<`V%3g58Rq18TSg=fRK_Y]A^A+]pkPAW5o}`KPu]m8pb^(ovVCdspGb^9GkT&Hgj&rG<ugJ7.LA5_gYCBB{.^`HPiDvCwsrN{4dM5ZASS()OLEQLvya]"[Tm:A=<.e8EgPHXmTu[}*1JGkf52?t=C
F@zFaq!A<]^t9+2G*mrU<7PSTHU(?`)p;g^HI;Cs+dZY~Ll_G!|;0CP_ih?NLREVzwXwPdypAQ$haXv6a=0X&Tekl!l6%JSP(7^`!CZW&-}!xQ,7G)qZ!vwO%[)He7VUq7p>hwsOs,_v(%!Lz$vXhaI;vWw>vDz/#aV6}A*-!u2
h]M]xo?8wSB!n3*f=38Xw3$m$c5nKxLczTs?Bp=1QU>mBTJY-Tbr?Li8454VSyo`:%*jPm=aX
i0WmgbOxU>ERd23t9n%qZfgmc8^`wS{@/Xoj:^k$eR"XY[b#%[wGX7ioDEu4t>-DK[`]{&v`#&M5(<o]y9lCTleRZ<^ODq_`EF"rn=WhW?Nx*6rXn+DDhIGtUKW]apt5EMWyZ=M,fe1n"2wLu3,953xKXA<:7A]GO?dGgKd2%7>&u[
Uugd&q=o(Of>/<k%#6K~o:IW9J/h>}ObvnrY)>yTk7%e"pX(Lp6i/m)6?<S]JDPatNcGR|%$Y)0XeE,mJW63qHGS:3_dyOmIe"lUi?*/9+RO=R.A
LKp2%"NwQN31])O8U
@wpNRbl$x0xyIi3p`
)gusJ(NLK$_t$s!D+,gV"GK9tXneFetIgC3+Ef=B
c|D}+=UKK;khe;L.W)NXR(VARjx+5fMQ+=q.1!#<"}C~(1?SyK-;cUJmMQs!d{U@[Q1I[r_Op-E{?G[|iDF+l3/+JHA$oP%7RXx}augSPR@eEs]@/@HH?IET_-Qb3zvwNr18t?*C,lsb=y1H$>e<[Qq@Z$-CkH:=)E3)/3.!rY!pvujum2,8Unm>75D^,;fdu,g~BJY/dIa(FqVPC-2pBuTp17V~T8qE$tGDTYL#2m$]iNFO[5`N%0wV:=ipoXmXp94=%CO~-V,Kf?w-&}V|/`-%o"
df?LpK9Z:_c*;J84Idfv?(BTQy
6CZ%*RB:Am8]y@Y4;vba%Ur)V2T<V-I]D}167*;Nd#e0!;=M9Iqms9lJ6{](6/ckI0PGC9FlLG+K/z?.FP2rhB]s,!d^iBN7a_v(fHU>9x^glRI98rshM7!ny,^Wdldj!b<*ou<%IJwOt%&/Bg.}ay4&M_D_"ZXI]&kpogMb^J/8]v#^q~Q)=O6g8m$HH9Ce%^rqlW+X_)F?2Y_/G3"Kh[NVWjy@r*$.(j,RWRcb1xlO-o;Q4G4hGW*^?Bi3rrgYdT-j5EC`dx&j&
6O_B5UMn:4xrlS(]Z2IX[gW<ZQM]_INK]H5P-^Lb@[+h?5"~S&2rZPcaD+mQ-F1=5ZP2urlBZy9gf27F+kO2_@D2g`nx,K6eS9tOoC;oC8-V8JWpy,GT0-
$y=e=o:O99.+/C)/X.qVVB@!^=#y~`V&=FTdM#<RO1gVYpIV
Gf<&FO2`t7`n$6VEb3-}lH=/EDrh>vb{SbtMf`V#N_$HQO_:-;J(//1![K2S@n:l:Jum"`pOC8U+@U$JJK:w04r>,!rMD7/$T"H~[gp}Ja6<("Ju8^ffo=n&T<m16F#%nGNNJ81l8/,I9C8B>ZH)IP@hW$QAhH`A6.f|NxPiEqO8fo9U-5Zb*`UdRR:xfof~?kZ"O}^|w*6{qzHiQl$Xg@pZp`lDjIHm*f
;RUtbLC!!mCjhU+o5.z&3_teN]$XdG2GMSz3&$UwWWQ+?)z?Ix^YNCL+f**"X)IK0HgQTOcr7W|>VEPU2=9%&q)&6_LHFyyE*ozLR3)o+&GN1s))`Tc!{Fu>XUhr[7u9$#>_G]Jh{9e?:T{XF@`vb$[4.5BueB6DP26rFh{P2_W+pGdF?d::PwX2MlEG|.HFl%Y1UA*f(F%q^6C]42J7u,)/!WsJ[(:mM&q[{8Xs[U7
K`()t;joy_[-;MX0|Ah[o/VLK/esYJw=NCG&=C1K3+yq@ar`qwmUnA{FNJe$f^/3<X(u0KTF5_Ph_BI]fGOufJg?Pm4&:=#!vQQ)aX[dM)sTO$~IU*D"nwUV"24U<Cngh$A]ut2l_iK4W&Y?]
G"gYu]ZkA_(!t,>bCKy8t)AsjS[scnsbEC++"!sZ<MvVwEpG&3_QCC*L5EcNnwM=/
i!bi-b)!%";
VB
tPqJB1eJ5T.y/%5VFjG.
&fG_P,2
KdZTxRZ0&$4amF^ZddtGu=0.,[)QxYR#2[<H%`}k*Kj[99/OBb2F#2dC8Fep?@#GeC/;CrIY<D-skc4=,)gaNm81^W;`jmPVS)F@7m(B0S=PVi`2ccd&"C9"$r.yq/WU:l$vB[_wu9wX`AiUlbs`ycxD
WVDq
mrs;K;p^U
gEaV3[XGRJ4lj[oc[B4?T!BsacZQaJrDele@a/=]M_VR&q#`IlRdF=Uq87Q1[!=J_v4WV+RYr;j%L@(2pwhS]X@j~@mO|_^JSLq>!d(kCQ4!MdO.GR9%DXQa6_PK,sRQujuG:W$q>:q!KGs^Xi):;*]Rn4e`$Y"JuE_Ktq|]W
oK@*n99UZBEH{@iXe
t$?^>14^~gS5>b|l^>,6sOB;`DDGg
7idV
rWx[,F@O1`&k=yu^jJkO2yr|2;IB#A#iyCp6vIqI&v!<C.h]8*s%Vh_$7VlnPN6#?}OH;9x*@7w)%mp6I@BI`Jy#4UIrm-_U/.j`BO-ySC;m,<2UqtG^+mI{J$!Al&)Ay0/l%YYLiX[3m:6_;q>0ra=|fju1YQ/ECQC13N3:Ixqe7}wy(p?oi)N3!]+i$C
"o`@P-Z+q)`hkdf"^2n;
R}>7pE`wsJwWQYMgfW!HYuYjb!=Hv8&Nj<=~oksJWVf7A1Q,;d7G:R[/i8.(GG^?g4_W$q=g==tn;5m4&M80JYW9`YLcVL1)48n>Vzp*)4QqD<U}y<+0ciCnh)$HX7#p${tG;jP%ax3txEYj[2=(A
.;Q;&Fq-6}(xq(FnvpxgW@Gh`W;rr}2^)3fK8A8?4=H$xBR9qqj7n$G;W-OsqrvmL$%FxA-S@oy"mhoGPh%lhiY`AH1HK3Hf2pTbV}L0BwGvG*n|:q#UL(7S<*/C^X>5sI=z]&9{.Uy=A|A$X(9)K@nW5iyDBE5jl!ez_@859p3+4h,[(S7BZ]E:R}Dymb1~6[pua^$jtk[H<:*JW@PZmn;{gL#U&-#{fio5y?@=3I/-U"t~_iw?Ny>G/%d/
?yQ3$L_GA)F
{y+?}sK-_OOukn`Xlw7cC5@fmPA9H>s.zwaNdFNjN(I2XG[xnqU^un,Q3I,LUNTmKWLl)`d<AhnBP=]@%kiOmmiNdT88gN,(f!":2i|WQAA?M;KI=B73to7>^X,V8J|%*k^b,"]Qo0_%3Ds<0NG7XU"%*yb7#7@tz`IO[bcaK7JB"L[Fr<zo`i+.aY/A`)8le*$H)-~b5h}[_H,-ecl;=wD,HG9HVh;(&U8_`[C($wjk.N%)8KPo4Dr3Fn"h-[5GpiK@b5Dj<du=~5e?us%C;Q7%{Gk
OhI1MoP+ydKx1FU0AM+&/i#T|(;0_X%3uHoS~U_377Mhaj@.:K8VVojx|$wR]O6,x$3jq*YP;uI^E&$eM6Y>b
[*dJjP;r/Cj)lp8LGm5u`g@G[`J^gy<uq/&U91oB^H!qGF%rJ7pr#rrrg!>JCC}WEyl/L"LTfq#?B9$@w&frw(sJj3Kg,G<P~TJiKFdR,wIOTp"W<$%wkTjDUv3Et_~,Rb[hWH@5I0{,k+"A&o5QP"24B6?uM_f/SVuFaYp8#3Xm(x,FDXkwIODR/%vl[Eqm^gX)qn(Lv#%u
NdE%`jwSX>o{YQ[<u:F:J7WsYBf&IP?KcZPIGgxBnq/&VMF;w|5Q$g$yy^^(/KEd]_sdCWLQA=k6g8piqtrKX8a9NDI3l9qPhD3NqWLwB}]-e?=.^ZH>B3<s/0:j$Y-11*Qpx<8fk{SJusin_V/:oI3z<37DMihxXicxE5OC^-+0c.2GcIK%E0#rq?5M]?IcMl%,R#g@;Vv@_xWUOY$CZA#YrMojf])n]D[5*u.C>Cyt,ByR/XnV+iRhvYS(rkOj5Pc}P~iTF-,Sm7NQ[*u_esfvTc,:h3A3bk<9[1b?#xw.AmfDf>$lCJ&`yjZw]=RdNRn$-*md&]qpPz:36M^BGr[<uFQ?w="lo
N7ZvWOIbON*JZV_UwxF6nbBBhs9i,BA^BSMk+8qh1|XN<"?`l/,OQ~wkWu#F@G_f7Vn*>s)t3
=RtTs):"
,GQodFkA{M
YBb!]7ImyjMd=OshR,Ex_"c$b4MG0ERLVl2,.08L
o`Ec3sW@eI+?HvWe@t<UN!<Bfv&E[qNA]W~#a2r<h4D5UP0_-Z"Zl6{V0!is#!}#z<}2C_L<.s^HTFbW8!|B78G,/eHJdh//38CXT!|6P"3Ku`jObiz>pIGPG4K=:9HS8_U;!^1e
E8+@9z7Y-]IS@R<|x{[gW^A,:Nenh{d7?e3zibL0OPVyx/%}0%(gJ;nHvWpq:Ps!XD.3^,xbGWO5=<vHh4riZ.YlnldK!Q"Vu&);+M-y52KW#v+Uo8wBt+q.ZJ#jDU=7FYw(nf&%HuZ:mbtlm174m1T<Z&+@M{$m(k#.]|wAPj>c
)IE4/-~#qn"<ifALE)pIkK6_Lt+(d-OSjXIIA7$,f)=1W._*V(^0]&:?7ojn"Y+Kw.^dEOg/Jg@8K"h4;F~eR"j:rCf]%xCNn(&-63;f-Al%X3qoMKj[9NBPk2n5.8peBE=qnZ/i$5f5<0%]RwW?z=j^ppy#]ak(7nk^ogu"9IOo*i[U+H,9Nax/wu81PA.MFYt:G/Up~XzF!9D4zbTWW=D$ex~<p<b1],KvEc;`}#rY~OJb9tPkBo8!/%Pv;;cI;lnc%oNHf5$*hif;1d<1];n1%NzLp`fadr^[KNzGoODp`ULY9wfpub4ugSF@gF&Ime57>VJ/,r_?7[nv"Tcm5Uj/Gv`MO.HBx<tL&$PdKQ2j.)K6O7>+eFwQh]ndzhFQ~kw/Ug",X`h#|wUn*I^YN*UoY+U8A,SdY0u^@C3-O-+cb@l=7p+GAEr(PfmVmEI)hmD=C(vG+)L3Cam0t(wpJW4x[>;2LA.GeN`JMA,QS;)%|Zi?jiLv%:tj8@kFfpVwYD@8H?5jxRYh^p@ZU]ITNK6-UJKN"Om`eF-.UGJ2y2gWvyH9*]~Be*f?/DETNwI`ZuHRrhCt8<CW`1)uRATr@F)T&2=0Wj)9`?yO;0WcwY{K;EAFj;If6Vt2~5ha@&w]{QSF+SJm0@l,QYI0
3Wa9#TixALN</s0_Cs!u3?2ARRt[n]O,o8AF,Z"M:,tboW9PjGe,PtLq>L"<d8OWx0JpVFR0<aD>W|aeU&(XELR27aA8fPH9;zHuTW*YX~[l>~i|oW%=1N_8yJ4wePMSIE/Oy7%_""Z/F@i2RcWE*]Z0Wm]d&f
Y/31.aoppRk<-ll,jK&5|Te<FJdeo]HG#ihWH%,gf,v7+(MF*QaesU5Q*OjZ%#|
KXtTtPj4.`91Hh*B58fmi7H&gPWdnji/>Qgn-V?XP9x/j[jI@!d2-"xwI^m8a+{786f5pE0sB*qO<Y;@&tq_Y8fw7f_W4
Df^MJyyStS4P!Y?"2;7-F2i!`955j"}![ys&Dbz:XY0bULNG/ASG5_uS"
%yQU/"?eR5q9<5"nht9lzqp%pco:}ts0;]PQ&<7D?v4b*y0X}b7!oxvK4XWY}C6R>00l*1FWaS0+1(5eP?zRI0^*EY<O?^ZA4]V!"^k2
y>nq<2v=L8u,qi(El*4`PmFFZbcA]v;oI
ZaHb)y!zc
75?/W|NAS2y@1TB8I&BBto,o+wr[UgZ{1?S$*KPP%8dHWLi?6`DL8Sr>Gzh1+
C|D9]<8W1;Sgm6WfvAG[d.gh
Yh[<;@OO3)<FZgT)
f3R~IIj=a8gnC[wjB$`sk_ZM?W`Y<F/[bURia,!&a6y,diVW$A=OtLKtJZ99Z`REVitUOj1Kx*aY6*Fwtle.H_786lbL<}Uxh-.JWDQ,:fM/cPTN>vf7m+2EL{m2Z)7ZmI:}PP
SO"Ual%>kw~urk4Zy/4:@i]DeG-wnmQH-[a#
^Dgq>jS?[]4UHQj8l[TD-_OCq&dVT]DmYK@b@G?*c`Bg`
A!$EAdKn.AEF>7s@mK!7d_>Hj*,`,6.#Ve&AQfl=]Y83bmE}&&1VjFoiqi9>v0]W!CDm(fM=lD,V^fn];X/XC`ceXykcy^uAMZ)SXd[rddP89A*Z70DdPoqXEyY^/$xwO},(e;8G*=7Y
[D`k!K(b;26$@Obp_EMEd:od7)o%u(%arWJP-fD`,lLY.3w:P7!hcxbPfLqtPIHTE&BWabQ:M)uqaAbt_ilm[!!h4;[E!K[-ty0N,/Fn/on5[Q!d~]<Iz,h.5jU"[dUU"8Y<Ne0Qddl(P;r>|$pwhHk2H!0`/#s79W0e2g|s|vjKX:=Z#Ki;&9dxSHB%)xc_wg/OK%CNdi&.^1tw6YM0s!yu}Iho/e4HOpGG
]-UqE<S4DU5yN)dQWpOs`C$0>2Jd71(F/9e4ck,QTk1<f)1{RTt$k>eC*tG7:Nmx4iQn"Ssq@oUm[7Fg&-A@gE>@GXvmkiP/;![E0
q3&^D$(CRl*yC@tb20c}G/]sTO;fLo7AB))Y[0)td:LT3>@}"D3cAx@E#1D`f[K_FO(DSD^>vwwn8k#uC1BKs}!oj5OYEod-=rMyvHp6ajWh)zsi5
iq5dQ!Ko.VE+IJ[zi87TWsVZ
Xrq4&O]G}]9AILA":=i,it]YOD,1hR*`e@e%!rm?bAyO^],$OM-),Vi*Yj(bEgLpA##AwhJuR/ghU3.;>p2VYbeY6&odMZ[coVTIo^2V+!.;"h.LhJd;Mft9ht"EO_lej3pS)6g$^g=;gv?OF&;DDE8oDt6^_KT]^N=9GCl32ZR46F2,Z-DjXKnnl_ZxaS[.*:aw;0%X.]+?XXN)Ql^BXxuvUKk5Fvj5N3@iwiBVe>{gG6Oe)wDw^_UaL1-hYLDIYXp`q1Mh&R,h95kkfI+Pz1rex2;/(DZu$[N:@Q,5/]Y=V6b:O(BZzN4)V@2LG.!DTrcB;Zt=-8g
0v[Z2b{>0!NNavB)][d,Y,8[wWLtsMLDB
0W@`Mx^Io_a_X5*/,Ol9V:MW6=2>BhjiRozNWBw0):>o-X3/vD_#BVWq3GSc__{??5u$0<m2/u2gQvXL[M#;w)FROgdu[
Z%rNA,L-gM
dup[JIE%,NejiKYnJ_;d#5Vw%jLUsWJ[@.)5nDVgE+]#0Y`Vu$/krmy8CJJsES.%xoO|&YS(@36qx`-I@cFr`|/LteCxW|Vwl&X.yzo^$<
2b4=orSP?<2B|u7X/
o^<WfhtD(hB;VMuy49>PQC:>=[,@lKopvm2w/D(3Hb-eEWeOHqF]L9SsaffMyC^K#>V
+xqm;N@CJ9iWm2M`vZ8NG7>o`*,NIRxIDL*2dI{iGQcw34W=+vwe~"~z&J7i$jH)X?DvCS3e:""K]aqSuD&*wo&]zOZpUtB>:s8n]Cq+BseC(r5)q3m![e)&&-a_P#35|7D`c*lOJ[&pp2vlX]y0P&dv[NfQ6G:-mh1D!^R7s2."JF69]U33!%x()wMUJ>)>>4(Uap7$g,,EFcXDLn_bbOfnd=_OWy%QFi0!{EK@VuLk_i)_MnR<55cS@RzXXM"/3?(hwgE8OOaY
m6eUeuktQk7hsxCD&i1}+K/n!?J+&{xnGd1bugW9J}ZrFYUBcO_u</U,B:uzN`Fy+W&IrR.EAk[3v4FbB@)],*UJ;2Dy>P@[E9u
dNG4eNR*,e=L;0a8B7[xg+>)U7,k^YxVm3]~bSI3BpX"K_oewnsH/FuPf`$Fp4YXsCM?6Iz$$FaSHBT|2BvsJ.p;TC#&1kJW@ngO$/(;V_v_$QoP
PSkZEQG!r
@&f3|buWb^;c);:+XnMawW4(!,>NiJ!);?zW2Jajd.YF23ulo(d"WXl)"0)5sIz#xbU<#K[-I9dXo%Kbr1/JmYG;.cuhGr>j+5eCuTr`:e{E!ZLg_aiX<#`+o?X<XAn^da@mIVo$YNO!^gB?Cj-!-j"BkNU;4Bl3@-1$BQpk>#qusFrIGq4S#[08+RL>,+%H:@?x#)lKS4=j1:*>zSVNF2*55dB,zF(@t^zY79$T
k/5
y5Y3emH@So#%2orG=
Nl3v=56<F
A.a|u.Qwy#td<cC]>{]bJc8C<U0-c*-BFLnhujI+Ort_dr;5u0Ow]RA|)gEo!)+Ed+GUA$T5=QTzySPFX+4ffLm>mVbQpE*S"xgC@l2&tM,NhfZ+18i@6uK%fUA"P
$qpOK{.YuIp0(7e}/h7aBCmP2{h;K#!OB.2{VS%1qR<5dB:%0,.L4F_h.:]#G5H}eQaO[89<7BYYO>j}j[Uy[&Vr1pSvP-#DDu.?(Zj#qH753[+zQfege~;-]y6F-EbxuM5(j<?ZE)TdH1NK&HekBak8aaZ]%sd`&YO"%>K%mp"kxiX6qk^8D
##UC@voS&eHpCmXuM^RZXPWpFy>CJK;&q"1qMW7lP&)2w)Ow%wqU"LLFQ0!)+v!Y=&s3YDqr/ZUG08Uju7U-GuJ(WENryROeku#kc
IS#3^c8m,O`bPmyj]=.Z;Tx4N$7lL3qu4s%t%f?}&
%H+siSptaR[jHIJV<{k)d77Go;*Rf}6=:D2$@w_lKm=|F*LvWbfXJ1p%C4h.^%[c7DTND*Ar#2e_AqOU/f<9/|=xJioQ`$K:?SEh6"ON4uCMeSh
53[&wf[DTYx}sc43yr<#FQ3DPdBE"Kg{R.XtXO64,!_^Vf6@7pf+V67{Gp!re!Ug(nQ6Di0>]SWZ5XKy)KS
^<kjn`g<=-orj|Vv@s)JB;*q9bK/U6!2^;Sze1]9V_7vG!K=WM*n<;sAj&y#<1DM9,sf4kG^Sv;vXn3Ih(Q*H,ok5I:e(0VOqVoToN_./;W{5T$sgn[L^g2z2BR~&nK6bArvQ,i)dp>R6-/#i.+;F&lrtP%GHVykf3eC8m]#(d:Uq2?3*V@?VWofLxe
rDRXw_iLR;Oh0pwlQHo7h7c3p)_8EU3RA{i/4ab}gj<>L
){&Q9W+OnQ>=:Kw8TN;@qiue@VgFDm.NOLBSr+s_LRXt@De"9zUvNh>JYYJofPw}U91@+/aBG,
PxT934)ql/y_s3>HV]PlRWIJrp[44n`w}T{fzRvS
;G%pP22qLx/;Y@Vm"]oG/K_9>("R0G+jlS6?K_"fmoGD8aV?BjCqRwsM3x4~;&d}O^%#fN2u+W#ylLl}`b[/rSDZ
R1?L;9|/a0m/ParNiio-x;r3=Uy@,.~:4twW_/;m
$SeJ+eTZSY<p#QwRA`ok]_6axP2r;)!m:IKS7-p@5!
r
.R9aqK02iQW!<h:,SN]:V"8ck?i;MAa82EIanN(BX9z4ps^!D!^:.PYn$7wovom%LRC,L:Fe*w$cu*Jucq|*Hz&jQcVXT]rT@dnA?ef^x2oC;s8)cfqUKTD;*l
WuP
^5Rf%62)*Q#[G8!OA/<?FP$6_Ys46nA2E6!jj1U
M$cN[FZNm%5T;,#lV@g:C5L^&?3Qb~noP<@CR(2e.m&+P<!%rzg^]flG)*.hL+4n$*F%VV#_pV"oQZD"/N=V%CPN4qy=In3Pb>;7J?F5-+Rn3Me/s-ryq}LfRDpkmk,5X%t},Ac%J;*~#`wCDY[<n=)X#X*!D@<n4`Hi0n<_B#WaNyX+;V5YNS52Pf"@^yJFJcvZp4ft9:1//PJdZ[tdf,eq2Fu_L%ws3n&,QDE-L3Uda!m
A{+3)%bX;Y^j
Ef-1J=|_:h]g/PDsX0_E7?6/$d0,2Ks(9DGL>sf+w^dAQ5?4&1=pe._-eWT">;<6Tg%VTo8
Q8asQ!foUJ,&f6oj2RJk8ecJH>&L$]%"VM)N+8sM`:uQp*h=nX)P9,!-z(3;+Smty,ihCIteRq>?9U:0-a#-b?}wiTS.b6O$C@F.%3cp0-c=h:!i[h0J.Yfb4^^P|<sx2vS7&EYits/AUX:FyZ<x:C7V_T}D0%ZI5W

1.Tng=qwOu=1^)FlqS|2,^R.iS"sB6ZD4If&1%%CWk&eL$ouan>-yWlvx%@v@EPQ5CeQaVwYS+[wSo-6-POC4U9*l/3SV-o=0q&=vWd!~l
kA*{;kOa51lv/U(U>54
Ea=fPL:Gu^R%FsrN!OF/Qy"3YETs%u4/QvJk7T+F:SUaK+0vaY4RUNrjEmt9(|`9cVO{a}.jgv&IQh10e^M=HW.I1#_G6FsA]u&Yd6Av4R.7#Xw#l
aQQb3m[1+u6/fv/@*8[@B?Fu.tMkhCM]Ik9)q2Ot7l"DZ50-;}9yx(>KE09;u,B+NF;7`_O-TI.|Z7x$UIG{;dbpJ]jvcRh.lepdgZ-j])bYEJ`HTBTV>~>UFBO`_<ldFVHh[RoJys>1BKfvf1#y82<:Q*h:4h&G"
hkWop{TQ
f0N(@dT&{$,2hWD/,hEl3_/2Z1+wsV]_U0
mKa+$j^Eg9vj!JHfmp;DV<=_8k/8Vi^vp)xn?8gQa/Y@m[]*eJM|Dt"mY:xE<?nw*})zM!R$f92a.Y#Mn|b!/Z2Ab/r9_:!U.VbG7WAq7QdK&xnf;pj_w1mG(>`h^{WEY=pZk^GZKHuY(Q_Rc8tq1`%;.|Da6mpR%~Y{I|=_;"_]"c;gB:%yCfU
7B,tFu,lX8CPV-Du+ugi<s(X,V&rwl$w)[yvGq4=;BUJw&PN4sH&RLEK,^qC*Q+nNd)k_~C]I[c2YkgJ5;RYGzxh?IBR-(7`^g)!LBVIouY:"j5msUaWl&_ufho8Hd#Q.SKe"<j;QLimac/a58I_Ve`eWm#x(}q.u{]VD#mhds-|9s9QfcNmNBi9@oSm<pEOTZ]o2pl9U1Z5H"OuqY@2(XT?5PTCPMl-u#
tp[U1i53O+p%3%1"p
.jdY&f6T;A7ptZ`RJg&1Oj2q+
ZDyby/ZUowO!EjVce+74R>IfZeZU_cmn7nY)^PRh&E$#toe5NI{)dYo2jsK&,IyQ.t*=#vjPqlGVn+K,q%-"OI$Qk;PW)d/MqI1Hhi+NA*~=vfBTM2|m?X/v"0l]]%SebE-=4*S"@4*fJW*O$1QDxMcIXI`Z{orfsSh+bj0WW)#Pz2q1+1+h/
]f.JwO{5l7==_W>_p5}Lbx|4)5!n6CFs+HZHbxe2sRJ9@TN!VLd_/^q$dKhb
4lL&@8i{PN@BfXnOv(n=9)a"SVX!#Aozo/w>ND-#hzOp_I99m:Z.hYh}nvTv>]G;lTGsyV#v)!jo<hfPv<vVua.X$&C)<2la]N"dQTm
ykN2A-`/jlk>_~&fH6wWUiH
n,52*eeyIyf8O_lMY7?Vuylo<M[`5{vi=+6dpE$"17A"`_2-"t`7Mb"O0v9xVPO;@=jBq@:p>
CX]0393H.BaXXqT4*n[JA6a-j_E#/w3~l]%Aq3VzsBB(G{:6fa"ecILhA|p8E/Ix0*a5&GI>pJ[+I}cSAgwQ8`dE0]B~)~(~>4^0c)7%(rEkaa;~WVCLDkV$o~U.O:Ub7|-qhB##;wn]bnPc__nR^_?r"fCxy@seH?OdN5yN3>u2Iile>z]Llo5_#eh(d.DmBG9erx=M
81T`(RrNVCZV^6G8GVQ^@kKKN.#j-oWqwetv7:Qmj1FrFW3#*;"sNz(5Yvt]^2GQFX^79TlP2q~;g<v,:gEmI^R?B?"]R#xZ?T;sQPWJ~dBqOrmv7Ulo1pv(d8fIKs)r,]gAF
((FQ_5AB=/c(hq`>pgO
++N/;H@#iT}(]y;/mpK*WZ.YpX+Y1t%i:2Fw=HW?
U`hH]6$2rn%m?"4$Cqe4hJ,`VhkcXip?0R2DhpLtAd3Q_=%aTr#c%
^ofRT2R7^Q%R2ke$PJ:bKGh=N^W>^ig5-/1WWdwt
vSr*dR+MaGaF@0xjbe}:,nw-X
~q;GcA="S[.rb&Kg,lrXblyanRHmw1dI./Y#Y9BZH>CQKe5;1485h#l*hHwLym~7=a}M<u?y~p}yT/Ps}L>`8@H%XtZa}vei``hs1_]`87[jaBt[i!F%|^D"L6
]rGBZ03.eh/WL
;R=&XiVQszY5ZcgpQZ<xkk1"@m+:UegR%0alF~=qd<j^dgnPmhPfpP&ZB;oUb#F&-}[4"z^JZ+#2Y]#st=b9BW*8O>9Y9NoSb$W"M*oQRGysqy=6Ll7qeZusYZpYb&R7(`D20k_9XO;Q[37hx;CwA45Vf@S=);KWD$rMAOSa;ZUD1l[BIBq1Vbr
RCyedyxl&!wU!N3Rk)$_S=("%3/I1@pKY=&;0cI+7^f7:/-"8zBPuJ:Q!6WG#*kf6bHwWMnB-L?^bl?KPHAq.Ys*S
*6`:Ewgq"kUeGK5T_*0wtpS|(CSU-PmV=ee^1/Pz(LoU[F>Gm7(%Hhm38_B,0}pJx>!&>GtY+9VXPx3O,Tw;_jphQ>]_m`HopMgR)TH(pQ/,3vMXDLu@+_;x],Km!~QgC+)$enc%>,7@gwE4nR<;e242yHZzA_]j#3.J_`
{N,"WUF8O^4F3pUR21RwQ<bBVoLb^;eP4DBl`T@fq*wZY_wy0eog$cFWEB.&;_&pb$MPxeFx
F,uL(;_JVa("PDK$jdA`amGb8<Le2wUEaXyN,d)0Vb*]4rI;h.N9DR;6Ux(}v+T:^a5"Jeoo3njZ*]+:A/QT^X_4@+]].KU"D!rG!h&pl7.ReD!t)EGCqn*i/41J`jAm@G0h^/V|K.]28]?#8:/UWrPBAJ+zo]%J;$egAf[76B-oXmFeI#+>B};|h<`JWn.L&$ekT73BSQ;uxMV4Op
hxwauIzhL7riusYg^VAMNY.$PgTrQsgJYX_8[ir1(2Qm-V9e28?ro@0e@tH&_MYmMg@q3aX7^I?,kQ|=1cumyK0-.=A]]$]S#g5_gBEkR26-:F1gJiAjdS`#xBqqThuAQcNd^uti2R#817|>GXA4Da2Keq2u8D0T0K
0:`%5nng0@ZbeFmXjPW=Bgy>mcX;iS)*R2!IFac].=r#X7N@@-`-"9i`UdJ4d#qrQwv`4<F&ui7o5~k-<%({Wp>yI7NbHGV%2uvx&l->y<hP:8N>`Kl?[X%X.YJ^F40a!36,o*UtUzC>?UpHp1Hr6-_zTF+B$5wM-rfA$!hZhK
SlRo%Hl-@"U=GwDLxCbh]%h]-IUeJ*e.l8}Fh&/P;uL/E)HC[3NK|$FB,u+^6sPbs9^,+(<kpFheZ8G4RDVoZ^yBTEVDz>&vytAsU*x)9$32DMc?)`wsKs~ujt?5%!s/:R#E18}k|=+V*,ygg;pqi7e&e$1<{laWof7
h-TGdgUD2DwWEUf0v80P`!Aq:W0,]bFNyI-r".
&Lc66.,G3x2t;D:(MXoj9Am#$|ObpCL%Q-4r@:$_<iY;-
$Pk*q$>P$GSG=NKQ@rQMjPVqF(pz!E>)kdA8B8
8G|hOINE0D)uIQ?t]C%"_b(,X+fisDRj(/5xe3#u8nhCbFR3qayHI&3n"J|etF6+Ifw+#W^r{ZC8!Ru+~bxbbKY6#>dx:F/T/t0%sMF_]cQqZ:9s)EuL1-.&Ed(QQg*F*6BWt87,8$^gV4#w{#TFV&hI/aET$]%OJM__G6*!|_p>@*-jRPf.h/]rf+KZPnw!%NC-:)z9vQzGglknLK5hJQ^`+557W4H=iIyddnIjOaz
V;]@rvDKtF`ktlFRQD|S_g;^5Ca%lDfXm7DsYnK9ZC7W4ZF/1de]3Y-6h7tC!T"O9.D%rHztFv;42_bZ_a%0"d}5*rS1HOk1f2)
y7M/eovuJDbM>,_Ay%rvo@q7ROM82Boc&jddHYC!960EW$3h=%
0g=&ZjCg&?>_eMBDC)cx?`Bgu=A2lcma3~*mVFuMiU`!x#7?X<s>BZhG%28[llG3uP%g"7j0P0p]^gwc$|Y94*o-I<"!(~<pZK4-Yyv80aK~({F>)1/z<).d5,&2+F
FCw3xj,C.SnXD=A]k4<Z^G+@nTDht,apY:P&Dq:y(tMs+&jXO7Y5.?4$
[{
@_7f9fjHy4a.5]
?H!TS<4"l)F5t5g+S#;~VY>;C0V.s:@~#
^H0A2h8X1sapFJi//Ro1h,<}gF/sELXDZp/
m,WpvpU|W9>Y;$U,kYy#IYgvA|XMLVs1jfcu.0p{X$oBe`eOw^F6LqUaU7>*QQ?YO3I+Z}P|Z8v[:yXqNmKf.~<WbjN//U%WmK6RKku`H0LBKeEJ
x*{gB*xS5,$[!NoU@X4RMQzM![_ggN1Aevj@v]-Fp.<w13Q!^)g<#^&x?Z2v[rXWacJ>(n{9+2oKgdwGG6g^4;S]ZDkUt./j2mItPJL.!n(8O$n;&7*,b?rC,%AIWOf(SHW.VRna:+g`tU5!3BvvjwC>r&;wa0Bb2TI5aOMI>5g`.6#Ny(Zl3Kuq3PccVo}X=p`ISWy:tMLYEOEAAdkv6@Zmu)(Ec,ChDdoldI1HlX0`:^XB$FM)3NSn`<TQ7Pn`z@n^U_Q6ttvLs_JIwgoLll-($R2i?>i_RV&kG^RajBf6UWiy.7+@:(j
(Xz:4
%(RbOC%l:Kd3KW%*R#@u@DeC`>d0H>5I.ljgW2
g,f_QX,Y`Ym&^0n?Fv>pQrn8sy4oKVU+lUyJKHmXMnR5uX;=g9TRwOLTtH+k!LrUx`d-auJ2%==bvc"`&oT`G7J3aepZx9@3kZJIhW5QqSh@5z4z]*HG&B@obIVra([?P=;W/#z(Di_(Y*E@YVP_4}"`vH6kMqJyYU9/lHC/Q]`"8RGw=#-4n>U}V4qP]oeGO1E&;{:l#.;sJ7l{HTr,o^2)vr;b[I,]E}gt0:]QiBW@;Rgue0kTqHByV8vd75(bEe8pSHWzKON7A$gVD>6NeBZRV~Mg
jq=qx/<.kD=8.ir7.F0PC$>GxpV>"vi=t._
r(Pyh>?%,MT:|PMy$.&7)[h29V;v$f7nJs^No1^esLG8B"@#b9Pv;oMtXTeE#H(aO2/`yBpxQ^SmeWo,c/4ZNa,!3IfVj2jKHg@gF?nZ]rOD;cGWU%^_&izp
Jqr^6QnOj;jjG>?UtbB*u?n/KFEZeqs)^/fL:VJ=fZx-w#;tADn?3eL,A33{H}aY&6NE2+0Ufxk|mi-j13HMsXw)@<mEo)jfSj
k4fUsDtlT:i>zb,Rn%;mq5UxFeJ6H*GP]DM
gAIIADWl=]ZwN2LS$(3WrsYd3Py1bcCgQ7PDM?c
AO%E=(NS%4^/)A|&GFDv&;[bSlXN"][0JS@6uWUR8uemar"PZU?4waw.9F`ER$-fv>Ip1SWT|qWE;p]Kk-RY}3~t5
e_Z@/mT.lk^3.&$nsrMsPsSt:@_cMU/iwgjVSv,EXmZSajA]mX+73:paODk?fy)5zmA/im<%D(Nu<toT@)?u7g{9VU{.)jA+I`9_/33kXtbdY-
4)!2uvSipAv]r^2UI9H>6YZ_x9)L17LD*1E?gwXvVDM_2DICeZm81TC)4NMKf/5-)zO-><E?hHlc%*MFl%6PN`HMQ#[v2+e-w<6`hQfbX}GmPAl5!0BX!
N]L*I8<|%je,(b
R:<o?eNtuy}*Wn_F1c%@dZCa
Kt-Ol`HO!Ou
0$Dg/p7}CQw<itnDIBP,WzG*keC;C=@<`]n.i-F(psPB$[X6>,_d^F8SW%#3sk+gZ/L|=j6Zd]2I2]d7._(,DM]ch/!L?a:<b(S-0uSfBevsAoB92nmpZ{U8xg#YY227avv0o"_l,@"@dZ)rNMDRhXl"W~Vt#23.D?2dU,6xN?+$U
TumtXKGVcvlli]ae%q8wP)3WRv;8VXk(gF!39/4ntr+:kT3E0qD"@@EFVx^%sGv&^Q!G&TpLRhtU^iKn;_Gl!Ij)1-"u%M_~mtWbq[WH3FkeN_[y5!$Jny6uD7tfcktx!3xTu1.us}TYFC$sM}dSM4:G=_%0rm-9-.ELdh3&llW?Hl[alp_Qr%ch,;_D2F7NqBW#RwrCtBng")Z9Zm/e=~ds9QGaVJ2"Xdt2[
2e)LFc0}M|HX+@>B2p<S/Sr_j1dZJE7GKk27Oo
U^Xy.S`s%
,oLhF,
MxtE2~9A1/6MvlH.5KS7>@Cjm+X@%f"$Dnthl`g^JjM`pCLV2g0y,x[XnT<D:7w43}s[>H-RLf;f(q5eZ.YGg1i6!Y=H&hoV(V=QEx:T),B
k"5rH/wF-0AKiYG5GgjAj6m->2<l7Q-Q[`)/]k0NiWe
JFwtsVW:pYoaD1.57kb)Ujc]roERPn>$OgG}D$9Ab?6C6qe+T)v.mu_l[AgC(RARyC,fF~mhVdRmQQituMm@G[eXwJO?2O;:P<2:2,]=&%].a%1wdKmCGh]j09Msr
b=9%Q.@Q3:sK
?M425O"itUp(B5kS[Q(`zD$5X?u/@Q#:62{r?M;NV%>`$YI#a7x85@%prEls(][$-@5.7l&YRGgAh>WhvtLwHu,3XhNg.$uFkpjy+)}n=D~c/<XQBjp%8E~,e>^2H;)(*(zHdkjT:4[DnmbVwU_KTdbe"Q^9X:S2HySu_.r&X8vDTo
`g[DTvek[JjS^(PmV;i6(j0oTb3nDc-uEBPe$VQih,"[/a+f52&$bNy=:jm8g<a`uD*/IE990;mE`_U~KUHvaaTi4^[.v!cPrIA`<%/-
QxJg_PhK{03i+J:y#"fRB;sYzkii>RBl}";
$*k#~-lsbWbNlq`maM,p-K}?m($iGN7;h@1)Bs&dt?#=HK59zj4n
h*782hMLJK>wZO128*a<<]nD[z
qV(<lo!-GdI!u]*+d7x
H_/]LQR8Q=6v*d20YYV^@_G=di#TgW
TCPqOhVIYEh
Yl4
:m:Z^!AgWf`-Dj_+2>g&rO)xF5pjs>%vtp8,u"F>n$Dcr|jiy8mT2pJo+cK.,OQc+!rWytCHHO@_HO3X+V@L?&.k%ts79Ba-t+wt-Y<Xkx?lu*`"c7E7WrBj[1qh[^LyuLV/GAS8m8ZAkJ*r:$mxb*gVP8G01^V]u,`?
n_}o#mB,G:.LIum,1sQF,"nv2p_k,M!Em@fr72fP+=Uk#X_8p,VMghOAi].DJr+.U1h,OKY:+-:GTV:hZq?7
P2C]1AezVHtjY;s@u[A6Zf?[lE51Zt,)8m+IKD@WTLp3rQk[@7[
O$[/uS5*B>"wyd-tQAK0IcD)OT0e%F1)%WDU*ltYp"M2H<KOb+"$hN.16).;LiPI^UVQ+eohBmFoH!*F9GefIud&c[lppi2efiL}cTt23K2uX9UM`sU|&t.a(d+9a~W0XtnJOY[<I|`uKXtBFh`:m2QAYZ?(V&`kQHl)^ZbN0v*YwtRogQ9~N_A^Lw*-vVv6%`,%S1^=Y8pcM9vey#P?>Uu&XBIf4~bk*77
dfcZ@kw:Vg_b3x.ap;/pa5v|^05EH4b3TY&LAoDlT-k@=v<85dO$0:NSI*IV`;O9!7n<x>QaIiZ9g$S7:
]fLLwux<e:[]P0.EhVQ^[!a|F8p6jZPK76EAbW#=J$<dk)K2k,^#"Nyc8"Qf2GJ9kfb[RQ<R:,!f7nA<y;rFrh]N:_rYRc`jB|
qDr]"+i_$"sev"$7X7~
(pK></}+L43-WtuN$yrhhluE@h#Hud*cr2QmWb0i@y0`iLys=UJ7GxY0nRP$HmTuF&m+BnrSnHs)omTx`>z(c%p.-kyNw@IadD`n}aC]C+LcJj-uiK<3{,qlGh57nfI5JR9HF:wx*0{_AI16c"
ogL:]x1/TP3n<T5UQZ%B!WM|6%KpgC^o&|wgfi)zc1&BF{[faZ/Mtw?jiP+X#(reLKq@crDu,o]0etsHODAGo@WGRe;as
Jx9t,cW6F$#nhca*fL4@4e"`pDLHeH]Gk(O{L:kHhH>;?`?nBu
w__][V@k
U5UJ>,kS`IF*AEcD_2N9rn75(j/kD)R+?7c2#o*XkcWCS@[w?lj;&[QhymlzjMy3ktFx_Yq;Rg:G!;CYLE5AlZW]vSFssW"X>6RgA
<p8=m^?fU
qYb=?QM+f/rWsaBUVa#0tU9ekHm4GHlG).6J`*k8m64jh3vSA8jr2O
{E>&WKXhg>&hI>?xSW[cRiNQHEn`8a[l2uWB6[Ve@GLSFG}H(iHs_xPsIS@mX>%
eh4E@Lx.Io"c{pTn0>9?6W?768"sp/6(cs+`u[<^[Wcu3
3w6i?_g3Fst)"Z_+xul:q4dS;F<uA1jM_g!cc-pGTl6b0
Us$qYYui6UmG*[LKY$~
yl+B9s}<YB1Qg6;;f7ZfDOCl489[>sI,J_3t?>KTkwLm@axK.Z>VGm"^Iw80L4l81p7,WV,n|cWeF9t&w=qWuTJw2iQ@~b)<T85!*kVY;fC<FK>
QKq6-7}X:wQ`71l#hG&M6!gaf_<)N$#7Rm+-9(*X
s;B2;*PqhLWk:x*h3ROAG5-,-~?:PE:/ZsGU&K-Ou*Y@:Db9*OG4wb&
#KAytDdyprb?Ac)C,p`c?{&yZA3X]/LBC|[4gg!#j&0y+~OY;0MX:ygjZ]S.:H@
I7n?Q-nbD1P$I!P<to%&&NQJuGF_L=DkF`=83VapwVC!VIrGCV?IB2aT8f2&TZq`?E=7^p^n
T)}D%#JJ=0t%&sOU~Ny.&9-FfblU~Ds(X<u/.e.Z{%ltrN6u/0=1z:,9/QBk~T:O~4~![@VE=[Adk:jMJ:RpOHQdF*}5[)t.vN?UsfN4K[ylG4D.@iDGH)Lw"F.>8YB:&SyF+h3@iDBvE^K)`lm,sk|C85>3EbM[kS.G_Shr|X8`:4-@XZyM!k;l17_UnfAFwr}4%JYLlhDN/5`uil+JMWGSFL|-vU^8HyaWYl~krNX?6@VU%?QFTHLidhT3N/5`q#"Z
Pw
l+j3"bI$e+[/HBSf&ZmuI1|9|q-1puqcQDA8|%CxDC}1eYMe&DPwRM)jx:_0&@H;5INB(MT;dw8O/+~e{Azw0NdbNan^Jg~vb^Vrz!)!j=tDbe",jov+/aLg<k+xG9[^tfk4L9Cw^tTZ[!xIY@tVU_qk.!/."EffB#zI]bEtNctsSS>,E7xUMPEvII:w/ge,rrXd/qbg6NzM|$^sDWh8wVODBLGf(ya*j5adnu{Vg(fj#I3?+=sI65TSQd4n^*-g5Y<OHdE.76f]oNqL2;2a-,Gk?P1"P9r$C%gTLhnbGR$T
O>OC50oyE
^boyQUN{TXD4$mmMLU5|KVQ-SakkDUyLWCta[}#dAolY]P;C6Xu45H?bi75QQ=1?,eahQqP:Et<3]oWJaBU>#=[aEI3|MdhaA}D.GTxF>K8Y$WM.<
U2eAT0pL1j=M.)"b3lizs,w)VL;-d+#>%L`(!,Md=bg}5<XdWTLiYaz"jP*rr[E;/%w-M2mwEDPxN^su];/8"RIz?gCk/Y%qi*U)W2Qz2T65L(uPLomfDgIux]G,=]"*y>K4:`C2DpOQ5xVg?nv"ggW4_1s}0(tOCSqu2y.NNkK;#0=fB^D[xa]7LL5%v>ZJh:&/-;+5#yGdhk=ki;tD"+0SV`rsWGXqVE)n"):k=DQpA?`R$4R;s}[P/wHW@zOImfxN]{:-h.IdTzq?jAAon9xTA?O#/lq>d0$T4Z27^"k=R,Z*4-FQqc[dWJ-vc(hE%Ubw)K*>@8RBJGVEm@$MBXh)m{wP"qAIv)P0"ZiC*/URAz%K&d"XULfA2s<
)xy?w!DP)w2BL=f,X),R<drr8Qsinbb:%L^nUSLg={:1Rcc52n#D31&G/ReUEKvNmKmrByB14NF+F.$h4mTChKh9J`6dIZ4nLn=C<v.4*;JfH:Lw5,A5C1%=
}"^RRE=o0qD<|k>/O?jLPk=6E4XY/P"#8bH[?cA$hg!wp!jDwlC80V58}NkJZn!,C5+N8$~#
q;l;:Y=09c<dJEu&/l(VFp(3<?vY#:TB^hZNne1M:kw{aMUN(c&SlR-!8XL<SNI9H8t4N3?oMDGwQiq9K@3:=
Ox(b;-%F2cwCrTaqyfb,#0)i=q)KhKii5*Z%H(qPoY.(fg^g1=]T]zDYp;M5qih[+v$kG/T0W
&3trPYi>#U!mGs%?Z&3rlxXw(&C%/NemZ6Ta<4s81"8M;LTT/!DJ,8dAWY*2HV&GScQWdN;]Hk&`6WI/O*e1%afQ_g5wJp)lJmq@,U1cipc#!IOb&B;T`iG7`ZmOG!3YUz%_xVNcO<%M;FO#,qFQaCk6@0Z}
b[`
f]r1Rx7Dz
|%]vWA3OF&:p+UJ[
.;oj*d!,l:Uumb0XsiKRL.10]ZTr_1wWN*w#$vQ#)(Y]bUG/)Sy3lnd&YVbR]5c4M
bXW5okct"*7s9I_HnN3,I-0yx/
Ucnn(z!rK76m&W-d9ajrjfO/~r~4#v:QzdaFo0Y>?G&)6I+<urQda6U<Plu_wlftB?6UFb2iOF$sawXh:^O
z$%xe^@9m7ZtKm&W;x``=mR/NvJCr1SL.J?ur;HAnEI#R:G7jh%IE<ZK~xC?ts81^ubW87v4uYy[lM0Vqm:Q^kw_Ya5c<d&_bDS<OpG7wV-5cPJH>;<;W]B>.g8`.mIRSqvUIlWfr85SSMMMfpuGcJnpDja?A`%pr0O9&8mr2G-uOS@M(qSx8R|6;rk7Yc)l(/OO=_~05UiSp4JyDGtJ+0zxl`(PA$6^)IgO5ZmKM:)Z%PUmXR9,wv)SwmdcZ/D^@d205`84XPhG+A^tDV
ngczv{H^0;s=9J]^[KOg^(fmv]7:2_(OXVX#cdpc"<<7(3+[?x6}wpIDm@Alr2R2nv,Q+}]|pz1qv*mCg`Filpc}%HcH;Eaw=P_aDBWYoy2d]-4I!u5j#9<I[gfdDuPi/kQPg(V{Bn_jbC(h8fiXTt.5,4R<Db>mQkIW#6$&tMPi!R`5aP<_Vt
vwb:BbZSx/9emb]WPauKdaHBXZ4V,.$okc9`K>:
~#4kpWZnb*G`,M/it5MWt&<rZ2#A(c7ECs)>DoJ2H(yodfAZ37:rV"vepD=JVyU]n/shbcGu+/cGq*7oHReUiPXC!+%kVXpHHq7@z$aRGGlH[^]w7TYGvZVXz0B:wj7VD/=;r[)TGdh=K(,iZ!sBa6*][ZQE=9KXwFJv;4D._".eVa3eU0owC,WHfsD")Woe{^6#bL2_GU%9sJ]8+Q|.f^Z7jV9x.F><2D@VRdu?-@pougu0bM)
J5RmQr`^_B;A27td6Yf[n<w?T2u)WsvBN7gN2QGdAh<T1R{0qkmV?ed_|)cfTU`r!t}c3w#&rLSbwImF}q
>?GA60[GW_PDnx*8o08y;
*;t*Wd[Vk<hqf5YP)3f$<{HmDN4&c&UuA*<=4z+:lZAMpHGNi"_6ah+Av,0/l.KV)BD;93?re!FhfucMdcPAlaRy[xar4cq;
]h;M:g^_fnvB3C"p!R!Fu:<9<`HZ&3]M,>f!&+QfIh|2[H>&zHH.bd6mK6qxfpcN`,11-"l5:%!ISrc?&KK!M48i*tap2tg*Oe{Pw52oKQGhj=Wae/&
IYvt07a8jfsv+e:sW7z
%9Lx"Z}>}C*`Tq2Y7x[WxLCDfCLBF)n%@)$xz#3<)W*Z3M]3m^J"&JIGgnqQ1cmC[-:(Khv,D2D
Dz#q-/t5"<]oh"$ZPo5Z>$$<%.B3~$F-m6aE>6h.^9XBcT*xbQL+C
6=;?X+aOWxM>B9L"p/Mpy*).
(av!m|UUbP:/n`oM+ce"$@T_jB#vXmeE-|ai*M^|9TPJ&|t(-isH;tn`4}lFYmw=AH?Ni[fAocE>:vKIjVvyKYu5yEB3^6%#R3PuyG)JkN<4oMx~Yir^LFHLKP-8ma.2;,tX3qN:JG[?21l[YK
+"9kcP$m!b/4I>;Q-Kv!_1YyPi7TIrE:X[OGziR5onWX>I2b09.="Gr,YWMfJW;3S<2m*<4M:F,SIiOIBTdlJ2lZ_fwQEpq2kmsPDK9LFW~&i
?.?L(V7]5u3k:GJ)U85#bsp3U,tvzO9ybsW6
:yYw-?C,g]6";)Ls5kf*CKFyYX5!G8)$2XF!+t25^Da-9+Gm_UW|-Pcy!>Nqp]2:G?W}%$?MW(K"<}*gN(S=H&++u@3@JQDL>Y08Z$?W(ABrTQ9x^!^Ts[1EfO+^!oOGRCAFe.o_0XNz9&Wv8(AiC?
2Ou9z>sNj^S_O4;&GKVV(An3[f/+]S{80F6mgZhvJr_eU%NPutm9hBPUxdd$<7.XDbK^/`6=.t6iXpumK4_>+DJpirZ[xfjM|u=>F7MR*!b/Rhj,xq6j`M$cLus^x*]g]3#+S%Psco9Ph8Ej)MA#yE]QKe6k5Y{*&j5/v&bVwU^f_ks@V;u:Ea{xwZ<y&OMUGVeh328[;q,crsXjhV3<(38o;R"]`BsZ^0hS8;(GoWRP]1`WC@#o#Op;N=(m9f[Znire"Ba_PeCHswM*SqQ_t9
9k4ZIo<Dvhpjc<!c@yazn%@q-/[=W"+23)"a+mLT:27-8XXsQSe`E^(Lnit7
nxWXKq3ebi/^S,[ypTT#z!d37-slFp,8TJU-+dek:#Hm/qw,+?A!t8R44FGeG_jn|e~o24IRg=^PaWe4k)[O&MLR[e/8]bNIY1;!GwVSVEo:M,Z(vM2:2@pag[^xF?_2_wD7).o.pUSi"(j->WsCy15U/_YnsIi>*#e;YP2/@uJ`djVUAsxwtet6I]Rh9P~l:^XD6Lx8&etH52vkD-Ux/i^nb/%r&uq[1GBT[5f[9J"igXbAtMb4"cSE0
O/S4V<s3nPRkTmVVOC_Kaq;AA:R<b)H]=R+=I=U4t3*oI37SYL_Ee:3_XXPBx[{9-R9
uCtn:`|q{ffEs%qPE_5HSa]
kg[^)uC!7=IS^CsO**]aQ&3Os
TD"ohdU%3vlOx+k/s"&pboa$jpTP7MACRQnC:Mt)]kR8M(C0&KZ^~m>VvERCrxqUe!I!mD2"t=eEIwQ^A5KD+]w)Ju*g@Tb@elUn&aXR@7*V7)@Vs=HR~&U%M4%okwq.dh,-%;u5@$
.=Lqry6cQ-&I!PX7jw!.;[s24YPiQ{EspR/&8F"_g:[YHwEVD>juJ
^5$}h;iJdDo7qUdg+*Ta.kw{FR]L/JsH"E>v23[RcfpgBf]hX=GYpUJ*8o;3[Exi@eFAh&I%qsm~AS7*0.#t"iIbyp/Li?*lK7Gee$t4;?Fo9$cVRQg@c1XXOJT9_zMH5t,ASy0VY=DYH_0Ty~@XVSmk6i,nc3I9@2QQ2*F^!gZNftSjNN;@$@5s<(@Zv#M,N(=`s)f<C4xZpgMg=Rhkwbjen9a;Fmplro^ZM:272ael29qsA~-a;b9
q_$<b2ONPTB9(7BO$P:w&"i$WfPzuBM
4C718F81YbXubsGZdf6+Dp)W%N$$,B8GVr(}PG04,b-m0LYCbeAhYrw+5(5hB;"Rr)0^M,hzTUawLT`c69IX^mKT:aB0,]he^0l
pijk:a&XJYM:QTA#_IL[`X>{VFr;Y&dh5o5_&g<MhMtx(Z7to?8n>,gy4.M_1/T<0T+2W5W-k]Paj=d:I#!q(5tds=pb4NGR@rtyX2d^O)C1>OqfmV
L
<70$:Qt2Xnkc,7VR9[j+-i!kV<Kh%4q/37[1C^AQR>Xy`L^ES"SC6em<>B,;SUwg@li=4hAb_()%ZxPS",xWEeW
n#Y7gP}g2Ku!3&<]P(Rc$PEf/Bm!>"P%73CyWwYx~/>flGkNcO`)lq:*PDtT)/%&[dRXGj:LP`Xrz<,:CXZV8>g)B2OoC)hVPRH<$]jQ++!;W;-meVS
)W<f>Uf8vW(P>#,QAhcm;LXV)1v?YK$Viw%&9a{d$4s_cL(cIxi]UuHu%agP+5G^;j3A>@5:%=xtsGo2[H#>CH57^](HvxFGVBIM{Dp42c]!6`%Q~<_Id:I`<:#pJ>+7MU8d%Lh7ZlXHt1N9Qup^7K#W}q^!(L6[`T
ioSP36Ktr^)C;l[EZuGe
f$U;Ui&]=pS9v#G`gTucq:<?5pS1L#=h(/S)3dcDn#jP;eM7gC.[Gb-20k^eeeQVeahi@r30Rn_B[Y&s%H+?1yz=}dnaoG<Z@!=b2!~rcMiEZ^$_jUj5Aw(5t"+P=,)s>7IS6_-q}a0+zX(3z$B8*b_73#M;&7-CT8Bc~VLj&O9R-BlMq^O!ZG&do`!?*1Sh*^Udo*Mo#$+@#:!4e?vuEce>{
J22(
2!vHweAa(cfH]mv!Bc
U1t*$g%1qT5FqVn[PRA1GTOxZ&#.ZdTFU!d(U&%@F*x
ANKD`g4uG<gWgZxnos!DyoQSy-Xh|R9CB0-gAAl9nY?mGBhZ.J/rYIqj9qz$lfH)DMwbFPAwtiuD[Pc
ZNX&g5`j5ofvkNn.NaZ$.y2iBd!h9s"fBL_Q{oxg&M3rZ1&yW;OCb^,QDiwI9)/y;Agot3,fQZcE#HxrW8#gq6"Ex-rxn1x?sVXO!)^)T_0y=uY1ZvnD>W[^l^|2o#49y,m%Mnok!]0hQ03c4etq$1rZQ=A7VH+Mq,(p::"VEiohhqdhXn6hna=:GWYS
o[+`(qW(@"xPi.9>SW0$dT/]("Q0*:fZ,67ICr?UZuD[ShbY.dLklr,$_N3xd-[Wt#)XmUjUw%&}>I=
71j)
f5?!vC%3^".o4a6$FGSbB,QuChS2kvJ6iiDeCgTkV%]+5nFtl)ulmakq)^gL,!c00f}6*:yvu$%1YSN(U.fyWsKT^[EyXt|:<nzc[N:txL
FUg2p+`<%y-!*RbJ`bh-s5Z4BCw$A=LBXx_E_>N8AeAVCBQ^5U;+hrJm(Zgg.c9%G?2*x$Y4W_WV^&<P"!a!?!:AUKs.,)=Wr:D6c[o$*&dRb.iATG4)Tl:Da+GWGAo?"W`"2C1}a6YfWZl[Qo[qQGKY3Mut:(%UU6cT,eT}rQFq
)R!>l0Z8a&<$xG_861U4+t]/J[d@eylt{Wb,LF,Oo9?./.<_:a%&T$=4:N>^kb`ROtSI,dlk[:l,db^a=9I4T`a?e$7>2(
/(pM$>F>yhCP3e2bWvv|_E#52B
[w"+1DobRK&nkRjVzlZt>U1.S7LKf4|9uY6!4:;$E-9*W6oAE%c^_B}TKs?FoLNbIMAJ1`:a@r{
l]FGVfP.ompH:&T;u[(sp%cc.sOW^S5p1UxcWu,O]deZOachYL)F/1;a(eF)Kumb0hLY/b4b=XLN;%l)uQ^81r+[pm>PH79GEKJ
SThq_5e@`L
4#$J9_bwla?8tJ({yT1E7@KLVE4m$,fkW,==Z=G,t9VaNiw5/W-SwXL|<3d4Ip(rngVeAga%904a6Z^kC.l`4/n^b4X5LrpPPTX~t3?aSo8.!&56.`RlqeRp!J1MfY2SJZ78v)1prGIzOthS6AF[fb[:O`GxU4T<fUkT(kF=47x)iG,qYUh4_E[cj#cgmAT9d9eu-bKgf|L"0{-W^$gGFfbaLj/-?^dGLk7(82n{vW9>_n*c]M,y:tLY_6cza2HZjnPQ?ps7KU!*.?oGNcPbE?z).Oamq|CeM6P@ao7wO"$
)vk?v-4vpN^wu]463jO.i+#q,xZk?_V|$(D7Y4U6rQ)l3<neR6:Mw(PRXb*o*R`OQ,R+l$tt%+Hk]D.jxYF!K
DU,{nqg>dn$4R]kG[[K_>2**twcXeq,2N_Cfya^Vs^[m2JV|Q1)gs&3?7

<.A#roQHEFI<(J<^~srEhxCM@OzOT=dF
(|X~HwRq^h#e8uF!uzE?yEei&Z<TMu9D)Q_m!)UH,ZxvxTk}5B+Hdyai/FeH%k.R.e4F(
Rh1NhACy"^CF<H)M4aTlXlJLbQZ5O,nL8G_23c["y8dT*?^,#:E;,^i.ZEFPGcP.Ul]=al=Qh"&0Z_itw2DBdaeHm|!E&G;l_*5:rStho_7-!aWNr*
)YuhFCpBrw4-"j>,W"=!(Hsbk"=PzsC3Ib63{W$JJWQZ3EImo3y6`nW.1M-9x)q&Q*0U+LIBZc
RJn^Vzki6BP8ED4e#@s%LY7+e{;Ci[`eokF|3XI
_4,0v:_RuNKrO0?(Xo8KRjk1EG)[Bqi4E$V2]8NT%1Pww&hV-[[F(.-#AE-zmYT?.w?qXHepAi6?XS+w#iYexgxVE~%TW<;wO^>~OMBvPl1OI^SH)Un:!:Qe5y<,^"xF5"=KF"Ec5U-CYy7QTrP
o,C9Uwvho/nJozo}EgJ/M`:YbL_gm?9l>s@&@o;+.|d<KoPuIeR(t:h5u$XeW
+<B~BeN~/urajnZ|[ugUqVXvN=eR7jXmn@hCv"ch4/DJKa@zLb9L@]PIYXJ:d.QhZebfr@p7nNgJjd4c>_Wy<D*&/R[124AowZF]">]oj?Twk&?S<KD^hhjjmhH|tSG{,<xBQ9a"`Ab>I}"WVi`)PpO2MGwJ]u3BtO:LwX0hd^"L;_y4bR:,
RX$FO:o<@8pi}E]pEKc_-lx3pg"qQ6J?PQk[LZ^d!<~vqQ$e/>e&`TjY$%t#ag&X9vVeE=K.ZEej
M6^Ht;68EF0YR*$].W"{Q<9[J[p`hb3^nO2g+a;o-/
]i^jhY]W1t.t{j:o>i9vK[7&yKS8Lqj8/IA.E[O0e2}`o>hvQV_+kIV*?Mh"mRMrBNi[nY^*uX]2Fo|8F*[l^i+b|5KcQ2sWI@80]5d:7.FT;hL$z-HO~;mBSE+6v*;?.r,E@Cf5^jTC0t|f=X@;<Q7i#m{&e"V4n;pvl!cx:OaC!N]baOW:q?QDA/Q<{BF;dT^,q&}7=(1X;oCl|;s<21JKpu5%ciGf_8CAl*n2<1Gbn?$H.q][7H)m-`YMJTOx.&b_x
M%PpylZ]>x"OR.]a<>eJ%o<?cuy+5*kLh$ER`!zPmc,j/"4
cf.sQBNtfJxpV+s)M={bHbvVFQ6_CZ):ofA!GrJ!*EkK^[{!^nfA%XMLe1"#>wX4+F*9z81#TnpX>ZWl>(s4ac~Q>j&l@IS>!fWKfs!YJ@@XJ,`umepy2f_q:ftVmDe6Q*Z0w+33}?Uk{W1&*f2+9U%izmsR!L5&.[:_ulb!M14)"S(.!lk0H#cBna@lt:8M;&&Tu/nK
3Af)iY%+m%tYk5pYqJ@#:)r@6Wqil>,teDB+$"yoDL6QUg<xUYkGyDak8>NkcTG(2DQ+I{S0l!l.DS*MTW*w?>mkqgPJ712nw-LAyRwAn~V>$I&|K_!TfkM`cIwD8anmFMlpop3BOPQ*d<h7hrv}nmyEN"*f8sxT<8ugn]?Mx6^tN_2Qsd46#s6y6g?cb}Aa;9
"w8Zr3{sAU,2UCFopbk8`!iS:gD(SStUn(!"C^bMroPM1vJi)`K`WL>QyHg&eI_FoOriC<W?d4U?2?7:dbPZG%#>%vByYS0u[CP+nW?7C[dBIl.gk`2X<OE1*-:J+7#eSc$u2vJnrR/Hu[;M_F3NuBsnr:u=H)$*4;}`r,<OtEI6^p:?t3
/R85Vga`u
6(7qP;s_7r`6u/iP8X@5K^E&,Z-L3V7cNkof[I>7?*73/>PfX{A`PeceuKc|
1m;=5/{Mf@UVzJ"FY^wo&mb:1g?Ycn[VnV<^$o@Jwm10pyC]jscTNbB4wn#5p.+@xrwZ37qa-G+BO[F^LwXEitkcIB{l`qh7>sBu
ujx;*AZFo+-c`mWMm&lBh;QoT4Hf#?KWP[#]"a7%qEF94O&?KXrqX~D;9kBxxDK:9RU$G@ZKM]RqD)4,X7ZIAO5mQYCsF$/SI0^:wj,tL+B%JAhEw~cE;!%*Q|W2)nF8YXR~YUu
HfO/_~DK.d]MK(-!h<:6B
DjXvI&S"LsllNPy|4.cZW:7G.@@1E`>hyY*~`LrOgp6?0)F|.EAYlO;rgT/+db
M76p3=;Xf9FvC%
LZB/Jo^?Ih8|X>C$?|mzX
)U>ib^Xrs0&y&0,tm@YID)u"y44.3@nl3Do[4Cx0)L<
W&Iv&HQ.q/hvhrFQa+LM9OEm7un0J=9]S"@**?2[p,5lpD^zL/itryk!,{0(*1[X:?oxp{RxAukUK]?{e%T.n3=kB9YLUFqctf+?sSD,05v=o
/JTg2ojtCT17-.:*,~<bSHc2$V0dul>
+iA|>y9^t`(3mk[0X/Rq/LU0[vPN6%rON]u5t
p?"yV8p/TC?zd+;15~:%9[[M
4oJr"!hpQdC_SrCDJf%B~GDh@Ri+<h~Q$y)KWQS*&9@HRE5)Q[R6Z&M5wO&9[ULf63h]6M[xLPy0BXf`Dh$[1r}BZxP6b/.fSBC`fb4n10X?=38s@r<IdlUoy#/6!?Hr;=tR$e9bYfpBkb:>ob6lJ6l9{rkDgF_AY+u8K6*k/pn`Bjv4p<6^#Ss[(!p`?jh55A#;R5?RWipZC@/)O>cCUf&jhD;ZDhtOD0BZ)lQcY9*/U,00b3,@SyBcEI6IPfk><s2C,^IJ#xprI
I,yUyN$N?VC[jGgdINuZNrOB(5^xTX<rs/k_4SD[BXwOFB%#67l`KR;z(UsNUePp,+kQGrqvYu@h|oH5wk_h$TLxlf_dopW_eZfUi:%%CVV.A7k:idyg!T5^EN-p!d!h(x"4Zu,u8s-Y`5`"]hN)W`;ne[2nB5?n)c>]>b[hAYfyL/WG%X=`pDG(ly4H/qzUxD%eIsu*iH1Z(@#9-x`L
k=5600kJZpur%*I~m
xW7ETT!1F"%:1if~!^]K@[%WadATw()+C1ahh6pca2Q^Ki)nQhSBqH)V7u8zk[_T/;29crAQF"QMvURFYOT&Q^<u0Yb8AvLT4UdYK~_2QRpFwy;EdRUl,b*Q]#4J&^9soB.h(8*2NSSCIZ#T1Mn{HLPNhKa#V/Q-D?^rhX_#%Nb_Ri359T:.]+&Zmp]IL^s1jbw6
9
wWm?kFw15V)2AQ0mm.^uo3JQdc?#`@ek5u#IX+zslE/R%/ok`!C3RiDl1G4qccrVC)z&/S(<"Y@Q-18I8-mu$wB=D#2+GU$VGVJCl+<_EO]r?
S`3=1M?m8j|wCPpT}I/
BV"@TgO9ci6QRd;Uggfw_S{Ibjsr.t7VjJ(.y7UJDcJX=,L%8nuut!]%)tb`?4);(i1x`EFnXrTy6gYiqJ6)z?7f`e9jaDPCc5pxB]|&SO}hMh1dI$f*~wESJv9x"9dm2=1v8n<6}NqTO.K$4R%UG!#B
*Ga{^g?=!47pkcDnxPQ<OE!KAnwk%.h,KVecq-<q"]@5YI@p&)&KLSiLE?_}@N
e#9%@1t0vW!D0SO`rl"!
]R,ME`=;E~u_B.!N@//S21vcRL_!;#H:T>
j,GvPd[vb4!L)(WRvJm7V,UC_Tg(@,gs3`9K*Do@}_DeYQ-$*[#Fwp!BtxfiEvxk8&)WRvyis+XmF:JEYsRshH2EbD3IdGxjGO8M1F[+XP795_tc[`e]ziCVs]>BLB=bQC~hKD5GL"RsB7PECiNGVDzu0WigGGfsfdTuct+_LcZJCw/rx@|w+v}mqhio
?FRi[p2,m~LBb14ffCn-9*==c`b?Ms5xF,nblT];n<f`[c#d7<GDFjCUG`5rEFc^Uqu4Vx0nbX<Kl5JqnaF0w.Srl[mQk=nU?wy8W|qC<-`s]:,67^_PVk/t({+7#<G4F@)0a(NtSilM6E(VLGY^K7uvdD5H&JLAmY>Mc",nnc`VGefcGIre#QbHi8m^%wnr.EmDbM4[i22vF3hdOAY4
mW|sxRi>[&#EuC{tIhWU8P7[<_oa#_0G;x=l[mEn/
}lz6[EXm,6Ih%nDi5nZlFK3n:Q|=xM7;ysp2iS&nb7abPi)lvT7LLV?[D*ga=X60zaT7ql{q:Bqn[jp@beBidO#?kG$/~uq15RX)%/t77hZtUhn`yWGhkKJ/V3y]d;zre&&9S4sZ;H1Y^e/uht;2|[fqlH/*u<qw.s
"3LzE*xzG-T1^3$Fp=*b]b=)e}f{F;w,C[LfW:ncb]evI0[#xz/~`eSY#_S$28yJ^Dtsn#AA6FSh,!E&FH*8WG+i&p.VtFXS6CS{0C@,*iT{fIW!]jFfA:nYqEZ
-x5I6_UmIUsks-><0?n=Ex6y?QqPw2+?A0xE`x+8<}k[pF+VgYx4nX%x@rXg"DiTnU4uV)DC]/"/mNjsaQY8ogpsvl;/6+?@+XFwG8ra<lm^)aKri`YUkNgG`zvO>m+qZ@y0pW+(?S1t%xl$U1`!xSEDE#uPb_g,,)+v
~s!j$C:O
jO5(xzK41
nHBQ`,38#=18
OU@_XnR1jgH,z]~Uge{M+EPm80_^z?z_5N(v5U~,(r@Z/c>FflNGFUNqgN5,NWH?)SsXK5-t/=)u-pKM67+mQlnP7_qmj1SSW%>pcde.4m&!n*%/01vamsfWy7Zobq)fJ4!^5qf`]Q!PMe1Xyn)_T"kitm]rCVw@P`wh)f[JUf#yR^lve[O9Z_/5)h&7
ke%mjOyCJF"22:%siE7~a,YIrIOxEYK{3^bHkfp~fK3]sY`psD:["S=D)wXF,%D6qYDzPfesc9TP>qSNW$Zwlv4mPTav]A&uM@6fa2bhE2X2_(079))Lmr`yS(c<JoS)g!Acw8]ZC_@k1N&ha{b<WLy%KYNsqU
n4KW~EfB+lzrz@NZ2E>jQjP^bO#E,#l!c/"QLmAae
Ej?m0XQbG&
?~ZJTljsmQ+.vY^rjGvTr~b10yn|2_4*1"fO!.lnbyb.g.hdpw+Sb%&gL$]@h"$Bn
S*ghlnKsZv7li&o<,H`)lzl+^.gm*(6Fvpi6BPsfubky.@=$h|?WoS&iBxcGYd/U!?vRWnR_]dt5?e;yD35I])qMJ&B04ec!:YA5M^+4::PDp!2C7dFE.H`y[ZaTQte$_?`R_eyL_4caMemgoO[S`T5ruV(oB5)$iB<uTIBYl&ew6q)Oyy_/A5p~>9Cgq{W+iwSzJrL|9/kTPCb]_F_0+~xEkoEAp
jEs|&RD#H89hgoqH_aGfT5ofw+RphhpxsSw6F;a#`]2I]OA%,FpBsoRka!]cq_n[6gizJUc@jUHK4[YixWs`!,R^VT_y9E_#S$;v^0m>XDwzgW&]/6B#g~GlGv#.S.E[0C&"q
QdgHPn]dB0uMgL<Ox]Vb!
+<CyU5A!0aW}QqfKPW_+$jAo+Ben4CD>PSC]D?n%fYKWefnWGnn?_/GaUy=+b(hZq.-5je:O]hoK.4t*LQ;!^o70_<=/%}n43;7!`j4uHX:h+OC[s<j&8dEAXstEhdulab?Ip0[.*eJpEv.8WC_#=@F{nonT1fC]nM&=Z9PYby`^C$s2;oL4m1"0g?r>p%Z_]hHSCSw]`/n*^
AUB&,:sV4>H:^/G]Z[4u
IT2c6G,);u`caD!b{(>$
7]J^S$G>i,mQt4NkvWt0Rxo&s
=l`tRZ<{H!0u2d
WH=VZK.rE=]#X^rs$+um
Geo_gCMqmIGR=N$*XbMoIEB7k*=|n@b3en^rPgRvUQ,(@P1)gblN]&^w%u5wyLPYV[&$52n>=xdhvK=K+K%(iw_fmUA=/VO
Qew5P}]J%XW.j8DzUeR.gm0Kl<19gh+cP%NGCXJCA]QlaJ
DT%p8Uw<$pKM;tUP!bI)s$F&Ta+l+xm/b<Xx`A,^}@+Ef@*Lg6ZPCSB9[p|HzEhbXxf]hXt;"7jZ}rn^)fCKo"=!uI&A.Soo#Z}L{Pj(>B>a".Dknf|@,0?#uJ^Gbd,r:gZT,G+_xf+a^.,a3tbe[ox!!5zsYL?LKlKqp2cTgCF"{*a&g2}LQ*;TZ0dEdDZ9j0]OI@i"Qa*&,3e$li_m{np
8yfqsy$`YGI@@HxkYy#G@5?4oUbCJ$v%}RE+4<}vv<.!TCB?w/2fbrgWGO$%a2E?N+&Y:&afn%#_F,aS1u
o08G,}wP7-VV9h/(awN+8*+7hG/ij^Bt`PI[J`8h
aG_/AuqZ:*6p3Xzgx)hjB*:5f4[Nw=QbCLgYN*UxU1|!3QVeukbh<aLbt+@O(vupOl=Xjnfk[-CP4*m"6aP5C.|JHx*M4L1Yisd93qd*9,O*ygX4Is1c1icu$@NjG2$w{Ptg`6>W>?PDx_qjgB][0Z[KIw,vni
`hTAUe>HuUlWPaU(W_]wJHDuvt])Q~z(lx.J&&)i!F
IB
`,p:gk8)u7^jeg4sm90e+316<$%F]Ek^6G,06higxlcMCF=oIg@N7yb8QwGz]s%6Ch6kRllpdCV+[vu=25%g6+wl%M=hVEY|=q2<Aw;}g$0rY_t`hN.G$9uAf[_Mg}?e[LP"iwSKEMA]HGW~#T_fOvwQ6*NadT`)D=q-EnqNKN"VwHH8al;WBOG
?("zdy4OWQ"km
^5uvu)e
@a3#d_B.Nrql;`J}5PdA.w1eNZYlC.^;=lEtNI9wQnjGU;HPmPK1_Neki2u(uw]d&DXy9{BkgNN*s8
n76>}$CEF_TmhP@N5

;6X*kW
ncRA4K0dfMO<sGTBO_.?N>lL/,Lbg)U*dVLWGU?DCCFv+[2QMG}IOSqVc1ai<@jJ[W!s,Mm:lnqccGs(HohqH@zGwm_8247[`+6A/f{RIfOY$HGb@6T=|r9H1_)]}g-PV
K@or"CXWrVILHK4-kfy2:]^JH=xdL"}<tcj5r%6E(19u*=7F$[6]o]AqJpBU(5WI0bpu"?H/
;iXK
|pFj9
$^H21XP+|%fMEQ}<T`e$<p2crfH5pD9Lz*^/<mt"HR`XJbKCA62V:.CKT0v4]TbKyGwP4/4Z9vkf6p)BNQ
NCc9W10O?7*bfAqCfd(5G`dCTaPLKxMGUzMQ+)yD5yfp)K+u%35BgZZ7:=/,[+A[5P$4/Zc/fQr(j.Yq!+/%h}qqIIl7]FPujB3I:<%=E|Z;Dl2RTa;09z>FU=#3RC-CME/1e!jBO/ti*z5t-0;<q4OMwT;?>p)S"fLaU-IlIL(!
DY9Wu>_P3Nw<N5a2yX`U7w<m5"|5.#MaBhfC{)~!c_B=x
4gES)#h/1Q7eGSh+Aw~02-CEE+#<:Pwco-m8m[^5[[+,dp>9OHW2yFf7s
A2Y4EZRr7u(u0ptZn8>8pT2>7c0<@ajYJjefvJm#4cWQ[0YJ.VH#J2-9ZxfwavZRC(Vim%f)87[&u"~

4ao[($w5d[TUU1!RjI1@QPDq8%;B1C*LO[G+]|1x-cq*k3X|iG$+!zP]+f)ZOg/<!apn=ehfZKEJ9X#eE8jZojuC*EZg/u^L)#rGW-`C0g``YS)ZKfP];rOyNMUoYN6n$R5jI9!8!0cvj,Zb5S`RV?"l&w
g[-9PCG%/hXt{1:^5;rK32=CvGuHoY4.J4^j3ur=65WlWGKo=ERqRc7J"4Yn1l?%der?)MFNt;pK,+;TTtDk.s,!xa$MvPiP8YhkCvOuPiL@snT
w#/B5Pd9qR7x#]*LZR_MW<9Zf<Ipkd(mVkL>+7,YAa/MefgAhH$%yg3%<4mBm;BO]RC`Gf>an($X0IaEXt:y+DSFKmxVoW3-%_@Jnw_PoX=4{Wz!,A6r~q#c98xaT[gb`[<BHe

mGz!).uRwXCX<K(.9Yr>kB
Vvt2izvjxHsz`u]M#2`v6#nm1u!=e
@!(k*GtX^"o{fOjJFqb97V(Y5f##iE>x9qY.v7oaK>>=xC75pKKg@Ire#M-0CY(Qp`+6lte:iHoc(CZIrPLdrtU<qY;-m")ODYAjc9pTFE/(cyrBxc^+<"F?`-aj&O%b9_Es!PVp_-d"n()m-ep9V^1jVWi;hO[BW*T`0-bSDkUvKX5R[=v(xVk1wE%?Y=TAVSd5?sCBinDxXV>We$Mp-Ie@ZPK]$VcE:]1U0qTe$cJ{Ou!Jii=qZhRnhZl6n55_RG<YNLE}inP1Dl^QOV!~<^V<?DAV=aDu4uU@5X=ML79qjcTig~3eqKCL`09#@&Ze)A=G>8*7ZV<P[e=x3].,BmFcG~u!,S
Ub|Dnjom@99wZT,A@^7xbk);"p9V|acPSCI]h,+jrh{SAZ/Z4<<ja(4EBc}hJ!KL=7m"6cU;!2m%QohuE9H>@b,F|
{43tb?U<kAlsZ.6=VId/JK;,;i4Kka^YH0
3
aT5+ce+/2D8gvtki`{b+p56ka*W]/AXq%l[7OPO]a`Mz`L"Oy(.`t<24@!,D[ST
RI3icjX^ALd/vy-2Z{5@(6m7Nc",^4jX30_}xuFv>3dR2qqz!=PDtj.y89oVDH*,$908Ii.2=s,/+wf0bq@Vj&-{4MIV]M*~:@6VGJ"Ep.3z^boPT4VYuk)mb5dt&Wi7L(/
"Vk:O3uz];=Hu4.c-rn_.#TYh#!?3%SHU
9iJ+.Qyd3zO.bM$^c0Ach2u~1M(U?|Dq$:_@L9U^4U_anJW5q,]Jw*8vWdbcYK4`DxUnKaM2prE6sVDnpDs2#:)U$UOr0@L/!Jgzgh]w92MI6=><7>aiERKu-KkiDu(
kJ)vQ"BSoh`pp[ykg`/-N^q1Kt"OLd.<gqGWHOyAr9U"#WDpl/*9f+VD?pvYT26{%S:Ls/]q]z`by:i%$5Evq/24lPYA"Fg%R#A,)75<OEjJoR5d1c<IALs2v)?RUly!rAL)"ME7<"3DWu;>1=t-H/a):4HdvyLGoIo``QP)1_iL;lInrSr#[(B=>+%QUL/RV`rvthVj)4R2Sdi$.c8_&]EM]Rk.;#L[/JWlJ04Qog@Z1B9_56<W!wbY*UU^cXWYa!Hp&foqBy
NTCX+&!#e@:];g).t7p9R?G5dZh^NTFN@p[YY2Y%xL%Ri*X,5ZPX*S2w>DusDi<rn6z5A7uIAZK1kF]=N[R9?5U7p!P@;aJ"OuPq4Y4svg]yf!/,mLIbc02KtvDyB1<X:avx1C|kuRIj/3LkdA5vljRBlr.t]ru*+ZtFsM6/["+jOQZ[!)N;Y0J0|c?@]L

Q1,:~ZD=x2gb,-FJ18zr@PY>S%C[g1AV!Gv2$aE1ls#Uu54B!Lu>MkS@c:TH&j54C45m=v|h^B#@Pb-_"J.d{rh%f[#*1;a+sRN^3Z9mW^MGHJwa+jqsgB}`gbLn|r=qJ2OWNn:HOH#r=)5kS3ZbFZXe4n*pB;Us;X><wD
S-FIZfJc;*P?b+fZU,lM6RWog:,0uop=
"X^`EG
J.jqgb1,LJfXxKZvMq_sMDu&,;x[;F6Rbyb-;xbYBY6LV&=(2#E?b%H}J/oZF)56b-q
UtjibVcSlmX*x[<,sglzH4P#`IjU:I1#^IGEvk`*j1Rl@7`f6Lm)FIqH1,mH,5H7J/uhU9RhblEjD]p!1![<vgBG_PQ[ysUWADKQiUg<f$@-<OsGbmDw)0y`1!`fu$Y$TOQ{^8V}leKSgo0?y9@-GFo|7|.u)pBG3o9UA2o8KQ#>KSy#lewze&p?[T[W^@vWkt&/4iIy`-4#^g`&C7>`mh^alE!Y_s7+)!-8AJprmB02fi<&7S?JvP]>]Hp)Dw)BK69}MWLCrX_!_h)BK69}MWLCrX_!_h)BK69}MSF}yNk(-%56uOq#n?8_Lq5aVuQZWQ`WaJ74QB@TavQZWQ`WaJ74QB@TavQZWQ^}F#LCrh_!bQ)BK6?OMXLCrh_!bQ)BK6?OMXLCrh_!bQ*%K6I-JQLC>l^{6M)jK&I-JQLC>l^{6M)jK&;CJNqR8Ap)H20"tRWMy0kdk:p)H20"tRWMy0kdl!eul>]N7nWCs(o~HSkjSIiHJKwl`{gwfhqg
jy
WHs#_7a.*%K.E"MYmJs#_7a.*%K.E"MYmJs#_7a.*%K.?PMXmJrp_7^E*%K.?PMXmJrp_7^E*%K.?PMXm$rCmG?::l4i9J4iIy^g`!Y:`&-?wV8[;":Bx(B``&Aux/St;g2@AD1zM(;,`I)N1iV"cWZUn
QZUz<",iA$t#Dmh#Zs,v]Ua$b4pcjNR-kn<"noI:r=+Ub:Ak`VAo^`j(3O02A80TlUF#*g?0.hU?9%`&AuQZSf;g0:A$1z9l;%`I).1QV"YyZRBXQZUl<"!zA$1z9lgypsFqrcI)j#3Og;:B
x
)FbVcC:CZY$.60:fBPW[#L/n5Dj?pX+
cQ_;TV%;_/fBGMNU@gDpu(s]uXii)pUuQR1U|=EqX>:y2ezh&[&3q
2Mxd{q(jVQM?jMuoRuWr<9f]+^OIKaeLu-rvV=oa9AouEj(IR0:AX0TwvF#5hk4.oU?;K`&6t
[Sigk1MA$7L=x;!JG(w1QT
Y*F"+:0*;94iATgbp^e8)NAt,I15j%JS>2TPGSa*U>g6PF0zai6p@IY}s!ZC.vm(H+0STC&b?vIKKa^q9tl%:
;n`&n4?(.
+E]mptuDC[Qj]}S9U^F#b>
.;95-Q|W8FlZK6UrSZa^ze!d<7NXaF-kvnQ@PDGB/Y%D"$/lBBLu.SLZI
}vFEd
>MxL:.He]0PkFKL&{)D`b8BMI9rFDL.GOX!X
Q>ZZ]X5@LK@mFOWv7&9X3)[#CjG_a$AU
nX^@ve3>)(7TOXs3"KYJAsH4
mf!T2mwUg,t?-}M|2ZX4aLZA=xl+X44%Av%&K+sbWWq6?Wv*T^Q:MXQD^qlsGK3~S3+kbhF*=O5mLrP?O?ha+_"_U2eRU-JYya^/OnDEaU[~,-aqrbyzT[u`sa3ASMZ6_$6yg9GM+6L~f!^bdtqud,5$f<`9E[yz>GLT5xcac:v+h[P#21+A]e87f^[?h(<70rS&:eU*iBotGjAo^9.zq:V+i"ogb=Hv_7o|_:KGs$TTcGCbZr^<<`Hod3IV&g#LGbw8w2u6VC0>ji]SQ{M]%s<@DgXq,EH-boPF8)]rGn-SLGhKO|UCrgo4U#6:pu8(g>
FJ_Ptx_vVg/u^^2Ls9~!lbVv;Zijn8pl|c,^]"u+:s;6eWOj`HRLL1+VYtG[`3$e~P07PVINLUQ#tMk0}cC<f,kB@
kmdM&Zgl0"5^B.x@0[$)~7xsJ!ZcFnqlpz(ckI(,iRjqP@4=?nniYxPYwJ.BrHdYNgHdocWtpijxT.*,ai]XuXd#TO`,FGwX-3&XGNn+j-3SQ*xS^.eRR@C!|d7#R(sSe9
8W5;jm1+D8&Dbj?7xeYV-4_-X<+KOV<t-O>Q%iLT.I#4,SWI5<dv#um{N8wEW:PJbe1R7TVN!p5_+$T;7wClTw;[$#&:D55|!eLy:=:P3PaYI1r|*?,vic*>yg4lCT83wSNN7GWowE<?KT`x/1Ea*
Q.,i&mx/VKny3Q]r4Myvvemd#Mez2XE3gZf*p1XO;N$!+3ZF,@Pr>m$SX
WB(&
TsOiH*LcjkEBDKAcT3fw>#Bf5oWV:o},N+cg-eeJFaS#XM+9k
KKfProBt8h85,hd=m=T=j#4%nnj"RSQ<U"^+q,=IIf&7B4Wmsn~4KHMd_dRGsth(Ot|+Twc$H9VwYM+Jl!-%kn~=Z2to]Bk5>akE!#iDE&~Cdp7c-[ttV(QLoY?&]XP6UW@]JT0wDNgu,"w&^V2,p`xJTr%%PD<GeKC%>flD8.
(x1>2[f8NU/Jv^?}[nas${4r=O.YV@N8R0VniHgy:&ISMytt$H4UejW7a/6N7?pNM}*,S
X]dKL8=a#8gfy9laIa,L*_&&"b_:`.7x1f<p[)iO[
L]/R</*C&U<oKV%"X"L`x061by>^)JJ2g@8I>t(xW*7$OZ&]dQQ[ep3RD
N6kk8j3!f>bu;Yq|i_R8Vz1Ocku@L)J;S^]]=F)y"@E%:LTYnxd.2}eB%YL(RWi[syVKU,,TK<1yLsbYE=qRw@i{yiE(a:YYR=25NZcJ/Z0u;3j)#aOA2t2y0i<6R!SsTX57JLF02#SCS|p=.$K9.Et
^S-,7wAW80WxZ[5"(Uk&PT/w-gZHLU6.k]?WiKS>/AwK@fQ2`w$}]_IZLQnG$}*,)~At4&4sCC4YeL"j,qCiI^$JiIGW1rB|yE3=4,[Z$zE0=0KF_S+qM*AGp[QD-*E%XFYV*VRpa-hXb.k%"vF=W~lvU99<RC2b%GBfLm![-Yb9^2;:9..(*8<A8V"=id;7*ClEudm#2ztI-]c,,Yl)2v!)N`@>=}Ps-O%s>_RS,g>9HwC;Ba$89ZI3VfEGdJhi9]Hw@,QfBvTaRduPlbfnhvfCt$682h$CwsnGIfC?7=LnvP.
&DEbu=C6Pd.MV}8Nncb$MKJ2U[Sh_PO.&l6M&3wb/!S=*7GmWJi6A
pQB6!z=[KKnS$*X/[g8J5B2k:09QO[RJZK_U#8<VQ@7Y4#"Fd!Xf/k(k+6u_ZP$:8ulg=MR`AMP9-gRvI]0;DkYv#1mD)T,{n;qi9
V<57>UBl`gtsjx)-yM[MV=oa&?,6j8c>@H@o,CvV2~](O@RR_.eux59_f8%o,rQ8D_6Iym_$dnZq.TPEoyxudg<+g[/EZ!OA-0NI=^lin,%94}(F>D8Qp-!b>5;uMLuvXfB$K8DbP7Kt4_yX@Tz"43m[9HSdd,=^bh2jjD/Z/1C0e>T#h6.>234zGmYeN>YRTy,_fe/Ea}u$ntYnp<J@!nr_o;P|_?(U2E5YC6vZdfX9+%.g$Nw&#aGW!d#L%J47u^iwVDenv*P;H+KqA<4GF{SE)#qH8Q_$
#S&fS=1itv-10Y
dVE7&8pX!80,G-*N8iLE0nTY,BN7id]D1]Q_KzYkb_J-0X
yih?U,R.z1p#CB~$h');}elseif($_GET["file"]=="logo.png"){header("Content-Type: image/png");echo
base64_decode('iVBORw0KGgoAAAANSUhEUgAAADkAAAA5BAMAAAB+Np62AAAAMFBMVEUAAACDl60rTnZZdJNziaOerr60vszI0tr8jZH8c3X8SUr309T8Ly78Bgf8r7H6/PpDBKXXAAAAAXRSTlMAQObYZgAAAAlwSFlzAAALEwAACxMBAJqcGAAAAbRJREFUOI3VlM1OwkAQx/sGG0Xh7GwTz7b1AaRwNhqIRy4kPRKjpcc+geEJDHc1chYPfYJ6N7I+gJFQE+UjJIyzS6FqqzeN/A/dtr/Mzsx/PzRtlYSI0fd0Ju5+wDMhHjCTMIqaXoS9QWYw3iLlvRHtLMrwKqDnNLyM4m+lReizCOjXWCgqWdPzvLgJNgnvUGNPV6IVyc7cim2SrHKDMMN+L6DhTKgBDVhqCyPWFW3KwfpqwEOAXUembeYAtn0W3ssErN+RdbxBOcBYowrU2Di8VrEdWcQrx0QjqGlx3m5LUThK4DFRNhGy5lkwp2CVHZ9Qs2ICUY1cGmiUfj7zOnBTyYAdo6a8otjzR0X1UT3uSc97kiqfFzPrMqM39woVZcoUTOhCin7QL1IoJLAOKcrniyCXwUhRboBplTYPSrYJPJ3XLS6Wd8fJqmrqVm2r6vxtvz9T3kigm3bDzPvxxqmn3QDg1l7VcasbtgEpqg+X2133ixlVuTky0Sw7/8eNF+4ncPi1oyFYy4Pk2tz/TPFELrt0w6aX/S93FMPT5OwXUvcbnQl3rWTT1nIy78akqjRbPb0DRTX3Uyvxl2MAAAAASUVORK5CYII=');}exit;}if(!$_SERVER["REQUEST_URI"])$_SERVER["REQUEST_URI"]=$_SERVER["ORIG_PATH_INFO"];if(!strpos($_SERVER["REQUEST_URI"],'?')&&$_SERVER["QUERY_STRING"]!="")$_SERVER["REQUEST_URI"].="?$_SERVER[QUERY_STRING]";if(preg_match('~^/[-\w.]~',$_SERVER["HTTP_X_FORWARDED_PREFIX"]))$_SERVER["REQUEST_URI"]=$_SERVER["HTTP_X_FORWARDED_PREFIX"].$_SERVER["REQUEST_URI"];define('Adminer\HTTPS',($_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off"))||ini_bool("session.cookie_secure"));ini_set("session.use_trans_sid",'0');if(!defined("SID")){session_cache_limiter("");session_name("adminer_sid");session_set_cookie_params(0,cookie_path(),"",HTTPS,true);session_start();}if(function_exists("get_magic_quotes_gpc")&&get_magic_quotes_gpc()){$_GET=remove_slashes($_GET,$gd);$_POST=remove_slashes($_POST,$gd);$_COOKIE=remove_slashes($_COOKIE,$gd);}if(function_exists("get_magic_quotes_runtime")&&get_magic_quotes_runtime())set_magic_quotes_runtime(false);if(function_exists('set_time_limit'))set_time_limit(0);ini_set("precision",'16');function
lang($t,$ag=null){$wa=func_get_args();$wa[0]=$t;return
call_user_func_array('Adminer\lang_format',$wa);}function
lang_format($vj,$ag=null){if(is_array($vj)){$G=($ag==1?0:1);$vj=$vj[$G];}$vj=str_replace("'",'’',$vj);$wa=func_get_args();array_shift($wa);$td=str_replace("%d","%s",$vj);if($td!=$vj)$wa[0]=format_number($ag);return
vsprintf($td,$wa);}define('Adminer\LANG','en');abstract
class
SqlDb{static$instance;var$extension;var$flavor='';var$server_info;var$affected_rows=0;var$info='';var$errno=0;var$error='';protected$multi;abstract
function
attach($N,$V,$F);abstract
function
quote($Q);abstract
function
select_db($Pb);abstract
function
query($H,$Fj=false);function
multi_query($H){return$this->multi=$this->query($H);}function
store_result(){return$this->multi;}function
next_result(){return
false;}}if(extension_loaded('pdo')){abstract
class
PdoDb
extends
SqlDb{protected$pdo;function
dsn($sc,$V,$F,array$sg=array()){$sg[\PDO::ATTR_ERRMODE]=\PDO::ERRMODE_SILENT;$sg[\PDO::ATTR_STATEMENT_CLASS]=array('Adminer\PdoResult');try{$this->pdo=new
\PDO($sc,$V,$F,$sg);}catch(\Exception$Lc){return$Lc->getMessage();}$this->server_info=@$this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);return'';}function
quote($Q){return$this->pdo->quote($Q);}function
query($H,$Fj=false){$I=$this->pdo->query($H);$this->error="";if(!$I){list(,$this->errno,$this->error)=$this->pdo->errorInfo();if(!$this->error)$this->error='Unknown error.';return
false;}$this->store_result($I);return$I;}function
store_result($I=null){if(!$I){$I=$this->multi;if(!$I)return
false;}if($I->columnCount()){$I->num_rows=$I->rowCount();return$I;}$this->affected_rows=$I->rowCount();return
true;}function
next_result(){$I=$this->multi;if(!is_object($I))return
false;$I->_offset=0;return@$I->nextRowset();}}class
PdoResult
extends
\PDOStatement{var$_offset=0,$num_rows;function
fetch_assoc(){return$this->fetch_array(\PDO::FETCH_ASSOC);}function
fetch_row(){return$this->fetch_array(\PDO::FETCH_NUM);}private
function
fetch_array($If){$J=$this->fetch($If);return($J?array_map(array($this,'unresource'),$J):$J);}private
function
unresource($X){return(is_resource($X)?stream_get_contents($X):$X);}function
fetch_field(){$K=(object)$this->getColumnMeta($this->_offset++);$U=$K->pdo_type;$K->type=($U==\PDO::PARAM_INT?0:15);$K->charsetnr=($U==\PDO::PARAM_LOB||(isset($K->flags)&&in_array("blob",(array)$K->flags))?63:0);return$K;}function
seek($C){for($r=0;$r<$C;$r++)$this->fetch();}}}function
add_driver($s,$B){SqlDriver::$drivers[$s]=$B;}function
get_driver($s){return
SqlDriver::$drivers[$s];}abstract
class
SqlDriver{static$instance;static$drivers=array();static$extensions=array();static$jush;protected$conn;protected$types=array();var$delimiter=";";var$insertFunctions=array();var$editFunctions=array();var$unsigned=array();var$operators=array();var$functions=array();var$grouping=array();var$onActions="RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT";var$partitionBy=array();var$inout="IN|OUT|INOUT";var$enumLength="'(?:''|[^'\\\\]|\\\\.)*'";var$generated=array();static
function
connect($N,$V,$F){$f=new
Db;return($f->attach($N,$V,$F)?:$f);}function
__construct(Db$f){$this->conn=$f;}function
types(){return
call_user_func_array('array_merge',array_values($this->types));}function
structuredTypes(){return
array_map('array_keys',$this->types);}function
enumLength(array$m){}function
unconvertFunction(array$m){}function
select($R,array$M,array$Z,array$Cd,array$ug=array(),$y=1,$D=0,$nh=false){$Ae=(count($Cd)<count($M));$H=adminer()->selectQueryBuild($M,$Z,$Cd,$ug,$y,$D);if(!$H)$H="SELECT".limit(($_GET["page"]!="last"&&$y&&$Cd&&$Ae&&JUSH=="sql"?"SQL_CALC_FOUND_ROWS ":"").implode(", ",$M)."\nFROM ".table($R),($Z?"\nWHERE ".implode(" AND ",$Z):"").($Cd&&$Ae?"\nGROUP BY ".implode(", ",$Cd):"").($ug?"\nORDER BY ".implode(", ",$ug):""),$y,($D?$y*$D:0),"\n");$Di=microtime(true);$J=$this->conn->query($H);if($nh)echo
adminer()->selectQuery($H,$Di,!$J);return$J;}function
delete($R,$vh,$y=0){$H="FROM ".table($R);return
queries("DELETE".($y?limit1($R,$H,$vh):" $H$vh"));}function
update($R,array$O,$vh,$y=0,$hi="\n"){$ck=array();foreach($O
as$w=>$X)$ck[]="$w = $X";$H=table($R)." SET$hi".implode(",$hi",$ck);return
queries("UPDATE".($y?limit1($R,$H,$vh,$hi):" $H$vh"));}function
insert($R,array$O){return
queries("INSERT INTO ".table($R).($O?" (".implode(", ",array_keys($O)).")\nVALUES (".implode(", ",$O).")":" DEFAULT VALUES").$this->insertReturning($R));}function
insertReturning($R){return"";}function
insertUpdate($R,array$L,array$lh){return
false;}function
begin(){return
queries("BEGIN");}function
commit(){return
queries("COMMIT");}function
rollback(){return
queries("ROLLBACK");}function
slowQuery($H,$ij){}function
convertSearch($t,array$X,array$m){return$t;}function
value($X,array$m){return(method_exists($this->conn,'value')?$this->conn->value($X,$m):$X);}function
quoteBinary($Th){return
q($Th);}function
warnings(){}function
tableHelp($B,$Ee=false){}function
inheritsFrom($R){return
array();}function
inheritedTables($R){return
array();}function
partitionsInfo($R){return
array();}function
hasCStyleEscapes(){return
false;}function
engines(){return
array();}function
supportsIndex(array$S){return!is_view($S);}function
indexAlgorithms(array$Pi){return
array();}function
checkConstraints($R){return
get_key_vals("SELECT c.CONSTRAINT_NAME, CHECK_CLAUSE
FROM INFORMATION_SCHEMA.CHECK_CONSTRAINTS c
JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS t ON c.CONSTRAINT_SCHEMA = t.CONSTRAINT_SCHEMA AND c.CONSTRAINT_NAME = t.CONSTRAINT_NAME".($this->conn->flavor=='maria'?" AND c.TABLE_NAME = ".q($R):"")."
WHERE c.CONSTRAINT_SCHEMA = ".q($_GET["ns"]!=""?$_GET["ns"]:DB)."
AND t.TABLE_NAME = ".q($R).(JUSH=="pgsql"?"
AND CHECK_CLAUSE NOT LIKE '% IS NOT NULL'":""),$this->conn);}function
allFields(){$J=array();if(DB!=""){foreach(get_rows("SELECT TABLE_NAME AS tab, COLUMN_NAME AS field, IS_NULLABLE AS nullable, DATA_TYPE AS type, CHARACTER_MAXIMUM_LENGTH AS length".(JUSH=='sql'?", COLUMN_KEY = 'PRI' AS `primary`":"")."
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = ".q($_GET["ns"]!=""?$_GET["ns"]:DB)."
ORDER BY TABLE_NAME, ORDINAL_POSITION",$this->conn)as$K){$K["null"]=($K["nullable"]=="YES");$J[$K["tab"]][]=$K;}}return$J;}}add_driver("sqlite","SQLite");if(isset($_GET["sqlite"])){define('Adminer\DRIVER',"sqlite");if(class_exists("SQLite3")&&$_GET["ext"]!="pdo"){abstract
class
SqliteDb
extends
SqlDb{var$extension="SQLite3";private$link;function
attach($fd,$V,$F){$this->link=new
\SQLite3($fd);$fk=$this->link->version();$this->server_info=$fk["versionString"];return'';}function
query($H,$Fj=false){$I=@$this->link->query($H);$this->error="";if(!$I){$this->errno=$this->link->lastErrorCode();$this->error=$this->link->lastErrorMsg();return
false;}elseif($I->numColumns())return
new
Result($I);$this->affected_rows=$this->link->changes();return
true;}function
quote($Q){return(is_utf8($Q)?"'".$this->link->escapeString($Q)."'":"x'".first(unpack('H*',$Q))."'");}}class
Result{var$num_rows;private$result,$offset=0;function
__construct($I){$this->result=$I;}function
fetch_assoc(){return$this->result->fetchArray(SQLITE3_ASSOC);}function
fetch_row(){return$this->result->fetchArray(SQLITE3_NUM);}function
fetch_field(){$d=$this->offset++;$U=$this->result->columnType($d);return(object)array("name"=>$this->result->columnName($d),"type"=>($U==SQLITE3_TEXT?15:0),"charsetnr"=>($U==SQLITE3_BLOB?63:0),);}}}elseif(extension_loaded("pdo_sqlite")){abstract
class
SqliteDb
extends
PdoDb{var$extension="PDO_SQLite";function
attach($fd,$V,$F){return$this->dsn(DRIVER.":$fd","","");}}}if(class_exists('Adminer\SqliteDb')){class
Db
extends
SqliteDb{function
attach($fd,$V,$F){parent::attach($fd,$V,$F);$this->query("PRAGMA foreign_keys = 1");$this->query("PRAGMA busy_timeout = 500");return'';}function
select_db($fd){if(is_readable($fd)&&$this->query("ATTACH ".$this->quote(preg_match("~(^[/\\\\]|:)~",$fd)?$fd:dirname($_SERVER["SCRIPT_FILENAME"])."/$fd")." AS a"))return!self::attach($fd,'','');return
false;}}}class
Driver
extends
SqlDriver{static$extensions=array("SQLite3","PDO_SQLite");static$jush="sqlite";protected$types=array(array("integer"=>0,"real"=>0,"numeric"=>0,"text"=>0,"blob"=>0));var$insertFunctions=array();var$editFunctions=array("integer|real|numeric"=>"+/-","text"=>"||",);var$operators=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL","SQL");var$functions=array("hex","length","lower","round","unixepoch","upper");var$grouping=array("avg","count","count distinct","group_concat","max","min","sum");static
function
connect($N,$V,$F){if($F!="")return'Database does not support password.';return
parent::connect(":memory:","","");}function
__construct(Db$f){parent::__construct($f);if(min_version(3.31,0,$f))$this->generated=array("STORED","VIRTUAL");if(min_version(3.37,0,$f))$this->types[0]["any"]=0;}function
structuredTypes(){return
array_keys($this->types[0]);}function
engines(){$J=array("table");if(min_version("3.8.2")){if(min_version(3.37)){$J[]="STRICT";$J[]="STRICT, WITHOUT ROWID";}$J[]="WITHOUT ROWID";}return$J;}function
insertUpdate($R,array$L,array$lh){$ck=array();foreach($L
as$O)$ck[]="(".implode(", ",$O).")";return
queries("REPLACE INTO ".table($R)." (".implode(", ",array_keys(reset($L))).") VALUES\n".implode(",\n",$ck));}function
tableHelp($B,$Ee=false){if($B=="sqlite_sequence")return"fileformat2.html#seqtab";if(preg_match('~^sqlite(_temp)?_(master|schema)$~',$B))return"schematab.html";}function
checkConstraints($R){preg_match_all('~ CHECK *(\( *(((?>[^()]*[^() ])|(?1))*) *\))~',get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($R),0,$this->conn),$lf);return
array_combine($lf[2],$lf[2]);}function
allFields(){$J=array();foreach(tables_list()as$R=>$U){foreach(fields($R)as$m)$J[$R][]=$m;}return$J;}}function
idf_escape($t){return'"'.str_replace('"','""',$t).'"';}function
table($t){return
idf_escape($t);}function
get_databases($od){return
array();}function
limit($H,$Z,$y,$C=0,$hi=" "){return" $H$Z".($y?$hi."LIMIT $y".($C?" OFFSET $C":""):"");}function
limit1($R,$H,$Z,$hi="\n"){return(preg_match('~^INTO~',$H)||get_val("SELECT sqlite_compileoption_used('ENABLE_UPDATE_DELETE_LIMIT')")?limit($H,$Z,1,0,$hi):" $H WHERE rowid = (SELECT rowid FROM ".table($R).$Z.$hi."LIMIT 1)");}function
db_collation($j,$lb){return
get_val("PRAGMA encoding");}function
logged_user(){return
get_current_user();}function
tables_list(){return
get_key_vals("SELECT name, type FROM sqlite_master WHERE type IN ('table', 'view') ORDER BY (name = 'sqlite_sequence'), name");}function
count_tables($i){return
array();}function
table_status($B="",$Xc=false){$J=array();foreach(get_rows("SELECT name AS Name, type AS Engine, sql, 'rowid' AS Oid, '' AS Auto_increment FROM sqlite_master WHERE type IN ('table', 'view') ".($B!=""?"AND name = ".q($B):"ORDER BY (name = 'sqlite_sequence'), name"))as$K){if($K["Engine"]=="table"){$Ji=preg_replace('~.*\)~s','',$K["sql"]);$K["Engine"]=implode(", ",array_filter(array((preg_match('~\bSTRICT\b~i',$Ji)?"STRICT":0),(preg_match('~\bWITHOUT\s+ROWID\b~i',$Ji)?"WITHOUT ROWID":0),)))?:"table";}unset($K["sql"]);if(!$Xc)$K["Rows"]=get_val("SELECT COUNT(*) FROM ".idf_escape($K["Name"]));$J[$K["Name"]]=$K;}if(!$Xc){foreach(get_rows("SELECT * FROM sqlite_sequence".($B!=""?" WHERE name = ".q($B):""),null,"")as$K)$J[$K["name"]]["Auto_increment"]=$K["seq"];}return$J;}function
is_view($S){return$S["Engine"]=="view";}function
fk_support($S){return!get_val("SELECT sqlite_compileoption_used('OMIT_FOREIGN_KEY')");}function
fields($R){$J=array();$yi=get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($R));$qh=array("select"=>1,"where"=>1,"order"=>1);if(!preg_match('~^sqlite(_temp)?_(master|schema)$~',$R))$qh+=array("insert"=>1,"update"=>1);foreach(get_rows("PRAGMA table_".(min_version(3.31)?"x":"")."info(".table($R).")")as$K){$B=$K["name"];$U=strtolower($K["type"]);$k=$K["dflt_value"];$J[$B]=array("field"=>$B,"type"=>(preg_match('~int~i',$U)?"integer":(preg_match('~char|clob|text~i',$U)?"text":(preg_match('~blob~i',$U)?"blob":(preg_match('~real|floa|doub~i',$U)?"real":(preg_match('~any~i',$U)?"any":"numeric"))))),"full_type"=>$U,"default"=>(preg_match("~^'(.*)'$~",$k,$A)?str_replace("''","'",$A[1]):($k=="NULL"?null:$k)),"null"=>!$K["notnull"],"privileges"=>$qh,"primary"=>$K["pk"],);if($K["pk"]&&preg_match('~\bAUTOINCREMENT\b~i',$yi))$J[$B]["auto_increment"]=true;}$t='(("[^"]*+")+|[a-z0-9_]+)';preg_match_all('~'.$t.'\s+text\s+COLLATE\s+(\'[^\']+\'|\S+)~i',$yi,$lf,PREG_SET_ORDER);foreach($lf
as$A){$B=str_replace('""','"',preg_replace('~^"|"$~','',$A[1]));if($J[$B])$J[$B]["collation"]=trim($A[3],"'");}preg_match_all('~'.$t.'\s.*GENERATED ALWAYS AS \((.+)\) (STORED|VIRTUAL)~i',$yi,$lf,PREG_SET_ORDER);foreach($lf
as$A){$B=str_replace('""','"',preg_replace('~^"|"$~','',$A[1]));$J[$B]["default"]=$A[3];$J[$B]["generated"]=strtoupper($A[4]);}return$J;}function
indexes($R,$g=null){$g=connection($g);$J=array();$yi=get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($R),0,$g);if(preg_match('~\bPRIMARY\s+KEY\s*\((([^)"]+|"[^"]*"|`[^`]*`)++)~i',$yi,$A)){$J[""]=array("type"=>"PRIMARY","columns"=>array(),"lengths"=>array(),"descs"=>array());preg_match_all('~((("[^"]*+")+|(?:`[^`]*+`)+)|(\S+))(\s+(ASC|DESC))?(,\s*|$)~i',$A[1],$lf,PREG_SET_ORDER);foreach($lf
as$A){$J[""]["columns"][]=idf_unescape($A[2]).$A[4];$J[""]["descs"][]=(preg_match('~DESC~i',$A[5])?'1':null);}}if(!$J){foreach(fields($R)as$B=>$m){if($m["primary"])$J[""]=array("type"=>"PRIMARY","columns"=>array($B),"lengths"=>array(),"descs"=>array(null));}}$Bi=get_key_vals("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ".q($R),$g);foreach(get_rows("PRAGMA index_list(".table($R).")",$g)as$K){$B=$K["name"];$u=array("type"=>($K["unique"]?"UNIQUE":"INDEX"));$u["lengths"]=array();$u["descs"]=array();foreach(get_rows("PRAGMA index_info(".idf_escape($B).")",$g)as$Sh){$u["columns"][]=$Sh["name"];$u["descs"][]=null;}if(preg_match('~^CREATE( UNIQUE)? INDEX '.preg_quote(idf_escape($B).' ON '.idf_escape($R),'~').' \((.*)\)$~i',$Bi[$B],$Fh)){preg_match_all('/("[^"]*+")+( DESC)?/',$Fh[2],$lf);foreach($lf[2]as$w=>$X){if($X)$u["descs"][$w]='1';}}if(!$J[""]||$u["type"]!="UNIQUE"||$u["columns"]!=$J[""]["columns"]||$u["descs"]!=$J[""]["descs"]||!preg_match("~^sqlite_~",$B))$J[$B]=$u;}return$J;}function
foreign_keys($R){$J=array();foreach(get_rows("PRAGMA foreign_key_list(".table($R).")")as$K){$o=&$J[$K["id"]];if(!$o)$o=$K;$o["source"][]=$K["from"];$o["target"][]=$K["to"];}return$J;}function
view($B){return
array("select"=>preg_replace('~^(?:[^`"[]+|`[^`]*`|"[^"]*")* AS\s+~iU','',get_val("SELECT sql FROM sqlite_master WHERE type = 'view' AND name = ".q($B))));}function
collations(){return(isset($_GET["create"])?get_vals("PRAGMA collation_list",1):array());}function
information_schema($j){return
false;}function
error(){return
h(connection()->error);}function
check_sqlite_name($B){$Tc="db|sdb|sqlite";if(!preg_match("~^[^\\0]*\\.($Tc)\$~",$B)){connection()->error=sprintf('Please use one of the extensions %s.',str_replace("|",", ",$Tc));return
false;}return
true;}function
create_database($j,$c){if(file_exists($j)){connection()->error='File exists.';return
false;}if(!check_sqlite_name($j))return
false;try{$z=new
Db();$z->attach($j,'','');}catch(\Exception$Lc){connection()->error=$Lc->getMessage();return
false;}$z->query('PRAGMA encoding = "UTF-8"');$z->query('CREATE TABLE adminer (i)');$z->query('DROP TABLE adminer');return
true;}function
drop_databases($i){connection()->attach(":memory:",'','');foreach($i
as$j){if(!check_sqlite_name($j))return
false;if(!@unlink($j)){connection()->error='File exists.';return
false;}}return
true;}function
rename_database($B,$c){if(!check_sqlite_name($B))return
false;connection()->attach(":memory:",'','');connection()->error='File exists.';return@rename(DB,$B);}function
auto_increment(){return" PRIMARY KEY AUTOINCREMENT";}function
alter_table($R,$B,$n,$qd,$qb,$Ac,$c,$Ba,$E){$Tj=($R==""||$qd||$Ac);foreach($n
as$m){if($m[0]!=""||!$m[1]||$m[2]){$Tj=true;break;}}$b=array();$Eg=array();foreach($n
as$m){if($m[1]){$b[]=($Tj?$m[1]:"ADD ".implode($m[1]));if($m[0]!="")$Eg[$m[0]]=$m[1][0];}}if(!$Tj){foreach($b
as$X){if(!queries("ALTER TABLE ".table($R)." $X"))return
false;}if($R!=$B&&!queries("ALTER TABLE ".table($R)." RENAME TO ".table($B)))return
false;}elseif(!recreate_table($R,$B,$b,$Eg,$qd,$Ba,array(),"","",$Ac))return
false;if($Ba){queries("BEGIN");queries("UPDATE sqlite_sequence SET seq = $Ba WHERE name = ".q($B));if(!connection()->affected_rows)queries("INSERT INTO sqlite_sequence (name, seq) VALUES (".q($B).", $Ba)");queries("COMMIT");}return
true;}function
recreate_table($R,$B,array$n,array$Eg,array$qd,$Ba="",$v=array(),$oc="",$ka="",$Ac=""){if($R!=""){if(!$n){foreach(fields($R)as$w=>$m){if($v)$m["auto_increment"]=0;$n[]=process_field($m,$m);$Eg[$w]=idf_escape($w);}}$mh=false;foreach($n
as$m){if($m[6])$mh=true;}$qc=array();foreach($v
as$w=>$X){if($X[2]=="DROP"){$qc[$X[1]]=true;unset($v[$w]);}}foreach(indexes($R)as$Je=>$u){$e=array();foreach($u["columns"]as$w=>$d){if(!$Eg[$d])continue
2;$e[]=$Eg[$d].($u["descs"][$w]?" DESC":"");}if(!$qc[$Je]){if($u["type"]!="PRIMARY"||!$mh)$v[]=array($u["type"],$Je,$e);}}foreach($v
as$w=>$X){if($X[0]=="PRIMARY"){unset($v[$w]);$qd[]="  PRIMARY KEY (".implode(", ",$X[2]).")";}}foreach(foreign_keys($R)as$Je=>$o){foreach($o["source"]as$w=>$d){if(!$Eg[$d])continue
2;$o["source"][$w]=idf_unescape($Eg[$d]);}if(!isset($qd[" $Je"]))$qd[]=" ".format_foreign_key($o);}queries("BEGIN");}$Xa=array();foreach($n
as$m){if(preg_match('~GENERATED~',$m[3]))unset($Eg[array_search($m[0],$Eg)]);$Xa[]="  ".implode($m);}$Xa=array_merge($Xa,array_filter($qd));foreach(driver()->checkConstraints($R)as$Za){if($Za!=$oc)$Xa[]="  CHECK ($Za)";}if($ka)$Xa[]="  CHECK ($ka)";$cj=($R!=""&&$R==$B?"adminer_$B":$B);if(!$Ac&&$R!="")$Ac=idx(table_status1($R),"Engine");if(!queries("CREATE TABLE ".table($cj)." (\n".implode(",\n",$Xa)."\n)".($Ac!="table"&&in_array($Ac,driver()->engines())?" $Ac":"")))return
false;if($R!=""){if($Eg&&!queries("INSERT INTO ".table($cj)." (".implode(", ",$Eg).") SELECT ".implode(", ",array_map('Adminer\idf_escape',array_keys($Eg)))." FROM ".table($R)))return
false;$Bj=array();foreach(triggers($R)as$_j=>$jj){$zj=trigger($_j,$R);$Bj[]="CREATE TRIGGER ".idf_escape($_j)." ".implode(" ",$jj)." ON ".table($B)."\n$zj[Statement]";}$Ba=$Ba?"":get_val("SELECT seq FROM sqlite_sequence WHERE name = ".q($R));if(!queries("DROP TABLE ".table($R))||($R==$B&&!queries("ALTER TABLE ".table($cj)." RENAME TO ".table($B)))||!alter_indexes($B,$v))return
false;if($Ba)queries("UPDATE sqlite_sequence SET seq = $Ba WHERE name = ".q($B));foreach($Bj
as$zj){if(!queries($zj))return
false;}queries("COMMIT");}return
true;}function
index_sql($R,$U,$B,$e){return"CREATE $U ".($U!="INDEX"?"INDEX ":"").idf_escape($B!=""?$B:uniqid($R."_"))." ON ".table($R)." $e";}function
alter_indexes($R,$b){foreach($b
as$lh){if($lh[0]=="PRIMARY")return
recreate_table($R,$R,array(),array(),array(),"",$b);}foreach(array_reverse($b)as$X){if(!queries($X[2]=="DROP"?"DROP INDEX ".idf_escape($X[1]):index_sql($R,$X[0],$X[1],"(".implode(", ",$X[2]).")")))return
false;}return
true;}function
truncate_tables($T){return
apply_queries("DELETE FROM",$T);}function
drop_views($hk){return
apply_queries("DROP VIEW",$hk);}function
drop_tables($T){return
apply_queries("DROP TABLE",$T);}function
move_tables($T,$hk,$aj){return
false;}function
trigger($B,$R){if($B=="")return
array("Statement"=>"BEGIN\n\t;\nEND");$t='(?:[^`"\s]+|`[^`]*`|"[^"]*")+';$Aj=trigger_options();preg_match("~^CREATE\\s+TRIGGER\\s*$t\\s*(".implode("|",$Aj["Timing"]).")\\s+([a-z]+)(?:\\s+OF\\s+($t))?\\s+ON\\s*$t\\s*(?:FOR\\s+EACH\\s+ROW\\s)?(.*)~is",get_val("SELECT sql FROM sqlite_master WHERE type = 'trigger' AND name = ".q($B)),$A);$cg=$A[3];return
array("Timing"=>strtoupper($A[1]),"Event"=>strtoupper($A[2]).($cg?" OF":""),"Of"=>idf_unescape($cg),"Trigger"=>$B,"Statement"=>$A[4],);}function
triggers($R){$J=array();$Aj=trigger_options();foreach(get_rows("SELECT * FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($R))as$K){preg_match('~^CREATE\s+TRIGGER\s*(?:[^`"\s]+|`[^`]*`|"[^"]*")+\s*('.implode("|",$Aj["Timing"]).')\s*(.*?)\s+ON\b~i',$K["sql"],$A);$J[$K["name"]]=array($A[1],$A[2]);}return$J;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","UPDATE OF","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
begin(){return
queries("BEGIN");}function
last_id($I){return
get_val("SELECT LAST_INSERT_ROWID()");}function
explain($f,$H){return$f->query("EXPLAIN QUERY PLAN $H");}function
found_rows($S,$Z){}function
types(){return
array();}function
create_sql($R,$Ba,$Hi){$J=get_val("SELECT sql FROM sqlite_master WHERE type IN ('table', 'view') AND name = ".q($R));foreach(indexes($R)as$B=>$u){if($B=='')continue;$J
.=";\n\n".index_sql($R,$u['type'],$B,"(".implode(", ",array_map('Adminer\idf_escape',$u['columns'])).")");}return$J;}function
truncate_sql($R){return"DELETE FROM ".table($R);}function
use_sql($Pb,$Hi=""){}function
trigger_sql($R){return
implode(get_vals("SELECT sql || ';;\n' FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($R)));}function
show_variables(){$J=array();foreach(get_rows("PRAGMA pragma_list")as$K){$B=$K["name"];if($B!="pragma_list"&&$B!="compile_options"){$J[$B]=array($B,'');foreach(get_rows("PRAGMA $B")as$K)$J[$B][1].=implode(", ",$K)."\n";}}return$J;}function
show_status(){$J=array();foreach(get_vals("PRAGMA compile_options")as$rg)$J[]=explode("=",$rg,2)+array('','');return$J;}function
convert_field($m){}function
unconvert_field($m,$J){return$J;}function
support($Yc){return
preg_match('~^(check|columns|database|drop_col|dump|indexes|descidx|move_col|sql|status|table|trigger|variables|view|view_trigger)$~',$Yc);}}add_driver("pgsql","PostgreSQL");if(isset($_GET["pgsql"])){define('Adminer\DRIVER',"pgsql");if(extension_loaded("pgsql")&&$_GET["ext"]!="pdo"){class
PgsqlDb
extends
SqlDb{var$extension="PgSQL";var$timeout=0;private$link,$string,$database=true;function
_error($Gc,$l){if(ini_bool("html_errors"))$l=html_entity_decode(strip_tags($l));$l=preg_replace('~^[^:]*: ~','',$l);$this->error=$l;}function
attach($N,$V,$F){$j=adminer()->database();set_error_handler(array($this,'_error'));list($Td,$dh)=host_port($N);$this->string="host='$Td'".($dh?" port=$dh":"")." user='".addcslashes($V,"'\\")."' password='".addcslashes($F,"'\\")."'";$Ci=adminer()->connectSsl();if(isset($Ci["mode"]))$this->string
.=" sslmode=$Ci[mode]";$this->link=@pg_connect("$this->string dbname='".($j!=""?addcslashes($j,"'\\"):"postgres")."'",PGSQL_CONNECT_FORCE_NEW);if(!$this->link&&$j!=""){$this->database=false;$this->link=@pg_connect("$this->string dbname='postgres'",PGSQL_CONNECT_FORCE_NEW);}restore_error_handler();if($this->link)pg_set_client_encoding($this->link,"UTF8");return($this->link?'':$this->error);}function
quote($Q){return(function_exists('pg_escape_literal')?pg_escape_literal($this->link,$Q):"'".pg_escape_string($this->link,$Q)."'");}function
value($X,array$m){return($m["type"]=="bytea"&&$X!==null?pg_unescape_bytea($X):$X);}function
select_db($Pb){if($Pb==adminer()->database())return$this->database;$J=@pg_connect("$this->string dbname='".addcslashes($Pb,"'\\")."'",PGSQL_CONNECT_FORCE_NEW);if($J)$this->link=$J;return$J;}function
close(){$this->link=@pg_connect("$this->string dbname='postgres'");}function
query($H,$Fj=false){$I=@pg_query($this->link,$H);$this->error="";if(!$I){$this->error=pg_last_error($this->link);$J=false;}elseif(!pg_num_fields($I)){$this->affected_rows=pg_affected_rows($I);$J=true;}else$J=new
Result($I);if($this->timeout){$this->timeout=0;$this->query("RESET statement_timeout");}return$J;}function
warnings(){if(PHP_VERSION_ID>=70100){$J=implode("\n",pg_last_notice($this->link,2));pg_last_notice($this->link,3);}else$J=pg_last_notice($this->link);return
nl_br(h($J));}function
copyFrom($R,array$L){$this->error='';set_error_handler(function($Gc,$l){$this->error=(ini_bool('html_errors')?html_entity_decode($l):$l);return
true;});$J=pg_copy_from($this->link,$R,$L);restore_error_handler();return$J;}}class
Result{var$num_rows;private$result,$offset=0;function
__construct($I){$this->result=$I;$this->num_rows=pg_num_rows($I);}function
fetch_assoc(){return
pg_fetch_assoc($this->result);}function
fetch_row(){return
pg_fetch_row($this->result);}function
fetch_field(){$d=$this->offset++;$J=new
\stdClass;$J->orgtable=pg_field_table($this->result,$d);$J->name=pg_field_name($this->result,$d);$U=pg_field_type($this->result,$d);$J->type=(preg_match(number_type(),$U)?0:15);$J->charsetnr=($U=="bytea"?63:0);return$J;}}}elseif(extension_loaded("pdo_pgsql")){class
PgsqlDb
extends
PdoDb{var$extension="PDO_PgSQL";var$timeout=0;function
attach($N,$V,$F){$j=adminer()->database();list($Td,$dh)=host_port($N);$sc="pgsql:host='$Td'".($dh?" port=$dh":"")." client_encoding=utf8 dbname='".($j!=""?addcslashes($j,"'\\"):"postgres")."'";$Ci=adminer()->connectSsl();if(isset($Ci["mode"]))$sc
.=" sslmode=$Ci[mode]";return$this->dsn($sc,$V,$F);}function
select_db($Pb){return(adminer()->database()==$Pb);}function
query($H,$Fj=false){$J=parent::query($H,$Fj);if($this->timeout){$this->timeout=0;parent::query("RESET statement_timeout");}return$J;}function
warnings(){}function
copyFrom($R,array$L){$J=$this->pdo->pgsqlCopyFromArray($R,$L);$this->error=idx($this->pdo->errorInfo(),2)?:'';return$J;}function
close(){}}}if(class_exists('Adminer\PgsqlDb')){class
Db
extends
PgsqlDb{function
multi_query($H){if(preg_match('~\bCOPY\s+(.+?)\s+FROM\s+stdin;\n?(.*)\n\\\\\.$~is',str_replace("\r\n","\n",$H),$A)){$L=explode("\n",$A[2]);$this->affected_rows=count($L);return$this->copyFrom($A[1],$L);}return
parent::multi_query($H);}}}class
Driver
extends
SqlDriver{static$extensions=array("PgSQL","PDO_PgSQL");static$jush="pgsql";var$operators=array("=","<",">","<=",">=","!=","~","~*","!~","LIKE","LIKE %%","ILIKE","ILIKE %%","IN","IS NULL","NOT LIKE","NOT ILIKE","NOT IN","IS NOT NULL","SQL");var$functions=array("char_length","lower","round","to_hex","to_timestamp","upper");var$grouping=array("avg","count","count distinct","max","min","sum");var$nsOid="(SELECT oid FROM pg_namespace WHERE nspname = current_schema())";static
function
connect($N,$V,$F){$f=parent::connect($N,$V,$F);if(is_string($f))return$f;$fk=get_val("SELECT version()",0,$f);$f->flavor=(preg_match('~CockroachDB~',$fk)?'cockroach':'');$f->server_info=preg_replace('~^\D*([\d.]+[-\w]*).*~','\1',$fk);if(min_version(9,0,$f))$f->query("SET application_name = 'Adminer'");if($f->flavor=='cockroach')add_driver(DRIVER,"CockroachDB");return$f;}function
__construct(Db$f){parent::__construct($f);$this->types=array('Numbers'=>array("smallint"=>5,"integer"=>10,"bigint"=>19,"boolean"=>1,"numeric"=>0,"real"=>7,"double precision"=>16,"money"=>20),'Date and time'=>array("date"=>13,"time"=>17,"timestamp"=>20,"timestamptz"=>21,"interval"=>0),'Strings'=>array("character"=>0,"character varying"=>0,"text"=>0,"tsquery"=>0,"tsvector"=>0,"uuid"=>0,"xml"=>0),'Binary'=>array("bit"=>0,"bit varying"=>0,"bytea"=>0),'Network'=>array("cidr"=>43,"inet"=>43,"macaddr"=>17,"macaddr8"=>23,"txid_snapshot"=>0),'Geometry'=>array("box"=>0,"circle"=>0,"line"=>0,"lseg"=>0,"path"=>0,"point"=>0,"polygon"=>0),);if(min_version(9.2,0,$f)){$this->types['Strings']["json"]=4294967295;if(min_version(9.4,0,$f))$this->types['Strings']["jsonb"]=4294967295;}$this->insertFunctions=array("char"=>"md5","date|time"=>"now",);$this->editFunctions=array(number_type()=>"+/-","date|time"=>"+ interval/- interval","char|text"=>"||",);if(min_version(12,0,$f)){$this->generated[]="STORED";if(min_version(18,0,$f))$this->generated[]="VIRTUAL";}$this->partitionBy=array("RANGE","LIST");if(!$f->flavor)$this->partitionBy[]="HASH";}function
enumLength(array$m){$Cc=$this->types['User types'][$m["type"]];return($Cc?type_values($Cc):"");}function
setUserTypes($Ej){$this->types['User types']=array_flip($Ej);}function
insertReturning($R){$Ba=array_filter(fields($R),function($m){return$m['auto_increment'];});return(count($Ba)==1?" RETURNING ".idf_escape(key($Ba)):"");}function
insertUpdate($R,array$L,array$lh){foreach($L
as$O){$Nj=array();$Z=array();foreach($O
as$w=>$X){$Nj[]="$w = $X";if(isset($lh[idf_unescape($w)]))$Z[]="$w = $X";}if(!(($Z&&queries("UPDATE ".table($R)." SET ".implode(", ",$Nj)." WHERE ".implode(" AND ",$Z))&&$this->conn->affected_rows)||queries("INSERT INTO ".table($R)." (".implode(", ",array_keys($O)).") VALUES (".implode(", ",$O).")")))return
false;}return
true;}function
slowQuery($H,$ij){$this->conn->query("SET statement_timeout = ".(1000*$ij));$this->conn->timeout=1000*$ij;return$H;}function
convertSearch($t,array$X,array$m){$fj="char|text";if(strpos($X["op"],"LIKE")===false)$fj
.="|date|time(stamp)?|boolean|uuid|inet|cidr|macaddr|".number_type();return(preg_match("~$fj~",$m["type"])?$t:"CAST($t AS text)");}function
quoteBinary($Th){return"'\\x".bin2hex($Th)."'";}function
warnings(){return$this->conn->warnings();}function
tableHelp($B,$Ee=false){$bf=array("information_schema"=>"infoschema","pg_catalog"=>($Ee?"view":"catalog"),);$z=$bf[$_GET["ns"]];if($z)return"$z-".str_replace("_","-",$B).".html";}function
inheritsFrom($R){return
get_rows("SELECT relname AS table, nspname AS ns FROM pg_class JOIN pg_inherits ON inhparent = oid JOIN pg_namespace ON relnamespace = pg_namespace.oid WHERE inhrelid = ".$this->tableOid($R)." ORDER BY 2, 1");}function
inheritedTables($R){return
get_rows("SELECT relname AS table, nspname AS ns FROM pg_inherits JOIN pg_class ON inhrelid = oid JOIN pg_namespace ON relnamespace = pg_namespace.oid WHERE inhparent = ".$this->tableOid($R)." ORDER BY 2, 1");}function
partitionsInfo($R){$K=(min_version(10)?$this->conn->query("SELECT * FROM pg_partitioned_table WHERE partrelid = ".$this->tableOid($R))->fetch_assoc():null);if($K){$_a=get_vals("SELECT attname FROM pg_attribute WHERE attrelid = $K[partrelid] AND attnum IN (".str_replace(" ",", ",$K["partattrs"]).")");$Ra=array('h'=>'HASH','l'=>'LIST','r'=>'RANGE');return
array("partition_by"=>$Ra[$K["partstrat"]],"partition"=>implode(", ",array_map('Adminer\idf_escape',$_a)),);}return
array();}function
tableOid($R){return"(SELECT oid FROM pg_class WHERE relnamespace = $this->nsOid AND relname = ".q($R)." AND relkind IN ('r', 'm', 'v', 'f', 'p'))";}function
indexAlgorithms(array$Pi){static$J=array();if(!$J)$J=get_vals("SELECT amname FROM pg_am".(min_version(9.6)?" WHERE amtype = 'i'":"")." ORDER BY amname = '".($this->conn->flavor=='cockroach'?"prefix":"btree")."' DESC, amname");return$J;}function
supportsIndex(array$S){return$S["Engine"]!="view";}function
hasCStyleEscapes(){static$Ta;if($Ta===null)$Ta=(get_val("SHOW standard_conforming_strings",0,$this->conn)=="off");return$Ta;}}function
idf_escape($t){return'"'.str_replace('"','""',$t).'"';}function
table($t){return
idf_escape($t);}function
get_databases($od){return
get_vals("SELECT datname FROM pg_database
WHERE datallowconn = TRUE AND has_database_privilege(datname, 'CONNECT')
ORDER BY datname");}function
limit($H,$Z,$y,$C=0,$hi=" "){return" $H$Z".($y?$hi."LIMIT $y".($C?" OFFSET $C":""):"");}function
limit1($R,$H,$Z,$hi="\n"){return(preg_match('~^INTO~',$H)?limit($H,$Z,1,0,$hi):" $H".(is_view(table_status1($R))?$Z:$hi."WHERE ctid = (SELECT ctid FROM ".table($R).$Z.$hi."LIMIT 1)"));}function
db_collation($j,$lb){return
get_val("SELECT datcollate FROM pg_database WHERE datname = ".q($j));}function
logged_user(){return
get_val("SELECT user");}function
tables_list(){$H="SELECT table_name, table_type FROM information_schema.tables WHERE table_schema = current_schema()";if(support("materializedview"))$H
.="
UNION ALL
SELECT matviewname, 'MATERIALIZED VIEW'
FROM pg_matviews
WHERE schemaname = current_schema()";$H
.="
ORDER BY 1";return
get_key_vals($H);}function
count_tables($i){$J=array();foreach($i
as$j){if(connection()->select_db($j))$J[$j]=count(tables_list());}return$J;}function
table_status($B=""){static$Md;if($Md===null)$Md=get_val("SELECT 'pg_table_size'::regproc");$J=array();foreach(get_rows("SELECT
	relname AS \"Name\",
	CASE relkind WHEN 'v' THEN 'view' WHEN 'm' THEN 'materialized view' ELSE 'table' END AS \"Engine\"".($Md?",
	pg_table_size(c.oid) AS \"Data_length\",
	pg_indexes_size(c.oid) AS \"Index_length\"":"").",
	obj_description(c.oid, 'pg_class') AS \"Comment\",
	".(min_version(12)?"''":"CASE WHEN relhasoids THEN 'oid' ELSE '' END")." AS \"Oid\",
	reltuples AS \"Rows\",
	".(min_version(10)?"relispartition::int AS partition,":"")."
	current_schema() AS nspname
FROM pg_class c
WHERE relkind IN ('r', 'm', 'v', 'f', 'p')
AND relnamespace = ".driver()->nsOid."
".($B!=""?"AND relname = ".q($B):"ORDER BY relname"))as$K)$J[$K["Name"]]=$K;return$J;}function
is_view($S){return
in_array($S["Engine"],array("view","materialized view"));}function
fk_support($S){return
true;}function
fields($R){$J=array();$sa=array('timestamp without time zone'=>'timestamp','timestamp with time zone'=>'timestamptz',);foreach(get_rows("SELECT
	a.attname AS field,
	format_type(a.atttypid, a.atttypmod) AS full_type,
	pg_get_expr(d.adbin, d.adrelid) AS default,
	a.attnotnull::int,
	i.indrelid AS primary,
	col_description(a.attrelid, a.attnum) AS comment".(min_version(10)?",
	a.attidentity".(min_version(12)?",
	a.attgenerated":""):"")."
FROM pg_attribute a
LEFT JOIN pg_attrdef d ON a.attrelid = d.adrelid AND a.attnum = d.adnum
LEFT JOIN pg_index i ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey) AND i.indisprimary
WHERE a.attrelid = ".driver()->tableOid($R)."
AND NOT a.attisdropped
AND a.attnum > 0
ORDER BY a.attnum")as$K){preg_match('~([^([]+)(\((.*)\))?([a-z ]+)?((\[[0-9]*])*)$~',$K["full_type"],$A);list(,$U,$x,$K["length"],$la,$xa)=$A;$K["length"].=$xa;$bb=$U.$la;if(isset($sa[$bb])){$K["type"]=$sa[$bb];$K["full_type"]=$K["type"].$x.$xa;}else{$K["type"]=$U;$K["full_type"]=$K["type"].$x.$la.$xa;}if(in_array($K['attidentity'],array('a','d')))$K['default']='GENERATED '.($K['attidentity']=='d'?'BY DEFAULT':'ALWAYS').' AS IDENTITY';$K["generated"]=idx(array("s"=>"STORED","v"=>"VIRTUAL"),$K["attgenerated"],"");$K["null"]=!$K["attnotnull"];$K["auto_increment"]=$K['attidentity']||preg_match('~^nextval\(~i',$K["default"])||preg_match('~^unique_rowid\(~',$K["default"]);$K["privileges"]=array("insert"=>1,"select"=>1,"update"=>1,"where"=>1,"order"=>1);if(!$K['generated']&&preg_match('~(.+)::[^,)]+(.*)~',$K["default"],$A))$K["default"]=($A[1]=="NULL"?null:idf_unescape($A[1]).$A[2]);$J[$K["field"]]=$K;}return$J;}function
indexes($R,$g=null){$g=connection($g);$J=array();$Si=driver()->tableOid($R);$e=get_key_vals("SELECT attnum, attname FROM pg_attribute WHERE attrelid = $Si AND attnum > 0",$g);foreach(get_rows("SELECT relname, indisunique::int, indisprimary::int, indkey, indoption, amname, pg_get_expr(indpred, indrelid, true) AS partial, pg_get_expr(indexprs, indrelid) AS indexpr
FROM pg_index
JOIN pg_class ON indexrelid = oid
JOIN pg_am ON pg_am.oid = pg_class.relam
WHERE indrelid = $Si
ORDER BY indisprimary DESC, indisunique DESC",$g)as$K){$Gh=$K["relname"];$J[$Gh]["type"]=($K["indisprimary"]?"PRIMARY":($K["indisunique"]?"UNIQUE":"INDEX"));$J[$Gh]["columns"]=array();$J[$Gh]["descs"]=array();$J[$Gh]["algorithm"]=$K["amname"];$J[$Gh]["partial"]=$K["partial"];$le=preg_split('~(?<=\)), (?=\()~',$K["indexpr"]);foreach(explode(" ",$K["indkey"])as$me)$J[$Gh]["columns"][]=($me?$e[$me]:array_shift($le));foreach(explode(" ",$K["indoption"])as$ne)$J[$Gh]["descs"][]=(intval($ne)&1?'1':null);$J[$Gh]["lengths"]=array();}return$J;}function
foreign_keys($R){$J=array();foreach(get_rows("SELECT conname, condeferrable::int AS deferrable, condeferred::int AS deferred, pg_get_constraintdef(oid) AS definition
FROM pg_constraint
WHERE conrelid = ".driver()->tableOid($R)."
AND contype = 'f'::char
ORDER BY conkey, conname")as$K){$K['deferrable']=($K['deferrable']?'':'NOT ').'DEFERRABLE'.($K['deferred']?' INITIALLY DEFERRED':'');if(preg_match('~FOREIGN KEY\s*\((.+)\)\s*REFERENCES (.+)\((.+)\)(.*)$~iA',$K['definition'],$A)){$K['source']=array_map('Adminer\idf_unescape',array_map('trim',explode(',',$A[1])));if(preg_match('~^(("([^"]|"")+"|[^"]+)\.)?"?("([^"]|"")+"|[^"]+)$~',$A[2],$jf)){$K['ns']=idf_unescape($jf[2]);$K['table']=idf_unescape($jf[4]);}$K['target']=array_map('Adminer\idf_unescape',array_map('trim',explode(',',$A[3])));$K['on_delete']=(preg_match("~ON DELETE (".driver()->onActions.")~",$A[4],$jf)?$jf[1]:'NO ACTION');$K['on_update']=(preg_match("~ON UPDATE (".driver()->onActions.")~",$A[4],$jf)?$jf[1]:'NO ACTION');$J[$K['conname']]=$K;}}return$J;}function
view($B){return
array("select"=>trim(get_val("SELECT pg_get_viewdef(".driver()->tableOid($B).")")));}function
collations(){return
array();}function
information_schema($j){return
get_schema()=="information_schema";}function
error(){$J=h(connection()->error);if(preg_match('~^(.*\n)?([^\n]*)\n( *)\^(\n.*)?$~s',$J,$A))$J=$A[1].preg_replace('~((?:[^&]|&[^;]*;){'.strlen($A[3]).'})(.*)~','\1<b>\2</b>',$A[2]).$A[4];return
nl_br($J);}function
create_database($j,$c){return
queries("CREATE DATABASE ".idf_escape($j).($c?" ENCODING ".idf_escape($c):""));}function
drop_databases($i){connection()->close();return
apply_queries("DROP DATABASE",$i,'Adminer\idf_escape');}function
rename_database($B,$c){connection()->close();return
queries("ALTER DATABASE ".idf_escape(DB)." RENAME TO ".idf_escape($B));}function
auto_increment(){return"";}function
alter_table($R,$B,$n,$qd,$qb,$Ac,$c,$Ba,$E){$b=array();$uh=array();if($R!=""&&$R!=$B)$uh[]="ALTER TABLE ".table($R)." RENAME TO ".table($B);$ii="";foreach($n
as$m){$d=idf_escape($m[0]);$X=$m[1];if(!$X)$b[]="DROP $d";else{$ak=$X[5];unset($X[5]);if($m[0]==""){if(isset($X[6]))$X[1]=($X[1]==" bigint"?" big":($X[1]==" smallint"?" small":" "))."serial";$b[]=($R!=""?"ADD ":"  ").implode($X);if(isset($X[6]))$b[]=($R!=""?"ADD":" ")." PRIMARY KEY ($X[0])";}else{if($d!=$X[0])$uh[]="ALTER TABLE ".table($B)." RENAME $d TO $X[0]";$b[]="ALTER $d TYPE$X[1]";$ji=$R."_".idf_unescape($X[0])."_seq";$b[]="ALTER $d ".($X[3]?"SET".preg_replace('~GENERATED ALWAYS(.*) (STORED|VIRTUAL)~','EXPRESSION\1',$X[3]):(isset($X[6])?"SET DEFAULT nextval(".q($ji).")":"DROP DEFAULT"));if(isset($X[6]))$ii="CREATE SEQUENCE IF NOT EXISTS ".idf_escape($ji)." OWNED BY ".idf_escape($R).".$X[0]";$b[]="ALTER $d ".($X[2]==" NULL"?"DROP NOT":"SET").$X[2];}if($m[0]!=""||$ak!="")$uh[]="COMMENT ON COLUMN ".table($B).".$X[0] IS ".($ak!=""?substr($ak,9):"''");}}$b=array_merge($b,$qd);if($R==""){$P="";if($E){$hb=(connection()->flavor=='cockroach');$P=" PARTITION BY $E[partition_by]($E[partition])";if($E["partition_by"]=='HASH'){$Tg=+$E["partitions"];for($r=0;$r<$Tg;$r++)$uh[]="CREATE TABLE ".idf_escape($B."_$r")." PARTITION OF ".idf_escape($B)." FOR VALUES WITH (MODULUS $Tg, REMAINDER $r)";}else{$kh="MINVALUE";foreach($E["partition_names"]as$r=>$X){$Y=$E["partition_values"][$r];$Pg=" VALUES ".($E["partition_by"]=='LIST'?"IN ($Y)":"FROM ($kh) TO ($Y)");if($hb)$P
.=($r?",":" (")."\n  PARTITION ".(preg_match('~^DEFAULT$~i',$X)?$X:idf_escape($X))."$Pg";else$uh[]="CREATE TABLE ".idf_escape($B."_$X")." PARTITION OF ".idf_escape($B)." FOR$Pg";$kh=$Y;}$P
.=($hb?"\n)":"");}}array_unshift($uh,"CREATE TABLE ".table($B)." (\n".implode(",\n",$b)."\n)$P");}elseif($b)array_unshift($uh,"ALTER TABLE ".table($R)."\n".implode(",\n",$b));if($ii)array_unshift($uh,$ii);if($qb!==null)$uh[]="COMMENT ON TABLE ".table($B)." IS ".q($qb);foreach($uh
as$H){if(!queries($H))return
false;}return
true;}function
alter_indexes($R,$b){$h=array();$nc=array();$uh=array();foreach($b
as$X){if($X[0]!="INDEX")$h[]=($X[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($X[1]):"\nADD".($X[1]!=""?" CONSTRAINT ".idf_escape($X[1]):"")." $X[0] ".($X[0]=="PRIMARY"?"KEY ":"")."(".implode(", ",$X[2]).")");elseif($X[2]=="DROP")$nc[]=idf_escape($X[1]);else$uh[]="CREATE INDEX ".idf_escape($X[1]!=""?$X[1]:uniqid($R."_"))." ON ".table($R).($X[3]?" USING $X[3]":"")." (".implode(", ",$X[2]).")".($X[4]?" WHERE $X[4]":"");}if($h)array_unshift($uh,"ALTER TABLE ".table($R).implode(",",$h));if($nc)array_unshift($uh,"DROP INDEX ".implode(", ",$nc));foreach($uh
as$H){if(!queries($H))return
false;}return
true;}function
truncate_tables($T){return
queries("TRUNCATE ".implode(", ",array_map('Adminer\table',$T)));}function
drop_views($hk){return
drop_tables($hk);}function
drop_tables($T){foreach($T
as$R){$P=table_status1($R);if(!queries("DROP ".strtoupper($P["Engine"])." ".table($R)))return
false;}return
true;}function
move_tables($T,$hk,$aj){foreach(array_merge($T,$hk)as$R){$P=table_status1($R);if(!queries("ALTER ".strtoupper($P["Engine"])." ".table($R)." SET SCHEMA ".idf_escape($aj)))return
false;}return
true;}function
trigger($B,$R){if($B=="")return
array("Statement"=>"EXECUTE PROCEDURE ()");$e=array();$Z="WHERE trigger_schema = current_schema() AND event_object_table = ".q($R)." AND trigger_name = ".q($B);foreach(get_rows("SELECT * FROM information_schema.triggered_update_columns $Z")as$K)$e[]=$K["event_object_column"];$J=array();foreach(get_rows('SELECT trigger_name AS "Trigger", action_timing AS "Timing", event_manipulation AS "Event", \'FOR EACH \' || action_orientation AS "Type", action_statement AS "Statement"
FROM information_schema.triggers'."
$Z
ORDER BY event_manipulation DESC")as$K){if($e&&$K["Event"]=="UPDATE")$K["Event"].=" OF";$K["Of"]=implode(", ",$e);if($J)$K["Event"].=" OR $J[Event]";$J=$K;}return$J;}function
triggers($R){$J=array();foreach(get_rows("SELECT * FROM information_schema.triggers WHERE trigger_schema = current_schema() AND event_object_table = ".q($R))as$K){$zj=trigger($K["trigger_name"],$R);$J[$zj["Trigger"]]=array($zj["Timing"],$zj["Event"]);}return$J;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>array("INSERT","UPDATE","UPDATE OF","DELETE","INSERT OR UPDATE","INSERT OR UPDATE OF","DELETE OR INSERT","DELETE OR UPDATE","DELETE OR UPDATE OF","DELETE OR INSERT OR UPDATE","DELETE OR INSERT OR UPDATE OF"),"Type"=>array("FOR EACH ROW","FOR EACH STATEMENT"),);}function
routine($B,$U){$L=get_rows('SELECT routine_definition AS definition, LOWER(external_language) AS language, *
FROM information_schema.routines
WHERE routine_schema = current_schema() AND specific_name = '.q($B));$J=idx($L,0,array());$J["returns"]=array("type"=>$J["type_udt_name"]);$J["fields"]=get_rows('SELECT COALESCE(parameter_name, ordinal_position::text) AS field, data_type AS type, character_maximum_length AS length, parameter_mode AS inout
FROM information_schema.parameters
WHERE specific_schema = current_schema() AND specific_name = '.q($B).'
ORDER BY ordinal_position');return$J;}function
routines(){return
get_rows('SELECT specific_name AS "SPECIFIC_NAME", routine_type AS "ROUTINE_TYPE", routine_name AS "ROUTINE_NAME", type_udt_name AS "DTD_IDENTIFIER"
FROM information_schema.routines
WHERE routine_schema = current_schema()
ORDER BY SPECIFIC_NAME');}function
routine_languages(){return
get_vals("SELECT LOWER(lanname) FROM pg_catalog.pg_language");}function
routine_id($B,$K){$J=array();foreach($K["fields"]as$m){$x=$m["length"];$J[]=$m["type"].($x?"($x)":"");}return
idf_escape($B)."(".implode(", ",$J).")";}function
last_id($I){$K=(is_object($I)?$I->fetch_row():array());return($K?$K[0]:0);}function
explain($f,$H){return$f->query("EXPLAIN $H");}function
found_rows($S,$Z){if(preg_match("~ rows=([0-9]+)~",get_val("EXPLAIN SELECT * FROM ".idf_escape($S["Name"]).($Z?" WHERE ".implode(" AND ",$Z):"")),$Fh))return$Fh[1];}function
types(){return
get_key_vals("SELECT oid, typname
FROM pg_type
WHERE typnamespace = ".driver()->nsOid."
AND typtype IN ('b','d','e')
AND typelem = 0");}function
type_values($s){$Fc=get_vals("SELECT enumlabel FROM pg_enum WHERE enumtypid = $s ORDER BY enumsortorder");return($Fc?"'".implode("', '",array_map('addslashes',$Fc))."'":"");}function
schemas(){return
get_vals("SELECT nspname FROM pg_namespace ORDER BY nspname");}function
get_schema(){return
get_val("SELECT current_schema()");}function
set_schema($Vh,$g=null){$J=connection($g)->query("SET search_path TO ".idf_escape($Vh));driver()->setUserTypes(types());return$J;}function
foreign_keys_sql($R){$J="";$P=table_status1($R);$Xf=idf_escape($P['nspname']);$md=foreign_keys($R);ksort($md);foreach($md
as$ld=>$kd)$J
.="ALTER TABLE ONLY $Xf.".idf_escape($P['Name'])." ADD CONSTRAINT ".idf_escape($ld)." ".preg_replace('~( REFERENCES )([^(.]+\()~',"\\1$Xf.\\2",$kd["definition"]).";\n";return($J?"$J\n":$J);}function
create_sql($R,$Ba,$Hi){$Lh=array();$ki=array();$P=table_status1($R);$Xf=idf_escape($P['nspname']);if(is_view($P)){$gk=view($R);return
rtrim("CREATE VIEW $Xf.".idf_escape($R)." AS $gk[select]",";");}$n=fields($R);if(count($P)<2||empty($n))return
false;$J="CREATE TABLE $Xf.".idf_escape($P['Name'])." (\n    ";foreach($n
as$m){if($m['default']=="nextval('$P[Name]_$m[field]_seq')"){$m['default']=null;$m['full_type']=preg_replace('~int(eger)?~','serial',$m['full_type']);}$Ng=idf_escape($m['field']).' '.$m['full_type'].preg_replace('~(nextval\(\')([^.\']+\')~','\1'.str_replace("'","''",$P['nspname']).'.\2',default_value($m)).($m['null']?"":" NOT NULL");$Lh[]=$Ng;if(preg_match('~nextval\(\'([^\']+)\'\)~',$m['default'],$lf)){$ji=$lf[1];$xi=first(get_rows((min_version(10)?"SELECT *, cache_size AS cache_value FROM pg_sequences WHERE schemaname = current_schema() AND sequencename = ".q(idf_unescape($ji)):"SELECT * FROM $ji"),null,"-- "));$ki[]=($Hi=="DROP+CREATE"?"DROP SEQUENCE IF EXISTS $Xf.$ji;\n":"")."CREATE SEQUENCE $Xf.$ji INCREMENT $xi[increment_by] MINVALUE $xi[min_value] MAXVALUE $xi[max_value]".($Ba&&$xi['last_value']?" START ".($xi["last_value"]+1):"")." CACHE $xi[cache_value];";}}if(!empty($ki))$J=implode("\n\n",$ki)."\n\n$J";$lh="";foreach(indexes($R)as$je=>$u){if($u['type']=='PRIMARY'){$lh=$je;$Lh[]="CONSTRAINT ".idf_escape($je)." PRIMARY KEY (".implode(', ',array_map('Adminer\idf_escape',$u['columns'])).")";}}foreach(driver()->checkConstraints($R)as$wb=>$yb)$Lh[]="CONSTRAINT ".idf_escape($wb)." CHECK ($yb)";$J
.=implode(",\n    ",$Lh)."\n)";$Pg=driver()->partitionsInfo($P['Name']);if($Pg)$J
.="\nPARTITION BY $Pg[partition_by]($Pg[partition])";$J
.="\nWITH (oids = ".($P['Oid']?'true':'false').");";if($P['Comment'])$J
.="\n\nCOMMENT ON TABLE $Xf.".idf_escape($P['Name'])." IS ".q($P['Comment']).";";foreach($n
as$ad=>$m){if($m['comment'])$J
.="\n\nCOMMENT ON COLUMN $Xf.".idf_escape($P['Name']).".".idf_escape($ad)." IS ".q($m['comment']).";";}foreach(get_rows("SELECT indexdef FROM pg_catalog.pg_indexes WHERE schemaname = current_schema() AND tablename = ".q($R).($lh?" AND indexname != ".q($lh):""),null,"-- ")as$K)$J
.="\n\n$K[indexdef];";return
rtrim($J,';');}function
truncate_sql($R){return"TRUNCATE ".table($R);}function
trigger_sql($R){$P=table_status1($R);$J="";foreach(triggers($R)as$yj=>$xj){$zj=trigger($yj,$P['Name']);$J
.="\nCREATE TRIGGER ".idf_escape($zj['Trigger'])." $zj[Timing] $zj[Event] ON ".idf_escape($P["nspname"]).".".idf_escape($P['Name'])." $zj[Type] $zj[Statement];;\n";}return$J;}function
use_sql($Pb,$Hi=""){$B=idf_escape($Pb);$J="";if(preg_match('~CREATE~',$Hi)){if($Hi=="DROP+CREATE")$J="DROP DATABASE IF EXISTS $B;\n";$J
.="CREATE DATABASE $B;\n";}return"$J\\connect $B";}function
show_variables(){return
get_rows("SHOW ALL");}function
process_list(){return
get_rows("SELECT * FROM pg_stat_activity ORDER BY ".(min_version(9.2)?"pid":"procpid"));}function
convert_field($m){}function
unconvert_field($m,$J){return$J;}function
support($Yc){return
preg_match('~^(check|columns|comment|database|drop_col|dump|descidx|indexes|kill|partial_indexes|routine|scheme|sequence|sql|table|trigger|type|variables|view'.(min_version(9.3)?'|materializedview':'').(min_version(11)?'|procedure':'').(connection()->flavor=='cockroach'?'':'|processlist').')$~',$Yc);}function
kill_process($X){return
queries("SELECT pg_terminate_backend(".number($X).")");}function
connection_id(){return"SELECT pg_backend_pid()";}function
max_connections(){return
get_val("SHOW max_connections");}}add_driver("oracle","Oracle (beta)");if(isset($_GET["oracle"])){define('Adminer\DRIVER',"oracle");if(extension_loaded("oci8")&&$_GET["ext"]!="pdo"){class
Db
extends
SqlDb{var$extension="oci8";var$_current_db;private$link;function
_error($Gc,$l){if(ini_bool("html_errors"))$l=html_entity_decode(strip_tags($l));$l=preg_replace('~^[^:]*: ~','',$l);$this->error=$l;}function
attach($N,$V,$F){$this->link=@oci_new_connect($V,$F,$N,"AL32UTF8");if($this->link){$this->server_info=oci_server_version($this->link);return'';}$l=oci_error();return$l["message"];}function
quote($Q){return"'".str_replace("'","''",$Q)."'";}function
select_db($Pb){$this->_current_db=$Pb;return
true;}function
query($H,$Fj=false){$I=oci_parse($this->link,$H);$this->error="";if(!$I){$l=oci_error($this->link);$this->errno=$l["code"];$this->error=$l["message"];return
false;}set_error_handler(array($this,'_error'));$J=@oci_execute($I);restore_error_handler();if($J){if(oci_num_fields($I))return
new
Result($I);$this->affected_rows=oci_num_rows($I);oci_free_statement($I);}return$J;}function
timeout($Jf){return
oci_set_call_timeout($this->link,$Jf);}}class
Result{var$num_rows;private$result,$offset=1;function
__construct($I){$this->result=$I;}private
function
convert($K){foreach((array)$K
as$w=>$X){if(is_a($X,'OCILob')||is_a($X,'OCI-Lob'))$K[$w]=$X->load();}return$K;}function
fetch_assoc(){return$this->convert(oci_fetch_assoc($this->result));}function
fetch_row(){return$this->convert(oci_fetch_row($this->result));}function
fetch_field(){$d=$this->offset++;$J=new
\stdClass;$J->name=oci_field_name($this->result,$d);$J->type=oci_field_type($this->result,$d);$J->charsetnr=(preg_match("~raw|blob|bfile~",$J->type)?63:0);return$J;}}}elseif(extension_loaded("pdo_oci")){class
Db
extends
PdoDb{var$extension="PDO_OCI";var$_current_db;function
attach($N,$V,$F){return$this->dsn("oci:dbname=//$N;charset=AL32UTF8",$V,$F);}function
select_db($Pb){$this->_current_db=$Pb;return
true;}}}class
Driver
extends
SqlDriver{static$extensions=array("OCI8","PDO_OCI");static$jush="oracle";var$insertFunctions=array("date"=>"current_date","timestamp"=>"current_timestamp",);var$editFunctions=array("number|float|double"=>"+/-","date|timestamp"=>"+ interval/- interval","char|clob"=>"||",);var$operators=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL","SQL");var$functions=array("length","lower","round","upper");var$grouping=array("avg","count","count distinct","max","min","sum");function
__construct(Db$f){parent::__construct($f);$this->types=array('Numbers'=>array("number"=>38,"binary_float"=>12,"binary_double"=>21),'Date and time'=>array("date"=>10,"timestamp"=>29,"interval year"=>12,"interval day"=>28),'Strings'=>array("char"=>2000,"varchar2"=>4000,"nchar"=>2000,"nvarchar2"=>4000,"clob"=>4294967295,"nclob"=>4294967295),'Binary'=>array("raw"=>2000,"long raw"=>2147483648,"blob"=>4294967295,"bfile"=>4294967296),);}function
begin(){return
true;}function
insertUpdate($R,array$L,array$lh){foreach($L
as$O){$Nj=array();$Z=array();foreach($O
as$w=>$X){$Nj[]="$w = $X";if(isset($lh[idf_unescape($w)]))$Z[]="$w = $X";}if(!(($Z&&queries("UPDATE ".table($R)." SET ".implode(", ",$Nj)." WHERE ".implode(" AND ",$Z))&&$this->conn->affected_rows)||queries("INSERT INTO ".table($R)." (".implode(", ",array_keys($O)).") VALUES (".implode(", ",$O).")")))return
false;}return
true;}function
hasCStyleEscapes(){return
true;}}function
idf_escape($t){return'"'.str_replace('"','""',$t).'"';}function
table($t){return
idf_escape($t);}function
get_databases($od){return
get_vals("SELECT DISTINCT tablespace_name FROM (
SELECT tablespace_name FROM user_tablespaces
UNION SELECT tablespace_name FROM all_tables WHERE tablespace_name IS NOT NULL
)
ORDER BY 1");}function
limit($H,$Z,$y,$C=0,$hi=" "){return($C?" * FROM (SELECT t.*, rownum AS rnum FROM (SELECT $H$Z) t WHERE rownum <= ".($y+$C).") WHERE rnum > $C":($y?" * FROM (SELECT $H$Z) WHERE rownum <= ".($y+$C):" $H$Z"));}function
limit1($R,$H,$Z,$hi="\n"){return" $H$Z";}function
db_collation($j,$lb){return
get_val("SELECT value FROM nls_database_parameters WHERE parameter = 'NLS_CHARACTERSET'");}function
logged_user(){return
get_val("SELECT USER FROM DUAL");}function
get_current_db(){$j=connection()->_current_db?:DB;unset(connection()->_current_db);return$j;}function
where_owner($ih,$Hg="owner"){if(!$_GET["ns"])return'';return"$ih$Hg = sys_context('USERENV', 'CURRENT_SCHEMA')";}function
views_table($e){$Hg=where_owner('');return"(SELECT $e FROM all_views WHERE ".($Hg?:"rownum < 0").")";}function
tables_list(){$gk=views_table("view_name");$Hg=where_owner(" AND ");return
get_key_vals("SELECT table_name, 'table' FROM all_tables WHERE tablespace_name = ".q(DB)."$Hg
UNION SELECT view_name, 'view' FROM $gk
ORDER BY 1");}function
count_tables($i){$J=array();foreach($i
as$j)$J[$j]=get_val("SELECT COUNT(*) FROM all_tables WHERE tablespace_name = ".q($j));return$J;}function
table_status($B=""){$J=array();$ai=q($B);$j=get_current_db();$gk=views_table("view_name");$Hg=where_owner(" AND ");foreach(get_rows('SELECT table_name "Name", \'table\' "Engine", avg_row_len * num_rows "Data_length", num_rows "Rows" FROM all_tables WHERE tablespace_name = '.q($j).$Hg.($B!=""?" AND table_name = $ai":"")."
UNION SELECT view_name, 'view', 0, 0 FROM $gk".($B!=""?" WHERE view_name = $ai":"")."
ORDER BY 1")as$K)$J[$K["Name"]]=$K;return$J;}function
is_view($S){return$S["Engine"]=="view";}function
fk_support($S){return
true;}function
fields($R){$J=array();$Hg=where_owner(" AND ");foreach(get_rows("SELECT * FROM all_tab_columns WHERE table_name = ".q($R)."$Hg ORDER BY column_id")as$K){$U=$K["DATA_TYPE"];$x="$K[DATA_PRECISION],$K[DATA_SCALE]";if($x==",")$x=$K["CHAR_COL_DECL_LENGTH"];$J[$K["COLUMN_NAME"]]=array("field"=>$K["COLUMN_NAME"],"full_type"=>$U.($x?"($x)":""),"type"=>strtolower($U),"length"=>$x,"default"=>$K["DATA_DEFAULT"],"null"=>($K["NULLABLE"]=="Y"),"privileges"=>array("insert"=>1,"select"=>1,"update"=>1,"where"=>1,"order"=>1),);}return$J;}function
indexes($R,$g=null){$J=array();$Hg=where_owner(" AND ","aic.table_owner");foreach(get_rows("SELECT aic.*, ac.constraint_type, atc.data_default
FROM all_ind_columns aic
LEFT JOIN all_constraints ac ON aic.index_name = ac.constraint_name AND aic.table_name = ac.table_name AND aic.index_owner = ac.owner
LEFT JOIN all_tab_cols atc ON aic.column_name = atc.column_name AND aic.table_name = atc.table_name AND aic.index_owner = atc.owner
WHERE aic.table_name = ".q($R)."$Hg
ORDER BY ac.constraint_type, aic.column_position",$g)as$K){$je=$K["INDEX_NAME"];$nb=$K["DATA_DEFAULT"];$nb=($nb?trim($nb,'"'):$K["COLUMN_NAME"]);$J[$je]["type"]=($K["CONSTRAINT_TYPE"]=="P"?"PRIMARY":($K["CONSTRAINT_TYPE"]=="U"?"UNIQUE":"INDEX"));$J[$je]["columns"][]=$nb;$J[$je]["lengths"][]=($K["CHAR_LENGTH"]&&$K["CHAR_LENGTH"]!=$K["COLUMN_LENGTH"]?$K["CHAR_LENGTH"]:null);$J[$je]["descs"][]=($K["DESCEND"]&&$K["DESCEND"]=="DESC"?'1':null);}return$J;}function
view($B){$gk=views_table("view_name, text");$L=get_rows('SELECT text "select" FROM '.$gk.' WHERE view_name = '.q($B));return
reset($L);}function
collations(){return
array();}function
information_schema($j){return
get_schema()=="INFORMATION_SCHEMA";}function
error(){return
h(connection()->error);}function
explain($f,$H){$f->query("EXPLAIN PLAN FOR $H");return$f->query("SELECT * FROM plan_table");}function
found_rows($S,$Z){}function
auto_increment(){return"";}function
alter_table($R,$B,$n,$qd,$qb,$Ac,$c,$Ba,$E){$b=$nc=array();$Ag=($R?fields($R):array());foreach($n
as$m){$X=$m[1];if($X&&$m[0]!=""&&idf_escape($m[0])!=$X[0])queries("ALTER TABLE ".table($R)." RENAME COLUMN ".idf_escape($m[0])." TO $X[0]");$_g=$Ag[$m[0]];if($X&&$_g){$eg=process_field($_g,$_g);if($X[2]==$eg[2])$X[2]="";}if($X)$b[]=($R!=""?($m[0]!=""?"MODIFY (":"ADD ("):"  ").implode($X).($R!=""?")":"");else$nc[]=idf_escape($m[0]);}if($R=="")return
queries("CREATE TABLE ".table($B)." (\n".implode(",\n",$b)."\n)");return(!$b||queries("ALTER TABLE ".table($R)."\n".implode("\n",$b)))&&(!$nc||queries("ALTER TABLE ".table($R)." DROP (".implode(", ",$nc).")"))&&($R==$B||queries("ALTER TABLE ".table($R)." RENAME TO ".table($B)));}function
alter_indexes($R,$b){$nc=array();$uh=array();foreach($b
as$X){if($X[0]!="INDEX"){$X[2]=preg_replace('~ DESC$~','',$X[2]);$h=($X[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($X[1]):"\nADD".($X[1]!=""?" CONSTRAINT ".idf_escape($X[1]):"")." $X[0] ".($X[0]=="PRIMARY"?"KEY ":"")."(".implode(", ",$X[2]).")");array_unshift($uh,"ALTER TABLE ".table($R).$h);}elseif($X[2]=="DROP")$nc[]=idf_escape($X[1]);else$uh[]="CREATE INDEX ".idf_escape($X[1]!=""?$X[1]:uniqid($R."_"))." ON ".table($R)." (".implode(", ",$X[2]).")";}if($nc)array_unshift($uh,"DROP INDEX ".implode(", ",$nc));foreach($uh
as$H){if(!queries($H))return
false;}return
true;}function
foreign_keys($R){$J=array();$H="SELECT c_list.CONSTRAINT_NAME as NAME,
c_src.COLUMN_NAME as SRC_COLUMN,
c_dest.OWNER as DEST_DB,
c_dest.TABLE_NAME as DEST_TABLE,
c_dest.COLUMN_NAME as DEST_COLUMN,
c_list.DELETE_RULE as ON_DELETE
FROM ALL_CONSTRAINTS c_list, ALL_CONS_COLUMNS c_src, ALL_CONS_COLUMNS c_dest
WHERE c_list.CONSTRAINT_NAME = c_src.CONSTRAINT_NAME
AND c_list.R_CONSTRAINT_NAME = c_dest.CONSTRAINT_NAME
AND c_list.CONSTRAINT_TYPE = 'R'
AND c_src.TABLE_NAME = ".q($R);foreach(get_rows($H)as$K)$J[$K['NAME']]=array("db"=>$K['DEST_DB'],"table"=>$K['DEST_TABLE'],"source"=>array($K['SRC_COLUMN']),"target"=>array($K['DEST_COLUMN']),"on_delete"=>$K['ON_DELETE'],"on_update"=>null,);return$J;}function
truncate_tables($T){return
apply_queries("TRUNCATE TABLE",$T);}function
drop_views($hk){return
apply_queries("DROP VIEW",$hk);}function
drop_tables($T){return
apply_queries("DROP TABLE",$T);}function
last_id($I){return
0;}function
schemas(){$J=get_vals("SELECT DISTINCT owner FROM dba_segments WHERE owner IN (SELECT username FROM dba_users WHERE default_tablespace NOT IN ('SYSTEM','SYSAUX')) ORDER BY 1");return($J?:get_vals("SELECT DISTINCT owner FROM all_tables WHERE tablespace_name = ".q(DB)." ORDER BY 1"));}function
get_schema(){return
get_val("SELECT sys_context('USERENV', 'SESSION_USER') FROM dual");}function
set_schema($Xh,$g=null){return
connection($g)->query("ALTER SESSION SET CURRENT_SCHEMA = ".idf_escape($Xh));}function
show_variables(){return
get_rows('SELECT name, display_value FROM v$parameter');}function
show_status(){$J=array();$L=get_rows('SELECT * FROM v$instance');foreach(reset($L)as$w=>$X)$J[]=array($w,$X);return$J;}function
process_list(){return
get_rows('SELECT
	sess.process AS "process",
	sess.username AS "user",
	sess.schemaname AS "schema",
	sess.status AS "status",
	sess.wait_class AS "wait_class",
	sess.seconds_in_wait AS "seconds_in_wait",
	sql.sql_text AS "sql_text",
	sess.machine AS "machine",
	sess.port AS "port"
FROM v$session sess LEFT OUTER JOIN v$sql sql
ON sql.sql_id = sess.sql_id
WHERE sess.type = \'USER\'
ORDER BY PROCESS
');}function
convert_field($m){}function
unconvert_field($m,$J){return$J;}function
support($Yc){return
preg_match('~^(columns|database|drop_col|indexes|descidx|processlist|scheme|sql|status|table|variables|view)$~',$Yc);}}add_driver("mssql","MS SQL");if(isset($_GET["mssql"])){define('Adminer\DRIVER',"mssql");if(extension_loaded("sqlsrv")&&$_GET["ext"]!="pdo"){class
Db
extends
SqlDb{var$extension="sqlsrv";private$link,$result;private
function
get_error(){$this->error="";foreach(sqlsrv_errors()as$l){$this->errno=$l["code"];$this->error
.="$l[message]\n";}$this->error=rtrim($this->error);}function
attach($N,$V,$F){$xb=array("UID"=>$V,"PWD"=>$F,"CharacterSet"=>"UTF-8");$Ci=adminer()->connectSsl();if(isset($Ci["Encrypt"]))$xb["Encrypt"]=$Ci["Encrypt"];if(isset($Ci["TrustServerCertificate"]))$xb["TrustServerCertificate"]=$Ci["TrustServerCertificate"];$j=adminer()->database();if($j!="")$xb["Database"]=$j;list($Td,$dh)=host_port($N);$this->link=@sqlsrv_connect($Td.($dh?",$dh":""),$xb);if($this->link){$oe=sqlsrv_server_info($this->link);$this->server_info=$oe['SQLServerVersion'];}else$this->get_error();return($this->link?'':$this->error);}function
quote($Q){$Gj=strlen($Q)!=strlen(utf8_decode($Q));return($Gj?"N":"")."'".str_replace("'","''",$Q)."'";}function
select_db($Pb){return$this->query(use_sql($Pb));}function
query($H,$Fj=false){$I=sqlsrv_query($this->link,$H);$this->error="";if(!$I){$this->get_error();return
false;}return$this->store_result($I);}function
multi_query($H){$this->result=sqlsrv_query($this->link,$H);$this->error="";if(!$this->result){$this->get_error();return
false;}return
true;}function
store_result($I=null){if(!$I)$I=$this->result;if(!$I)return
false;if(sqlsrv_field_metadata($I))return
new
Result($I);$this->affected_rows=sqlsrv_rows_affected($I);return
true;}function
next_result(){return$this->result?!!sqlsrv_next_result($this->result):false;}}class
Result{var$num_rows;private$result,$offset=0,$fields;function
__construct($I){$this->result=$I;}private
function
convert($K){foreach((array)$K
as$w=>$X){if(is_a($X,'DateTime'))$K[$w]=$X->format("Y-m-d H:i:s");}return$K;}function
fetch_assoc(){return$this->convert(sqlsrv_fetch_array($this->result,SQLSRV_FETCH_ASSOC));}function
fetch_row(){return$this->convert(sqlsrv_fetch_array($this->result,SQLSRV_FETCH_NUMERIC));}function
fetch_field(){if(!$this->fields)$this->fields=sqlsrv_field_metadata($this->result);$m=$this->fields[$this->offset++];$J=new
\stdClass;$J->name=$m["Name"];$J->type=($m["Type"]==1?254:15);$J->charsetnr=0;return$J;}function
seek($C){for($r=0;$r<$C;$r++)sqlsrv_fetch($this->result);}}function
last_id($I){return
get_val("SELECT SCOPE_IDENTITY()");}function
explain($f,$H){$f->query("SET SHOWPLAN_ALL ON");$J=$f->query($H);$f->query("SET SHOWPLAN_ALL OFF");return$J;}}else{abstract
class
MssqlDb
extends
PdoDb{function
select_db($Pb){return$this->query(use_sql($Pb));}function
lastInsertId(){return$this->pdo->lastInsertId();}}function
last_id($I){return
connection()->lastInsertId();}function
explain($f,$H){}if(extension_loaded("pdo_sqlsrv")){class
Db
extends
MssqlDb{var$extension="PDO_SQLSRV";function
attach($N,$V,$F){list($Td,$dh)=host_port($N);return$this->dsn("sqlsrv:Server=$Td".($dh?",$dh":""),$V,$F);}}}elseif(extension_loaded("pdo_dblib")){class
Db
extends
MssqlDb{var$extension="PDO_DBLIB";function
attach($N,$V,$F){list($Td,$dh)=host_port($N);return$this->dsn("dblib:charset=utf8;host=$Td".($dh?(is_numeric($dh)?";port=":";unix_socket=").$dh:""),$V,$F);}}}}class
Driver
extends
SqlDriver{static$extensions=array("SQLSRV","PDO_SQLSRV","PDO_DBLIB");static$jush="mssql";var$insertFunctions=array("date|time"=>"getdate");var$editFunctions=array("int|decimal|real|float|money|datetime"=>"+/-","char|text"=>"+",);var$operators=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");var$functions=array("len","lower","round","upper");var$grouping=array("avg","count","count distinct","max","min","sum");var$generated=array("PERSISTED","VIRTUAL");var$onActions="NO ACTION|CASCADE|SET NULL|SET DEFAULT";static
function
connect($N,$V,$F){if($N=="")$N="localhost:1433";return
parent::connect($N,$V,$F);}function
__construct(Db$f){parent::__construct($f);$this->types=array('Numbers'=>array("tinyint"=>3,"smallint"=>5,"int"=>10,"bigint"=>20,"bit"=>1,"decimal"=>0,"real"=>12,"float"=>53,"smallmoney"=>10,"money"=>20),'Date and time'=>array("date"=>10,"smalldatetime"=>19,"datetime"=>19,"datetime2"=>19,"time"=>8,"datetimeoffset"=>10),'Strings'=>array("char"=>8000,"varchar"=>8000,"text"=>2147483647,"nchar"=>4000,"nvarchar"=>4000,"ntext"=>1073741823),'Binary'=>array("binary"=>8000,"varbinary"=>8000,"image"=>2147483647),);}function
insertUpdate($R,array$L,array$lh){$n=fields($R);$Nj=array();$Z=array();$O=reset($L);$e="c".implode(", c",range(1,count($O)));$Sa=0;$ue=array();foreach($O
as$w=>$X){$Sa++;$B=idf_unescape($w);if(!$n[$B]["auto_increment"])$ue[$w]="c$Sa";if(isset($lh[$B]))$Z[]="$w = c$Sa";else$Nj[]="$w = c$Sa";}$ck=array();foreach($L
as$O)$ck[]="(".implode(", ",$O).")";if($Z){$Yd=queries("SET IDENTITY_INSERT ".table($R)." ON");$J=queries("MERGE ".table($R)." USING (VALUES\n\t".implode(",\n\t",$ck)."\n) AS source ($e) ON ".implode(" AND ",$Z).($Nj?"\nWHEN MATCHED THEN UPDATE SET ".implode(", ",$Nj):"")."\nWHEN NOT MATCHED THEN INSERT (".implode(", ",array_keys($Yd?$O:$ue)).") VALUES (".($Yd?$e:implode(", ",$ue)).");");if($Yd)queries("SET IDENTITY_INSERT ".table($R)." OFF");}else$J=queries("INSERT INTO ".table($R)." (".implode(", ",array_keys($O)).") VALUES\n".implode(",\n",$ck));return$J;}function
begin(){return
queries("BEGIN TRANSACTION");}function
tableHelp($B,$Ee=false){$bf=array("sys"=>"catalog-views/sys-","INFORMATION_SCHEMA"=>"information-schema-views/",);$z=$bf[get_schema()];if($z)return"relational-databases/system-$z".preg_replace('~_~','-',strtolower($B))."-transact-sql";}}function
idf_escape($t){return"[".str_replace("]","]]",$t)."]";}function
table($t){return($_GET["ns"]!=""?idf_escape($_GET["ns"]).".":"").idf_escape($t);}function
get_databases($od){return
get_vals("SELECT name FROM sys.databases WHERE name NOT IN ('master', 'tempdb', 'model', 'msdb')");}function
limit($H,$Z,$y,$C=0,$hi=" "){return($y?" TOP (".($y+$C).")":"")." $H$Z";}function
limit1($R,$H,$Z,$hi="\n"){return
limit($H,$Z,1,0,$hi);}function
db_collation($j,$lb){return
get_val("SELECT collation_name FROM sys.databases WHERE name = ".q($j));}function
logged_user(){return
get_val("SELECT SUSER_NAME()");}function
tables_list(){return
get_key_vals("SELECT name, type_desc FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ORDER BY name");}function
count_tables($i){$J=array();foreach($i
as$j){connection()->select_db($j);$J[$j]=get_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES");}return$J;}function
table_status($B=""){$J=array();foreach(get_rows("SELECT ao.name AS Name, ao.type_desc AS Engine, (SELECT cast(value as varchar(max)) FROM fn_listextendedproperty(default, 'SCHEMA', schema_name(schema_id), 'TABLE', ao.name, null, null)) AS Comment
FROM sys.all_objects AS ao
WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ".($B!=""?"AND name = ".q($B):"ORDER BY name"))as$K)$J[$K["Name"]]=$K;return$J;}function
is_view($S){return$S["Engine"]=="VIEW";}function
fk_support($S){return
true;}function
fields($R){$sb=get_key_vals("SELECT objname, cast(value as varchar(max)) FROM fn_listextendedproperty('MS_DESCRIPTION', 'schema', ".q(get_schema()).", 'table', ".q($R).", 'column', NULL)");$J=array();$Qi=get_val("SELECT object_id FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') AND name = ".q($R));foreach(get_rows("SELECT c.max_length, c.precision, c.scale, c.name, c.is_nullable, c.is_identity, c.collation_name, t.name type, d.definition [default], d.name default_constraint, i.is_primary_key
FROM sys.all_columns c
JOIN sys.types t ON c.user_type_id = t.user_type_id
LEFT JOIN sys.default_constraints d ON c.default_object_id = d.object_id
LEFT JOIN sys.index_columns ic ON c.object_id = ic.object_id AND c.column_id = ic.column_id
LEFT JOIN sys.indexes i ON ic.object_id = i.object_id AND ic.index_id = i.index_id
WHERE c.object_id = ".q($Qi))as$K){$U=$K["type"];$x=(preg_match("~char|binary~",$U)?intval($K["max_length"])/($U[0]=='n'?2:1):($U=="decimal"?"$K[precision],$K[scale]":""));$J[$K["name"]]=array("field"=>$K["name"],"full_type"=>$U.($x?"($x)":""),"type"=>$U,"length"=>$x,"default"=>(preg_match("~^\('(.*)'\)$~",$K["default"],$A)?str_replace("''","'",$A[1]):$K["default"]),"default_constraint"=>$K["default_constraint"],"null"=>$K["is_nullable"],"auto_increment"=>$K["is_identity"],"collation"=>$K["collation_name"],"privileges"=>array("insert"=>1,"select"=>1,"update"=>1,"where"=>1,"order"=>1),"primary"=>$K["is_primary_key"],"comment"=>$sb[$K["name"]],);}foreach(get_rows("SELECT * FROM sys.computed_columns WHERE object_id = ".q($Qi))as$K){$J[$K["name"]]["generated"]=($K["is_persisted"]?"PERSISTED":"VIRTUAL");$J[$K["name"]]["default"]=$K["definition"];}return$J;}function
indexes($R,$g=null){$J=array();foreach(get_rows("SELECT i.name, key_ordinal, is_unique, is_primary_key, c.name AS column_name, is_descending_key
FROM sys.indexes i
INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
WHERE OBJECT_NAME(i.object_id) = ".q($R),$g)as$K){$B=$K["name"];$J[$B]["type"]=($K["is_primary_key"]?"PRIMARY":($K["is_unique"]?"UNIQUE":"INDEX"));$J[$B]["lengths"]=array();$J[$B]["columns"][$K["key_ordinal"]]=$K["column_name"];$J[$B]["descs"][$K["key_ordinal"]]=($K["is_descending_key"]?'1':null);}return$J;}function
view($B){return
array("select"=>preg_replace('~^(?:[^[]|\[[^]]*])*\s+AS\s+~isU','',get_val("SELECT VIEW_DEFINITION FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = SCHEMA_NAME() AND TABLE_NAME = ".q($B))));}function
collations(){$J=array();foreach(get_vals("SELECT name FROM fn_helpcollations()")as$c)$J[preg_replace('~_.*~','',$c)][]=$c;return$J;}function
information_schema($j){return
get_schema()=="INFORMATION_SCHEMA";}function
error(){return
nl_br(h(preg_replace('~^(\[[^]]*])+~m','',connection()->error)));}function
create_database($j,$c){return
queries("CREATE DATABASE ".idf_escape($j).(preg_match('~^[a-z0-9_]+$~i',$c)?" COLLATE $c":""));}function
drop_databases($i){return
queries("DROP DATABASE ".implode(", ",array_map('Adminer\idf_escape',$i)));}function
rename_database($B,$c){if(preg_match('~^[a-z0-9_]+$~i',$c))queries("ALTER DATABASE ".idf_escape(DB)." COLLATE $c");queries("ALTER DATABASE ".idf_escape(DB)." MODIFY NAME = ".idf_escape($B));return
true;}function
auto_increment(){return" IDENTITY".($_POST["Auto_increment"]!=""?"(".number($_POST["Auto_increment"]).",1)":"")." PRIMARY KEY";}function
alter_table($R,$B,$n,$qd,$qb,$Ac,$c,$Ba,$E){$b=array();$sb=array();$Ag=fields($R);foreach($n
as$m){$d=idf_escape($m[0]);$X=$m[1];if(!$X)$b["DROP"][]=" COLUMN $d";else{$X[1]=preg_replace("~( COLLATE )'(\\w+)'~",'\1\2',$X[1]);$sb[$m[0]]=$X[5];unset($X[5]);if(preg_match('~ AS ~',$X[3]))unset($X[1],$X[2]);if($m[0]=="")$b["ADD"][]="\n  ".implode("",$X).($R==""?substr($qd[$X[0]],16+strlen($X[0])):"");else{$k=$X[3];unset($X[3]);unset($X[6]);if($d!=$X[0])queries("EXEC sp_rename ".q(table($R).".$d").", ".q(idf_unescape($X[0])).", 'COLUMN'");$b["ALTER COLUMN ".implode("",$X)][]="";$_g=$Ag[$m[0]];if(default_value($_g)!=$k){if($_g["default"]!==null)$b["DROP"][]=" ".idf_escape($_g["default_constraint"]);if($k)$b["ADD"][]="\n $k FOR $d";}}}}if($R=="")return
queries("CREATE TABLE ".table($B)." (".implode(",",(array)$b["ADD"])."\n)");if($R!=$B)queries("EXEC sp_rename ".q(table($R)).", ".q($B));if($qd)$b[""]=$qd;foreach($b
as$w=>$X){if(!queries("ALTER TABLE ".table($B)." $w".implode(",",$X)))return
false;}foreach($sb
as$w=>$X){$qb=substr($X,9);queries("EXEC sp_dropextendedproperty @name = N'MS_Description', @level0type = N'Schema', @level0name = ".q(get_schema()).", @level1type = N'Table', @level1name = ".q($B).", @level2type = N'Column', @level2name = ".q($w));queries("EXEC sp_addextendedproperty
@name = N'MS_Description',
@value = $qb,
@level0type = N'Schema',
@level0name = ".q(get_schema()).",
@level1type = N'Table',
@level1name = ".q($B).",
@level2type = N'Column',
@level2name = ".q($w));}return
true;}function
alter_indexes($R,$b){$u=array();$nc=array();foreach($b
as$X){if($X[2]=="DROP"){if($X[0]=="PRIMARY")$nc[]=idf_escape($X[1]);else$u[]=idf_escape($X[1])." ON ".table($R);}elseif(!queries(($X[0]!="PRIMARY"?"CREATE $X[0] ".($X[0]!="INDEX"?"INDEX ":"").idf_escape($X[1]!=""?$X[1]:uniqid($R."_"))." ON ".table($R):"ALTER TABLE ".table($R)." ADD PRIMARY KEY")." (".implode(", ",$X[2]).")"))return
false;}return(!$u||queries("DROP INDEX ".implode(", ",$u)))&&(!$nc||queries("ALTER TABLE ".table($R)." DROP ".implode(", ",$nc)));}function
found_rows($S,$Z){}function
foreign_keys($R){$J=array();$lg=array("CASCADE","NO ACTION","SET NULL","SET DEFAULT");foreach(get_rows("EXEC sp_fkeys @fktable_name = ".q($R).", @fktable_owner = ".q(get_schema()))as$K){$o=&$J[$K["FK_NAME"]];$o["db"]=$K["PKTABLE_QUALIFIER"];$o["ns"]=$K["PKTABLE_OWNER"];$o["table"]=$K["PKTABLE_NAME"];$o["on_update"]=$lg[$K["UPDATE_RULE"]];$o["on_delete"]=$lg[$K["DELETE_RULE"]];$o["source"][]=$K["FKCOLUMN_NAME"];$o["target"][]=$K["PKCOLUMN_NAME"];}return$J;}function
truncate_tables($T){return
apply_queries("TRUNCATE TABLE",$T);}function
drop_views($hk){return
queries("DROP VIEW ".implode(", ",array_map('Adminer\table',$hk)));}function
drop_tables($T){return
queries("DROP TABLE ".implode(", ",array_map('Adminer\table',$T)));}function
move_tables($T,$hk,$aj){return
apply_queries("ALTER SCHEMA ".idf_escape($aj)." TRANSFER",array_merge($T,$hk));}function
trigger($B,$R){if($B=="")return
array();$L=get_rows("SELECT s.name [Trigger],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(s.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(s.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing],
c.text
FROM sysobjects s
JOIN syscomments c ON s.id = c.id
WHERE s.xtype = 'TR' AND s.name = ".q($B));$J=reset($L);if($J)$J["Statement"]=preg_replace('~^.+\s+AS\s+~isU','',$J["text"]);return$J;}function
triggers($R){$J=array();foreach(get_rows("SELECT sys1.name,
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing]
FROM sysobjects sys1
JOIN sysobjects sys2 ON sys1.parent_obj = sys2.id
WHERE sys1.xtype = 'TR' AND sys2.name = ".q($R))as$K)$J[$K["name"]]=array($K["Timing"],$K["Event"]);return$J;}function
trigger_options(){return
array("Timing"=>array("AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("AS"),);}function
schemas(){return
get_vals("SELECT name FROM sys.schemas");}function
get_schema(){if($_GET["ns"]!="")return$_GET["ns"];return
get_val("SELECT SCHEMA_NAME()");}function
set_schema($Vh){$_GET["ns"]=$Vh;return
true;}function
create_sql($R,$Ba,$Hi){if(is_view(table_status1($R))){$gk=view($R);return"CREATE VIEW ".table($R)." AS $gk[select]";}$n=array();$lh=false;foreach(fields($R)as$B=>$m){$X=process_field($m,$m);if($X[6])$lh=true;$n[]=implode("",$X);}foreach(indexes($R)as$B=>$u){if(!$lh||$u["type"]!="PRIMARY"){$e=array();foreach($u["columns"]as$w=>$X)$e[]=idf_escape($X).($u["descs"][$w]?" DESC":"");$B=idf_escape($B);$n[]=($u["type"]=="INDEX"?"INDEX $B":"CONSTRAINT $B ".($u["type"]=="UNIQUE"?"UNIQUE":"PRIMARY KEY"))." (".implode(", ",$e).")";}}foreach(driver()->checkConstraints($R)as$B=>$Za)$n[]="CONSTRAINT ".idf_escape($B)." CHECK ($Za)";return"CREATE TABLE ".table($R)." (\n\t".implode(",\n\t",$n)."\n)";}function
foreign_keys_sql($R){$n=array();foreach(foreign_keys($R)as$qd)$n[]=ltrim(format_foreign_key($qd));return($n?"ALTER TABLE ".table($R)." ADD\n\t".implode(",\n\t",$n).";\n\n":"");}function
truncate_sql($R){return"TRUNCATE TABLE ".table($R);}function
use_sql($Pb,$Hi=""){return"USE ".idf_escape($Pb);}function
trigger_sql($R){$J="";foreach(triggers($R)as$B=>$zj)$J
.=create_trigger(" ON ".table($R),trigger($B,$R)).";";return$J;}function
convert_field($m){}function
unconvert_field($m,$J){return$J;}function
support($Yc){return
preg_match('~^(check|comment|columns|database|drop_col|dump|indexes|descidx|scheme|sql|table|trigger|view|view_trigger)$~',$Yc);}}class
Adminer{static$instance;var$error='';function
name(){return"<a href='https://www.adminer.org/'".target_blank()." id='h1'><img src='".h(preg_replace("~\\?.*~","",ME)."?file=logo.png&version=5.5.1")."' width='24' height='24' alt='' id='logo'>Adminer</a>";}function
credentials(){return
array(SERVER,$_GET["username"],get_password());}function
connectSsl(){}function
permanentLogin($h=false){return
password_file($h);}function
bruteForceKey(){return$_SERVER["REMOTE_ADDR"];}function
serverName($N){return
h($N);}function
database(){return
DB;}function
databases($od=true){return
get_databases($od);}function
pluginsLinks(){}function
operators(){return
driver()->operators;}function
schemas(){return
schemas();}function
queryTimeout(){return
2;}function
afterConnect(){}function
headers(){}function
csp(array$Ib){return$Ib;}function
head($Mb=null){return
true;}function
bodyClass(){echo" adminer";}function
css(){$J=array();foreach(array("","-dark")as$If){$fd="adminer$If.css";if(file_exists($fd)){$ed=file_get_contents($fd);$J["$fd?v=".crc32($ed)]=($If?"dark":(preg_match('~prefers-color-scheme:\s*dark~',$ed)?'':'light'));}}return$J;}function
loginForm(){echo"<table class='layout'>\n",adminer()->loginFormField('driver','<tr><th>'.'System'.'<td>',html_select("auth[driver]",SqlDriver::$drivers,DRIVER,"loginDriver(this);")),adminer()->loginFormField('server','<tr><th>'.'Server'.'<td>','<input name="auth[server]" value="'.h(SERVER).'" title="'.'hostname[:port] or :socket'.'" placeholder="localhost" autocapitalize="off">'),adminer()->loginFormField('username','<tr><th>'.'Username'.'<td>','<input name="auth[username]" id="username" autofocus value="'.h($_GET["username"]).'" autocomplete="username" autocapitalize="off">'.script("const authDriver = qs('#username').form['auth[driver]']; authDriver && authDriver.onchange();")),adminer()->loginFormField('password','<tr><th>'.'Password'.'<td>','<input type="password" name="auth[password]" autocomplete="current-password">'),adminer()->loginFormField('db','<tr><th>'.'Database'.'<td>','<input name="auth[db]" value="'.h($_GET["db"]).'" autocapitalize="off">'),"</table>\n","<p><input type='submit' value='".'Login'."'>\n",checkbox("auth[permanent]",1,$_COOKIE["adminer_permanent"],'Permanent login')."\n";}function
loginFormField($B,$Od,$Y){return$Od.$Y."\n";}function
login($ff,$F){if($F=="")return
sprintf('Adminer does not support accessing a database without a password, <a href="https://www.adminer.org/en/password/"%s>more information</a>.',target_blank());return
true;}function
tableName(array$Pi){return
h($Pi["Name"]);}function
fieldName(array$m,$ug=0){$U=$m["full_type"].($m["null"]?" NULL":"");$qb=$m["comment"];return'<span title="'.h($U.($qb!=""?($U?": ":"").$qb:'')).'">'.h($m["field"]).'</span>';}function
selectLinks(array$Pi,$O=""){$B=$Pi["Name"];echo'<p class="links">';$bf=array("select"=>'Select data');if(support("table")||support("indexes"))$bf["table"]='Show structure';$Ee=false;if(support("table")){$Ee=is_view($Pi);if($Ee){if(support("view"))$bf["view"]='Alter view';}elseif(function_exists('Adminer\alter_table')&&$B!="")$bf["create"]='Alter table';}if($O!==null)$bf["edit"]='New item';foreach($bf
as$w=>$X)echo" <a href='".h(ME)."$w=".urlencode($B).($w=="edit"?$O:"")."'".bold(isset($_GET[$w])).">$X</a>";echo
doc_link(array(JUSH=>driver()->tableHelp($B,$Ee)),"?"),"\n";}function
foreignKeys($R){return
foreign_keys($R);}function
backwardKeys($R,$Oi){return
array();}function
backwardKeysPrint(array$Ga,array$K){}function
selectQuery($H,$Di,$Wc=false){$J="</p>\n";if(!$Wc&&($kk=driver()->warnings())){$s="warnings";$J=", <a href='#$s'>".'Warnings'."</a>".script("qsl('a').onclick = partial(toggle, '$s');","")."$J<div id='$s' class='hidden'>\n$kk</div>\n";}return"<p><code class='jush-".JUSH."'>".h(str_replace("\n"," ",$H))."</code> <span class='time'>(".format_time($Di).")</span>".(support("sql")?" <a href='".h(ME)."sql=".urlencode($H)."'>".'Edit'."</a>":"").$J;}function
sqlCommandQuery($H){return
shorten_utf8(trim($H),1000);}function
sqlPrintAfter(){}function
rowDescription($R){return"";}function
rowDescriptions(array$L,array$rd){return$L;}function
selectLink($X,array$m){}function
selectVal($X,$z,array$m,$Dg){$J=($X===null?"<i>NULL</i>":(preg_match("~char|binary|boolean~",$m["type"])&&!preg_match("~var~",$m["type"])?"<code>$X</code>":(preg_match('~^jsonb?$~',$m["full_type"])?"<code class='jush-js'>$X</code>":$X)));if(is_blob($m)&&!is_utf8($X))$J="<i>".lang_format(array('%d byte','%d bytes'),strlen($Dg))."</i>";return($z?"<a href='".h($z)."'".(is_url($z)?target_blank():"").">$J</a>":$J);}function
editVal($X,array$m){return$X;}function
config(){return
array();}function
tableStructurePrint(array$n,$Pi=null){echo"<div class='scrollable'>\n","<table class='nowrap odds'>\n","<thead><tr><th>".'Column'."<td>".'Type'.(support("comment")?"<td>".'Comment':"")."</thead>\n";$Gi=driver()->structuredTypes();foreach($n
as$m){echo"<tr><th>".h($m["field"]);$U=h($m["full_type"]);$c=h($m["collation"]);echo"<td><span title='$c'>".(in_array($U,(array)$Gi['User types'])?"<a href='".h(ME.'type='.urlencode($U))."'>$U</a>":$U.($c&&isset($Pi["Collation"])&&$c!=$Pi["Collation"]?" $c":""))."</span>",($m["null"]?" <i>NULL</i>":""),($m["auto_increment"]?" <i>".'Auto Increment'."</i>":"");$k=h($m["default"]);echo(isset($m["default"])?" <span title='".'Default value'."'>[<b>".($m["generated"]?"<code class='jush-".JUSH."'>$k</code>":$k)."</b>]</span>":""),(support("comment")?"<td>".h($m["comment"]):""),"\n";}echo"</table>\n","</div>\n";}function
tableIndexesPrint(array$v,array$Pi){$Og=false;foreach($v
as$B=>$u)$Og|=!!$u["partial"];echo"<table>\n";$Ub=first(driver()->indexAlgorithms($Pi));foreach($v
as$B=>$u){ksort($u["columns"]);$nh=array();foreach($u["columns"]as$w=>$X)$nh[]="<i>".h($X)."</i>".($u["lengths"][$w]?"(".$u["lengths"][$w].")":"").($u["descs"][$w]?" DESC":"");echo"<tr title='".h($B)."'>","<th>$u[type]".($Ub&&$u['algorithm']!=$Ub?" ($u[algorithm])":""),"<td>".implode(", ",$nh);if($Og)echo"<td>".($u['partial']?"<code class='jush-".JUSH."'>WHERE ".h($u['partial']):"");echo"\n";}echo"</table>\n";}function
selectColumnsPrint(array$M,array$e){print_fieldset("select",'Select',$M);$r=0;$M[""]=array();foreach($M
as$w=>$X){$X=idx($_GET["columns"],$w,array());$d=select_input(" name='columns[$r][col]'",$e,$X["col"],($w!==""?"selectFieldChange":"selectAddRow"));echo"<div>".(driver()->functions||driver()->grouping?html_select("columns[$r][fun]",array(-1=>"")+array_filter(array('Functions'=>driver()->functions,'Aggregation'=>driver()->grouping)),$X["fun"]).on_help("event.target.value && event.target.value.replace(/ |\$/, '(') + ')'",1).script("qsl('select').onchange = function () { helpClose();".($w!==""?"":" qsl('select, input', this.parentNode).onchange();")." };","")."($d)":$d)."</div>\n";$r++;}echo"</div></fieldset>\n";}function
selectSearchPrint(array$Z,array$e,array$v){print_fieldset("search",'Search',$Z);foreach($v
as$r=>$u){if($u["type"]=="FULLTEXT")echo"<div>(<i>".implode("</i>, <i>",array_map('Adminer\h',$u["columns"]))."</i>) AGAINST"," <input type='search' name='fulltext[$r]' value='".h(idx($_GET["fulltext"],$r))."'>",script("qsl('input').oninput = selectFieldChange;",""),(JUSH=='sql'?checkbox("boolean[$r]",1,isset($_GET["boolean"][$r]),"BOOL"):''),"</div>\n";}$Wa="this.parentNode.firstChild.onchange();";foreach(array_merge((array)$_GET["where"],array(array()))as$r=>$X){if(!$X||("$X[col]$X[val]"!=""&&in_array($X["op"],adminer()->operators())))echo"<div>".select_input(" name='where[$r][col]'",$e,$X["col"],($X?"selectFieldChange":"selectAddRow"),"(".'anywhere'.")"),html_select("where[$r][op]",adminer()->operators(),$X["op"],$Wa),"<input type='search' name='where[$r][val]' value='".h($X["val"])."'>",script("mixin(qsl('input'), {oninput: function () { $Wa }, onkeydown: selectSearchKeydown, onsearch: selectSearchSearch});",""),"</div>\n";}echo"</div></fieldset>\n";}function
selectOrderPrint(array$ug,array$e,array$v){print_fieldset("sort",'Sort',$ug);$r=0;foreach((array)$_GET["order"]as$w=>$X){if($X!=""){echo"<div>".select_input(" name='order[$r]'",$e,$X,"selectFieldChange"),checkbox("desc[$r]",1,isset($_GET["desc"][$w]),'descending')."</div>\n";$r++;}}echo"<div>".select_input(" name='order[$r]'",$e,"","selectAddRow"),checkbox("desc[$r]",1,false,'descending')."</div>\n","</div></fieldset>\n";}function
selectLimitPrint($y){echo"<fieldset><legend>".'Limit'."</legend><div>","<input type='number' name='limit' class='size' value='".intval($y)."'>",script("qsl('input').oninput = selectFieldChange;",""),"</div></fieldset>\n";}function
selectLengthPrint($gj){if($gj!==null)echo"<fieldset><legend>".'Text length'."</legend><div>","<input type='number' name='text_length' class='size' value='".h($gj)."'>","</div></fieldset>\n";}function
selectActionPrint(array$v){echo"<fieldset><legend>".'Action'."</legend><div>","<input type='submit' value='".'Select'."'>"," <span id='noindex' title='".'Full table scan'."'></span>","<script".nonce().">\n","const indexColumns = ";$e=array();foreach($v
as$u){$Lb=reset($u["columns"]);if($u["type"]!="FULLTEXT"&&$Lb)$e[$Lb]=1;}$e[""]=1;foreach($e
as$w=>$X)json_row($w);echo";\n","selectFieldChange.call(qs('#form')['select']);\n","</script>\n","</div></fieldset>\n";}function
selectCommandPrint(){return!information_schema(DB);}function
selectImportPrint(){return!information_schema(DB);}function
selectEmailPrint(array$yc,array$e){}function
selectColumnsProcess(array$e,array$v){$M=array();$Cd=array();foreach((array)$_GET["columns"]as$w=>$X){if($X["fun"]=="count"||($X["col"]!=""&&(!$X["fun"]||in_array($X["fun"],driver()->functions)||in_array($X["fun"],driver()->grouping)))){$M[$w]=apply_sql_function($X["fun"],($X["col"]!=""?idf_escape($X["col"]):"*"));if(!in_array($X["fun"],driver()->grouping))$Cd[]=$M[$w];}}return
array($M,$Cd);}function
selectSearchProcess(array$n,array$v){$J=array();foreach($v
as$r=>$u){if($u["type"]=="FULLTEXT"&&idx($_GET["fulltext"],$r)!="")$J[]="MATCH (".implode(", ",array_map('Adminer\idf_escape',$u["columns"])).") AGAINST (".q($_GET["fulltext"][$r]).(isset($_GET["boolean"][$r])?" IN BOOLEAN MODE":"").")";}foreach((array)$_GET["where"]as$w=>$X){$jb=$X["col"];if("$jb$X[val]"!=""&&in_array($X["op"],adminer()->operators())){$ub=array();foreach(($jb!=""?array($jb=>$n[$jb]):$n)as$B=>$m){$ih="";$tb=" $X[op]";if(preg_match('~IN$~',$X["op"])){$de=process_length($X["val"]);$tb
.=" ".($de!=""?$de:"(NULL)");}elseif($X["op"]=="SQL")$tb=" $X[val]";elseif(preg_match('~^(I?LIKE) %%$~',$X["op"],$A))$tb=" $A[1] ".adminer()->processInput($m,"%$X[val]%");elseif($X["op"]=="FIND_IN_SET"){$ih="$X[op](".q($X["val"]).", ";$tb=")";}elseif(!preg_match('~NULL$~',$X["op"]))$tb
.=" ".adminer()->processInput($m,$X["val"]);if($jb!=""||(isset($m["privileges"]["where"])&&(preg_match('~^[-\d.'.(preg_match('~IN$~',$X["op"])?',':'').']+$~',$X["val"])||!preg_match('~'.number_type().'|bit~',$m["type"]))&&(!preg_match("~[\x80-\xFF]~",$X["val"])||preg_match('~char|text|enum|set~',$m["type"]))&&(!preg_match('~date|timestamp~',$m["type"])||preg_match('~^\d+-\d+-\d+~',$X["val"]))))$ub[]=$ih.driver()->convertSearch(idf_escape($B),$X,$m).$tb;}$J[]=(count($ub)==1?$ub[0]:($ub?"(".implode(" OR ",$ub).")":"1 = 0"));}}return$J;}function
selectOrderProcess(array$n,array$v){$J=array();foreach((array)$_GET["order"]as$w=>$X){if($X!="")$J[]=(preg_match('~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~',$X)?$X:idf_escape($X)).(isset($_GET["desc"][$w])?" DESC".(JUSH=='pgsql'&&idx($n[$X],"null")?" NULLS LAST":""):"");}return$J;}function
selectLimitProcess(){return(isset($_GET["limit"])?intval($_GET["limit"]):50);}function
selectLengthProcess(){return(isset($_GET["text_length"])?"$_GET[text_length]":"100");}function
selectEmailProcess(array$Z,array$rd){return
false;}function
selectQueryBuild(array$M,array$Z,array$Cd,array$ug,$y,$D){return"";}function
messageQuery($H,$hj,$Wc=false){restart_session();$Qd=&get_session("queries");if(!idx($Qd,$_GET["db"]))$Qd[$_GET["db"]]=array();if(strlen($H)>1e6)$H=preg_replace('~[\x80-\xFF]+$~','',substr($H,0,1e6))."\n…";$Qd[$_GET["db"]][]=array($H,time(),$hj);$_i="sql-".count($Qd[$_GET["db"]]);$J="<a href='#$_i' class='toggle'>".'SQL command'."</a> <a href='' class='jsonly copy'>🗐</a>\n";if(!$Wc&&($kk=driver()->warnings())){$s="warnings-".count($Qd[$_GET["db"]]);$J="<a href='#$s' class='toggle'>".'Warnings'."</a>, $J<div id='$s' class='hidden'>\n$kk</div>\n";}return" <span class='time'>".@date("H:i:s")."</span>"." $J<div id='$_i' class='hidden'><pre><code class='jush-".JUSH."'>".shorten_utf8($H,1e4)."</code></pre>".($hj?" <span class='time'>($hj)</span>":'').(support("sql")?'<p><a href="'.h(str_replace("db=".urlencode(DB),"db=".urlencode($_GET["db"]),ME).'sql=&history='.(count($Qd[$_GET["db"]])-1)).'">'.'Edit'.'</a>':'').'</div>';}function
editRowPrint($R,array$n,$K,$Nj){}function
editFunctions(array$m){$J=($m["null"]?"NULL/":"");$Nj=isset($_GET["select"])||where($_GET);foreach(array(driver()->insertFunctions,driver()->editFunctions)as$w=>$yd){if(!$w||(!isset($_GET["call"])&&$Nj)){foreach($yd
as$Xg=>$X){if(!$Xg||preg_match("~$Xg~",$m["type"]))$J
.="/$X";}}if($w&&$yd&&!preg_match('~set|bool~',$m["type"])&&!is_blob($m))$J
.="/SQL";}if($m["auto_increment"]&&!$Nj)$J='Auto Increment';return
explode("/",$J);}function
editInput($R,array$m,$_a,$Y){if($m["type"]=="enum")return(isset($_GET["select"])?"<label><input type='radio'$_a value='orig' checked><i>".'original'."</i></label> ":"").enum_input("radio",$_a,$m,$Y,"NULL");return"";}function
editHint($R,array$m,$Y){return"";}function
processInput(array$m,$Y,$q=""){if($q=="SQL")return$Y;$B=$m["field"];$J=q($Y);if(preg_match('~^(now|getdate|uuid)$~',$q))$J="$q()";elseif(preg_match('~^current_(date|timestamp)$~',$q))$J=$q;elseif(preg_match('~^([+-]|\|\|)$~',$q))$J=idf_escape($B)." $q $J";elseif(preg_match('~^[+-] interval$~',$q))$J=idf_escape($B)." $q ".(preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+\$~i",$Y)&&JUSH!="pgsql"?$Y:$J);elseif(preg_match('~^(addtime|subtime|concat)$~',$q))$J="$q(".idf_escape($B).", $J)";elseif(preg_match('~^(md5|sha1|password|encrypt)$~',$q))$J="$q($J)";return
unconvert_field($m,$J);}function
dumpOutput(){$J=array('text'=>'open','file'=>'save');if(function_exists('gzencode'))$J['gz']='gzip';return$J;}function
dumpFormat(){return(support("dump")?array('sql'=>'SQL'):array())+array('csv'=>'CSV,','csv;'=>'CSV;','tsv'=>'TSV');}function
dumpDatabase($j){}function
dumpTable($R,$Hi,$Ee=0){if($_POST["format"]!="sql"){echo"\xef\xbb\xbf";if($Hi)dump_csv(array_keys(fields($R)));}else{if($Ee==2){$n=array();foreach(fields($R)as$B=>$m)$n[]=idf_escape($B)." $m[full_type]";$h="CREATE TABLE ".table($R)." (".implode(", ",$n).")";}else$h=create_sql($R,$_POST["auto_increment"],$Hi);set_utf8mb4($h);if($Hi&&$h){if($Hi=="DROP+CREATE"||$Ee==1)echo"DROP ".($Ee==2?"VIEW":"TABLE")." IF EXISTS ".table($R).";\n";if($Ee==1)$h=remove_definer($h);echo"$h;\n\n";}}}function
dumpData($R,$Hi,$H){if($Hi){$pf=(JUSH=="sqlite"?0:1048576);$n=array();$Zd=false;if($_POST["format"]=="sql"){if($Hi=="TRUNCATE+INSERT")echo
truncate_sql($R).";\n";$n=fields($R);if(JUSH=="mssql"){foreach($n
as$m){if($m["auto_increment"]){echo"SET IDENTITY_INSERT ".table($R)." ON;\n";$Zd=true;break;}}}}$I=connection()->query($H,1);if($I){$ue="";$Qa="";$Ke=array();$zd=array();$Ji="";$Zc=($R!=''?'fetch_assoc':'fetch_row');$Eb=0;while($K=$I->$Zc()){if(!$Ke){$ck=array();foreach($K
as$X){$m=$I->fetch_field();if(idx($n[$m->name],'generated')){$zd[$m->name]=true;continue;}$Ke[]=$m->name;$w=idf_escape($m->name);$ck[]="$w = VALUES($w)";}$Ji=($Hi=="INSERT+UPDATE"?"\nON DUPLICATE KEY UPDATE ".implode(", ",$ck):"").";\n";}if($_POST["format"]!="sql"){if($Hi=="table"){dump_csv($Ke);$Hi="INSERT";}dump_csv($K);}else{if(!$ue)$ue="INSERT INTO ".table($R)." (".implode(", ",array_map('Adminer\idf_escape',$Ke)).") VALUES";foreach($K
as$w=>$X){if($zd[$w]){unset($K[$w]);continue;}$m=$n[$w];$K[$w]=($X===null?"NULL":($X===false?0:unconvert_field($m,preg_match(number_type(),$m["type"])&&!preg_match('~\[~',$m["full_type"])&&is_numeric($X)?$X:(!is_blob($m)||is_utf8($X)?q($X):driver()->quoteBinary($X)))));}$Th=($pf?"\n":" ")."(".implode(",\t",$K).")";if(!$Qa)$Qa=$ue.$Th;elseif(JUSH=='mssql'?$Eb%1000!=0:strlen($Qa)+4+strlen($Th)+strlen($Ji)<$pf)$Qa
.=",$Th";else{echo$Qa.$Ji;$Qa=$ue.$Th;}}$Eb++;}if($Qa)echo$Qa.$Ji;}elseif($_POST["format"]=="sql")echo"-- ".str_replace("\n"," ",connection()->error)."\n";if($Zd)echo"SET IDENTITY_INSERT ".table($R)." OFF;\n";}}function
dumpFilename($Xd){return
friendly_url($Xd!=""?$Xd:(SERVER?:"localhost"));}function
dumpHeaders($Xd,$Lf=false){$Gg=$_POST["output"];$Rc=(preg_match('~sql~',$_POST["format"])?"sql":($Lf?"tar":"csv"));header("Content-Type: ".($Gg=="gz"?"application/x-gzip":($Rc=="tar"?"application/x-tar":($Rc=="sql"||$Gg!="file"?"text/plain":"text/csv")."; charset=utf-8")));if($Gg=="gz"){ob_start(function($Q){return
gzencode($Q);},1e6);}return$Rc;}function
dumpFooter(){if($_POST["format"]=="sql")echo"-- ".gmdate("Y-m-d H:i:s e")."\n";}function
importServerPath(){return"adminer.sql";}function
homepage(){echo'<p class="links">'.($_GET["ns"]==""&&support("database")?'<a href="'.h(ME).'database=">'.'Alter database'."</a>\n":""),(support("scheme")?"<a href='".h(ME)."scheme='>".($_GET["ns"]!=""?'Alter schema':'Create schema')."</a>\n":""),($_GET["ns"]!==""?'<a href="'.h(ME).'schema=">'.'Database schema'."</a>\n":""),(support("privileges")?"<a href='".h(ME)."privileges='>".'Privileges'."</a>\n":"");if($_GET["ns"]!=="")echo(support("routine")?"<a href='#routines'>".'Routines'."</a>\n":""),(support("sequence")?"<a href='#sequences'>".'Sequences'."</a>\n":""),(support("type")?"<a href='#user-types'>".'User types'."</a>\n":""),(support("event")?"<a href='#events'>".'Events'."</a>\n":"");return
true;}function
navigation($Hf){echo"<h1>".adminer()->name()." <span class='version'>".VERSION;$Uf=$_COOKIE["adminer_version"];echo" <a href='https://www.adminer.org/#download'".target_blank()." id='version'>".(version_compare(VERSION,$Uf)<0?h($Uf):"")."</a>","</span></h1>\n";if($Hf=="auth"){$Gg="";foreach((array)$_SESSION["pwds"]as$ek=>$mi){foreach($mi
as$N=>$Xj){$B=h(get_setting("vendor-$ek-$N")?:get_driver($ek));foreach($Xj
as$V=>$F){if($F!==null){$Sb=$_SESSION["db"][$ek][$N][$V];foreach(($Sb?array_keys($Sb):array(""))as$j)$Gg
.="<li><a href='".h(auth_url($ek,$N,$V,$j))."'>($B) ".h("$V@".($N!=""?adminer()->serverName($N):"").($j!=""?" - $j":""))."</a>\n";}}}}if($Gg)echo"<ul id='logins'>\n$Gg</ul>\n".script("mixin(qs('#logins'), {onmouseover: menuOver, onmouseout: menuOut});");}else{$T=array();if($_GET["ns"]!==""&&!$Hf&&DB!=""){connection()->select_db(DB);$T=table_status('',true);}adminer()->syntaxHighlighting($T);adminer()->databasesPrint($Hf);$ja=array();if(DB==""||!$Hf){if(support("sql")){$ja['sql']="<a href='".h(ME)."sql='".bold(isset($_GET["sql"])&&!isset($_GET["import"])).">".'SQL command'."</a>";$ja['import']="<a href='".h(ME)."import='".bold(isset($_GET["import"])).">".'Import'."</a>";}$ja['dump']="<a href='".h(ME)."dump=".urlencode(isset($_GET["table"])?$_GET["table"]:$_GET["select"])."' id='dump'".bold(isset($_GET["dump"])).">".'Export'."</a>";}$ee=$_GET["ns"]!==""&&!$Hf&&DB!="";if($ee&&function_exists('Adminer\alter_table'))$ja['create']='<a href="'.h(ME).'create="'.bold($_GET["create"]==="").">".'Create table'."</a>";$ja=adminer()->menuActions($ja,$Hf);echo($ja?"<p class='links'>\n".implode("\n",$ja)."\n":"");if($ee){if($T)adminer()->tablesPrint($T);else
echo"<p class='message'>".'No tables.'."</p>\n";}}}function
syntaxHighlighting(array$T){echo
script_src(preg_replace("~\\?.*~","",ME)."?file=jush.js&version=5.5.1",true);if(support("sql")){echo"<script".nonce().">\n";if($T){$bf=array();foreach($T
as$R=>$U)$bf[]=preg_quote($R,'/');echo"var jushLinks = { ".JUSH.":";json_row(js_escape(ME).(support("table")?"table":"select").'=$&','/\b('.implode('|',$bf).')\b/g',false);if(support('routine')){foreach(routines()as$K)json_row(js_escape(ME).'function='.urlencode($K["SPECIFIC_NAME"]).'&name=$&','/\b'.preg_quote($K["ROUTINE_NAME"],'/').'(?=["`]?\()/g',false);}json_row('');echo"};\n";foreach(array("bac","bra","sqlite_quo","mssql_bra")as$X)echo"jushLinks.$X = jushLinks.".JUSH.";\n";if(isset($_GET["sql"])||isset($_GET["trigger"])||isset($_GET["check"])){$Wi=array_fill_keys(array_keys($T),array());foreach(driver()->allFields()as$R=>$n){foreach($n
as$m)$Wi[$R][]=$m["field"];}echo"addEventListener('DOMContentLoaded', () => { autocompleter = jush.autocompleteSql('".idf_escape("")."', ".json_encode($Wi)."); });\n";}}echo"</script>\n";}echo
script("syntaxHighlighting('".(preg_match('~^\d\.?\d~',connection()->server_info,$A)?$A[0]:"")."', '".connection()->flavor."');");}function
databasesPrint($Hf){$i=adminer()->databases();if(DB&&$i&&!in_array(DB,$i))array_unshift($i,DB);echo"<form action=''>\n<p id='dbs'>\n";hidden_fields_get();$Qb=script("mixin(qsl('select'), {onmousedown: dbMouseDown, onchange: dbChange});");echo"<label title='".'Database'."'>".'DB'.": ".($i?html_select("db",array(""=>"")+$i,DB).$Qb:"<input name='db' value='".h(DB)."' autocapitalize='off' size='19'>\n")."</label>","<input type='submit' value='".'Use'."'".($i?" class='hidden'":"").">\n";if(support("scheme")){if($Hf!="db"&&DB!=""&&connection()->select_db(DB)){echo"<br><label>".'Schema'.": ".html_select("ns",array(""=>"")+adminer()->schemas(),$_GET["ns"])."$Qb</label>";if($_GET["ns"]!="")set_schema($_GET["ns"]);}}foreach(array("import","sql","schema","dump","privileges")as$X){if(isset($_GET[$X])){echo
input_hidden($X);break;}}echo"</p></form>\n";}function
menuActions(array$ja,$Hf){return$ja;}function
tablesPrint(array$T){echo"<ul id='tables'>".script("mixin(qs('#tables'), {onmouseover: menuOver, onmouseout: menuOut});");foreach($T
as$R=>$P){$R="$R";$B=adminer()->tableName($P);if($B!=""&&!$P["partition"])echo'<li><a href="'.h(ME).'select='.urlencode($R).'"'.bold($_GET["select"]==$R||$_GET["edit"]==$R,"select")." title='".'Select data'."'>".'select'."</a> ",(support("table")||support("indexes")?'<a href="'.h(ME).'table='.urlencode($R).'"'.bold(in_array($R,array($_GET["table"],$_GET["create"],$_GET["indexes"],$_GET["foreign"],$_GET["trigger"],$_GET["check"],$_GET["view"])),(is_view($P)?"view":"structure"))." title='".'Show structure'."'>$B</a>":"<span>$B</span>")."\n";}echo"</ul>\n";}function
showVariables(){return
show_variables();}function
showStatus(){return
show_status();}function
processList(){return
process_list();}function
killProcess($s){return
kill_process($s);}}class
Plugins{private
static$append=array('dumpFormat'=>true,'dumpOutput'=>true,'editRowPrint'=>true,'editFunctions'=>true,'config'=>true);var$plugins;var$error='';private$hooks=array();function
__construct($ch){if($ch===null){$ch=array();$Ka="adminer-plugins";if(is_dir($Ka)){foreach(glob("$Ka/*.php")as$fd)$this->includeOnce($fd);}$Pd=" href='https://www.adminer.org/plugins/#use'".target_blank();if(file_exists("$Ka.php")){$fe=$this->includeOnce("$Ka.php");if(is_array($fe)){foreach($fe
as$bh)$ch[get_class($bh)]=$bh;}else$this->error
.=sprintf('%s must <a%s>return an array</a>.',"<b>$Ka.php</b>",$Pd)."<br>";}foreach(get_declared_classes()as$gb){if(!$ch[$gb]&&(preg_match('~^Adminer\w~i',$gb)||is_subclass_of($gb,'Adminer\Plugin'))){$Dh=new
\ReflectionClass($gb);$zb=$Dh->getConstructor();if($zb&&$zb->getNumberOfRequiredParameters())$this->error
.=sprintf('<a%s>Configure</a> %s in %s.',$Pd,"<b>$gb</b>","<b>$Ka.php</b>")."<br>";else$ch[$gb]=new$gb;}}}$this->plugins=$ch;$ma=new
Adminer;$ch[]=$ma;$Dh=new
\ReflectionObject($ma);foreach($Dh->getMethods()as$Ff){foreach($ch
as$bh){$B=$Ff->getName();if(method_exists($bh,$B))$this->hooks[$B][]=$bh;}}}function
includeOnce($fd){return
include_once"./$fd";}function
__call($B,array$Lg){$wa=array();foreach($Lg
as$w=>$X)$wa[]=&$Lg[$w];$J=null;foreach($this->hooks[$B]as$bh){$Y=call_user_func_array(array($bh,$B),$wa);if($Y!==null){if(!self::$append[$B])return$Y;$J=$Y+(array)$J;}}return$J;}}abstract
class
Plugin{protected$translations=array();function
description(){return$this->lang('');}function
screenshot(){return"";}protected
function
lang($t,$ag=null){$wa=func_get_args();$wa[0]=idx($this->translations[LANG],$t)?:$t;return
call_user_func_array('Adminer\lang_format',$wa);}}Adminer::$instance=(function_exists('adminer_object')?adminer_object():(is_dir("adminer-plugins")||file_exists("adminer-plugins.php")?new
Plugins(null):new
Adminer));SqlDriver::$drivers=array("server"=>"MySQL / MariaDB")+SqlDriver::$drivers;if(!defined('Adminer\DRIVER')){define('Adminer\DRIVER',"server");if(extension_loaded("mysqli")&&$_GET["ext"]!="pdo"){class
Db
extends
\MySQLi{static$instance;var$extension="MySQLi",$flavor='';function
__construct(){parent::init();}function
attach($N,$V,$F){mysqli_report(MYSQLI_REPORT_OFF);list($Td,$dh)=host_port($N);$Ci=adminer()->connectSsl();if($Ci)$this->ssl_set($Ci['key'],$Ci['cert'],$Ci['ca'],'','');$J=@$this->real_connect(($N!=""?$Td:ini_get("mysqli.default_host")),($N.$V!=""?$V:ini_get("mysqli.default_user")),($N.$V.$F!=""?$F:ini_get("mysqli.default_pw")),null,(is_numeric($dh)?intval($dh):ini_get("mysqli.default_port")),(is_numeric($dh)?null:$dh),($Ci?($Ci['verify']!==false?2048:64):0));$this->options(MYSQLI_OPT_LOCAL_INFILE,0);return($J?'':$this->error);}function
set_charset($Ya){if(parent::set_charset($Ya))return
true;parent::set_charset('utf8');return$this->query("SET NAMES $Ya");}function
next_result(){return
self::more_results()&&parent::next_result();}function
quote($Q){return"'".$this->escape_string($Q)."'";}}}elseif(extension_loaded("mysql")&&!((ini_bool("sql.safe_mode")||ini_bool("mysql.allow_local_infile"))&&extension_loaded("pdo_mysql"))){class
Db
extends
SqlDb{private$link;function
attach($N,$V,$F){if(ini_bool("mysql.allow_local_infile"))return
sprintf('Disable %s or enable %s or %s extensions.',"'mysql.allow_local_infile'","MySQLi","PDO_MySQL");$this->link=@mysql_connect(($N!=""?$N:ini_get("mysql.default_host")),($N.$V!=""?$V:ini_get("mysql.default_user")),($N.$V.$F!=""?$F:ini_get("mysql.default_password")),true,131072);if(!$this->link)return
mysql_error();$this->server_info=mysql_get_server_info($this->link);return'';}function
set_charset($Ya){return
mysql_set_charset($Ya,$this->link)||mysql_set_charset('utf8',$this->link);}function
quote($Q){return"'".mysql_real_escape_string($Q,$this->link)."'";}function
select_db($Pb){return
mysql_select_db($Pb,$this->link);}function
query($H,$Fj=false){$I=@($Fj?mysql_unbuffered_query($H,$this->link):mysql_query($H,$this->link));$this->error="";if(!$I){$this->errno=mysql_errno($this->link);$this->error=mysql_error($this->link);return
false;}if($I===true){$this->affected_rows=mysql_affected_rows($this->link);$this->info=mysql_info($this->link);return
true;}return
new
Result($I);}}class
Result{var$num_rows;private$result;private$offset=0;function
__construct($I){$this->result=$I;$this->num_rows=mysql_num_rows($I);}function
fetch_assoc(){return
mysql_fetch_assoc($this->result);}function
fetch_row(){return
mysql_fetch_row($this->result);}function
fetch_field(){$J=mysql_fetch_field($this->result,$this->offset++);$J->orgtable=$J->table;$J->charsetnr=($J->blob?63:0);return$J;}}}elseif(extension_loaded("pdo_mysql")){class
Db
extends
PdoDb{var$extension="PDO_MySQL";function
attach($N,$V,$F){$sg=array(\PDO::MYSQL_ATTR_LOCAL_INFILE=>false);$Ci=adminer()->connectSsl();if($Ci){if($Ci['key'])$sg[\PDO::MYSQL_ATTR_SSL_KEY]=$Ci['key'];if($Ci['cert'])$sg[\PDO::MYSQL_ATTR_SSL_CERT]=$Ci['cert'];if($Ci['ca'])$sg[\PDO::MYSQL_ATTR_SSL_CA]=$Ci['ca'];if(isset($Ci['verify']))$sg[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]=$Ci['verify'];}list($Td,$dh)=host_port($N);return$this->dsn("mysql:charset=utf8".($Td!=""?";host=$Td":'').($dh?(is_numeric($dh)?";port=":";unix_socket=").$dh:""),$V,$F,$sg);}function
set_charset($Ya){return$this->query("SET NAMES $Ya");}function
select_db($Pb){return$this->query("USE ".idf_escape($Pb));}function
query($H,$Fj=false){$this->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,!$Fj);return
parent::query($H,$Fj);}}}class
Driver
extends
SqlDriver{static$extensions=array("MySQLi","MySQL","PDO_MySQL");static$jush="sql";var$unsigned=array("unsigned","zerofill","unsigned zerofill");var$operators=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","REGEXP","IN","FIND_IN_SET","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL");var$functions=array("char_length","date","from_unixtime","lower","round","floor","ceil","sec_to_time","time_to_sec","upper");var$grouping=array("avg","count","count distinct","group_concat","max","min","sum");var$partitionBy=array("HASH","LINEAR HASH","KEY","LINEAR KEY","RANGE","LIST");static
function
connect($N,$V,$F){$f=parent::connect($N,$V,$F);if(is_string($f)){if(function_exists('iconv')&&!is_utf8($f)&&strlen($Th=iconv("windows-1250","utf-8",$f))>strlen($f))$f=$Th;return$f;}$f->set_charset(charset($f));$f->query("SET sql_quote_show_create = 1, autocommit = 1");$f->flavor=(preg_match('~MariaDB~',$f->server_info)?'maria':'mysql');add_driver(DRIVER,($f->flavor=='maria'?"MariaDB":"MySQL"));return$f;}function
__construct(Db$f){parent::__construct($f);$this->types=array('Numbers'=>array("tinyint"=>3,"smallint"=>5,"mediumint"=>8,"int"=>10,"bigint"=>20,"decimal"=>66,"float"=>12,"double"=>21),'Date and time'=>array("date"=>10,"datetime"=>19,"timestamp"=>19,"time"=>10,"year"=>4),'Strings'=>array("char"=>255,"varchar"=>65535,"tinytext"=>255,"text"=>65535,"mediumtext"=>16777215,"longtext"=>4294967295),'Lists'=>array("enum"=>65535,"set"=>64),'Binary'=>array("bit"=>20,"binary"=>255,"varbinary"=>65535,"tinyblob"=>255,"blob"=>65535,"mediumblob"=>16777215,"longblob"=>4294967295),'Geometry'=>array("geometry"=>0,"point"=>0,"linestring"=>0,"polygon"=>0,"multipoint"=>0,"multilinestring"=>0,"multipolygon"=>0,"geometrycollection"=>0),);$this->insertFunctions=array("char"=>"md5/sha1/password/encrypt/uuid","binary"=>"md5/sha1","date|time"=>"now",);$this->editFunctions=array(number_type()=>"+/-","date"=>"+ interval/- interval","time"=>"addtime/subtime","char|text"=>"concat",);if(min_version('5.7.8',10.2,$f))$this->types['Strings']["json"]=4294967295;if(min_version('',10.7,$f)){$this->types['Strings']["uuid"]=128;$this->insertFunctions['uuid']='uuid';}if(min_version('',10.5,$f)){$this->types['Network']["inet6"]=39;if(min_version('','10.10',$f))$this->types['Network']["inet4"]=15;}if(min_version(9,11.7,$f))$this->types['Numbers']["vector"]=16383;if(min_version(5.7,10.2,$f))$this->generated=array("STORED","VIRTUAL");}function
unconvertFunction(array$m){return(preg_match("~binary~",$m["type"])?"<code class='jush-sql'>UNHEX</code>":($m["type"]=="bit"?doc_link(array('sql'=>'bit-value-literals.html'),"<code>b''</code>"):($m["type"]=="vector"?"<code class='jush-sql'>".($this->conn->flavor=='maria'?"VEC_FromText":"STRING_TO_VECTOR")."</code>":(preg_match("~geometry|point|linestring|polygon~",$m["type"])?"<code class='jush-sql'>GeomFromText</code>":""))));}function
insert($R,array$O){return($O?parent::insert($R,$O):queries("INSERT INTO ".table($R)." ()\nVALUES ()"));}function
insertUpdate($R,array$L,array$lh){$e=array_keys(reset($L));$ih="INSERT INTO ".table($R)." (".implode(", ",$e).") VALUES\n";$ck=array();foreach($e
as$w)$ck[$w]="$w = VALUES($w)";$Ji="\nON DUPLICATE KEY UPDATE ".implode(", ",$ck);$ck=array();$x=0;foreach($L
as$O){$Y="(".implode(", ",$O).")";if($ck&&(strlen($ih)+$x+strlen($Y)+strlen($Ji)>1e6)){if(!queries($ih.implode(",\n",$ck).$Ji))return
false;$ck=array();$x=0;}$ck[]=$Y;$x+=strlen($Y)+2;}return
queries($ih.implode(",\n",$ck).$Ji);}function
slowQuery($H,$ij){if(min_version('5.7.8','10.1.2')){if($this->conn->flavor=='maria')return"SET STATEMENT max_statement_time=$ij FOR $H";elseif(preg_match('~^(SELECT\b)(.+)~is',$H,$A))return"$A[1] /*+ MAX_EXECUTION_TIME(".($ij*1000).") */ $A[2]";}}function
convertSearch($t,array$X,array$m){return(preg_match('~char|text|enum|set~',$m["type"])&&!preg_match("~^utf8~",$m["collation"])&&preg_match('~[\x80-\xFF]~',$X['val'])?"CONVERT($t USING ".charset($this->conn).")":$t);}function
quoteBinary($Th){return"X".q(bin2hex($Th));}function
warnings(){$I=$this->conn->query("SHOW WARNINGS");if($I&&$I->num_rows){ob_start();print_select_result($I);return
ob_get_clean();}}function
tableHelp($B,$Ee=false){$hf=($this->conn->flavor=='maria');if(information_schema(DB))return
strtolower("information-schema-".($hf?"$B-table/":str_replace("_","-",$B)."-table.html"));if(DB=="mysql")return($hf?"mysql$B-table/":"system-schema.html");}function
partitionsInfo($R){$wd="FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = ".q(DB)." AND TABLE_NAME = ".q($R);$I=$this->conn->query("SELECT PARTITION_METHOD, PARTITION_EXPRESSION, PARTITION_ORDINAL_POSITION $wd ORDER BY PARTITION_ORDINAL_POSITION DESC LIMIT 1");$J=array();list($J["partition_by"],$J["partition"],$J["partitions"])=$I->fetch_row();$Tg=get_key_vals("SELECT PARTITION_NAME, PARTITION_DESCRIPTION $wd AND PARTITION_NAME != '' ORDER BY PARTITION_ORDINAL_POSITION");$J["partition_names"]=array_keys($Tg);$J["partition_values"]=array_values($Tg);return$J;}function
hasCStyleEscapes(){static$Ta;if($Ta===null){$Ai=get_val("SHOW VARIABLES LIKE 'sql_mode'",1,$this->conn);$Ta=(strpos($Ai,'NO_BACKSLASH_ESCAPES')===false);}return$Ta;}function
engines(){$J=array();foreach(get_rows("SHOW ENGINES")as$K){if(preg_match("~YES|DEFAULT~",$K["Support"]))$J[]=$K["Engine"];}return$J;}function
indexAlgorithms(array$Pi){return(preg_match('~^(MEMORY|NDB)$~',$Pi["Engine"])?array("HASH","BTREE"):array());}}function
idf_escape($t){return"`".str_replace("`","``",$t)."`";}function
table($t){return
idf_escape($t);}function
get_databases($od){$J=get_session("dbs");if($J===null){$H="SELECT SCHEMA_NAME FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME";$J=($od?slow_query($H):get_vals($H));restart_session();set_session("dbs",$J);stop_session();}return$J;}function
limit($H,$Z,$y,$C=0,$hi=" "){return" $H$Z".($y?$hi."LIMIT $y".($C?" OFFSET $C":""):"");}function
limit1($R,$H,$Z,$hi="\n"){return
limit($H,$Z,1,0,$hi);}function
db_collation($j,array$lb){$J=null;$h=get_val("SHOW CREATE DATABASE ".idf_escape($j),1);if(preg_match('~ COLLATE ([^ ]+)~',$h,$A))$J=$A[1];elseif(preg_match('~ CHARACTER SET ([^ ]+)~',$h,$A))$J=$lb[$A[1]][-1];return$J;}function
logged_user(){return
get_val("SELECT USER()");}function
tables_list(){return
get_key_vals("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");}function
count_tables(array$i){$J=array();foreach($i
as$j)$J[$j]=count(get_vals("SHOW TABLES IN ".idf_escape($j)));return$J;}function
table_status($B="",$Xc=false){$J=array();foreach(get_rows($Xc?"SELECT TABLE_NAME AS Name, ENGINE AS Engine, TABLE_COMMENT AS Comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ".($B!=""?"AND TABLE_NAME = ".q($B):"ORDER BY Name"):"SHOW TABLE STATUS".($B!=""?" LIKE ".q(addcslashes($B,"%_\\")):""))as$K){if($K["Engine"]=="InnoDB")$K["Comment"]=preg_replace('~(?:(.+); )?InnoDB free: .*~','\1',$K["Comment"]);if(!isset($K["Engine"]))$K["Comment"]="";if($B!="")$K["Name"]=$B;$J[$K["Name"]]=$K;}return$J;}function
is_view(array$S){return$S["Engine"]===null;}function
fk_support(array$S){return
preg_match('~InnoDB|IBMDB2I'.(min_version(5.6)?'|NDB':'').'~i',$S["Engine"]);}function
fields($R){$hf=(connection()->flavor=='maria');$J=array();foreach(get_rows("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ".q($R)." ORDER BY ORDINAL_POSITION")as$K){$m=$K["COLUMN_NAME"];$U=$K["COLUMN_TYPE"];$_d=$K["GENERATION_EXPRESSION"];$Uc=$K["EXTRA"];preg_match('~^(VIRTUAL|PERSISTENT|STORED)~',$Uc,$zd);preg_match('~^([^( ]+)(?:\((.+)\))?( unsigned)?( zerofill)?$~',$U,$kf);$k=$K["COLUMN_DEFAULT"];if($k!=""){$De=preg_match('~text|json~',$kf[1]);if(!$hf&&$De)$k=preg_replace("~^(_\w+)?('.*')$~",'\2',stripslashes($k));if($hf||$De){$k=($k=="NULL"?null:preg_replace_callback("~^'(.*)'$~",function($A){return
stripslashes(str_replace("''","'",$A[1]));},$k));}if(!$hf&&preg_match('~binary~',$kf[1])&&preg_match('~^0x(\w*)$~',$k,$A))$k=pack("H*",$A[1]);}$J[$m]=array("field"=>$m,"full_type"=>$U,"type"=>$kf[1],"length"=>$kf[2],"unsigned"=>ltrim($kf[3].$kf[4]),"default"=>($zd?($hf?$_d:stripslashes($_d)):$k),"null"=>($K["IS_NULLABLE"]=="YES"),"auto_increment"=>($Uc=="auto_increment"),"on_update"=>(preg_match('~\bon update (\w+)~i',$Uc,$A)?$A[1]:""),"collation"=>$K["COLLATION_NAME"],"privileges"=>array_flip(explode(",","$K[PRIVILEGES],where,order")),"comment"=>$K["COLUMN_COMMENT"],"primary"=>($K["COLUMN_KEY"]=="PRI"),"generated"=>($zd[1]=="PERSISTENT"?"STORED":$zd[1]),);}return$J;}function
indexes($R,$g=null){$J=array();foreach(get_rows("SHOW INDEX FROM ".table($R),$g)as$K){$B=$K["Key_name"];$J[$B]["type"]=($B=="PRIMARY"?"PRIMARY":($K["Index_type"]=="FULLTEXT"?"FULLTEXT":($K["Non_unique"]?(preg_match('~^(SPATIAL|VECTOR)$~',$K["Index_type"])?$K["Index_type"]:"INDEX"):"UNIQUE")));$J[$B]["columns"][]=$K["Column_name"];$J[$B]["lengths"][]=($K["Index_type"]=="SPATIAL"?null:$K["Sub_part"]);$J[$B]["descs"][]=null;$J[$B]["algorithm"]=$K["Index_type"];}return$J;}function
foreign_keys($R){static$Xg='(?:`(?:[^`]|``)+`|"(?:[^"]|"")+")';$J=array();$Fb=get_val("SHOW CREATE TABLE ".table($R),1);if($Fb){preg_match_all("~CONSTRAINT ($Xg) FOREIGN KEY ?\\(((?:$Xg,? ?)+)\\) REFERENCES ($Xg)(?:\\.($Xg))? \\(((?:$Xg,? ?)+)\\)(?: ON DELETE (".driver()->onActions."))?(?: ON UPDATE (".driver()->onActions."))?~",$Fb,$lf,PREG_SET_ORDER);foreach($lf
as$A){preg_match_all("~$Xg~",$A[2],$vi);preg_match_all("~$Xg~",$A[5],$aj);$J[idf_unescape($A[1])]=array("db"=>idf_unescape($A[4]!=""?$A[3]:$A[4]),"table"=>idf_unescape($A[4]!=""?$A[4]:$A[3]),"source"=>array_map('Adminer\idf_unescape',$vi[0]),"target"=>array_map('Adminer\idf_unescape',$aj[0]),"on_delete"=>($A[6]?:"RESTRICT"),"on_update"=>($A[7]?:"RESTRICT"),);}}return$J;}function
view($B){return
array("select"=>preg_replace('~^(?:[^`]|`[^`]*`)*\s+AS\s+~isU','',get_val("SHOW CREATE VIEW ".table($B),1)));}function
collations(){$J=array();foreach(get_rows("SHOW COLLATION")as$K){if($K["Default"])$J[$K["Charset"]][-1]=$K["Collation"];else$J[$K["Charset"]][]=$K["Collation"];}ksort($J);foreach($J
as$w=>$X)sort($J[$w]);return$J;}function
information_schema($j){return($j=="information_schema")||(min_version(5.5)&&$j=="performance_schema");}function
error(){return
h(preg_replace('~^You have an error.*syntax to use~U',"Syntax error",connection()->error));}function
create_database($j,$c){return
queries("CREATE DATABASE ".idf_escape($j).($c?" COLLATE ".q($c):""));}function
drop_databases(array$i){$J=apply_queries("DROP DATABASE",$i,'Adminer\idf_escape');restart_session();set_session("dbs",null);return$J;}function
rename_database($B,$c){$J=false;if(create_database($B,$c)){$T=array();$hk=array();foreach(tables_list()as$R=>$U){if($U=='VIEW')$hk[]=$R;else$T[]=$R;}$J=(!$T&&!$hk)||move_tables($T,$hk,$B);drop_databases($J?array(DB):array());}return$J;}function
auto_increment(){$Ca=" PRIMARY KEY";if($_GET["create"]!=""&&$_POST["auto_increment_col"]){foreach(indexes($_GET["create"])as$u){if(in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"],$u["columns"],true)){$Ca="";break;}if($u["type"]=="PRIMARY")$Ca=" UNIQUE";}}return" AUTO_INCREMENT$Ca";}function
alter_table($R,$B,array$n,array$qd,$qb,$Ac,$c,$Ba,$E){$b=array();foreach($n
as$m){if($m[1]){$k=$m[1][3];if(preg_match('~ GENERATED~',$k)){$m[1][3]=(connection()->flavor=='maria'?"":$m[1][2]);$m[1][2]=$k;}$b[]=($R!=""?($m[0]!=""?"CHANGE ".idf_escape($m[0]):"ADD"):" ")." ".implode($m[1]).($R!=""?$m[2]:"");}else$b[]="DROP ".idf_escape($m[0]);}$b=array_merge($b,$qd);$P=($qb!==null?" COMMENT=".q($qb):"").($Ac?" ENGINE=".q($Ac):"").($c?" COLLATE ".q($c):"").($Ba!=""?" AUTO_INCREMENT=$Ba":"");if($E){$Tg=array();if($E["partition_by"]=='RANGE'||$E["partition_by"]=='LIST'){foreach($E["partition_names"]as$w=>$X){$Y=$E["partition_values"][$w];$Tg[]="\n  PARTITION ".idf_escape($X)." VALUES ".($E["partition_by"]=='RANGE'?"LESS THAN":"IN").($Y!=""?" ($Y)":" MAXVALUE");}}$P
.="\nPARTITION BY $E[partition_by]($E[partition])";if($Tg)$P
.=" (".implode(",",$Tg)."\n)";elseif($E["partitions"])$P
.=" PARTITIONS ".(+$E["partitions"]);}elseif($E===null)$P
.="\nREMOVE PARTITIONING";if($R=="")return
queries("CREATE TABLE ".table($B)." (\n".implode(",\n",$b)."\n)$P");if($R!=$B)$b[]="RENAME TO ".table($B);if($P)$b[]=ltrim($P);return($b?queries("ALTER TABLE ".table($R)."\n".implode(",\n",$b)):true);}function
alter_indexes($R,$b){$Xa=array();foreach($b
as$X)$Xa[]=($X[2]=="DROP"?"\nDROP INDEX ".idf_escape($X[1]):"\nADD $X[0] ".($X[0]=="PRIMARY"?"KEY ":"").($X[1]!=""?idf_escape($X[1])." ":"")."(".implode(", ",$X[2]).")");return
queries("ALTER TABLE ".table($R).implode(",",$Xa));}function
truncate_tables(array$T){return
apply_queries("TRUNCATE TABLE",$T);}function
drop_views(array$hk){return
queries("DROP VIEW ".implode(", ",array_map('Adminer\table',$hk)));}function
drop_tables(array$T){return
queries("DROP TABLE ".implode(", ",array_map('Adminer\table',$T)));}function
move_tables(array$T,array$hk,$aj){$Hh=array();foreach($T
as$R)$Hh[]=table($R)." TO ".idf_escape($aj).".".table($R);if(!$Hh||queries("RENAME TABLE ".implode(", ",$Hh))){$Yb=array();foreach($hk
as$R)$Yb[table($R)]=view($R);connection()->select_db($aj);$j=idf_escape(DB);foreach($Yb
as$B=>$gk){if(!queries("CREATE VIEW $B AS ".str_replace(" $j."," ",$gk["select"]))||!queries("DROP VIEW $j.$B"))return
false;}return
true;}return
false;}function
copy_tables(array$T,array$hk,$aj){queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");foreach($T
as$R){$B=($aj==DB?table("copy_$R"):idf_escape($aj).".".table($R));if(($_POST["overwrite"]&&!queries("\nDROP TABLE IF EXISTS $B"))||!queries("CREATE TABLE $B LIKE ".table($R))||!queries("INSERT INTO $B SELECT * FROM ".table($R)))return
false;foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($R,"%_\\")))as$K){$zj=$K["Trigger"];if(!queries("CREATE TRIGGER ".($aj==DB?idf_escape("copy_$zj"):idf_escape($aj).".".idf_escape($zj))." $K[Timing] $K[Event] ON $B FOR EACH ROW\n$K[Statement];"))return
false;}}foreach($hk
as$R){$B=($aj==DB?table("copy_$R"):idf_escape($aj).".".table($R));$gk=view($R);if(($_POST["overwrite"]&&!queries("DROP VIEW IF EXISTS $B"))||!queries("CREATE VIEW $B AS $gk[select]"))return
false;}return
true;}function
trigger($B,$R){if($B=="")return
array();$L=get_rows("SHOW TRIGGERS WHERE `Trigger` = ".q($B));return
reset($L);}function
triggers($R){$J=array();foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($R,"%_\\")))as$K)$J[$K["Trigger"]]=array($K["Timing"],$K["Event"]);return$J;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
routine($B,$U){$n=get_rows("SELECT
	PARAMETER_NAME field,
	DATA_TYPE type,
	REGEXP_REPLACE(DTD_IDENTIFIER, '^[^(]+\\\\(?|\\\\)$', '') length,
	REGEXP_REPLACE(DTD_IDENTIFIER, '^[^ ]+ ', '') `unsigned`,
	1 `null`,
	DTD_IDENTIFIER full_type,
	".($U=="FUNCTION"?"''":"PARAMETER_MODE")." `inout`,
	CHARACTER_SET_NAME collation
FROM information_schema.PARAMETERS
WHERE SPECIFIC_SCHEMA = DATABASE() AND ROUTINE_TYPE = '$U' AND SPECIFIC_NAME = ".q($B)."
ORDER BY ORDINAL_POSITION");$J=connection()->query("SELECT
	ROUTINE_COMMENT comment,
	CONCAT(IF(IS_DETERMINISTIC = 'YES', 'DETERMINISTIC\\n', ''), IF(SQL_DATA_ACCESS != 'CONTAINS SQL', CONCAT(SQL_DATA_ACCESS, '\\n'), ''), ROUTINE_DEFINITION) definition,
	'SQL' language
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = '$U' AND ROUTINE_NAME = ".q($B))->fetch_assoc();if($n&&$n[0]['field']=='')$J['returns']=array_shift($n);$J['fields']=$n;return$J;}function
routines(){return
get_rows("SELECT SPECIFIC_NAME, ROUTINE_NAME, ROUTINE_TYPE, DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE()");}function
routine_languages(){return
array();}function
routine_id($B,array$K){return
idf_escape($B);}function
last_id($I){return
get_val("SELECT LAST_INSERT_ID()");}function
explain(Db$f,$H){return$f->query("EXPLAIN ".(min_version(5.7)?"":"PARTITIONS ").$H);}function
found_rows(array$S,array$Z){return($Z||$S["Engine"]!="InnoDB"?null:$S["Rows"]);}function
create_sql($R,$Ba,$Hi){$J=get_val("SHOW CREATE TABLE ".table($R),1);if(!$Ba)$J=preg_replace('~ AUTO_INCREMENT=\d+~','',$J);return$J;}function
truncate_sql($R){return"TRUNCATE ".table($R);}function
use_sql($Pb,$Hi=""){$B=idf_escape($Pb);$J="";if(preg_match('~CREATE~',$Hi)&&($h=get_val("SHOW CREATE DATABASE $B",1))){set_utf8mb4($h);if($Hi=="DROP+CREATE")$J="DROP DATABASE IF EXISTS $B;\n";$J
.="$h;\n";}return$J."USE $B";}function
trigger_sql($R){$J="";foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($R,"%_\\")),null,"-- ")as$K)$J
.="\nCREATE TRIGGER ".idf_escape($K["Trigger"])." $K[Timing] $K[Event] ON ".table($K["Table"])." FOR EACH ROW\n$K[Statement];;\n";return$J;}function
show_variables(){return
get_rows("SHOW VARIABLES");}function
show_status(){return
get_rows("SHOW STATUS");}function
process_list(){return
get_rows("SHOW FULL PROCESSLIST");}function
convert_field(array$m){if(preg_match("~binary~",$m["type"]))return"HEX(".idf_escape($m["field"]).")";if($m["type"]=="bit")return"BIN(".idf_escape($m["field"])." + 0)";if($m["type"]=="vector")return(connection()->flavor=='maria'?"VEC_ToText":"VECTOR_TO_STRING")."(".idf_escape($m["field"]).")";if(preg_match("~geometry|point|linestring|polygon~",$m["type"]))return(min_version(8)?"ST_":"")."AsWKT(".idf_escape($m["field"]).")";}function
unconvert_field(array$m,$J){if(preg_match("~binary~",$m["type"]))$J="UNHEX($J)";if($m["type"]=="bit")$J="CONVERT(b$J, UNSIGNED)";if($m["type"]=="vector")$J=(connection()->flavor=='maria'?"VEC_FromText":"STRING_TO_VECTOR")."($J)";if(preg_match("~geometry|point|linestring|polygon~",$m["type"])){$ih=(min_version(8)?"ST_":"");$J=$ih."GeomFromText($J, $ih"."SRID($m[field]))";}return$J;}function
support($Yc){return
preg_match('~^(comment|columns|copy|database|drop_col|dump|event|indexes|kill|privileges|move_col|procedure|processlist|routine|sql|status|table|trigger|variables|view'.(min_version(8)?'|descidx':'').(min_version('8.0.16','10.2.1')?'|check':'').')$~',$Yc);}function
kill_process($s){return
queries("KILL ".number($s));}function
connection_id(){return"SELECT CONNECTION_ID()";}function
max_connections(){return
get_val("SELECT @@max_connections");}function
types(){return
array();}function
type_values($s){return"";}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($Vh,$g=null){return
true;}}define('Adminer\JUSH',Driver::$jush);define('Adminer\SERVER',"".$_GET[DRIVER]);define('Adminer\DB',"$_GET[db]");define('Adminer\ME',preg_replace('~\?.*~','',relative_uri()).'?'.(sid()?SID.'&':'').(SERVER!==null?DRIVER."=".urlencode(SERVER).'&':'').($_GET["ext"]?"ext=".urlencode($_GET["ext"]).'&':'').(isset($_GET["username"])?"username=".urlencode($_GET["username"]).'&':'').(DB!=""?'db='.urlencode(DB).'&'.(isset($_GET["ns"])?"ns=".urlencode($_GET["ns"])."&":""):''));function
page_header($kj,$l="",$Pa=array(),$lj=""){page_headers();if(is_ajax()&&$l){page_messages($l);exit;}if(!ob_get_level())ob_start('ob_gzhandler',4096);$mj=$kj.($lj!=""?": $lj":"");$nj=strip_tags($mj.(SERVER!=""&&SERVER!="localhost"?h(" - ".SERVER):"")." - ".adminer()->name());echo'<!DOCTYPE html>
<html lang="en" dir="ltr">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>',$nj,'</title>
<link rel="stylesheet" href="',h(preg_replace("~\\?.*~","",ME)."?file=default.css&version=5.5.1"),'">
';$Jb=adminer()->css();if(is_int(key($Jb)))$Jb=array_fill_keys($Jb,'light');$Ld=in_array('light',$Jb)||in_array('',$Jb);$Jd=in_array('dark',$Jb)||in_array('',$Jb);$Mb=($Ld?($Jd?null:false):($Jd?:null));$vf=" media='(prefers-color-scheme: dark)'";if($Mb!==false)echo"<link rel='stylesheet'".($Mb?"":$vf)." href='".h(preg_replace("~\\?.*~","",ME)."?file=dark.css&version=5.5.1")."'>\n";echo"<meta name='color-scheme' content='".($Mb===null?"light dark":($Mb?"dark":"light"))."'>\n",script_src(preg_replace("~\\?.*~","",ME)."?file=functions.js&version=5.5.1");if(adminer()->head($Mb))echo"<link rel='icon' href='data:image/gif;base64,R0lGODlhEAAQAJEAAAQCBPz+/PwCBAROZCH5BAEAAAAALAAAAAAQABAAAAI2hI+pGO1rmghihiUdvUBnZ3XBQA7f05mOak1RWXrNq5nQWHMKvuoJ37BhVEEfYxQzHjWQ5qIAADs='>\n","<link rel='apple-touch-icon' href='".h(preg_replace("~\\?.*~","",ME)."?file=logo.png&version=5.5.1")."'>\n";foreach($Jb
as$Rj=>$If){$_a=($If=='dark'&&!$Mb?$vf:($If=='light'&&$Jd?" media='(prefers-color-scheme: light)'":""));echo"<link rel='stylesheet'$_a href='".h($Rj)."'>\n";}echo"\n<body class='".'ltr'." nojs";adminer()->bodyClass();echo"'>\n",script("mixin(document.body, {onkeydown: bodyKeydown, onclick: bodyClick".(isset($_COOKIE["adminer_version"])?"":", onload: partial(verifyVersion, '".VERSION."')")."});
document.body.classList.replace('nojs', 'js');
if (!window.isSecureContext) {
	document.body.classList.add('insecure');
}
const offlineMessage = '".js_escape('You are offline.')."';
const thousandsSeparator = '".js_escape(',')."';"),"<div id='help' class='jush-".JUSH." jsonly hidden'></div>\n",script("mixin(qs('#help'), {onmouseover: () => { helpOpen = 1; }, onmouseout: helpMouseout});"),"<div id='content'>\n","<span id='menuopen' class='jsonly'>".icon("move","","menu","")."</span>".script("qs('#menuopen').onclick = event => { qs('#foot').classList.toggle('foot'); event.stopPropagation(); }");if($Pa!==null){$z=substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb"><a href="'.h($z?:".").'">'.get_driver(DRIVER).'</a> » ';$z=substr(preg_replace('~\b(db|ns)=[^&]*&~','',ME),0,-1);$N=adminer()->serverName(SERVER);$N=($N!=""?$N:'Server');if($Pa===false)echo"$N\n";else{echo"<a href='".h($z)."' accesskey='1' title='Alt+Shift+1'>$N</a> » ";if($_GET["ns"]!=""||(DB!=""&&is_array($Pa)))echo'<a href="'.h($z."&db=".urlencode(DB).(support("scheme")?"&ns=":"")).'">'.h(DB).'</a> » ';if(is_array($Pa)){if($_GET["ns"]!="")echo'<a href="'.h(substr(ME,0,-1)).'">'.h($_GET["ns"]).'</a> » ';foreach($Pa
as$w=>$X){$ac=(is_array($X)?$X[1]:h($X));if($ac!="")echo"<a href='".h(ME."$w=").urlencode(is_array($X)?$X[0]:$X)."'>$ac</a> » ";}}echo"$kj\n";}}echo"<h2>$mj</h2>\n","<div id='ajaxstatus' class='jsonly hidden'></div>\n";restart_session();page_messages($l);$i=&get_session("dbs");if(DB!=""&&$i&&!in_array(DB,$i,true))$i=null;stop_session();define('Adminer\PAGE_HEADER',1);}function
page_headers(){header("Content-Type: text/html; charset=utf-8");header("Cache-Control: no-cache");header("X-Frame-Options: deny");header("X-XSS-Protection: 0");header("X-Content-Type-Options: nosniff");header("Referrer-Policy: origin-when-cross-origin");foreach(adminer()->csp(csp())as$Ib){$Nd=array();foreach($Ib
as$w=>$X)$Nd[]="$w $X";header("Content-Security-Policy: ".implode("; ",$Nd));}adminer()->headers();}function
csp(){return
array(array("script-src"=>"'self' 'unsafe-inline' 'nonce-".get_nonce()."' 'strict-dynamic'","connect-src"=>"'self' https://www.adminer.org","frame-src"=>"'none'","object-src"=>"'none'","base-uri"=>"'none'","form-action"=>"'self'",),);}function
get_nonce(){static$Wf;if(!$Wf)$Wf=base64_encode(rand_string());return$Wf;}function
page_messages($l){$Qj=preg_replace('~^[^?]*~','',$_SERVER["REQUEST_URI"]);$Bf=idx($_SESSION["messages"],$Qj);if($Bf){echo"<div class='message'>".implode("</div>\n<div class='message'>",$Bf)."</div>".script("messagesPrint();");unset($_SESSION["messages"][$Qj]);}if($l)echo"<div class='error'>$l</div>\n";if(adminer()->error)echo"<div class='error'>".adminer()->error."</div>\n";}function
page_footer($Hf=""){echo"</div>\n\n<div id='foot' class='foot'>\n<div id='menu'>\n";adminer()->navigation($Hf);echo"</div>\n";if($Hf!="auth")echo'<form action="" method="post">
<p class="logout">
<span>',h($_GET["username"])."\n",'</span>
<input type="submit" name="logout" value="Logout" id="logout">
',input_token(),'</form>
';echo"</div>\n\n",script("setupSubmitHighlight(document);");}function
int32($Nf){while($Nf>=2147483648)$Nf-=4294967296;while($Nf<=-2147483649)$Nf+=4294967296;return(int)$Nf;}function
long2str(array$W,$jk){$Th='';foreach($W
as$X)$Th
.=pack('V',$X);if($jk)return
substr($Th,0,end($W));return$Th;}function
str2long($Th,$jk){$W=array_values(unpack('V*',str_pad($Th,4*ceil(strlen($Th)/4),"\0")));if($jk)$W[]=strlen($Th);return$W;}function
xxtea_mx($pk,$ok,$Ki,$Ie){return
int32((($pk>>5&0x7FFFFFF)^$ok<<2)+(($ok>>3&0x1FFFFFFF)^$pk<<4))^int32(($Ki^$ok)+($Ie^$pk));}function
encrypt_string($Fi,$w){if($Fi=="")return"";$w=array_values(unpack("V*",pack("H*",md5($w))));$W=str2long($Fi,true);$Nf=count($W)-1;$pk=$W[$Nf];$ok=$W[0];$th=floor(6+52/($Nf+1));$Ki=0;while($th-->0){$Ki=int32($Ki+0x9E3779B9);$tc=$Ki>>2&3;for($Ig=0;$Ig<$Nf;$Ig++){$ok=$W[$Ig+1];$Mf=xxtea_mx($pk,$ok,$Ki,$w[$Ig&3^$tc]);$pk=int32($W[$Ig]+$Mf);$W[$Ig]=$pk;}$ok=$W[0];$Mf=xxtea_mx($pk,$ok,$Ki,$w[$Ig&3^$tc]);$pk=int32($W[$Nf]+$Mf);$W[$Nf]=$pk;}return
long2str($W,false);}function
decrypt_string($Fi,$w){if($Fi=="")return"";if(!$w)return
false;$w=array_values(unpack("V*",pack("H*",md5($w))));$W=str2long($Fi,false);$Nf=count($W)-1;$pk=$W[$Nf];$ok=$W[0];$th=floor(6+52/($Nf+1));$Ki=int32($th*0x9E3779B9);while($Ki){$tc=$Ki>>2&3;for($Ig=$Nf;$Ig>0;$Ig--){$pk=$W[$Ig-1];$Mf=xxtea_mx($pk,$ok,$Ki,$w[$Ig&3^$tc]);$ok=int32($W[$Ig]-$Mf);$W[$Ig]=$ok;}$pk=$W[$Nf];$Mf=xxtea_mx($pk,$ok,$Ki,$w[$Ig&3^$tc]);$ok=int32($W[0]-$Mf);$W[0]=$ok;$Ki=int32($Ki-0x9E3779B9);}return
long2str($W,true);}$Zg=array();if($_COOKIE["adminer_permanent"]){foreach(explode(" ",$_COOKIE["adminer_permanent"])as$X){list($w)=explode(":",$X);$Zg[$w]=$X;}}function
add_invalid_login(){$Ia=get_temp_dir()."/adminer-invalid";foreach(glob("$Ia*")?:array($Ia)as$fd){$p=file_open_lock($fd);if($p)break;}if(!$p)$p=file_open_lock("$Ia-".rand_string());if(!$p)return;$ze=json_decode(stream_get_contents($p),true);$hj=time();if($ze){foreach($ze
as$_e=>$X){if($X[0]<$hj)unset($ze[$_e]);}}$ye=&$ze[adminer()->bruteForceKey()];if(!$ye)$ye=array($hj+30*60,0);$ye[1]++;file_write_unlock($p,json_encode($ze));}function
check_invalid_login(array&$Zg){$ze=array();foreach(glob(get_temp_dir()."/adminer-invalid*")as$fd){$p=file_open_lock($fd);if($p){$ze=json_decode(stream_get_contents($p),true);file_unlock($p);break;}}$ye=idx($ze,adminer()->bruteForceKey(),array());$Vf=($ye[1]>29?$ye[0]-time():0);if($Vf>0)auth_error(lang_format(array('Too many unsuccessful logins, try again in %d minute.','Too many unsuccessful logins, try again in %d minutes.'),ceil($Vf/60)),$Zg);}$Aa=$_POST["auth"];if($Aa){session_regenerate_id();$ek=$Aa["driver"];$N=$Aa["server"];$V=$Aa["username"];$F=(string)$Aa["password"];$j=$Aa["db"];set_password($ek,$N,$V,$F);$_SESSION["db"][$ek][$N][$V][$j]=true;if($Aa["permanent"]){$w=implode("-",array_map('base64_encode',array($ek,$N,$V,$j)));$oh=adminer()->permanentLogin(true);$Zg[$w]="$w:".base64_encode($oh?encrypt_string($F,$oh):"");cookie("adminer_permanent",implode(" ",$Zg));}if(count($_POST)==1||DRIVER!=$ek||SERVER!=$N||$_GET["username"]!==$V||DB!=$j)redirect(auth_url($ek,$N,$V,$j));}elseif($_POST["logout"]&&(!$_SESSION["token"]||verify_token())){foreach(array("pwds","db","dbs","queries")as$w)set_session($w,null);unset_permanent($Zg);redirect(substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1),'Logout successful.'.' '.'Thanks for using Adminer, consider <a href="https://www.adminer.org/en/donation/">donating</a>.');}elseif($Zg&&!$_SESSION["pwds"]){session_regenerate_id();$oh=adminer()->permanentLogin();foreach($Zg
as$w=>$X){list(,$fb)=explode(":",$X);list($ek,$N,$V,$j)=array_map('base64_decode',explode("-",$w));set_password($ek,$N,$V,decrypt_string(base64_decode($fb),$oh));$_SESSION["db"][$ek][$N][$V][$j]=true;}}function
unset_permanent(array&$Zg){foreach($Zg
as$w=>$X){list($ek,$N,$V,$j)=array_map('base64_decode',explode("-",$w));if($ek==DRIVER&&$N==SERVER&&$V==$_GET["username"]&&$j==DB)unset($Zg[$w]);}cookie("adminer_permanent",implode(" ",$Zg));}function
auth_error($l,array&$Zg){$ni=session_name();if(isset($_GET["username"])){header("HTTP/1.1 403 Forbidden");if(($_COOKIE[$ni]||$_GET[$ni])&&!$_SESSION["token"])$l='Session expired, please login again.';else{restart_session();add_invalid_login();$F=get_password();if($F!==null){if($F===false)$l
.=($l?'<br>':'').sprintf('Master password expired. <a href="https://www.adminer.org/en/extension/"%s>Implement</a> %s method to make it permanent.',target_blank(),'<code>permanentLogin()</code>');set_password(DRIVER,SERVER,$_GET["username"],null);}unset_permanent($Zg);}}if(!$_COOKIE[$ni]&&$_GET[$ni]&&ini_bool("session.use_only_cookies"))$l='Session support must be enabled.';$Lg=session_get_cookie_params();cookie("adminer_key",($_COOKIE["adminer_key"]?:rand_string()),$Lg["lifetime"]);if(!$_SESSION["token"])$_SESSION["token"]=rand(1,1e6);page_header('Login',$l,null);echo"<form action='' method='post'>\n","<div>";if(hidden_fields($_POST,array("auth")))echo"<p class='message'>".'The action will be performed after successful login with the same credentials.'."\n";echo"</div>\n";adminer()->loginForm();echo"</form>\n";page_footer("auth");exit;}if(isset($_GET["username"])&&!class_exists('Adminer\Db')){unset($_SESSION["pwds"][DRIVER]);unset_permanent($Zg);page_header('No extension',sprintf('None of the supported PHP extensions (%s) are available.',implode(", ",Driver::$extensions)),false);page_footer("auth");exit;}$f='';if(isset($_GET["username"])&&is_string(get_password())){list($Td,$dh)=host_port(SERVER);if(preg_match('~[^-\w.:/]~',$Td.$dh))auth_error('Invalid server.',$Zg);if(preg_match('~^-?\d+~',$dh,$A)&&($A[0]<1024||$A[0]>65535))auth_error('Connecting to privileged ports is not allowed.',$Zg);check_invalid_login($Zg);$Hb=adminer()->credentials();$f=Driver::connect($Hb[0],$Hb[1],$Hb[2]);if(is_object($f)){Db::$instance=$f;Driver::$instance=new
Driver($f);if($f->flavor)save_settings(array("vendor-".DRIVER."-".SERVER=>get_driver(DRIVER)));}}$ff=null;if(!is_object($f)||($ff=adminer()->login($_GET["username"],get_password()))!==true){$l=(is_string($f)?nl_br(h($f)):(is_string($ff)?$ff:'Invalid credentials.')).(preg_match('~^ | $~',get_password())?'<br>'.'There is a space in the input password which might be the cause.':'');auth_error($l,$Zg);}if($_POST["logout"]&&$_SESSION["token"]&&!verify_token()){page_header('Logout','Invalid CSRF token. Send the form again.');page_footer("db");exit;}if(!$_SESSION["token"])$_SESSION["token"]=rand(1,1e6);stop_session(true);if($Aa&&$_POST["token"])$_POST["token"]=get_token();$l='';if($_POST){if(!verify_token()){$re="max_input_vars";$tf=ini_get($re);if(extension_loaded("suhosin")){foreach(array("suhosin.request.max_vars","suhosin.post.max_vars")as$w){$X=ini_get($w);if($X&&(!$tf||$X<$tf)){$re=$w;$tf=$X;}}}$l=(!$_POST["token"]&&$tf?sprintf('Maximum number of allowed fields exceeded. Please increase %s.',"'$re'"):'Invalid CSRF token. Send the form again.'.' '.'If you did not send this request from Adminer then close this page.');}}elseif($_SERVER["REQUEST_METHOD"]=="POST"){$l=sprintf('Too big POST data. Reduce the data or increase the %s configuration directive.',"'post_max_size'");if(isset($_GET["sql"]))$l
.=' '.'You can upload a big SQL file via FTP and import it from server.';}function
print_select_result($I,$g=null,array$yg=array(),$y=0){$bf=array();$v=array();$e=array();$Na=array();$Ej=array();$J=array();for($r=0;(!$y||$r<$y)&&($K=$I->fetch_row());$r++){if(!$r){echo"<div class='scrollable'>\n","<table class='nowrap odds'>\n","<thead><tr>";for($Fe=0;$Fe<count($K);$Fe++){$m=$I->fetch_field();$B=$m->name;$xg=(isset($m->orgtable)?$m->orgtable:"");$wg=(isset($m->orgname)?$m->orgname:$B);if($yg&&JUSH=="sql")$bf[$Fe]=($B=="table"?"table=":($B=="possible_keys"?"indexes=":null));elseif($xg!=""){if(isset($m->table))$J[$m->table]=$xg;if(!isset($v[$xg])){$v[$xg]=array();foreach(indexes($xg,$g)as$u){if($u["type"]=="PRIMARY"){$v[$xg]=array_flip($u["columns"]);break;}}$e[$xg]=$v[$xg];}if(isset($e[$xg][$wg])){unset($e[$xg][$wg]);$v[$xg][$wg]=$Fe;$bf[$Fe]=$xg;}}if($m->charsetnr==63)$Na[$Fe]=true;$Ej[$Fe]=$m->type;echo"<th".($xg!=""||$m->name!=$wg?" title='".h(($xg!=""?"$xg.":"").$wg)."'":"").">".h($B).($yg?doc_link(array('sql'=>"explain-output.html#explain_".strtolower($B),'mariadb'=>"explain/#the-columns-in-explain-select",)):"");}echo"</thead>\n";}echo"<tr>";foreach($K
as$w=>$X){$z="";if(isset($bf[$w])&&!$e[$bf[$w]]){if($yg&&JUSH=="sql"){$R=$K[array_search("table=",$bf)];$z=ME.$bf[$w].urlencode($yg[$R]!=""?$yg[$R]:$R);}else{$z=ME."edit=".urlencode($bf[$w]);foreach($v[$bf[$w]]as$jb=>$Fe){if($K[$Fe]===null){$z="";break;}$z
.="&where".urlencode("[".bracket_escape($jb)."]")."=".urlencode($K[$Fe]);}}}$m=array('type'=>($Na[$w]?'blob':($Ej[$w]==254?'char':'')),);$X=select_value($X,$z,$m,null);echo"<td".($Ej[$w]<=9||$Ej[$w]==246?" class='number'":"").">$X";}}echo($r?"</table>\n</div>":"<p class='message'>".'No rows.')."\n";return$J;}function
referencable_primary($fi){$J=array();foreach(table_status('',true)as$Ri=>$R){if($Ri!=$fi&&fk_support($R)){foreach(fields($Ri)as$m){if($m["primary"]){if($J[$Ri]){unset($J[$Ri]);break;}$J[$Ri]=$m;}}}}return$J;}function
textarea($B,$Y,$L=10,$mb=80){echo"<textarea name='".h($B)."' rows='$L' cols='$mb' class='sqlarea jush-".JUSH."' spellcheck='false' wrap='off'>";if(is_array($Y)){foreach($Y
as$X)echo
h($X[0])."\n\n\n";}else
echo
h($Y);echo"</textarea>";}function
select_input($_a,array$sg,$Y="",$mg="",$ah=""){$Zi=($sg?"select":"input");return"<$Zi$_a".($sg?"><option value=''>$ah".optionlist($sg,$Y,true)."</select>":" size='10' value='".h($Y)."' placeholder='$ah'>").($mg?script("qsl('$Zi').onchange = $mg;",""):"");}function
json_row($w,$X=null,$Jc=true){static$id=true;if($id)echo"{";if($w!=""){echo($id?"":",")."\n\t\"".addcslashes($w,"\r\n\t\"\\/").'": '.($X!==null?($Jc?'"'.addcslashes($X,"\r\n\"\\/").'"':$X):'null');$id=false;}else{echo"\n}\n";$id=true;}}function
edit_type($w,array$m,array$lb,array$sd=array(),array$Vc=array()){$U=$m["type"];echo"<td><select name='".h($w)."[type]' class='type' aria-labelledby='label-type'>";if($U&&!array_key_exists($U,driver()->types())&&!isset($sd[$U])&&!in_array($U,$Vc))$Vc[]=$U;$Gi=driver()->structuredTypes();if($sd)$Gi['Foreign keys']=$sd;echo
optionlist(array_merge($Vc,$Gi),$U),"</select><td>","<input name='".h($w)."[length]' value='".h($m["length"])."' size='3'".(!$m["length"]&&preg_match('~var(char|binary)$~',$U)?" class='required'":"")." aria-labelledby='label-length'>","<td class='options'>",($lb?"<input list='collations' name='".h($w)."[collation]'".(preg_match('~(char|text|enum|set)$~',$U)?"":" class='hidden'")." value='".h($m["collation"])."' placeholder='(".'collation'.")'>":''),(driver()->unsigned?"<select name='".h($w)."[unsigned]'".(!$U||preg_match(number_type(),$U)?"":" class='hidden'").'><option>'.optionlist(driver()->unsigned,$m["unsigned"]).'</select>':''),(isset($m['on_update'])?"<select name='".h($w)."[on_update]'".(preg_match('~timestamp|datetime~',$U)?"":" class='hidden'").'>'.optionlist(array(""=>"(".'ON UPDATE'.")","CURRENT_TIMESTAMP"),(preg_match('~^CURRENT_TIMESTAMP~i',$m["on_update"])?"CURRENT_TIMESTAMP":$m["on_update"])).'</select>':''),($sd?"<select name='".h($w)."[on_delete]'".(preg_match("~`~",$U)?"":" class='hidden'")."><option value=''>(".'ON DELETE'.")".optionlist(explode("|",driver()->onActions),$m["on_delete"])."</select> ":" ");}function
process_length($x){$Ec=driver()->enumLength;return(preg_match("~^\\s*\\(?\\s*$Ec(?:\\s*,\\s*$Ec)*+\\s*\\)?\\s*\$~",$x)&&preg_match_all("~$Ec~",$x,$lf)?"(".implode(",",$lf[0]).")":preg_replace('~^[0-9].*~','(\0)',preg_replace('~[^-0-9,+()[\]]~','',$x)));}function
process_type(array$m,$kb="COLLATE"){return" $m[type]".process_length($m["length"]).(preg_match(number_type(),$m["type"])&&in_array($m["unsigned"],driver()->unsigned)?" $m[unsigned]":"").(preg_match('~char|text|enum|set~',$m["type"])&&$m["collation"]?" $kb ".(JUSH=="mssql"?$m["collation"]:q($m["collation"])):"");}function
process_field(array$m,array$Dj){if($m["on_update"])$m["on_update"]=str_ireplace("current_timestamp()","CURRENT_TIMESTAMP",$m["on_update"]);return
array(idf_escape(trim($m["field"])),process_type($Dj),($m["null"]?" NULL":" NOT NULL"),default_value($m),(preg_match('~timestamp|datetime~',$m["type"])&&$m["on_update"]?" ON UPDATE $m[on_update]":""),(support("comment")&&$m["comment"]!=""?" COMMENT ".q($m["comment"]):""),($m["auto_increment"]?auto_increment():null),);}function
default_value(array$m){if($m["default"]===null)return"";$k=str_replace("\r","",$m["default"]);$zd=$m["generated"];return(in_array($zd,driver()->generated)?(JUSH=="mssql"?" AS ($k)".($zd=="VIRTUAL"?"":" $zd"):" GENERATED ALWAYS AS ($k) $zd"):(preg_match('~^GENERATED ~i',$k)?" $k":" DEFAULT ".(preg_match('~char|binary|text|json|enum|set~',$m["type"])||preg_match('~^(?![a-z])~i',$k)?(JUSH=="sql"&&preg_match('~text|json~',$m["type"])?"(".q($k).")":q($k)):str_ireplace("current_timestamp()","CURRENT_TIMESTAMP",(JUSH=="sqlite"?"($k)":$k)))));}function
type_class($U){foreach(array('char'=>'text','date'=>'time|year','binary'=>'blob','enum'=>'set',)as$w=>$X){if(preg_match("~$w|$X~",$U))return" class='$w'";}}function
edit_fields(array$n,array$lb,$U="TABLE",array$sd=array()){$n=array_values($n);$Vb=(($_POST?$_POST["defaults"]:get_setting("defaults"))?"":" class='hidden'");$rb=(($_POST?$_POST["comments"]:get_setting("comments"))?"":" class='hidden'");echo"<thead><tr>\n",($U=="PROCEDURE"?"<td>":""),"<th id='label-name'>".($U=="TABLE"?'Column name':'Parameter name'),"<td id='label-type'>".'Type'."<textarea id='enum-edit' rows='4' cols='12' wrap='off' style='display: none;'></textarea>".script("qs('#enum-edit').onblur = editingLengthBlur;"),"<td id='label-length'>".'Length',"<td>".'Options';if($U=="TABLE")echo"<td id='label-null'>NULL\n","<td><input type='radio' name='auto_increment_col' value=''><abbr id='label-ai' title='".'Auto Increment'."'>AI</abbr>",doc_link(array('sql'=>"example-auto-increment.html",'mariadb'=>"auto_increment/",'sqlite'=>"autoinc.html",'pgsql'=>"datatype-numeric.html#DATATYPE-SERIAL",'mssql'=>"t-sql/statements/create-table-transact-sql-identity-property",)),"<td id='label-default'$Vb>".'Default value',(support("comment")?"<td id='label-comment'$rb>".'Comment':"");echo"<td>".icon("plus","add[".(support("move_col")?0:count($n))."]","+",'Add next'),"</thead>\n<tbody>\n",script("mixin(qsl('tbody'), {onclick: editingClick, onkeydown: editingKeydown, oninput: editingInput});");foreach($n
as$r=>$m){$r++;$zg=$m[($_POST?"orig":"field")];$dc=(isset($_POST["add"][$r-1])||(isset($m["field"])&&!idx($_POST["drop_col"],$r)))&&(support("drop_col")||$zg=="");echo"<tr".($dc?"":" style='display: none;'").">\n",($U=="PROCEDURE"?"<td>".html_select("fields[$r][inout]",explode("|",driver()->inout),$m["inout"]):"")."<th>",(support("move_col")?icon("move","","↕",'Move')." ":"");if($dc)echo"<input name='fields[$r][field]' value='".h($m["field"])."' data-maxlength='64' autocapitalize='off' aria-labelledby='label-name'".(isset($_POST["add"][$r-1])?" autofocus":"").">";echo
input_hidden("fields[$r][orig]",$zg);edit_type("fields[$r]",$m,$lb,$sd);if($U=="TABLE"){echo"<td>".checkbox("fields[$r][null]",1,$m["null"],"","","block","label-null"),"<td><label class='block'><input type='radio' name='auto_increment_col' value='$r'".($m["auto_increment"]?" checked":"")." aria-labelledby='label-ai'></label>","<td$Vb>".(driver()->generated?html_select("fields[$r][generated]",array_merge(array("","DEFAULT"),driver()->generated),$m["generated"])." ":checkbox("fields[$r][generated]",1,$m["generated"],"","","","label-default"));$_a=" name='fields[$r][default]' aria-labelledby='label-default'";$Y=h($m["default"]);echo(preg_match('~\n~',$m["default"])?"<textarea$_a rows='2' cols='30' style='vertical-align: bottom;'>\n$Y</textarea>":"<input$_a value='$Y'>"),(support("comment")?"<td$rb><input name='fields[$r][comment]' value='".h($m["comment"])."' data-maxlength='".(min_version(5.5)?1024:255)."' aria-labelledby='label-comment'>":"");}echo"<td>",(support("move_col")?icon("plus","add[$r]","+",'Add next')." ":""),($zg==""||support("drop_col")?icon("cross","drop_col[$r]","x",'Remove'):"");}}function
process_fields(array&$n){if($_POST["add"]){$n=array_values($n);array_splice($n,key($_POST["add"]),0,array(array()));}return$_POST["add"]||$_POST["drop_col"];}function
normalize_enum(array$A){$X=$A[0];return"'".str_replace("'","''",addcslashes(stripcslashes(str_replace($X[0].$X[0],$X[0],substr($X,1,-1))),'\\'))."'";}function
grant($Ad,array$qh,$e,$jg){if(!$qh)return
true;if($qh==array("ALL PRIVILEGES","GRANT OPTION"))return($Ad=="GRANT"?queries("$Ad ALL PRIVILEGES$jg WITH GRANT OPTION"):queries("$Ad ALL PRIVILEGES$jg")&&queries("$Ad GRANT OPTION$jg"));return
queries("$Ad ".preg_replace('~(GRANT OPTION)\([^)]*\)~','\1',implode("$e, ",$qh).$e).$jg);}function
drop_create($nc,$h,$pc,$dj,$rc,$_,$Af,$zf,$_f,$hg,$Sf){if($_POST["drop"])query_redirect($nc,$_,$Af);elseif($hg=="")query_redirect($h,$_,$_f);elseif($hg!=$Sf){$Gb=queries($h);queries_redirect($_,$zf,$Gb&&queries($nc));if($Gb)queries($pc);}else
queries_redirect($_,$zf,queries($dj)&&queries($rc)&&queries($nc)&&queries($h));}function
create_trigger($jg,array$K){$jj=" $K[Timing] $K[Event]".(preg_match('~ OF~',$K["Event"])?" $K[Of]":"");return"CREATE TRIGGER ".idf_escape($K["Trigger"]).(JUSH=="mssql"?$jg.$jj:$jj.$jg).rtrim(" $K[Type]\n$K[Statement]",";").";";}function
create_routine($Ph,array$K){$O=array();$n=(array)$K["fields"];ksort($n);foreach($n
as$m){if($m["field"]!="")$O[]=(preg_match("~^(".driver()->inout.")\$~",$m["inout"])?"$m[inout] ":"").idf_escape($m["field"]).process_type($m,"CHARACTER SET");}$Xb=rtrim($K["definition"],";");return"CREATE $Ph ".idf_escape(trim($K["name"]))." (".implode(", ",$O).")".($Ph=="FUNCTION"?" RETURNS".process_type($K["returns"],"CHARACTER SET"):"").($K["language"]?" LANGUAGE $K[language]":"").(JUSH=="pgsql"?" AS ".q($Xb):"\n$Xb;");}function
remove_definer($H){return
preg_replace('~^([A-Z =]+) DEFINER=`'.preg_replace('~@(.*)~','`@`(%|\1)',logged_user()).'`~','\1',$H);}function
format_foreign_key(array$o){$j=$o["db"];$Xf=$o["ns"];return" FOREIGN KEY (".implode(", ",array_map('Adminer\idf_escape',$o["source"])).") REFERENCES ".($j!=""&&$j!=$_GET["db"]?idf_escape($j).".":"").($Xf!=""&&$Xf!=$_GET["ns"]?idf_escape($Xf).".":"").idf_escape($o["table"])." (".implode(", ",array_map('Adminer\idf_escape',$o["target"])).")".(preg_match("~^(".driver()->onActions.")\$~",$o["on_delete"])?" ON DELETE $o[on_delete]":"").(preg_match("~^(".driver()->onActions.")\$~",$o["on_update"])?" ON UPDATE $o[on_update]":"").($o["deferrable"]?" $o[deferrable]":"");}function
tar_file($fd,$oj){$J=pack("a100a8a8a8a12a12",$fd,644,0,0,decoct($oj->size),decoct(time()));$eb=8*32;for($r=0;$r<strlen($J);$r++)$eb+=ord($J[$r]);$J
.=sprintf("%06o",$eb)."\0 ";echo$J,str_repeat("\0",512-strlen($J));$oj->send();echo
str_repeat("\0",511-($oj->size+511)%512);}function
doc_link(array$Wg,$ej="<sup>?</sup>"){$li=connection()->server_info;$fk=preg_replace('~^(\d\.?\d).*~s','\1',$li);$Sj=array('sql'=>"https://dev.mysql.com/doc/refman/$fk/en/",'sqlite'=>"https://www.sqlite.org/",'pgsql'=>"https://www.postgresql.org/docs/".(connection()->flavor=='cockroach'?"current":$fk)."/",'mssql'=>"https://learn.microsoft.com/en-us/sql/",'oracle'=>"https://www.oracle.com/pls/topic/lookup?ctx=db".preg_replace('~^.* (\d+)\.(\d+)\.\d+\.\d+\.\d+.*~s','\1\2',$li)."&id=",);if(connection()->flavor=='maria'){$Sj['sql']="https://mariadb.com/kb/en/";$Wg['sql']=(isset($Wg['mariadb'])?$Wg['mariadb']:str_replace(".html","/",$Wg['sql']));}return($Wg[JUSH]?"<a href='".h($Sj[JUSH].$Wg[JUSH].(JUSH=='mssql'?"?view=sql-server-ver$fk":""))."'".target_blank().">$ej</a>":"");}function
db_size($j){if(!connection()->select_db($j))return"?";$J=0;foreach(table_status()as$S)$J+=$S["Data_length"]+$S["Index_length"];return
format_number($J);}function
set_utf8mb4($h){static$O=false;if(!$O&&preg_match('~\butf8mb4~i',$h)){$O=true;echo"SET NAMES ".charset(connection()).";\n\n";}}if(isset($_GET["status"]))$_GET["variables"]=$_GET["status"];if(isset($_GET["import"]))$_GET["sql"]=$_GET["import"];if(!(DB!=""?connection()->select_db(DB):isset($_GET["sql"])||isset($_GET["dump"])||isset($_GET["database"])||isset($_GET["processlist"])||isset($_GET["privileges"])||isset($_GET["user"])||isset($_GET["variables"])||$_GET["script"]=="connect"||$_GET["script"]=="kill")){if(DB!=""||$_GET["refresh"]){restart_session();set_session("dbs",null);}if(DB!=""){header("HTTP/1.1 404 Not Found");page_header('Database'.": ".h(DB),'Invalid database.',true);}else{if($_POST["db"]&&!$l)queries_redirect(substr(ME,0,-1),'Databases have been dropped.',drop_databases($_POST["db"]));page_header('Select database',$l,false);echo"<p class='links'>\n";foreach(array('database'=>'Create database','privileges'=>'Privileges','processlist'=>'Process list','variables'=>'Variables','status'=>'Status',)as$w=>$X){if(support($w))echo"<a href='".h(ME)."$w='>$X</a>\n";}echo"<p>".sprintf('%s version: %s through PHP extension %s',get_driver(DRIVER),"<b>".h(connection()->server_info)."</b>","<b>".connection()->extension."</b>")."\n","<p>".sprintf('Logged as: %s',"<b>".h(logged_user())."</b>")."\n";$i=adminer()->databases();if($i){$Xh=support("scheme");$lb=collations();echo"<form action='' method='post'>\n","<table class='checkable odds'>\n",script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});"),"<thead><tr>".(support("database")?"<td>":"")."<th>".'Database'.(get_session("dbs")!==null?" - <a href='".h(ME)."refresh=1'>".'Refresh'."</a>":"")."<td>".'Collation'."<td>".'Tables'."<td>".'Size'." - <a href='".h(ME)."dbsize=1'>".'Compute'."</a>".script("qsl('a').onclick = partial(ajaxSetHtml, '".js_escape(ME)."script=connect');","")."</thead>\n";$i=($_GET["dbsize"]?count_tables($i):array_flip($i));foreach($i
as$j=>$T){$Oh=h(ME)."db=".urlencode($j);$s=h("Db-".$j);echo"<tr>".(support("database")?"<td>".checkbox("db[]",$j,in_array($j,(array)$_POST["db"]),"","","",$s):""),"<th><a href='$Oh' id='$s'>".h($j)."</a>";$c=h(db_collation($j,$lb));echo"<td>".(support("database")?"<a href='$Oh".($Xh?"&amp;ns=":"")."&amp;database=' title='".'Alter database'."'>$c</a>":$c),"<td align='right'><a href='$Oh&amp;schema=' id='tables-".h($j)."' title='".'Database schema'."'>".($_GET["dbsize"]?$T:"?")."</a>","<td align='right' id='size-".h($j)."'>".($_GET["dbsize"]?db_size($j):"?"),"\n";}echo"</table>\n",(support("database")?"<div class='footer'><div>\n"."<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>\n".input_hidden("all").script("qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^db/)); };")."<input type='submit' name='drop' value='".'Drop'."'>".confirm()."\n"."</div></fieldset>\n"."</div></div>\n":""),input_token(),"</form>\n",script("tableCheck();");}if(!empty(adminer()->plugins)){echo"<div class='plugins'>\n","<h3>".'Loaded plugins'."</h3>\n<ul>\n";foreach(adminer()->plugins
as$bh){$bc=(method_exists($bh,'description')?$bh->description():"");if(!$bc){$Dh=new
\ReflectionObject($bh);if(preg_match('~^/[\s*]+(.+)~',$Dh->getDocComment(),$A))$bc=$A[1];}$Yh=(method_exists($bh,'screenshot')?$bh->screenshot():"");echo"<li><b>".get_class($bh)."</b>".h($bc?": $bc":"").($Yh?" (<a href='".h($Yh)."'".target_blank().">".'screenshot'."</a>)":"")."\n";}echo"</ul>\n";adminer()->pluginsLinks();echo"</div>\n";}}page_footer("db");exit;}if(support("scheme")){if(DB!=""&&$_GET["ns"]!==""){if(!isset($_GET["ns"]))redirect(preg_replace('~ns=[^&]*&~','',ME)."ns=".get_schema());if(!set_schema($_GET["ns"])){header("HTTP/1.1 404 Not Found");page_header('Schema'.": ".h($_GET["ns"]),'Invalid schema.',true);page_footer("ns");exit;}}}adminer()->afterConnect();class
TmpFile{private$handler;var$size;function
__construct(){$this->handler=tmpfile();}function
write($Ab){$this->size+=strlen($Ab);fwrite($this->handler,$Ab);}function
send(){fseek($this->handler,0);fpassthru($this->handler);fclose($this->handler);}}if(isset($_GET["select"])&&($_POST["edit"]||$_POST["clone"])&&!$_POST["save"])$_GET["edit"]=$_GET["select"];if(isset($_GET["callf"]))$_GET["call"]=$_GET["callf"];if(isset($_GET["function"]))$_GET["procedure"]=$_GET["function"];if(isset($_GET["download"])){$a=$_GET["download"];$n=fields($a);header("Content-Type: application/octet-stream");header("Content-Disposition: attachment; filename=".friendly_url("$a-".implode("_",$_GET["where"])).".".friendly_url($_GET["field"]));$M=array(idf_escape($_GET["field"]));$I=driver()->select($a,$M,array(where($_GET,$n)),$M);$K=($I?$I->fetch_row():array());echo
driver()->value($K[0],$n[$_GET["field"]]);exit;}elseif(isset($_GET["table"])){$a=$_GET["table"];$n=fields($a);if(!$n)$l=error()?:'No tables.';$S=table_status1($a);$B=adminer()->tableName($S);page_header(($n&&is_view($S)?$S['Engine']=='materialized view'?'Materialized view':'View':'Table').": ".($B!=""?$B:h($a)),$l);$Nh=array();foreach($n
as$w=>$m)$Nh+=$m["privileges"];adminer()->selectLinks($S,(isset($Nh["insert"])||!support("table")?"":null));$qb=$S["Comment"];if($qb!="")echo"<p class='nowrap'>".'Comment'.": ".h($qb)."\n";if($n)adminer()->tableStructurePrint($n,$S);function
tables_links(array$T){echo"<ul>\n";foreach($T
as$K){$z=preg_replace('~ns=[^&]*~',"ns=".urlencode($K["ns"]),ME);echo"<li><a href='".h($z."table=".urlencode($K["table"]))."'>".($K["ns"]!=$_GET["ns"]?"<b>".h($K["ns"])."</b>.":"").h($K["table"])."</a>";}echo"</ul>\n";}$qe=driver()->inheritsFrom($a);if($qe){echo"<h3>".'Inherits from'."</h3>\n";tables_links($qe);}if(support("indexes")&&driver()->supportsIndex($S)){echo"<h3 id='indexes'>".'Indexes'."</h3>\n";$v=indexes($a);if($v)adminer()->tableIndexesPrint($v,$S);echo'<p class="links"><a href="'.h(ME).'indexes='.urlencode($a).'">'.'Alter indexes'."</a>\n";}if(!is_view($S)){if(fk_support($S)){echo"<h3 id='foreign-keys'>".'Foreign keys'."</h3>\n";$sd=foreign_keys($a);if($sd){echo"<table>\n","<thead><tr><th>".'Source'."<td>".'Target'."<td>".'ON DELETE'."<td>".'ON UPDATE'."<td></thead>\n";foreach($sd
as$B=>$o){echo"<tr title='".h($B)."'>","<th><i>".implode("</i>, <i>",array_map('Adminer\h',$o["source"]))."</i>";$z=($o["db"]!=""?preg_replace('~db=[^&]*~',"db=".urlencode($o["db"]),ME):($o["ns"]!=""?preg_replace('~ns=[^&]*~',"ns=".urlencode($o["ns"]),ME):ME));echo"<td><a href='".h($z."table=".urlencode($o["table"]))."'>".($o["db"]!=""&&$o["db"]!=DB?"<b>".h($o["db"])."</b>.":"").($o["ns"]!=""&&$o["ns"]!=$_GET["ns"]?"<b>".h($o["ns"])."</b>.":"").h($o["table"])."</a>","(<i>".implode("</i>, <i>",array_map('Adminer\h',$o["target"]))."</i>)","<td>".h($o["on_delete"]),"<td>".h($o["on_update"]),'<td><a href="'.h(ME.'foreign='.urlencode($a).'&name='.urlencode($B)).'">'.'Alter'.'</a>',"\n";}echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'foreign='.urlencode($a).'">'.'Add foreign key'."</a>\n";}if(support("check")){echo"<h3 id='checks'>".'Checks'."</h3>\n";$ab=driver()->checkConstraints($a);if($ab){echo"<table>\n";foreach($ab
as$w=>$X)echo"<tr title='".h($w)."'>","<td><code class='jush-".JUSH."'>".h($X),"<td><a href='".h(ME.'check='.urlencode($a).'&name='.urlencode($w))."'>".'Alter'."</a>","\n";echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'check='.urlencode($a).'">'.'Create check'."</a>\n";}}if(support(is_view($S)?"view_trigger":"trigger")){echo"<h3 id='triggers'>".'Triggers'."</h3>\n";$Bj=triggers($a);if($Bj){echo"<table>\n";foreach($Bj
as$w=>$X)echo"<tr valign='top'><td>".h($X[0])."<td>".h($X[1])."<th>".h($w)."<td><a href='".h(ME.'trigger='.urlencode($a).'&name='.urlencode($w))."'>".'Alter'."</a>\n";echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'trigger='.urlencode($a).'">'.'Add trigger'."</a>\n";}$pe=driver()->inheritedTables($a);if($pe){echo"<h3 id='partitions'>".'Inherited by'."</h3>\n";$Pg=driver()->partitionsInfo($a);if($Pg)echo"<p><code class='jush-".JUSH."'>BY ".h("$Pg[partition_by]($Pg[partition])")."</code>\n";tables_links($pe);}}elseif(isset($_GET["schema"])){page_header('Database schema',"",array(),h(DB.($_GET["ns"]?".$_GET[ns]":"")));$Ti=array();$Ui=array();$bd=array();$ca=($_GET["schema"]?:$_COOKIE["adminer_schema-".str_replace(".","_",DB)]);preg_match_all('~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~',$ca,$lf,PREG_SET_ORDER);foreach($lf
as$r=>$A){$Ti[$A[1]]=array((float)$A[2],(float)$A[3]);$Ui[]="\n\t'".js_escape($A[1])."': [ $A[2], $A[3] ]";}$rj=0;$Ja=-1;$Vh=array();$Ch=array();$Ue=array();$ta=driver()->allFields();foreach(table_status('',true)as$R=>$S){if(is_view($S))continue;$G=0;$Vh[$R]["fields"]=array();foreach($ta[$R]as$m){$G+=1.25;$bd[$R][$m["field"]]=$G;$Vh[$R]["fields"][$m["field"]]=$m;}$Vh[$R]["pos"]=($Ti[$R]?:array($rj,0));foreach(adminer()->foreignKeys($R)as$X){if(!$X["db"]){$Se=$Ja;if(idx($Ti[$R],1)||idx($Ti[$X["table"]],1))$Se=min(idx($Ti[$R],1,0),idx($Ti[$X["table"]],1,0))-1;else$Ja-=.1;while($Ue[(string)$Se])$Se-=.0001;$Vh[$R]["references"][$X["table"]][(string)$Se]=array($X["source"],$X["target"]);$Ch[$X["table"]][$R][(string)$Se]=$X["target"];$Ue[(string)$Se]=true;}}$rj=max($rj,$Vh[$R]["pos"][0]+2.5+$G);}echo'<div id="schema" style="height: ',$rj,'em;">
<script',nonce(),'>
qs(\'#schema\').onselectstart = () => false;
const tablePos = {',implode(",",$Ui)."\n",'};
const em = qs(\'#schema\').offsetHeight / ',$rj,';
document.onmousemove = schemaMousemove;
document.onmouseup = partialArg(schemaMouseup, \'',js_escape(DB),'\');
</script>
';foreach($Vh
as$B=>$R){echo"<div class='table' style='top: ".$R["pos"][0]."em; left: ".$R["pos"][1]."em;'>",'<a href="'.h(ME).'table='.urlencode($B).'"><b>'.h($B)."</b></a>",script("qsl('div').onmousedown = schemaMousedown;");foreach($R["fields"]as$m){$X='<span'.type_class($m["type"]).' title="'.h($m["type"].($m["length"]?"($m[length])":"").($m["null"]?" NULL":'')).'">'.h($m["field"]).'</span>';echo"<br>".($m["primary"]?"<i>$X</i>":$X);}foreach((array)$R["references"]as$bj=>$Eh){foreach($Eh
as$Se=>$_h){$Te=$Se-idx($Ti[$B],1);$r=0;foreach($_h[0]as$vi)echo"\n<div class='references' title='".h($bj)."' id='refs$Se-".($r++)."' style='left: $Te"."em; top: ".$bd[$B][$vi]."em; padding-top: .5em;'>"."<div style='border-top: 1px solid gray; width: ".(-$Te)."em;'></div></div>";}}foreach((array)$Ch[$B]as$bj=>$Eh){foreach($Eh
as$Se=>$e){$Te=$Se-idx($Ti[$B],1);$r=0;foreach($e
as$aj)echo"\n<div class='references arrow' title='".h($bj)."' id='refd$Se-".($r++)."' style='left: $Te"."em; top: ".$bd[$B][$aj]."em;'>"."<div style='height: .5em; border-bottom: 1px solid gray; width: ".(-$Te)."em;'></div>"."</div>";}}echo"\n</div>\n";}foreach($Vh
as$B=>$R){foreach((array)$R["references"]as$bj=>$Eh){if($Vh[$bj]){foreach($Eh
as$Se=>$_h){$Gf=$rj;$rf=-10;foreach($_h[0]as$w=>$vi){$eh=$R["pos"][0]+$bd[$B][$vi];$fh=$Vh[$bj]["pos"][0]+$bd[$bj][$_h[1][$w]];$Gf=min($Gf,$eh,$fh);$rf=max($rf,$eh,$fh);}echo"<div class='references' id='refl$Se' style='left: $Se"."em; top: $Gf"."em; padding: .5em 0;'><div style='border-right: 1px solid gray; margin-top: 1px; height: ".($rf-$Gf)."em;'></div></div>\n";}}}}echo'</div>
<p class="links"><a href="',h(ME."schema=".urlencode($ca)),'" id="schema-link">Permanent link</a>
';}elseif(isset($_GET["dump"])){$a=$_GET["dump"];if($_POST&&!$l){$k=array("auto_increment"=>'');foreach(array("type","routine","event","trigger")as$Mi){if(support($Mi))$k[$Mi."s"]='';}save_settings(array_intersect_key($_POST+$k,array_flip(array("output","format","db_style","table_style","data_style"))+$k),"adminer_export");$T=array_flip((array)$_POST["tables"])+array_flip((array)$_POST["data"]);$Rc=dump_headers((count($T)==1?key($T):DB),(DB==""||$_GET["ns"]===""||count($T)>1));$Ce=preg_match('~sql~',$_POST["format"]);if($Ce){echo"-- Adminer ".VERSION." ".get_driver(DRIVER)." ".str_replace("\n"," ",connection()->server_info)." dump\n\n";if(JUSH=="sql"){echo"SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
".($_POST["data_style"]?"SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
":"")."
";connection()->query("SET time_zone = '+00:00'");connection()->query("SET sql_mode = ''");}}$Hi=$_POST["db_style"];$i=array(DB);if(DB==""){$i=$_POST["databases"];if(is_string($i))$i=explode("\n",rtrim(str_replace("\r","",$i),"\n"));}foreach((array)$i
as$j){adminer()->dumpDatabase($j);if(connection()->select_db($j)){if($Ce){if($Hi)echo
use_sql($j,$Hi).";\n\n";$Fg="";if($_POST["types"]){foreach(types()as$s=>$U){$Fc=type_values($s);if($Fc)$Fg
.=($Hi!='DROP+CREATE'?"DROP TYPE IF EXISTS ".idf_escape($U).";;\n":"")."CREATE TYPE ".idf_escape($U)." AS ENUM ($Fc);\n\n";else$Fg
.="-- Could not export type $U\n\n";}}if($_POST["routines"]){foreach(routines()as$K){$B=$K["ROUTINE_NAME"];$Ph=$K["ROUTINE_TYPE"];$h=create_routine($Ph,array("name"=>$B)+routine($K["SPECIFIC_NAME"],$Ph));set_utf8mb4($h);$Fg
.=($Hi!='DROP+CREATE'?"DROP $Ph IF EXISTS ".idf_escape($B).";;\n":"")."$h;\n\n";}}if($_POST["events"]){foreach(get_rows("SHOW EVENTS",null,"-- ")as$K){$h=remove_definer(get_val("SHOW CREATE EVENT ".idf_escape($K["Name"]),3));set_utf8mb4($h);$Fg
.=($Hi!='DROP+CREATE'?"DROP EVENT IF EXISTS ".idf_escape($K["Name"]).";;\n":"")."$h;;\n\n";}}echo($Fg&&JUSH=='sql'?"DELIMITER ;;\n\n$Fg"."DELIMITER ;\n\n":$Fg);}if($_POST["table_style"]||$_POST["data_style"]){foreach(($_GET["ns"]===""?(array)$_POST["schemas"]:(DB!=""||!support("scheme")?array(""):adminer()->schemas()))as$Vh){if($Vh!=""){set_schema($Vh);if(DB==""&&(information_schema(DB)||$Vh=="pg_catalog"))continue;}$hk=array();foreach(table_status('',true)as$B=>$S){$R=(DB==""||$_GET["ns"]===""||in_array($B,(array)$_POST["tables"]));$Nb=(DB==""||$_GET["ns"]===""||in_array($B,(array)$_POST["data"]));if($R||$Nb){$oj=null;if($Rc=="tar"){$oj=new
TmpFile;ob_start(array($oj,'write'),1e5);}adminer()->dumpTable($B,($R?$_POST["table_style"]:""),(is_view($S)?2:0));if(is_view($S))$hk[]=$B;elseif($Nb){$n=fields($B);adminer()->dumpData($B,$_POST["data_style"],"SELECT *".convert_fields($n,$n)." FROM ".table($B));}if($Ce&&$_POST["triggers"]&&$R&&($Bj=trigger_sql($B)))echo"\nDELIMITER ;;\n$Bj\nDELIMITER ;\n";if($Rc=="tar"){ob_end_flush();tar_file((DB!=""?"":"$j/")."$B.csv",$oj);}elseif($Ce)echo"\n";}}if(function_exists('Adminer\foreign_keys_sql')){foreach(table_status('',true)as$B=>$S){$R=(DB==""||$_GET["ns"]===""||in_array($B,(array)$_POST["tables"]));if($R&&!is_view($S))echo
foreign_keys_sql($B);}}foreach($hk
as$gk)adminer()->dumpTable($gk,$_POST["table_style"],1);if($Rc=="tar")echo
pack("x512");}}}}adminer()->dumpFooter();exit;}page_header('Export',$l,($_GET["export"]!=""?array("table"=>$_GET["export"]):array()),h(DB));echo'
<form action="" method="post">
<table class="layout">
';$Rb=array('','USE','DROP+CREATE','CREATE');$Vi=array('','DROP+CREATE','CREATE');$Ob=array('','TRUNCATE+INSERT','INSERT');if(JUSH=="sql")$Ob[]='INSERT+UPDATE';$K=get_settings("adminer_export");if(!$K)$K=array("output"=>"text","format"=>"sql","db_style"=>(DB!=""?"":"CREATE"),"table_style"=>"DROP+CREATE","data_style"=>"INSERT");echo"<tr><th>".'Output'."<td>".html_radios("output",adminer()->dumpOutput(),$K["output"])."\n","<tr><th>".'Format'."<td>".html_radios("format",adminer()->dumpFormat(),$K["format"])."\n",(JUSH=="sqlite"?"":"<tr><th>".'Database'."<td>".html_select('db_style',$Rb,$K["db_style"]).(support("type")?checkbox("types",1,$K["types"],'User types'):"").(support("routine")?checkbox("routines",1,$K["routines"],'Routines'):"").(support("event")?checkbox("events",1,$K["events"],'Events'):"")),"<tr><th>".'Tables'."<td>".html_select('table_style',$Vi,$K["table_style"]).checkbox("auto_increment",1,$K["auto_increment"],'Auto Increment').(support("trigger")?checkbox("triggers",1,$K["triggers"],'Triggers'):""),"<tr><th>".'Data'."<td>".html_select('data_style',$Ob,$K["data_style"]),'</table>
<p><input type="submit" value="Export">
',input_token(),'
<table>
',script("qsl('table').onclick = dumpClick;");$jh=array();if($_GET["ns"]===""){echo"<thead><tr><th style='text-align: left;'>","<label class='block'><input type='checkbox' id='check-schemas' checked class='jsonly'>".'Schema'."</label>","</thead>\n",script("qs('#check-schemas').onclick = partial(formCheck, /^schemas\\[/);");foreach(adminer()->schemas()as$Vh)echo"<tr><td>".checkbox("schemas[]",$Vh,true,$Vh,"","block")."\n";}elseif(DB!=""){$cb=($a!=""?"":" checked");echo"<thead><tr>","<th style='text-align: left;'><label class='block'><input type='checkbox' id='check-tables'$cb class='jsonly'>".'Table'."</label>".script("qs('#check-tables').onclick = partial(formCheck, /^tables\\[/);",""),"<th style='text-align: right;'><label class='block'>".'Data'."<input type='checkbox' id='check-data'$cb class='jsonly'></label>".script("qs('#check-data').onclick = partial(formCheck, /^data\\[/);",""),"</thead>\n";$hk="";$Xi=tables_list();foreach($Xi
as$B=>$U){$ih=preg_replace('~_.*~','',$B);$cb=($a==""||$a==(substr($a,-1)=="%"?"$ih%":$B));$nh="<tr><td>".checkbox("tables[]",$B,$cb,$B,"","block");if($U!==null&&!preg_match('~table~i',$U))$hk
.="$nh\n";else
echo"$nh<td align='right'><label class='block'><span id='Rows-".h($B)."'></span>".checkbox("data[]",$B,$cb)."</label>\n";$jh[$ih]++;}echo$hk;if($Xi)echo
script("ajaxSetHtml('".js_escape(ME)."script=db');");}else{$i=adminer()->databases();echo"<thead><tr><th style='text-align: left;'>","<label class='block'>".($i?"<input type='checkbox' id='check-databases'".($a==""?" checked":"")." class='jsonly'>".script("qs('#check-databases').onclick = partial(formCheck, /^databases\\[/);",""):"").'Database'."</label>","</thead>\n";if($i){foreach($i
as$j){if(!information_schema($j)){$ih=preg_replace('~_.*~','',$j);echo"<tr><td>".checkbox("databases[]",$j,$a==""||$a=="$ih%",$j,"","block")."\n";$jh[$ih]++;}}}else
echo"<tr><td><textarea name='databases' rows='10' cols='20'></textarea>";}echo'</table>
</form>
';$id=true;foreach($jh
as$w=>$X){if($w!=""&&$X>1){echo($id?"<p>":" ")."<a href='".h(ME)."dump=".urlencode("$w%")."'>".h($w)."</a>";$id=false;}}}elseif(isset($_GET["privileges"])){page_header('Privileges');echo'<p class="links"><a href="'.h(ME).'user=">'.'Create user'."</a>";$I=connection()->query("SELECT User, Host FROM mysql.".(DB==""?"user":"db WHERE ".q(DB)." LIKE Db")." ORDER BY Host, User");$Ad=$I;if(!$I)$I=connection()->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");echo"<form action=''><p>\n";hidden_fields_get();echo
input_hidden("db",DB),($Ad?"":input_hidden("grant")),"<table class='odds'>\n","<thead><tr><th>".'Username'."<th>".'Server'."<th></thead>\n";while($K=$I->fetch_assoc())echo'<tr><td>'.h($K["User"])."<td>".h($K["Host"]).'<td><a href="'.h(ME.'user='.urlencode($K["User"]).'&host='.urlencode($K["Host"])).'">'.'Edit'."</a>\n";if(!$Ad||DB!="")echo"<tr><td><input name='user' autocapitalize='off'><td><input name='host' value='localhost' autocapitalize='off'><td><input type='submit' value='".'Edit'."'>\n";echo"</table>\n","</form>\n";}elseif(isset($_GET["sql"])){if(!$l&&$_POST["export"]){save_settings(array("output"=>$_POST["output"],"format"=>$_POST["format"]),"adminer_import");dump_headers("sql");if($_POST["format"]=="sql")echo"$_POST[query]\n";else{adminer()->dumpTable("","");adminer()->dumpData("","table",$_POST["query"]);adminer()->dumpFooter();}exit;}restart_session();$Rd=&get_session("queries");$Qd=&$Rd[DB];if(!$l&&$_POST["clear"]){$Qd=array();redirect(remove_from_uri("history"));}stop_session();page_header((isset($_GET["import"])?'Import':'SQL command'),$l);$af='--'.(JUSH=='sql'?' ':'');if(!$l&&$_POST){$p=false;if(!isset($_GET["import"]))$H=$_POST["query"];elseif($_POST["webfile"]){$zi=adminer()->importServerPath();$p=@fopen((file_exists($zi)?$zi:"compress.zlib://$zi.gz"),"rb");$H=($p?fread($p,1e6):false);}else$H=get_file("sql_file",true,";");if(is_string($H)){if(($wf=ini_bytes("memory_limit"))!="-1")ini_set("memory_limit",max($wf,strval(2*strlen($H)+memory_get_usage()+8e6)));if($H!=""&&strlen($H)<1e6){$th=$H.(preg_match("~;[ \t\r\n]*\$~",$H)?"":";");if(!$Qd||first(end($Qd))!=$th){restart_session();$Qd[]=array($th,time());set_session("queries",$Rd);stop_session();}}$wi="(?:\\s|/\\*[\s\S]*?\\*/|(?:#|$af)[^\n]*\n?|--\r?\n)";$Zb=driver()->delimiter;$C=0;$_c=true;$g=connect();if($g&&DB!=""){$g->select_db(DB);if($_GET["ns"]!="")set_schema($_GET["ns"],$g);}$pb=0;$Hc=array();$Mg='[\'"'.(JUSH=="sql"?'`#':(JUSH=="sqlite"?'`[':(JUSH=="mssql"?'[':''))).']|/\*|'.$af.'|$'.(JUSH=="pgsql"?'|\$([a-zA-Z]\w*)?\$':'');$sj=microtime(true);$na=get_settings("adminer_import");while($H!=""){if(!$C&&preg_match("~^$wi*+DELIMITER\\s+(\\S+)~i",$H,$A)){$Zb=preg_quote($A[1]);$H=substr($H,strlen($A[0]));}elseif(!$C&&JUSH=='pgsql'&&preg_match("~^($wi*+COPY\\s+)[^;]+\\s+FROM\\s+stdin;~i",$H,$A)){$Zb="\n\\\\\\.\r?\n";$C=strlen($A[0]);}else{preg_match("($Zb\\s*|$Mg)",$H,$A,PREG_OFFSET_CAPTURE,$C);list($ud,$G)=$A[0];if(!$ud&&$p&&!feof($p))$H
.=fread($p,1e5);else{if(!$ud&&rtrim($H)=="")break;$C=$G+strlen($ud);if($ud&&!preg_match("(^$Zb)",$ud)){$Ua=driver()->hasCStyleEscapes()||(JUSH=="pgsql"&&($G>0&&strtolower($H[$G-1])=="e"));$Xg=($ud=='/*'?'\*/':($ud=='['?']':(preg_match("~^$af|^#~",$ud)?"\n":preg_quote($ud).($Ua?'|\\\\.':''))));while(preg_match("($Xg|\$)s",$H,$A,PREG_OFFSET_CAPTURE,$C)){$Th=$A[0][0];if(!$Th&&$p&&!feof($p))$H
.=fread($p,1e5);else{$C=$A[0][1]+strlen($Th);if(!$Th||$Th[0]!="\\")break;}}}else{$_c=false;$th=substr($H,0,$G+($Zb[0]=="\n"?3:0));$pb++;$nh="<pre id='sql-$pb'><code class='jush-".JUSH."'>".adminer()->sqlCommandQuery($th)."</code></pre>\n";if(JUSH=="sqlite"&&preg_match("~^$wi*+(ATTACH|VACUUM\\b.*\\bINTO)\\b~is",$th,$A)!==0){echo$nh,"<p class='error'>".sprintf('%s queries are not supported.',preg_match('~ATTACH~i',$A[1])?'ATTACH':'VACUUM INTO')."\n";$Hc[]=" <a href='#sql-$pb'>$pb</a>";if($_POST["error_stops"])break;}else{if(!$_POST["only_errors"]){echo$nh;ob_flush();flush();}$Di=microtime(true);if(connection()->multi_query($th)&&$g&&preg_match("~^$wi*+USE\\b~i",$th))$g->query($th);do{$I=connection()->store_result();if(connection()->error){echo($_POST["only_errors"]?$nh:""),"<p class='error'>".'Error in query'.(connection()->errno?" (".connection()->errno.")":"").": ".error()."\n";$Hc[]=" <a href='#sql-$pb'>$pb</a>";if($_POST["error_stops"])break
2;}else{$hj=" <span class='time'>(".format_time($Di).")</span>".(strlen($th)<1000?" <a href='".h(ME)."sql=".urlencode(trim($th))."'>".'Edit'."</a>":"");$pa=connection()->affected_rows;$kk=($_POST["only_errors"]?"":driver()->warnings());$lk="warnings-$pb";if($kk)$hj
.=", <a href='#$lk'>".'Warnings'."</a>".script("qsl('a').onclick = partial(toggle, '$lk');","");$Pc=null;$yg=null;$Qc="explain-$pb";if(is_object($I)){$y=$_POST["limit"];$yg=print_select_result($I,$g,array(),$y);if(!$_POST["only_errors"]){echo"<form action='' method='post'>\n";$Zf=$I->num_rows;echo"<p class='sql-footer'>".($Zf?($y&&$Zf>$y?sprintf('%d / ',$y):"").lang_format(array('%d row','%d rows'),$Zf):""),$hj;if($g&&preg_match("~^($wi|\\()*+SELECT\\b~i",$th)&&($Pc=explain($g,$th)))echo", <a href='#$Qc'>Explain</a>".script("qsl('a').onclick = partial(toggle, '$Qc');","");$s="export-$pb";echo", <a href='#$s'>".'Export'."</a>".script("qsl('a').onclick = partial(toggle, '$s');","")."<span id='$s' class='hidden'>: ".html_select("output",adminer()->dumpOutput(),$na["output"])." ".html_select("format",adminer()->dumpFormat(),$na["format"]).input_hidden("query",$th)."<input type='submit' name='export' value='".'Export'."'>".input_token()."</span>\n"."</form>\n";}}else{if(preg_match("~^$wi*+(CREATE|DROP|ALTER)$wi++(DATABASE|SCHEMA)\\b~i",$th)){restart_session();set_session("dbs",null);stop_session();}if(!$_POST["only_errors"])echo"<p class='message' title='".h(connection()->info)."'>".lang_format(array('Query executed OK, %d row affected.','Query executed OK, %d rows affected.'),$pa)."$hj\n";}echo($kk?"<div id='$lk' class='hidden'>\n$kk</div>\n":"");if($Pc){echo"<div id='$Qc' class='hidden explain'>\n";print_select_result($Pc,$g,$yg);echo"</div>\n";}}$Di=microtime(true);}while(connection()->next_result());}$H=substr($H,$C);$C=0;}}}}if($_c)echo"<p class='message'>".'No commands to execute.'."\n";elseif($_POST["only_errors"])echo"<p class='message'>".lang_format(array('%d query executed OK.','%d queries executed OK.'),$pb-count($Hc))," <span class='time'>(".format_time($sj).")</span>\n";elseif($Hc&&$pb>1)echo"<p class='error'>".'Error in query'.": ".implode("",$Hc)."\n";}else
echo"<p class='error'>".upload_error($H)."\n";}echo'
<form action="" method="post" enctype="multipart/form-data" id="form">
';$Nc="<input type='submit' value='".'Execute'."' title='Ctrl+Enter'>";if(!isset($_GET["import"])){$th=$_GET["sql"];if($_POST)$th=$_POST["query"];elseif($_GET["history"]=="all")$th=$Qd;elseif($_GET["history"]!="")$th=idx($Qd[$_GET["history"]],0);echo"<p>";textarea("query",$th,20);echo
script(($_POST?"":"qs('textarea').focus();\n")."qs('#form').onsubmit = partial(sqlSubmit, qs('#form'), '".js_escape(remove_from_uri("sql|limit|error_stops|only_errors|history"))."');"),"<p>";adminer()->sqlPrintAfter();echo"$Nc\n",'Limit rows'.": <input type='number' name='limit' class='size' value='".h($_POST?$_POST["limit"]:$_GET["limit"])."'>\n";}else{$Gd=(extension_loaded("zlib")?"[.gz]":"");echo"<fieldset><legend>".'File upload'."</legend><div>",file_input("SQL$Gd: <input type='file' name='sql_file[]' multiple>\n$Nc"),"</div></fieldset>\n";$ce=adminer()->importServerPath();if($ce)echo"<fieldset><legend>".'From server'."</legend><div>",sprintf('Webserver file %s',"<code>".h($ce)."$Gd</code>"),' <input type="submit" name="webfile" value="'.'Run file'.'">',"</div></fieldset>\n";echo"<p>";}echo
checkbox("error_stops",1,($_POST?$_POST["error_stops"]:isset($_GET["import"])||$_GET["error_stops"]),'Stop on error')."\n",checkbox("only_errors",1,($_POST?$_POST["only_errors"]:isset($_GET["import"])||$_GET["only_errors"]),'Show only errors')."\n",input_token();if(!isset($_GET["import"])&&$Qd){print_fieldset("history",'History',$_GET["history"]!="");for($X=end($Qd);$X;$X=prev($Qd)){$w=key($Qd);list($th,$hj,$wc)=$X;echo'<a href="'.h(ME."sql=&history=$w").'">'.'Edit'."</a>"." <span class='time' title='".@date('Y-m-d',$hj)."'>".@date("H:i:s",$hj)."</span>"." <code class='jush-".JUSH."'>".shorten_utf8(ltrim(str_replace("\n"," ",str_replace("\r","",preg_replace("~^(#|$af).*~m",'',$th)))),80,"</code>").($wc?" <span class='time'>($wc)</span>":"")."<br>\n";}echo"<input type='submit' name='clear' value='".'Clear'."'>\n","<a href='".h(ME."sql=&history=all")."'>".'Edit all'."</a>\n","</div></fieldset>\n";}echo'</form>
';}elseif(isset($_GET["edit"])){$a=$_GET["edit"];$n=fields($a);$Z=(isset($_GET["select"])?($_POST["check"]&&count($_POST["check"])==1?where_check($_POST["check"][0],$n):""):where($_GET,$n));$Nj=(isset($_GET["select"])?$_POST["edit"]:$Z);foreach($n
as$B=>$m){if((!$Nj&&!isset($m["privileges"]["insert"]))||adminer()->fieldName($m)=="")unset($n[$B]);}if($_POST&&!$l&&!isset($_GET["select"])){$_=$_POST["referer"];if($_POST["insert"])$_=($Nj?null:$_SERVER["REQUEST_URI"]);elseif(!preg_match('~^.+&select=.+$~',$_))$_=ME."select=".urlencode($a);$v=indexes($a);$Ij=unique_array($_GET["where"],$v);$wh="\nWHERE $Z";if(isset($_POST["delete"]))queries_redirect($_,'Item has been deleted.',driver()->delete($a,$wh,$Ij?0:1));else{$O=array();foreach($n
as$B=>$m){$X=process_input($m);if($X!==false&&$X!==null)$O[idf_escape($B)]=$X;}if($Nj){if(!$O)redirect($_);queries_redirect($_,'Item has been updated.',driver()->update($a,$O,$wh,$Ij?0:1));if(is_ajax()){page_headers();page_messages($l);exit;}}else{$I=driver()->insert($a,$O);$Re=($I?last_id($I):0);queries_redirect($_,sprintf('Item%s has been inserted.',($Re?" $Re":"")),$I);}}}$K=null;if($Z){$M=array();foreach($n
as$B=>$m){if(isset($m["privileges"]["select"])){$ya=($_POST["clone"]&&$m["auto_increment"]?"''":convert_field($m));$M[]=($ya?"$ya AS ":"").idf_escape($B);}}$K=array();if(!support("table"))$M=array("*");if($M){$I=driver()->select($a,$M,array($Z),$M,array(),(isset($_GET["select"])?2:1));if(!$I)$l=error();else{$K=$I->fetch_assoc();if(!$K)$K=false;}if(isset($_GET["select"])&&(!$K||$I->fetch_assoc()))$K=null;}}if(!support("table")&&!$n){if(!$Z){$I=driver()->select($a,array("*"),array(),array("*"));$K=($I?$I->fetch_assoc():false);if(!$K)$K=array(driver()->primary=>"");}if($K){foreach($K
as$w=>$X){if(!$Z)$K[$w]=null;$n[$w]=array("field"=>$w,"null"=>($w!=driver()->primary),"auto_increment"=>($w==driver()->primary));}}}if($_POST["save"])$K=(array)$_POST["fields"]+($K?$K:array());edit_form($a,$n,$K,$Nj,$l);}elseif(isset($_GET["create"])){$a=$_GET["create"];$Rg=driver()->partitionBy;$Ug=($Rg?driver()->partitionsInfo($a):array());$Bh=referencable_primary($a);$sd=array();foreach($Bh
as$Ri=>$m)$sd[str_replace("`","``",$Ri)."`".str_replace("`","``",$m["field"])]=$Ri;$Ag=array();$S=array();if($a!=""){$Ag=fields($a);$S=table_status1($a);if(count($S)<2)$l='No tables.';}$K=$_POST;$K["fields"]=(array)$K["fields"];if($K["auto_increment_col"])$K["fields"][$K["auto_increment_col"]]["auto_increment"]=true;if($_POST&&!$l)save_settings(array("comments"=>$_POST["comments"],"defaults"=>$_POST["defaults"]));if($_POST&&!process_fields($K["fields"])&&!$l){if($_POST["drop"])queries_redirect(substr(ME,0,-1),'Table has been dropped.',drop_tables(array($a)));else{$n=array();$ta=array();$Tj=false;$qd=array();$_g=reset($Ag);$ra=" FIRST";foreach($K["fields"]as$w=>$m){$o=$sd[$m["type"]];$Dj=($o!==null?$Bh[$o]:$m);if($m["field"]!=""){if(!$m["generated"])$m["default"]=null;$sh=process_field($m,$Dj);$ta[]=array($m["orig"],$sh,$ra);if(!$_g||$sh!==process_field($_g,$_g)){$n[]=array($m["orig"],$sh,$ra);if($m["orig"]!=""||$ra)$Tj=true;}if($o!==null)$qd[idf_escape($m["field"])]=($a!=""&&JUSH!="sqlite"?"ADD":" ").format_foreign_key(array('table'=>$sd[$m["type"]],'source'=>array($m["field"]),'target'=>array($Dj["field"]),'on_delete'=>$m["on_delete"],));$ra=" AFTER ".idf_escape($m["field"]);}elseif($m["orig"]!=""){$Tj=true;$n[]=array($m["orig"]);}if($m["orig"]!=""){$_g=next($Ag);if(!$_g)$ra="";}}$E=array();if(in_array($K["partition_by"],$Rg)){foreach($K
as$w=>$X){if(preg_match('~^partition~',$w))$E[$w]=$X;}foreach($E["partition_names"]as$w=>$B){if($B==""){unset($E["partition_names"][$w]);unset($E["partition_values"][$w]);}}$E["partition_names"]=array_values($E["partition_names"]);$E["partition_values"]=array_values($E["partition_values"]);if($E==$Ug)$E=array();}elseif(preg_match("~partitioned~",$S["Create_options"]))$E=null;$yf='Table has been altered.';if($a==""){cookie("adminer_engine",$K["Engine"]);$yf='Table has been created.';}$B=trim($K["name"]);queries_redirect(ME.(support("table")?"table=":"select=").urlencode($B),$yf,alter_table($a,$B,(JUSH=="sqlite"&&($Tj||$qd)?$ta:$n),$qd,($K["Comment"]!=$S["Comment"]?$K["Comment"]:null),($K["Engine"]&&$K["Engine"]!=$S["Engine"]?$K["Engine"]:""),($K["Collation"]&&$K["Collation"]!=$S["Collation"]?$K["Collation"]:""),($K["Auto_increment"]!=""?number($K["Auto_increment"]):""),$E));}}page_header(($a!=""?'Alter table':'Create table'),$l,array("table"=>$a),h($a));if(!$_POST){$Ej=driver()->types();$K=array("Engine"=>$_COOKIE["adminer_engine"],"fields"=>array(array("field"=>"","type"=>(isset($Ej["int"])?"int":(isset($Ej["integer"])?"integer":"")),"on_update"=>"")),"partition_names"=>array(""),);if($a!=""){$K=$S;$K["name"]=$a;$K["fields"]=array();if(!$_GET["auto_increment"])$K["Auto_increment"]="";foreach($Ag
as$m){$m["generated"]=$m["generated"]?:(isset($m["default"])?"DEFAULT":"");$K["fields"][]=$m;}if($Rg){$K+=$Ug;$K["partition_names"][]="";$K["partition_values"][]="";}}}$lb=collations();if(is_array(reset($lb)))$lb=call_user_func_array('array_merge',array_values($lb));$Bc=driver()->engines();foreach($Bc
as$Ac){if(!strcasecmp($Ac,$K["Engine"])){$K["Engine"]=$Ac;break;}}echo'
<form action="" method="post" id="form">
<p>
';if(support("columns")||$a==""){echo'Table name'.": <input name='name'".($a==""&&!$_POST?" autofocus":"")." data-maxlength='64' value='".h($K["name"])."' autocapitalize='off'>\n",($Bc?html_select("Engine",array(""=>"(".'engine'.")")+$Bc,$K["Engine"]).on_help("event.target.value",1).script("qsl('select').onchange = helpClose;")."\n":"");if($lb)echo"<datalist id='collations'>".optionlist($lb)."</datalist>\n",(preg_match("~sqlite|mssql~",JUSH)?"":"<input list='collations' name='Collation' value='".h($K["Collation"])."' placeholder='(".'collation'.")'>\n");echo"<input type='submit' value='".'Save'."'>\n";}if(support("columns")){echo"<div class='scrollable'>\n","<table id='edit-fields' class='nowrap'>\n";edit_fields($K["fields"],$lb,"TABLE",$sd);echo"</table>\n",script("editFields();"),"</div>\n<p>\n",'Auto Increment'.": <input type='number' name='Auto_increment' class='size' value='".h($K["Auto_increment"])."'>\n",checkbox("defaults",1,($_POST?$_POST["defaults"]:get_setting("defaults")),'Default values',"columnShow(this.checked, 5)","jsonly");$sb=($_POST?$_POST["comments"]:get_setting("comments"));echo(support("comment")?checkbox("comments",1,$sb,'Comment',"editingCommentsClick(this, true);","jsonly").' '.(preg_match('~\n~',$K["Comment"])?"<textarea name='Comment' rows='2' cols='20'".($sb?"":" class='hidden'").">".h($K["Comment"])."</textarea>":'<input name="Comment" value="'.h($K["Comment"]).'" data-maxlength="'.(min_version(5.5)?2048:60).'"'.($sb?"":" class='hidden'").'>'):''),'<p>
<input type="submit" value="Save">
';}echo'
';if($a!="")echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$a));if($Rg&&(JUSH=='sql'||$a=="")){$Sg=preg_match('~RANGE|LIST~',$K["partition_by"]);print_fieldset("partition",'Partition by',$K["partition_by"]);echo"<p>".html_select("partition_by",array_merge(array(""),$Rg),$K["partition_by"]).on_help("event.target.value.replace(/./, 'PARTITION BY \$&')",1).script("qsl('select').onchange = partitionByChange;"),"(<input name='partition' value='".h($K["partition"])."'>)\n",'Partitions'.": <input type='number' name='partitions' class='size".($Sg||!$K["partition_by"]?" hidden":"")."' value='".h($K["partitions"])."'>\n","<table id='partition-table'".($Sg?"":" class='hidden'").">\n","<thead><tr><th>".'Partition name'."<th>".'Values'."</thead>\n";foreach($K["partition_names"]as$w=>$X)echo'<tr>','<td><input name="partition_names[]" value="'.h($X).'" autocapitalize="off">',($w==count($K["partition_names"])-1?script("qsl('input').oninput = partitionNameChange;"):''),'<td><input name="partition_values[]" value="'.h(idx($K["partition_values"],$w)).'">';echo"</table>\n</div></fieldset>\n";}echo
input_token(),'</form>
';}elseif(isset($_GET["indexes"])){$a=$_GET["indexes"];$ke=array("PRIMARY","UNIQUE","INDEX");$S=table_status1($a,true);$he=driver()->indexAlgorithms($S);if(preg_match('~MyISAM|M?aria'.(min_version(5.6,'10.0.5')?'|InnoDB':'').'~i',$S["Engine"]))$ke[]="FULLTEXT";if(preg_match('~MyISAM|M?aria'.(min_version(5.7,'10.2.2')?'|InnoDB':'').'~i',$S["Engine"]))$ke[]="SPATIAL";if(min_version('',11.7)&&preg_match('~MyISAM|InnoDB~i',$S["Engine"]))$ke[]="VECTOR";$v=indexes($a);$n=fields($a);$lh=array();if(JUSH=="mongo"){$lh=$v["_id_"];unset($ke[0]);unset($v["_id_"]);}$K=$_POST;if($K)save_settings(array("index_options"=>$K["options"]));if($_POST&&!$l&&!$_POST["add"]&&!$_POST["drop_col"]){$b=array();foreach($K["indexes"]as$u){$B=$u["name"];if(in_array($u["type"],$ke)){$e=array();$Ye=array();$cc=array();$ie=(support("partial_indexes")?$u["partial"]:"");$ge=(in_array($u["algorithm"],$he)?$u["algorithm"]:"");$O=array();ksort($u["columns"]);foreach($u["columns"]as$w=>$d){if($d!=""){$x=idx($u["lengths"],$w);$ac=idx($u["descs"],$w);$O[]=($n[$d]?idf_escape($d):$d).($x?"(".(+$x).")":"").($ac?" DESC":"");$e[]=$d;$Ye[]=($x?:null);$cc[]=$ac;}}$Oc=$v[$B];if($Oc){ksort($Oc["columns"]);ksort($Oc["lengths"]);ksort($Oc["descs"]);if($u["type"]==$Oc["type"]&&array_values($Oc["columns"])===$e&&(!$Oc["lengths"]||array_values($Oc["lengths"])===$Ye)&&array_values($Oc["descs"])===$cc&&$Oc["partial"]==$ie&&(!$he||$Oc["algorithm"]==$ge)){unset($v[$B]);continue;}}if($e)$b[]=array($u["type"],$B,$O,$ge,$ie);}}foreach($v
as$B=>$Oc)$b[]=array($Oc["type"],$B,"DROP");if(!$b)redirect(ME."table=".urlencode($a));queries_redirect(ME."table=".urlencode($a),'Indexes have been altered.',alter_indexes($a,$b));}page_header('Indexes',$l,array("table"=>$a),h($a));$dd=array_keys($n);if($_POST["add"]){foreach($K["indexes"]as$w=>$u){if($u["columns"][count($u["columns"])]!="")$K["indexes"][$w]["columns"][]="";}$u=end($K["indexes"]);if($u["type"]||array_filter($u["columns"],'strlen'))$K["indexes"][]=array("columns"=>array(1=>""));}if(!$K){foreach($v
as$w=>$u){$v[$w]["name"]=$w;$v[$w]["columns"][]="";}$v[]=array("columns"=>array(1=>""));$K["indexes"]=$v;}$Ye=(JUSH=="sql"||JUSH=="mssql");$qi=($_POST?$_POST["options"]:get_setting("index_options"));echo'
<form action="" method="post">
<div class="scrollable">
<table class="nowrap">
<thead><tr>
<th id="label-type">Index Type
';$ae=" class='idxopts".($qi?"":" hidden")."'";if($he)echo"<th id='label-algorithm'$ae>".'Algorithm'.doc_link(array('sql'=>'create-index.html#create-index-storage-engine-index-types','mariadb'=>'storage-engine-index-types/','pgsql'=>'indexes-types.html',));echo'<th><input type="submit" class="wayoff">','Columns'.($Ye?"<span$ae> (".'length'.")</span>":"");if($Ye||support("descidx"))echo
checkbox("options",1,$qi,'Options',"indexOptionsShow(this.checked)","jsonly")."\n";echo'<th id="label-name">Name
';if(support("partial_indexes"))echo"<th id='label-condition'$ae>".'Condition';echo'<th><noscript>',icon("plus","add[0]","+",'Add next'),'</noscript>
</thead>
';if($lh){echo"<tr><td>PRIMARY<td>";foreach($lh["columns"]as$w=>$d)echo
select_input(" disabled",$dd,$d),"<label><input disabled type='checkbox'>".'descending'."</label> ";echo"<td><td>\n";}$Fe=1;foreach($K["indexes"]as$u){if(!$_POST["drop_col"]||$Fe!=key($_POST["drop_col"])){echo"<tr><td>".html_select("indexes[$Fe][type]",array(-1=>"")+$ke,$u["type"],($Fe==count($K["indexes"])?"indexesAddRow.call(this);":""),"label-type");if($he)echo"<td$ae>".html_select("indexes[$Fe][algorithm]",array_merge(array(""),$he),$u['algorithm'],"label-algorithm");echo"<td>";ksort($u["columns"]);$r=1;foreach($u["columns"]as$w=>$d){echo"<span>".select_input(" name='indexes[$Fe][columns][$r]' title='".'Column'."'",($n&&($d==""||$n[$d])?array_combine($dd,$dd):array()),$d,"partial(".($r==count($u["columns"])?"indexesAddColumn":"indexesChangeColumn").", '".js_escape(JUSH=="sql"?"":$_GET["indexes"]."_")."')"),"<span$ae>",($Ye?"<input type='number' name='indexes[$Fe][lengths][$r]' class='size' value='".h(idx($u["lengths"],$w))."' title='".'Length'."'>":""),(support("descidx")?checkbox("indexes[$Fe][descs][$r]",1,idx($u["descs"],$w),'descending'):""),"</span> </span>";$r++;}echo"<td><input name='indexes[$Fe][name]' value='".h($u["name"])."' autocapitalize='off' aria-labelledby='label-name'>\n";if(support("partial_indexes"))echo"<td$ae><input name='indexes[$Fe][partial]' value='".h($u["partial"])."' autocapitalize='off' aria-labelledby='label-condition'>\n";echo"<td>".icon("cross","drop_col[$Fe]","x",'Remove').script("qsl('button').onclick = partial(editingRemoveRow, 'indexes\$1[type]');");}$Fe++;}echo'</table>
</div>
<p>
<input type="submit" value="Save">
',input_token(),'</form>
';}elseif(isset($_GET["database"])){$K=$_POST;if($_POST&&!$l&&!$_POST["add"]){$B=trim($K["name"]);if($_POST["drop"]){$_GET["db"]="";queries_redirect(remove_from_uri("db|database"),'Database has been dropped.',drop_databases(array(DB)));}elseif(DB!==$B){if(DB!=""){$_GET["db"]=$B;queries_redirect(preg_replace('~\bdb=[^&]*&~','',ME)."db=".urlencode($B),'Database has been renamed.',rename_database($B,$K["collation"]));}else{$i=explode("\n",str_replace("\r","",$B));$Ii=true;$Qe="";foreach($i
as$j){if(count($i)==1||$j!=""){if(!create_database($j,$K["collation"]))$Ii=false;$Qe=$j;}}restart_session();set_session("dbs",null);queries_redirect(ME."db=".urlencode($Qe),'Database has been created.',$Ii);}}else{if(!$K["collation"])redirect(substr(ME,0,-1));query_redirect("ALTER DATABASE ".idf_escape($B).(preg_match('~^[a-z0-9_]+$~i',$K["collation"])?" COLLATE $K[collation]":""),substr(ME,0,-1),'Database has been altered.');}}page_header(DB!=""?'Alter database':'Create database',$l,array(),h(DB));$lb=collations();$B=DB;if($_POST)$B=$K["name"];elseif(DB!="")$K["collation"]=db_collation(DB,$lb);elseif(JUSH=="sql"){foreach(get_vals("SHOW GRANTS")as$Ad){if(preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\.\*)?~',$Ad,$A)&&$A[1]){$B=stripcslashes(idf_unescape("`$A[2]`"));break;}}}echo'
<form action="" method="post">
<p>
',($_POST["add"]||strpos($B,"\n")?'<textarea autofocus name="name" rows="10" cols="40">'.h($B).'</textarea><br>':'<input name="name" autofocus value="'.h($B).'" data-maxlength="64" autocapitalize="off">')."\n".($lb?html_select("collation",array(""=>"(".'collation'.")")+$lb,$K["collation"]).doc_link(array('sql'=>"charset-charsets.html",'mariadb'=>"supported-character-sets-and-collations/",'mssql'=>"relational-databases/system-functions/sys-fn-helpcollations-transact-sql",)):""),'<input type="submit" value="Save">
';if(DB!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',DB))."\n";elseif(!$_POST["add"]&&$_GET["db"]=="")echo
icon("plus","add[0]","+",'Add next')."\n";echo
input_token(),'</form>
';}elseif(isset($_GET["scheme"])){$K=$_POST;if($_POST&&!$l){$z=preg_replace('~ns=[^&]*&~','',ME)."ns=";if($_POST["drop"])query_redirect("DROP SCHEMA ".idf_escape($_GET["ns"]),$z,'Schema has been dropped.');else{$B=trim($K["name"]);$z
.=urlencode($B);if($_GET["ns"]=="")query_redirect("CREATE SCHEMA ".idf_escape($B),$z,'Schema has been created.');elseif($_GET["ns"]!=$B)query_redirect("ALTER SCHEMA ".idf_escape($_GET["ns"])." RENAME TO ".idf_escape($B),$z,'Schema has been altered.');else
redirect($z);}}page_header($_GET["ns"]!=""?'Alter schema':'Create schema',$l);if(!$K)$K["name"]=$_GET["ns"];echo'
<form action="" method="post">
<p><input name="name" autofocus value="',h($K["name"]),'" autocapitalize="off">
<input type="submit" value="Save">
';if($_GET["ns"]!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',$_GET["ns"]))."\n";echo
input_token(),'</form>
';}elseif(isset($_GET["call"])){$ba=($_GET["name"]?:$_GET["call"]);page_header('Call'.": ".h($ba),$l);$Ph=routine($_GET["call"],(isset($_GET["callf"])?"FUNCTION":"PROCEDURE"));$de=array();$Fg=array();foreach($Ph["fields"]as$r=>$m){if(substr($m["inout"],-3)=="OUT"&&JUSH=='sql')$Fg[$r]="@".idf_escape($m["field"])." AS ".idf_escape($m["field"]);if(!$m["inout"]||substr($m["inout"],0,2)=="IN")$de[]=$r;}if(!$l&&$_POST){$Va=array();foreach($Ph["fields"]as$w=>$m){$X="";if(in_array($w,$de)){$X=process_input($m);if($X===false)$X="''";if(isset($Fg[$w]))connection()->query("SET @".idf_escape($m["field"])." = $X");}if(isset($Fg[$w]))$Va[]="@".idf_escape($m["field"]);elseif(in_array($w,$de))$Va[]=$X;}$H=(isset($_GET["callf"])?"SELECT ":"CALL ").(idx($Ph["returns"],"type")=="record"?"* FROM ":"").table($ba)."(".implode(", ",$Va).")";$Di=microtime(true);$I=connection()->multi_query($H);$pa=connection()->affected_rows;echo
adminer()->selectQuery($H,$Di,!$I);if(!$I)echo"<p class='error'>".error()."\n";else{$g=connect();if($g)$g->select_db(DB);do{$I=connection()->store_result();if(is_object($I))print_select_result($I,$g);else
echo"<p class='message'>".lang_format(array('Routine has been called, %d row affected.','Routine has been called, %d rows affected.'),$pa)." <span class='time'>".@date("H:i:s")."</span>\n";}while(connection()->next_result());if($Fg)print_select_result(connection()->query("SELECT ".implode(", ",$Fg)));}}echo'
<form action="" method="post">
';if($de){echo"<table class='layout'>\n";foreach($de
as$w){$m=$Ph["fields"][$w];$B=$m["field"];echo"<tr><th>".adminer()->fieldName($m);$Y=idx($_POST["fields"],$B);if($Y!=""){if($m["type"]=="set")$Y=implode(",",$Y);}input($m,$Y,idx($_POST["function"],$B,""));echo"\n";}echo"</table>\n";}echo'<p>
<input type="submit" value="Call">
',input_token(),'</form>

<pre>
';function
pre_tr($Th){return
preg_replace('~^~m','<tr>',preg_replace('~\|~','<td>',preg_replace('~\|$~m',"",rtrim($Th))));}$R='(\+--[-+]+\+\n)';$K='(\| .* \|\n)';echo
preg_replace_callback("~^$R?$K$R?($K*)$R?~m",function($A){$jd=pre_tr($A[2]);return"<table>\n".($A[1]?"<thead>$jd</thead>\n":$jd).pre_tr($A[4])."\n</table>";},preg_replace('~(\n(    -|mysql)&gt; )(.+)~',"\\1<code class='jush-sql'>\\3</code>",preg_replace('~(.+)\n---+\n~',"<b>\\1</b>\n",h($Ph['comment']))));echo'</pre>
';}elseif(isset($_GET["foreign"])){$a=$_GET["foreign"];$B=$_GET["name"];$K=$_POST;if($_POST&&!$l&&!$_POST["add"]&&!$_POST["change"]&&!$_POST["change-js"]){if(!$_POST["drop"]){$K["source"]=array_filter($K["source"],'strlen');ksort($K["source"]);$aj=array();foreach($K["source"]as$w=>$X)$aj[$w]=$K["target"][$w];$K["target"]=$aj;}if(JUSH=="sqlite")$I=recreate_table($a,$a,array(),array(),array(" $B"=>($K["drop"]?"":" ".format_foreign_key($K))));else{$b="ALTER TABLE ".table($a);$I=($B==""||queries("$b DROP ".(JUSH=="sql"?"FOREIGN KEY ":"CONSTRAINT ").idf_escape($B)));if(!$K["drop"])$I=queries("$b ADD".format_foreign_key($K));}queries_redirect(ME."table=".urlencode($a),($K["drop"]?'Foreign key has been dropped.':($B!=""?'Foreign key has been altered.':'Foreign key has been created.')),$I);if(!$K["drop"])$l='Source and target columns must have the same data type, there must be an index on the target columns and referenced data must exist.';}page_header('Foreign key',$l,array("table"=>$a),h($a));if($_POST){ksort($K["source"]);if($_POST["change"]||$_POST["change-js"])$K["target"]=array();else$K["source"][]="";}elseif($B!=""){$sd=foreign_keys($a);$K=$sd[$B];$K["source"][]="";}else{$K["table"]=$a;$K["source"]=array("");}echo'
<form action="" method="post">
';$vi=array_keys(fields($a));if($K["db"]!="")connection()->select_db($K["db"]);if($K["ns"]!=""){$Bg=get_schema();set_schema($K["ns"]);}$Ah=array_keys(array_filter(table_status('',true),'Adminer\fk_support'));$aj=array_keys(fields(in_array($K["table"],$Ah)?$K["table"]:reset($Ah)));$mg="this.form['change-js'].value = '1'; this.form.submit();";echo"<p><label>".'Target table'.": ".html_select("table",$Ah,$K["table"],$mg)."</label>\n";if(support("scheme")){$Wh=array_filter(adminer()->schemas(),function($Vh){return!preg_match('~^information_schema$~i',$Vh);});echo"<label>".'Schema'.": ".html_select("ns",$Wh,$K["ns"]!=""?$K["ns"]:$_GET["ns"],$mg)."</label>";if($K["ns"]!="")set_schema($Bg);}elseif(JUSH!="sqlite"){$Sb=array();foreach(adminer()->databases()as$j){if(!information_schema($j))$Sb[]=$j;}echo"<label>".'DB'.": ".html_select("db",$Sb,$K["db"]!=""?$K["db"]:$_GET["db"],$mg)."</label>";}echo
input_hidden("change-js"),'<noscript><p><input type="submit" name="change" value="Change"></noscript>
<table>
<thead><tr><th id="label-source">Source<th id="label-target">Target</thead>
';$Fe=0;foreach($K["source"]as$w=>$X){echo"<tr>","<td>".html_select("source[".(+$w)."]",array(-1=>"")+$vi,$X,($Fe==count($K["source"])-1?"foreignAddRow.call(this);":""),"label-source"),"<td>".html_select("target[".(+$w)."]",$aj,idx($K["target"],$w),"","label-target");$Fe++;}echo'</table>
<p>
<label>ON DELETE: ',html_select("on_delete",array(-1=>"")+explode("|",driver()->onActions),$K["on_delete"]),'</label>
<label>ON UPDATE: ',html_select("on_update",array(-1=>"")+explode("|",driver()->onActions),$K["on_update"]),'</label>
',(DRIVER==='pgsql'?html_select("deferrable",array('NOT DEFERRABLE','DEFERRABLE','DEFERRABLE INITIALLY DEFERRED'),$K["deferrable"]).' ':''),doc_link(array('sql'=>"innodb-foreign-key-constraints.html",'mariadb'=>"foreign-keys/",'pgsql'=>"sql-createtable.html#SQL-CREATETABLE-PARMS-REFERENCES",'mssql'=>"t-sql/statements/create-table-transact-sql",'oracle'=>"SQLRF01111",)),'<p>
<input type="submit" value="Save">
<noscript><p><input type="submit" name="add" value="Add column"></noscript>
';if($B!="")echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$B));echo
input_token(),'</form>
';}elseif(isset($_GET["view"])){$a=$_GET["view"];$K=$_POST;$Cg="VIEW";if(JUSH=="pgsql"&&$a!=""){$P=table_status1($a);$Cg=strtoupper($P["Engine"]);}if($_POST&&!$l){$B=trim($K["name"]);$ya=" AS\n$K[select]";$_=ME."table=".urlencode($B);$yf='View has been altered.';$U=($_POST["materialized"]?"MATERIALIZED VIEW":"VIEW");if(!$_POST["drop"]&&$a==$B&&JUSH!="sqlite"&&$U=="VIEW"&&$Cg=="VIEW")query_redirect((JUSH=="mssql"?"ALTER":"CREATE OR REPLACE")." VIEW ".table($B).$ya,$_,$yf);else{$cj=$B."_adminer_".uniqid();drop_create("DROP $Cg ".table($a),"CREATE $U ".table($B).$ya,"DROP $U ".table($B),"CREATE $U ".table($cj).$ya,"DROP $U ".table($cj),($_POST["drop"]?substr(ME,0,-1):$_),'View has been dropped.',$yf,'View has been created.',$a,$B);}}if(!$_POST&&$a!=""){$K=view($a);$K["name"]=$a;$K["materialized"]=($Cg!="VIEW");if(!$l)$l=error();}page_header(($a!=""?'Alter view':'Create view'),$l,array("table"=>$a),h($a));echo'
<form action="" method="post">
<p>Name: <input name="name" value="',h($K["name"]),'" data-maxlength="64" autocapitalize="off">
',(support("materializedview")?" ".checkbox("materialized",1,$K["materialized"],'Materialized view'):""),'<p>';textarea("select",$K["select"]);echo'<p>
<input type="submit" value="Save">
';if($a!="")echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$a));echo
input_token(),'</form>
';}elseif(isset($_GET["event"])){$aa=$_GET["event"];$xe=array("YEAR","QUARTER","MONTH","DAY","HOUR","MINUTE","WEEK","SECOND","YEAR_MONTH","DAY_HOUR","DAY_MINUTE","DAY_SECOND","HOUR_MINUTE","HOUR_SECOND","MINUTE_SECOND");$Ei=array("ENABLED"=>"ENABLE","DISABLED"=>"DISABLE","SLAVESIDE_DISABLED"=>"DISABLE ON SLAVE");$K=$_POST;if($_POST&&!$l){if($_POST["drop"])query_redirect("DROP EVENT ".idf_escape($aa),substr(ME,0,-1),'Event has been dropped.');elseif(in_array($K["INTERVAL_FIELD"],$xe)&&isset($Ei[$K["STATUS"]])){$Uh="\nON SCHEDULE ".($K["INTERVAL_VALUE"]?"EVERY ".q($K["INTERVAL_VALUE"])." $K[INTERVAL_FIELD]".($K["STARTS"]?" STARTS ".q($K["STARTS"]):"").($K["ENDS"]?" ENDS ".q($K["ENDS"]):""):"AT ".q($K["STARTS"]))." ON COMPLETION".($K["ON_COMPLETION"]?"":" NOT")." PRESERVE";queries_redirect(substr(ME,0,-1),($aa!=""?'Event has been altered.':'Event has been created.'),queries(($aa!=""?"ALTER EVENT ".idf_escape($aa).$Uh.($aa!=$K["EVENT_NAME"]?"\nRENAME TO ".idf_escape($K["EVENT_NAME"]):""):"CREATE EVENT ".idf_escape($K["EVENT_NAME"]).$Uh)."\n".$Ei[$K["STATUS"]]." COMMENT ".q($K["EVENT_COMMENT"]).rtrim(" DO\n$K[EVENT_DEFINITION]",";").";"));}}page_header(($aa!=""?'Alter event'.": ".h($aa):'Create event'),$l);if(!$K&&$aa!=""){$L=get_rows("SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ".q(DB)." AND EVENT_NAME = ".q($aa));$K=reset($L);}echo'
<form action="" method="post">
<table class="layout">
<tr><th>Name<td><input name="EVENT_NAME" value="',h($K["EVENT_NAME"]),'" data-maxlength="64" autocapitalize="off">
<tr><th title="datetime">Start<td><input name="STARTS" value="',h("$K[EXECUTE_AT]$K[STARTS]"),'">
<tr><th title="datetime">End<td><input name="ENDS" value="',h($K["ENDS"]),'">
<tr><th>Every<td><input type="number" name="INTERVAL_VALUE" value="',h($K["INTERVAL_VALUE"]),'" class="size"> ',html_select("INTERVAL_FIELD",$xe,$K["INTERVAL_FIELD"]),'<tr><th>Status<td>',html_select("STATUS",$Ei,$K["STATUS"]),'<tr><th>Comment<td><input name="EVENT_COMMENT" value="',h($K["EVENT_COMMENT"]),'" data-maxlength="64">
<tr><th><td>',checkbox("ON_COMPLETION","PRESERVE",$K["ON_COMPLETION"]=="PRESERVE",'On completion preserve'),'</table>
<p>';textarea("EVENT_DEFINITION",$K["EVENT_DEFINITION"]);echo'<p>
<input type="submit" value="Save">
';if($aa!="")echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$aa));echo
input_token(),'</form>
';}elseif(isset($_GET["procedure"])){$ba=($_GET["name"]?:$_GET["procedure"]);$Ph=(isset($_GET["function"])?"FUNCTION":"PROCEDURE");$K=$_POST;$K["fields"]=(array)$K["fields"];if($_POST&&!process_fields($K["fields"])&&!$l){foreach($K["fields"]as$w=>$m){if($m["field"]=="")unset($K["fields"][$w]);}$gg=routine_id($ba,routine($_GET["procedure"],$Ph));$Rf=routine_id($K["name"],$K);$h=create_routine($Ph,$K);$_=substr(ME,0,-1);$yf='Routine has been altered.';if(!$_POST["drop"]&&$gg==$Rf&&connection()->flavor!="mysql")query_redirect(substr_replace($h,' OR REPLACE',6,0),$_,$yf);else{$cj="$K[name]_adminer_".uniqid();drop_create("DROP $Ph $gg",$h,"DROP $Ph $Rf",create_routine($Ph,array("name"=>$cj)+$K),"DROP $Ph ".routine_id($cj,$K),$_,'Routine has been dropped.',$yf,'Routine has been created.',$ba,$K["name"]);}}page_header(($ba!=""?(isset($_GET["function"])?'Alter function':'Alter procedure').": ".h($ba):(isset($_GET["function"])?'Create function':'Create procedure')),$l);if(!$_POST){if($ba=="")$K["language"]="sql";else{$K=routine($_GET["procedure"],$Ph);$K["name"]=$ba;}}$lb=get_vals("SHOW CHARACTER SET");sort($lb);$Qh=routine_languages();echo($lb?"<datalist id='collations'>".optionlist($lb)."</datalist>":""),'
<form action="" method="post" id="form">
<p>Name: <input name="name" value="',h($K["name"]),'" data-maxlength="64" autocapitalize="off">
',($Qh?"<label>".'Language'.": ".html_select("language",$Qh,$K["language"])."</label>\n":""),'<input type="submit" value="Save">
<div class="scrollable">
<table id="edit-fields" class="nowrap">
';edit_fields($K["fields"],$lb,$Ph);if(isset($_GET["function"])){echo"<tr><td>".'Return type';edit_type("returns",(array)$K["returns"],$lb,array(),(JUSH=="pgsql"?array("void","trigger"):array()));}echo'</table>
',script("editFields();"),'</div>
<p>';textarea("definition",$K["definition"],20);echo'<p>
<input type="submit" value="Save">
';if($ba!="")echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$ba));echo
input_token(),'</form>
';}elseif(isset($_GET["sequence"])){$da=$_GET["sequence"];$K=$_POST;if($_POST&&!$l){$z=substr(ME,0,-1);$B=trim($K["name"]);if($_POST["drop"])query_redirect("DROP SEQUENCE ".idf_escape($da),$z,'Sequence has been dropped.');elseif($da=="")query_redirect("CREATE SEQUENCE ".idf_escape($B),$z,'Sequence has been created.');elseif($da!=$B)query_redirect("ALTER SEQUENCE ".idf_escape($da)." RENAME TO ".idf_escape($B),$z,'Sequence has been altered.');else
redirect($z);}page_header($da!=""?'Alter sequence'.": ".h($da):'Create sequence',$l);if(!$K)$K["name"]=$da;echo'
<form action="" method="post">
<p><input name="name" value="',h($K["name"]),'" autocapitalize="off">
<input type="submit" value="Save">
';if($da!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',$da))."\n";echo
input_token(),'</form>
';}elseif(isset($_GET["type"])){$ea=$_GET["type"];$K=$_POST;if($_POST&&!$l){$z=substr(ME,0,-1);if($_POST["drop"])query_redirect("DROP TYPE ".idf_escape($ea),$z,'Type has been dropped.');else
query_redirect("CREATE TYPE ".idf_escape(trim($K["name"]))." $K[as]",$z,'Type has been created.');}page_header($ea!=""?'Alter type'.": ".h($ea):'Create type',$l);if(!$K)$K["as"]="AS ";echo'
<form action="" method="post">
<p>
';if($ea!=""){$Ej=driver()->types();$Fc=type_values($Ej[$ea]);if($Fc)echo"<code class='jush-".JUSH."'>ENUM (".h($Fc).")</code>\n<p>";echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',$ea))."\n";}else{echo'Name'.": <input name='name' value='".h($K['name'])."' autocapitalize='off'>\n",doc_link(array('pgsql'=>"datatype-enum.html",),"?");textarea("as",$K["as"]);echo"<p><input type='submit' value='".'Save'."'>\n";}echo
input_token(),'</form>
';}elseif(isset($_GET["check"])){$a=$_GET["check"];$B=$_GET["name"];$K=$_POST;if($K&&!$l){if(JUSH=="sqlite")$I=recreate_table($a,$a,array(),array(),array(),"",array(),"$B",($K["drop"]?"":$K["clause"]));else{$I=($B==""||queries("ALTER TABLE ".table($a)." DROP CONSTRAINT ".idf_escape($B)));if(!$K["drop"])$I=queries("ALTER TABLE ".table($a)." ADD".($K["name"]!=""?" CONSTRAINT ".idf_escape($K["name"]):"")." CHECK ($K[clause])");}queries_redirect(ME."table=".urlencode($a),($K["drop"]?'Check has been dropped.':($B!=""?'Check has been altered.':'Check has been created.')),$I);}page_header(($B!=""?'Alter check'.": ".h($B):'Create check'),$l,array("table"=>$a));if(!$K){$db=driver()->checkConstraints($a);$K=array("name"=>$B,"clause"=>$db[$B]);}echo'
<form action="" method="post">
<p>';if(JUSH!="sqlite")echo'Name'.': <input name="name" value="'.h($K["name"]).'" data-maxlength="64" autocapitalize="off"> ';echo
doc_link(array('sql'=>"create-table-check-constraints.html",'mariadb'=>"constraint/",'pgsql'=>"ddl-constraints.html#DDL-CONSTRAINTS-CHECK-CONSTRAINTS",'mssql'=>"relational-databases/tables/create-check-constraints",'sqlite'=>"lang_createtable.html#check_constraints",),"?"),'<p>';textarea("clause",$K["clause"]);echo'<p><input type="submit" value="Save">
';if($B!="")echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$B));echo
input_token(),'</form>
';}elseif(isset($_GET["trigger"])){$a=$_GET["trigger"];$B="$_GET[name]";$Aj=trigger_options();$K=(array)trigger($B,$a)+array("Trigger"=>$a."_bi");if($_POST){if(!$l&&in_array($_POST["Timing"],$Aj["Timing"])&&in_array($_POST["Event"],$Aj["Event"])&&in_array($_POST["Type"],$Aj["Type"])){$jg=" ON ".table($a);$nc="DROP TRIGGER ".idf_escape($B).(JUSH=="pgsql"?$jg:"");$_=ME."table=".urlencode($a);if($_POST["drop"])query_redirect($nc,$_,'Trigger has been dropped.');else{if($B!="")queries($nc);queries_redirect($_,($B!=""?'Trigger has been altered.':'Trigger has been created.'),queries(create_trigger($jg,$_POST)));if($B!="")queries(create_trigger($jg,$K+array("Type"=>reset($Aj["Type"]))));}}$K=$_POST;}page_header(($B!=""?'Alter trigger'.": ".h($B):'Create trigger'),$l,array("table"=>$a));echo'
<form action="" method="post" id="form">
<table class="layout">
<tr><th>Time<td>',html_select("Timing",$Aj["Timing"],$K["Timing"],"triggerChange(/^".preg_quote($a,"/")."_[ba][iud]$/, '".js_escape($a)."', this.form);"),'<tr><th>Event<td>',html_select("Event",$Aj["Event"],$K["Event"],"this.form['Timing'].onchange();"),(in_array("UPDATE OF",$Aj["Event"])?" <input name='Of' value='".h($K["Of"])."' class='hidden'>":""),'<tr><th>Type<td>',html_select("Type",$Aj["Type"],$K["Type"]),'</table>
<p>Name: <input name="Trigger" value="',h($K["Trigger"]),'" data-maxlength="64" autocapitalize="off">
',script("qs('#form')['Timing'].onchange();"),'<p>';textarea("Statement",$K["Statement"]);echo'<p>
<input type="submit" value="Save">
';if($B!="")echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$B));echo
input_token(),'</form>
';}elseif(isset($_GET["user"])){$fa=$_GET["user"];$qh=array(""=>array("All privileges"=>""));foreach(get_rows("SHOW PRIVILEGES")as$K){foreach(explode(",",($K["Privilege"]=="Grant option"?"":$K["Context"]))as$Bb)$qh[$Bb=="File access on server"?"Server Admin":$Bb][$K["Privilege"]]=$K["Comment"];}unset($qh["Server Admin"]["Usage"]);foreach($qh["Tables"]as$w=>$X)unset($qh["Databases"][$w]);$Qf=array();if($_POST){foreach($_POST["objects"]as$w=>$X)$Qf[$X]=(array)$Qf[$X]+idx($_POST["grants"],$w,array());}$Bd=array();if(isset($_GET["host"])&&($I=connection()->query("SHOW GRANTS FOR ".q($fa)."@".q($_GET["host"])))){while($K=$I->fetch_row()){if(preg_match('~GRANT (.*) ON (.*) TO ~',$K[0],$A)&&preg_match_all('~ *([^(,]*[^ ,(])( *\([^)]+\))?~',$A[1],$lf,PREG_SET_ORDER)){foreach($lf
as$X){if($X[1]!="USAGE")$Bd["$A[2]$X[2]"][$X[1]]=true;if(preg_match('~ WITH GRANT OPTION~',$K[0]))$Bd["$A[2]$X[2]"]["GRANT OPTION"]=true;}}}}if($_POST&&!$l){$ig=(isset($_GET["host"])?q($fa)."@".q($_GET["host"]):"''");if($_POST["drop"])query_redirect("DROP USER $ig",ME."privileges=",'User has been dropped.');else{$Tf=q($_POST["user"])."@".q($_POST["host"]);$Vg=$_POST["pass"];$Gb=false;$I=true;if($ig!=$Tf){$Gb=queries("CREATE USER $Tf IDENTIFIED BY ".($_POST["hashed"]?"PASSWORD ":"").q($Vg));$I=$Gb;}elseif($Vg!="")$I=queries("SET PASSWORD FOR $Tf = ".(min_version(8,99)||$_POST["hashed"]?q($Vg):"PASSWORD(".q($Vg).")"));if($I){$Mh=array();foreach($Qf
as$bg=>$Ad){if(isset($_GET["grant"]))$Ad=array_filter($Ad);$Ad=array_keys($Ad);if(isset($_GET["grant"]))$Mh=array_diff(array_keys(array_filter($Qf[$bg],'strlen')),$Ad);elseif($ig==$Tf){$fg=array_keys((array)$Bd[$bg]);$Mh=array_diff($fg,$Ad);$Ad=array_diff($Ad,$fg);unset($Bd[$bg]);}if(preg_match('~^(.+)\s*(\(.*\))?$~U',$bg,$A)&&(!grant("REVOKE",$Mh,$A[2]," ON $A[1] FROM $Tf")||!grant("GRANT",$Ad,$A[2]," ON $A[1] TO $Tf"))){$I=false;break;}}}if($I&&isset($_GET["host"])){if($ig!=$Tf)queries("DROP USER $ig");elseif(!isset($_GET["grant"])){foreach($Bd
as$bg=>$Mh){if(preg_match('~^(.+)(\(.*\))?$~U',$bg,$A))grant("REVOKE",array_keys($Mh),$A[2]," ON $A[1] FROM $Tf");}}}queries_redirect(ME."privileges=",(isset($_GET["host"])?'User has been altered.':'User has been created.'),$I);if($Gb)connection()->query("DROP USER $Tf");}}page_header((isset($_GET["host"])?'Username'.": ".h("$fa@$_GET[host]"):'Create user'),$l,array("privileges"=>array('','Privileges')));$K=$_POST;if($K)$Bd=$Qf;else{$K=$_GET+array("host"=>get_val("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', -1)"));$Bd[(DB==""||$Bd?"":idf_escape(addcslashes(DB,"%_\\"))).".*"]=array();}echo'<form action="" method="post">
<table class="layout">
<tr><th>Server<td><input name="host" data-maxlength="60" value="',h($K["host"]),'" autocapitalize="off">
<tr><th>Username<td><input name="user" data-maxlength="80" value="',h($K["user"]),'" autocapitalize="off">
<tr><th>Password<td><input name="pass" id="pass" value="',h($K["pass"]),'" autocomplete="new-password">
',($K["hashed"]?"":script("typePassword(qs('#pass'));")),(min_version(8,99)?"":checkbox("hashed",1,$K["hashed"],'Hashed',"typePassword(this.form['pass'], this.checked);")),'</table>

',"<table class='odds'>\n","<thead><tr><th colspan='2'>".'Privileges'.doc_link(array('sql'=>"grant.html#priv_level"));$r=0;foreach($Bd
as$bg=>$Ad){echo'<th>'.($bg!="*.*"?"<input name='objects[$r]' value='".h($bg)."' size='10' autocapitalize='off'>":input_hidden("objects[$r]","*.*")."*.*");$r++;}echo"</thead>\n";foreach(array(""=>"","Server Admin"=>'Server',"Databases"=>'Database',"Tables"=>'Table',"Procedures"=>'Routine',)as$Bb=>$ac){foreach((array)$qh[$Bb]as$ph=>$qb){echo"<tr><td".($ac?">$ac<td":" colspan='2'").' lang="en" title="'.h($qb).'">'.h($ph);$r=0;foreach($Bd
as$bg=>$Ad){$B="'grants[$r][".h(strtoupper($ph))."]'";$Y=$Ad[strtoupper($ph)];if($Bb=="Server Admin"&&$bg!=(isset($Bd["*.*"])?"*.*":".*"))echo"<td>";elseif(isset($_GET["grant"]))echo"<td><select name=$B><option><option value='1'".($Y?" selected":"").">".'Grant'."<option value='0'".($Y=="0"?" selected":"").">".'Revoke'."</select>";else
echo"<td align='center'><label class='block'>","<input type='checkbox' name=$B value='1'".($Y?" checked":"").($ph=="All privileges"?" id='grants-$r-all'>":">".($ph=="Grant option"?"":script("qsl('input').onclick = function () { if (this.checked) formUncheck('grants-$r-all'); };"))),"</label>";$r++;}}}echo"</table>\n",'<p>
<input type="submit" value="Save">
';if(isset($_GET["host"]))echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',"$fa@$_GET[host]"));echo
input_token(),'</form>
';}elseif(isset($_GET["processlist"])){if(support("kill")){if($_POST&&!$l){$Me=0;foreach((array)$_POST["kill"]as$X){if(adminer()->killProcess($X))$Me++;}queries_redirect(ME."processlist=",lang_format(array('%d process has been killed.','%d processes have been killed.'),$Me),$Me||!$_POST["kill"]);}}page_header('Process list',$l);echo'
<form action="" method="post">
<div class="scrollable">
<table class="nowrap checkable odds">
',script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});");$r=-1;foreach(adminer()->processList()as$r=>$K){if(!$r){echo"<thead><tr lang='en'>".(support("kill")?"<th>":"");foreach($K
as$w=>$X)echo"<th>$w".doc_link(array('sql'=>"show-processlist.html#processlist_".strtolower($w),'pgsql'=>"monitoring-stats.html#PG-STAT-ACTIVITY-VIEW",'oracle'=>"REFRN30223",));echo"</thead>\n";}echo"<tr>".(support("kill")?"<td>".checkbox("kill[]",$K[JUSH=="sql"?"Id":"pid"],0):"");foreach($K
as$w=>$X)echo"<td>".($X!=""&&((JUSH=="sql"&&$w=="Info"&&preg_match("~Query|Killed~",$K["Command"]))||(JUSH=="pgsql"&&$w=="query")||(JUSH=="oracle"&&$w=="sql_text"))?"<code class='jush-".JUSH."' data-full='".h($X)."'>".shorten_utf8($X,100,"</code>").' <a href="'.h(ME.($K["db"]!=""?"db=".urlencode($K["db"])."&":"")."sql=".urlencode($X)).'">'.'Clone'.'</a>'.' <a href="" class="jsonly copy">🗐</a>':h($X));echo"\n";}echo'</table>
</div>
<p>
',script("copyCode(qsl('table'));");if(support("kill"))echo($r+1)."/".sprintf('%d in total',max_connections()),"<p><input type='submit' value='".'Kill'."'>\n";echo
input_token(),'</form>
',script("tableCheck();");}elseif(isset($_GET["select"])){$a=$_GET["select"];$S=table_status1($a);$v=indexes($a);$n=fields($a);$sd=column_foreign_keys($a);$dg=$S["Oid"];$oa=get_settings("adminer_import");$Nh=array();$e=array();$bi=array();$vg=array();$gj="";foreach($n
as$w=>$m){$B=adminer()->fieldName($m);$Of=html_entity_decode(strip_tags($B),ENT_QUOTES);if(isset($m["privileges"]["select"])&&$B!=""){$e[$w]=$Of;if(is_shortable($m))$gj=adminer()->selectLengthProcess();}if(isset($m["privileges"]["where"])&&$B!="")$bi[$w]=$Of;if(isset($m["privileges"]["order"])&&$B!="")$vg[$w]=$Of;$Nh+=$m["privileges"];}list($M,$Cd)=adminer()->selectColumnsProcess($e,$v);$M=array_unique($M);$Cd=array_unique($Cd);$Ae=count($Cd)<count($M);$Z=adminer()->selectSearchProcess($n,$v);$ug=adminer()->selectOrderProcess($n,$v);$y=adminer()->selectLimitProcess();if($_GET["val"]&&is_ajax()){header("Content-Type: text/plain; charset=utf-8");foreach($_GET["val"]as$Jj=>$K){$ya=convert_field($n[key($K)]);$M=array($ya?:idf_escape(key($K)));$Z[]=where_check($Jj,$n);$J=driver()->select($a,$M,$Z,$M);if($J)echo
first($J->fetch_row());}exit;}$lh=$Lj=array();foreach($v
as$u){if($u["type"]=="PRIMARY"){$lh=array_flip($u["columns"]);$Lj=($M?$lh:array());foreach($Lj
as$w=>$X){if(in_array(idf_escape($w),$M))unset($Lj[$w]);}break;}}if($dg&&!$lh){$lh=$Lj=array($dg=>0);$v[]=array("type"=>"PRIMARY","columns"=>array($dg));}if($_POST&&!$l){$nk=$Z;if(!$_POST["all"]&&is_array($_POST["check"])){$db=array();foreach($_POST["check"]as$Za)$db[]=where_check($Za,$n);$nk[]="((".implode(") OR (",$db)."))";}$nk=($nk?"\nWHERE ".implode(" AND ",$nk):"");if($_POST["export"]){save_settings(array("output"=>$_POST["output"],"format"=>$_POST["format"]),"adminer_import");dump_headers($a);adminer()->dumpTable($a,"");$wd=($M?implode(", ",$M):"*").convert_fields($e,$n,$M)."\nFROM ".table($a);$Ed=($Cd&&$Ae?"\nGROUP BY ".implode(", ",$Cd):"").($ug?"\nORDER BY ".implode(", ",$ug):"");$H="SELECT $wd$nk$Ed";if(is_array($_POST["check"])&&!$lh){$Hj=array();foreach($_POST["check"]as$X)$Hj[]="(SELECT".limit($wd,"\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$n).$Ed,1).")";$H=implode(" UNION ALL ",$Hj);}adminer()->dumpData($a,"table",$H);adminer()->dumpFooter();exit;}if(!adminer()->selectEmailProcess($Z,$sd)){if($_POST["save"]||$_POST["delete"]){$I=true;$pa=0;$O=array();if(!$_POST["delete"]){foreach($n
as$B=>$X){$t=bracket_escape($B);if(isset($_POST["fields"][$t])||$_FILES["fields-$t"]){$X=process_input($n[$B]);if($X!==null&&($_POST["clone"]||$X!==false))$O[idf_escape($B)]=($X!==false?$X:idf_escape($B));}}}if($_POST["delete"]||$O){$H=($_POST["clone"]?"INTO ".table($a)." (".implode(", ",array_keys($O)).")\nSELECT ".implode(", ",$O)."\nFROM ".table($a):"");if($_POST["all"]||($lh&&is_array($_POST["check"]))||$Ae){$I=($_POST["delete"]?driver()->delete($a,$nk):($_POST["clone"]?queries("INSERT $H$nk".driver()->insertReturning($a)):driver()->update($a,$O,$nk)));$pa=connection()->affected_rows;if(is_object($I))$pa+=$I->num_rows;}else{foreach((array)$_POST["check"]as$X){$mk="\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$n);$I=($_POST["delete"]?driver()->delete($a,$mk,1):($_POST["clone"]?queries("INSERT".limit1($a,$H,$mk)):driver()->update($a,$O,$mk,1)));if(!$I)break;$pa+=connection()->affected_rows;}}}$yf=lang_format(array('%d item has been affected.','%d items have been affected.'),$pa);if($_POST["clone"]&&$I&&$pa==1){$Re=last_id($I);if($Re)$yf=sprintf('Item%s has been inserted.'," $Re");}queries_redirect(remove_from_uri($_POST["all"]&&$_POST["delete"]?"page":""),$yf,$I);if(!$_POST["delete"]){$gh=(array)$_POST["fields"];edit_form($a,array_intersect_key($n,$gh),$gh,!$_POST["clone"],$l);page_footer();exit;}}elseif(!$_POST["import"]){if(!$_POST["val"])$l='Ctrl+click on a value to modify it.';else{$I=true;$pa=0;foreach($_POST["val"]as$Jj=>$K){$O=array();foreach($K
as$w=>$X){$w=bracket_escape($w,true);$O[idf_escape($w)]=(preg_match('~char|text~',$n[$w]["type"])||$X!=""?adminer()->processInput($n[$w],$X):"NULL");}$I=driver()->update($a,$O," WHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($Jj,$n),($Ae||$lh?0:1)," ");if(!$I)break;$pa+=connection()->affected_rows;}queries_redirect(remove_from_uri(),lang_format(array('%d item has been affected.','%d items have been affected.'),$pa),$I);}}elseif(!is_string($ed=get_file("csv_file",true)))$l=upload_error($ed);elseif(!preg_match('~~u',$ed))$l='File must be in UTF-8 encoding.';else{save_settings(array("output"=>$oa["output"],"format"=>$_POST["separator"]),"adminer_import");$I=true;$mb=array_keys($n);preg_match_all('~(?>"[^"]*"|[^"\r\n]+)+~',$ed,$lf);$pa=count($lf[0]);driver()->begin();$hi=($_POST["separator"]=="csv"?",":($_POST["separator"]=="tsv"?"\t":";"));$L=array();foreach($lf[0]as$w=>$X){preg_match_all("~((?>\"[^\"]*\")+|[^$hi]*)$hi~",$X.$hi,$mf);if(!$w&&!array_diff($mf[1],$mb)){$mb=$mf[1];$pa--;}else{$O=array();foreach($mf[1]as$r=>$jb)$O[idf_escape($mb[$r])]=($jb==""&&$n[$mb[$r]]["null"]?"NULL":q(preg_match('~^".*"$~s',$jb)?str_replace('""','"',substr($jb,1,-1)):$jb));$L[]=$O;}}$I=(!$L||driver()->insertUpdate($a,$L,$lh));if($I)driver()->commit();queries_redirect(remove_from_uri("page"),lang_format(array('%d row has been imported.','%d rows have been imported.'),$pa),$I);driver()->rollback();}}}$Ri=adminer()->tableName($S);if(is_ajax()){page_headers();ob_start();}else
page_header('Select'.": $Ri",$l);$O=null;if(isset($Nh["insert"])||!support("table")){$Lg=array();foreach((array)$_GET["where"]as$X){if(isset($sd[$X["col"]])&&count($sd[$X["col"]])==1&&($X["op"]=="="||(!$X["op"]&&(is_array($X["val"])||!preg_match('~[_%]~',$X["val"])))))$Lg["set"."[".bracket_escape($X["col"])."]"]=$X["val"];}$O=$Lg?"&".http_build_query($Lg):"";}adminer()->selectLinks($S,$O);if(!$e&&support("table"))echo"<p class='error'>".'Unable to select the table'.($n?".":": ".error())."\n";else{echo"<form action='' id='form'>\n","<div style='display: none;'>";hidden_fields_get();echo(DB!=""?input_hidden("db",DB).(isset($_GET["ns"])?input_hidden("ns",$_GET["ns"]):""):""),input_hidden("select",$a),"</div>\n";adminer()->selectColumnsPrint($M,$e);adminer()->selectSearchPrint($Z,$bi,$v);adminer()->selectOrderPrint($ug,$vg,$v);adminer()->selectLimitPrint($y);adminer()->selectLengthPrint($gj);adminer()->selectActionPrint($v);echo"</form>\n";$D=$_GET["page"];$vd=null;if($D=="last"){$vd=get_val(count_rows($a,$Z,$Ae,$Cd));$D=floor(max(0,intval($vd)-1)/$y);}$ci=$M;$Dd=$Cd;if(!$ci){$ci[]="*";$Cb=convert_fields($e,$n,$M);if($Cb)$ci[]=substr($Cb,2);}foreach($M
as$w=>$X){$m=$n[idf_unescape($X)];if($m&&($ya=convert_field($m)))$ci[$w]="$ya AS $X";}if(!$Ae&&$Lj){foreach($Lj
as$w=>$X){$ci[]=idf_escape($w);if($Dd)$Dd[]=idf_escape($w);}}$I=driver()->select($a,$ci,$Z,$Dd,$ug,$y,$D,true);if(!$I)echo"<p class='error'>".error()."\n";else{if(JUSH=="mssql"&&$D)$I->seek($y*$D);$zc=array();echo"<form action='' method='post' enctype='multipart/form-data'>\n";$L=array();while($K=$I->fetch_assoc()){if($D&&JUSH=="oracle")unset($K["RNUM"]);$L[]=$K;}if($_GET["page"]!="last"&&$y&&$Cd&&$Ae&&JUSH=="sql")$vd=get_val(" SELECT FOUND_ROWS()");if(!$L)echo"<p class='message'>".'No rows.'."\n";else{$Ha=adminer()->backwardKeys($a,$Ri);echo"<div class='scrollable'>","<table id='table' class='nowrap checkable odds'>",script("mixin(qs('#table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true), onkeydown: editingKeydown});"),"<thead><tr>".(!$Cd&&$M?"":"<td><input type='checkbox' id='all-page' class='jsonly'>".script("qs('#all-page').onclick = partial(formCheck, /check/);","")." <a href='".h($_GET["modify"]?remove_from_uri("modify"):$_SERVER["REQUEST_URI"]."&modify=1")."'>".'Modify'."</a>");$Pf=array();$yd=array();reset($M);$yh=1;foreach($L[0]as$w=>$X){if(!isset($Lj[$w])){$X=idx($_GET["columns"],key($M))?:array();$m=$n[$M?($X?$X["col"]:current($M)):$w];$B=($m?adminer()->fieldName($m,$yh):($X["fun"]?"*":h($w)));if($B!=""){$yh++;$Pf[$w]=$B;$d=idf_escape($w);$Ud=remove_from_uri('(order|desc)[^=]*|page').'&order%5B0%5D='.urlencode($w);$ac="&desc%5B0%5D=1";echo"<th id='th[".h(bracket_escape($w))."]'>".script("mixin(qsl('th'), {onmouseover: partial(columnMouse), onmouseout: partial(columnMouse, ' hidden')});","");$xd=apply_sql_function($X["fun"],$B);$ui=isset($m["privileges"]["order"])||$xd!=$B;echo($ui?"<a href='".h($Ud.($ug[0]==$d||$ug[0]==$w?$ac:''))."'>$xd</a>":$xd);$xf=($ui?"<a href='".h($Ud.$ac)."' title='".'descending'."' class='text'> ↓</a>":'');if(!$X["fun"]&&isset($m["privileges"]["where"])){$xf
.='<a href="#fieldset-search" title="'.'Search'.'" class="text jsonly"> =</a>';$xf
.=script("qsl('a').onclick = partial(selectSearch, '".js_escape($w)."');");}echo($xf?"<span class='column hidden'>$xf</span>":"");}$yd[$w]=$X["fun"];next($M);}}$Ye=array();if($_GET["modify"]){foreach($L
as$K){foreach($K
as$w=>$X)$Ye[$w]=max($Ye[$w],min(40,strlen(utf8_decode($X))));}}echo($Ha?"<th>".'Relations':"")."</thead>\n";if(is_ajax())ob_end_clean();foreach(adminer()->rowDescriptions($L,$sd)as$Nf=>$K){$Ij=unique_array($L[$Nf],$v);if(!$Ij){$Ij=array();reset($M);foreach($L[$Nf]as$w=>$X){if(!preg_match('~^(COUNT|AVG|GROUP_CONCAT|MAX|MIN|SUM)\(~',current($M)))$Ij[$w]=$X;next($M);}}$Jj="";foreach($Ij
as$w=>$X){$m=(array)$n[$w];if((JUSH=="sql"||JUSH=="pgsql")&&preg_match('~char|text|enum|set~',$m["type"])&&strlen($X)>64){$w=(strpos($w,'(')?$w:idf_escape($w));$w="MD5(".(JUSH!='sql'||preg_match("~^utf8~",$m["collation"])?$w:"CONVERT($w USING ".charset(connection()).")").")";$X=md5($X);}$Jj
.="&".($X!==null?urlencode("where[".bracket_escape($w)."]")."=".urlencode($X===false?"f":$X):"null%5B%5D=".urlencode($w));}echo"<tr>".(!$Cd&&$M?"":"<td>".checkbox("check[]",substr($Jj,1),in_array(substr($Jj,1),(array)$_POST["check"])).($Ae||information_schema(DB)?"":" <a href='".h(ME."edit=".urlencode($a).$Jj)."' class='edit'>".'edit'."</a>"));reset($M);foreach($K
as$w=>$X){if(isset($Pf[$w])){$d=current($M);$m=(array)$n[$w];if($X!=""&&(!isset($zc[$w])||$zc[$w]!=""))$zc[$w]=(is_mail($X)?$Pf[$w]:"");$z="";if(is_blob($m)&&$X!="")$z=ME.'download='.urlencode($a).'&field='.urlencode($w).$Jj;if(!$z&&$X!==null){foreach((array)$sd[$w]as$o){if(count($sd[$w])==1||end($o["source"])==$w){$z="";foreach($o["source"]as$r=>$vi)$z
.=where_link($r,$o["target"][$r],$L[$Nf][$vi]);$z=($o["db"]!=""?preg_replace('~([?&]db=)[^&]+~','\1'.urlencode($o["db"]),ME):ME).'select='.urlencode($o["table"]).$z;if($o["ns"])$z=preg_replace('~([?&]ns=)[^&]+~','\1'.urlencode($o["ns"]),$z);if(count($o["source"])==1)break;}}}if($d=="COUNT(*)"){$z=ME."select=".urlencode($a);$r=0;foreach((array)$_GET["where"]as$W){if(!array_key_exists($W["col"],$Ij))$z
.=where_link($r++,$W["col"],$W["val"],$W["op"]);}foreach($Ij
as$Ie=>$W)$z
.=where_link($r++,$Ie,$W);}$Vd=select_value($X,$z,$m,$gj);$s=h("val[$Jj][".bracket_escape($w)."]");$hh=idx(idx($_POST["val"],$Jj),bracket_escape($w));$Nj=idx($m["privileges"],"update");$vc=!is_array($K[$w])&&is_utf8($Vd)&&$L[$Nf][$w]==$K[$w]&&!$yd[$w]&&!$m["generated"]&&$Nj;$U=(preg_match('~^(AVG|MIN|MAX)\((.+)\)~',$d,$A)?$n[idf_unescape($A[2])]["type"]:$m["type"]);$ej=preg_match('~text|json|lob~',$U);$Be=preg_match(number_type(),$U)||preg_match('~^(CHAR_LENGTH|ROUND|FLOOR|CEIL|TIME_TO_SEC|COUNT|SUM)\(~',$d);echo"<td id='$s'".($Be&&($X===null||is_numeric(strip_tags($Vd))||$U=="money")?" class='number'":"");if(($_GET["modify"]&&$vc&&$X!==null)||$hh!==null){$Hd=h($hh!==null?$hh:$K[$w]);echo">".($ej?"<textarea name='$s' cols='30' rows='".(substr_count($K[$w],"\n")+1)."'>$Hd</textarea>":"<input name='$s' value='$Hd' size='$Ye[$w]'>");}else{$gf=strpos($Vd,"<i>…</i>");echo($Nj?" data-text='".($gf?2:($ej?1:0))."'".($vc?"":" data-warning='".h('Use edit link to modify this value.')."'"):"").">$Vd";}}next($M);}if($Ha)echo"<td>";adminer()->backwardKeysPrint($Ha,$L[$Nf]);echo"</tr>\n";}if(is_ajax())exit;echo"</table>\n","</div>\n";}if(!is_ajax()){if($L||$D){$Mc=true;if($_GET["page"]!="last"){if(!$y||(count($L)<$y&&($L||!$D)))$vd=($D?$D*$y:0)+count($L);elseif(JUSH!="sql"||!$Ae){$vd=($Ae?false:found_rows($S,$Z));if(intval($vd)<max(1e4,2*($D+1)*$y))$vd=first(slow_query(count_rows($a,$Z,$Ae,$Cd)));elseif(JUSH=='sql'||JUSH=='pgsql')$Mc=false;}}$Jg=($y&&($vd===false||$vd>$y||$D));if($Jg)echo(($vd===false?count($L)+1:$vd-$D*$y)>$y?'<p><a href="'.h(remove_from_uri("page|next").($_GET["next"]?"&next=".urlencode($_GET["next"]):"")."&page=".($D+1)).'" class="loadmore">'.'Load more data'.'</a>'.script("qsl('a').onclick = partial(selectLoadMore, $y, '".'Loading'."…');",""):''),"\n";echo"<div class='footer'><div>\n";if($Jg){$qf=($vd===false?$D+($L?(count($L)>=$y?2:1):0):floor(($vd-1)/$y));echo"<fieldset>";if(JUSH!="simpledb"&&JUSH!="redis"){echo"<legend><a href='".h(remove_from_uri("page"))."'>".'Page'."</a></legend>",script("qsl('a').onclick = function () { pageClick(this.href, +prompt('".'Page'."', '".($D+1)."')); return false; };"),pagination(0,$D).($D>5?" …":"");for($r=max(1,$D-4);$r<min($qf,$D+5);$r++)echo
pagination($r,$D);if($qf>0)echo($D+5<$qf?" …":""),($Mc&&$vd!==false?pagination($qf,$D):" <a href='".h(remove_from_uri("page")."&page=last")."' title='~$qf'>".'last'."</a>");}else
echo"<legend>".'Page'."</legend>",pagination(0,$D).($D>1?" …":""),($D?pagination($D,$D):""),($qf>$D?pagination($D+1,$D).($qf>$D+1?" …":""):"");echo"</fieldset>\n";}echo"<fieldset>","<legend>".'Whole result'."</legend>";$ec=($Mc?"":"~ ").$vd;$ng="const checked = formChecked(this, /check/); selectCount('selected', this.checked ? '$ec' : checked); selectCount('selected2', this.checked || !checked ? '$ec' : checked);";echo
checkbox("all",1,0,($vd!==false?($Mc?"":"~ ").lang_format(array('%d row','%d rows'),$vd):""),$ng)."\n","</fieldset>\n";if(adminer()->selectCommandPrint())echo'<fieldset',($_GET["modify"]?'':' class="jsonly"'),'><legend>Modify</legend><div>
<input type="submit" value="Save"',($_GET["modify"]?'':' title="'.'Ctrl+click on a value to modify it.'.'"'),'>
</div></fieldset>
<fieldset><legend>Selected <span id="selected"></span></legend><div>
<input type="submit" name="edit" value="Edit">
<input type="submit" name="clone" value="Clone">
<input type="submit" name="delete" value="Delete">',confirm(),'</div></fieldset>
';$td=adminer()->dumpFormat();foreach((array)$_GET["columns"]as$d){if($d["fun"]){unset($td['sql']);break;}}if($td){print_fieldset("export",'Export'." <span id='selected2'></span>");$Gg=adminer()->dumpOutput();echo($Gg?html_select("output",$Gg,$oa["output"])." ":""),html_select("format",$td,$oa["format"])," <input type='submit' name='export' value='".'Export'."'>\n","</div></fieldset>\n";}adminer()->selectEmailPrint(array_filter($zc,'strlen'),$e);echo"</div></div>\n";}if(adminer()->selectImportPrint())echo"<p>","<a href='#import'>".'Import'."</a>",script("qsl('a').onclick = partial(toggle, 'import');",""),"<span id='import'".($_POST["import"]?"":" class='hidden'").">: ",file_input("<input type='file' name='csv_file'> ".html_select("separator",array("csv"=>"CSV,","csv;"=>"CSV;","tsv"=>"TSV"),$oa["format"])." <input type='submit' name='import' value='".'Import'."'>"),"</span>";echo
input_token(),"</form>\n",(!$Cd&&$M?"":script("tableCheck();"));}}}if(is_ajax()){ob_end_clean();exit;}}elseif(isset($_GET["variables"])){$P=isset($_GET["status"]);page_header($P?'Status':'Variables');$dk=($P?adminer()->showStatus():adminer()->showVariables());if(!$dk)echo"<p class='message'>".'No rows.'."\n";else{echo"<table>\n";foreach($dk
as$K){echo"<tr>";$w=array_shift($K);echo"<th><code class='jush-".JUSH.($P?"status":"set")."'>".h($w)."</code>";foreach($K
as$X)echo"<td>".nl_br(h($X));}echo"</table>\n";}}elseif(isset($_GET["script"])){header("Content-Type: text/javascript; charset=utf-8");if($_GET["script"]=="db"){$Li=array("Data_length"=>0,"Index_length"=>0,"Data_free"=>0);foreach(table_status()as$B=>$S){json_row("Comment-$B",h($S["Comment"]));if(!is_view($S)||preg_match('~materialized~i',$S["Engine"])){foreach(array("Engine","Collation")as$w)json_row("$w-$B",h($S[$w]));foreach($Li+array("Auto_increment"=>0,"Rows"=>0)as$w=>$X){if($S[$w]!=""){$X=format_number($S[$w]);if($X>=0)json_row("$w-$B",($w=="Rows"&&$X&&$S["Engine"]==(JUSH=="pgsql"?"table":"InnoDB")?"~ $X":$X));if(isset($Li[$w]))$Li[$w]+=($S["Engine"]!="InnoDB"||$w!="Data_free"?$S[$w]:0);}elseif(array_key_exists($w,$S))json_row("$w-$B","?");}}}foreach($Li
as$w=>$X)json_row("sum-$w",format_number($X));json_row("");}elseif($_GET["script"]=="kill")connection()->query("KILL ".number($_POST["kill"]));else{foreach(count_tables(adminer()->databases())as$j=>$X){json_row("tables-$j",$X);json_row("size-$j",db_size($j));}json_row("");}exit;}else{$Yi=array_merge((array)$_POST["tables"],(array)$_POST["views"]);if($Yi&&!$l&&!$_POST["search"]){$I=true;$yf="";if(JUSH=="sql"&&$_POST["tables"]&&count($_POST["tables"])>1&&($_POST["drop"]||$_POST["truncate"]||$_POST["copy"]))queries("SET foreign_key_checks = 0");if($_POST["truncate"]){if($_POST["tables"])$I=truncate_tables($_POST["tables"]);$yf='Tables have been truncated.';}elseif($_POST["move"]){$I=move_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$yf='Tables have been moved.';}elseif($_POST["copy"]){$I=copy_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$yf='Tables have been copied.';}elseif($_POST["drop"]){if($_POST["views"])$I=drop_views($_POST["views"]);if($I&&$_POST["tables"])$I=drop_tables($_POST["tables"]);$yf='Tables have been dropped.';}elseif(JUSH=="sqlite"&&$_POST["check"]){foreach((array)$_POST["tables"]as$R){foreach(get_rows("PRAGMA integrity_check(".q($R).")")as$K)$yf
.="<b>".h($R)."</b>: ".h($K["integrity_check"])."<br>";}}elseif(JUSH!="sql"){$I=(JUSH=="sqlite"?queries("VACUUM"):apply_queries("VACUUM".($_POST["optimize"]?" ANALYZE":""),$_POST["tables"]));$yf='Tables have been optimized.';}elseif(!$_POST["tables"])$yf='No tables.';elseif($I=queries(($_POST["optimize"]?"OPTIMIZE":($_POST["check"]?"CHECK":($_POST["repair"]?"REPAIR":"ANALYZE")))." TABLE ".implode(", ",array_map('Adminer\idf_escape',$_POST["tables"])))){while($K=$I->fetch_assoc())$yf
.="<b>".h($K["Table"])."</b>: ".h($K["Msg_text"])."<br>";}queries_redirect($_SERVER["REQUEST_URI"],$yf,$I);}page_header(($_GET["ns"]==""?'Database'.": ".h(DB):'Schema'.": ".h($_GET["ns"])),$l,true);if(adminer()->homepage()){if($_GET["ns"]!==""){$ug=$_GET["order"];echo"<h3 id='tables-views'>".'Tables and views'."</h3>\n";$Xi=($ug?table_status():tables_list());if(!$Xi)echo"<p class='message'>".'No tables.'."\n";else{echo"<form action='' method='post'>\n";if(support("table")){echo"<fieldset><legend>".'Search data in tables'." <span id='selected2'></span></legend><div>",html_select("op",adminer()->operators(),idx($_POST,"op",JUSH=="elastic"?"should":"LIKE %%"))," <input type='search' name='query' value='".h($_POST["query"])."'>",script("qsl('input').onkeydown = partialArg(bodyKeydown, 'search');","")," <input type='submit' name='search' value='".'Search'."'>\n","</div></fieldset>\n";if($_POST["search"]&&$_POST["query"]!=""){$_GET["where"][0]["op"]=$_POST["op"];search_tables();}}echo"<div class='scrollable'>\n","<table class='nowrap checkable odds'>\n",script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});"),'<thead><tr class="wrap">','<td><input id="check-all" type="checkbox" class="jsonly">'.script("qs('#check-all').onclick = partial(formCheck, /^(tables|views)\[/);",""),'<th><a href="'.h(substr(ME,0,-1)).'">'.'Table'.'</a>';$e=array("Engine"=>array('Engine'.doc_link(array('sql'=>'storage-engines.html'))));if(collations())$e["Collation"]=array('Collation'.doc_link(array('sql'=>'charset-charsets.html','mariadb'=>'supported-character-sets-and-collations/')));if(function_exists('Adminer\alter_table'))$e["Data_length"]=array('Data Length'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT','oracle'=>'REFRN20286')),"create",'Alter table');if(support('indexes'))$e["Index_length"]=array('Index Length'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT')),"indexes",'Alter indexes');$e["Data_free"]=array('Data Free'.doc_link(array('sql'=>'show-table-status.html')),"edit",'New item');if(function_exists('Adminer\alter_table'))$e["Auto_increment"]=array('Auto Increment'.doc_link(array('sql'=>'example-auto-increment.html','mariadb'=>'auto_increment/')),"auto_increment=1&create",'Alter table');$e["Rows"]=array('Rows'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'catalog-pg-class.html#CATALOG-PG-CLASS','oracle'=>'REFRN20286')),"select",'Select data');if(support("comment"))$e["Comment"]=array('Comment'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'functions-info.html#FUNCTIONS-INFO-COMMENT-TABLE')));foreach($e
as$w=>$d)echo"<th><a href='".h(ME)."order=$w'>$d[0]</a>";echo"</thead>\n";if($ug){uasort($Xi,function($ia,$Ea)use($ug){$J=($ia[$ug]<$Ea[$ug]?-1:($ia[$ug]>$Ea[$ug]?1:0));return(in_array($ug,array('Engine','Collation','Comment'))?$J:-$J);});}$T=0;foreach($Xi
as$B=>$P){$gk=($ug?is_view($P):$P!==null&&!preg_match('~table|sequence~i',$P));$P=($ug?$P:array('Engine'=>$P));$s=h("Table-".$B);echo'<tr><td>'.checkbox(($gk?"views[]":"tables[]"),$B,in_array("$B",$Yi,true),"","","",$s),'<th>'.(support("table")||support("indexes")?"<a href='".h(ME)."table=".urlencode($B)."' title='".'Show structure'."' id='$s'>".h($B).'</a>':h($B));if($gk&&!preg_match('~materialized~i',$P['Engine'])){$kj='View';echo'<td colspan="'.(count($e)-(support("comment")?2:1)).'">'.(support("view")?"<a href='".h(ME)."view=".urlencode($B)."' title='".'Alter view'."'>$kj</a>":$kj),'<td align="right"><a href="'.h(ME)."select=".urlencode($B).'" title="'.'Select data'.'">?</a>';if(support("comment"))echo'<td>'.h($P['Comment']);}else{foreach($e
as$w=>$d){$s=" id='$w-".h($B)."'";$X=idx($P,$w,'?');echo($d[1]?"<td align='right'><a href='".h(ME."$d[1]=").urlencode($B)."'$s title='$d[2]'>".(is_numeric($X)?($X<0?'?':($w=="Rows"&&$X&&$P["Engine"]==(JUSH=="pgsql"?"table":"InnoDB")?'~ ':'').format_number($X)):$X)."</a>":"<td id='$w-".h($B)."'>".h($X));}$T++;}echo"\n";}echo"<tr><td><th>".sprintf('%d in total',count($Xi)),"<td>".h(JUSH=="sql"?get_val("SELECT @@default_storage_engine"):""),(collations()?"<td>".h(db_collation(DB,collations())):'');foreach(array("Data_length","Index_length","Data_free")as$w)echo($e[$w]?"<td align='right' id='sum-$w'>":"");echo"\n","</table>\n",($ug?'':script("ajaxSetHtml('".js_escape(ME)."script=db');")),"</div>\n";if(!information_schema(DB)){$Zj="<input type='submit' value='".'Vacuum'."'> ".on_help("'VACUUM'");$qg="<input type='submit' name='optimize' value='".'Optimize'."'> ".on_help(JUSH=="sql"?"'OPTIMIZE TABLE'":"'VACUUM ANALYZE'");$nh=(JUSH=="sqlite"?$Zj."<input type='submit' name='check' value='".'Check'."'> ".on_help("'PRAGMA integrity_check'"):(JUSH=="pgsql"?$Zj.$qg:(JUSH=="sql"?"<input type='submit' value='".'Analyze'."'> ".on_help("'ANALYZE TABLE'").$qg."<input type='submit' name='check' value='".'Check'."'> ".on_help("'CHECK TABLE'")."<input type='submit' name='repair' value='".'Repair'."'> ".on_help("'REPAIR TABLE'"):""))).(function_exists('Adminer\truncate_tables')?"<input type='submit' name='truncate' value='".'Truncate'."'> ".on_help(JUSH=="sqlite"?"'DELETE'":"'TRUNCATE".(JUSH=="pgsql"?"'":" TABLE'")).confirm():"").(function_exists('Adminer\drop_tables')?"<input type='submit' name='drop' value='".'Drop'."'>".on_help("'DROP TABLE'").confirm():"");echo($nh?"<div class='footer'><div>\n<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>$nh\n</div></fieldset>\n":"");$i=(support("scheme")?adminer()->schemas():adminer()->databases());$Zh="";if(count($i)!=1&&function_exists('Adminer\move_tables')){echo"<fieldset><legend>".'Move to other database'." <span id='selected3'></span></legend><div>";$j=(isset($_POST["target"])?$_POST["target"]:(support("scheme")?$_GET["ns"]:DB));echo($i?html_select("target",$i,$j):'<input name="target" value="'.h($j).'" autocapitalize="off">'),"</label> <input type='submit' name='move' value='".'Move'."'>",(support("copy")?" <input type='submit' name='copy' value='".'Copy'."'> ".checkbox("overwrite",1,$_POST["overwrite"],'overwrite'):""),"</div></fieldset>\n";$Zh=" selectCount('selected3', formChecked(this, /^(tables|views)\[/));";}echo"<input type='hidden' name='all' value=''>",script("qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^(tables|views)\[/));".(support("table")?" selectCount('selected2', formChecked(this, /^tables\[/) || $T);":"")."$Zh }"),input_token(),"</div></div>\n";}echo"</form>\n",script("tableCheck();");}echo(function_exists('Adminer\alter_table')?"<p class='links'><a href='".h(ME)."create='>".'Create table'."</a>\n":''),(support("view")?"<a href='".h(ME)."view='>".'Create view'."</a>\n":"");if(support("routine")){echo"<h3 id='routines'>".'Routines'."</h3>\n";$Rh=routines();if($Rh){echo"<table class='odds'>\n",'<thead><tr><th>'.'Name'.'<td>'.'Type'.'<td>'.'Return type'."<td></thead>\n";foreach($Rh
as$K){$B=($K["SPECIFIC_NAME"]==$K["ROUTINE_NAME"]?"":"&name=".urlencode($K["ROUTINE_NAME"]));echo'<tr>','<th><a href="'.h(ME.($K["ROUTINE_TYPE"]!="PROCEDURE"?'callf=':'call=').urlencode($K["SPECIFIC_NAME"]).$B).'">'.h($K["ROUTINE_NAME"]).'</a>','<td>'.h($K["ROUTINE_TYPE"]),'<td>'.h($K["DTD_IDENTIFIER"]),'<td><a href="'.h(ME.($K["ROUTINE_TYPE"]!="PROCEDURE"?'function=':'procedure=').urlencode($K["SPECIFIC_NAME"]).$B).'">'.'Alter'."</a>";}echo"</table>\n";}echo'<p class="links">'.(support("procedure")?'<a href="'.h(ME).'procedure=">'.'Create procedure'.'</a>':'').'<a href="'.h(ME).'function=">'.'Create function'."</a>\n";}if(support("sequence")){echo"<h3 id='sequences'>".'Sequences'."</h3>\n";$ki=get_vals("SELECT sequence_name FROM information_schema.sequences WHERE sequence_schema = current_schema() ORDER BY sequence_name");if($ki){echo"<table class='odds'>\n","<thead><tr><th>".'Name'."</thead>\n";foreach($ki
as$X)echo"<tr><th><a href='".h(ME)."sequence=".urlencode($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo"<p class='links'><a href='".h(ME)."sequence='>".'Create sequence'."</a>\n";}if(support("type")){echo"<h3 id='user-types'>".'User types'."</h3>\n";$Wj=types();if($Wj){echo"<table class='odds'>\n","<thead><tr><th>".'Name'."</thead>\n";foreach($Wj
as$X)echo"<tr><th><a href='".h(ME)."type=".urlencode($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo"<p class='links'><a href='".h(ME)."type='>".'Create type'."</a>\n";}if(support("event")){echo"<h3 id='events'>".'Events'."</h3>\n";$L=get_rows("SHOW EVENTS");if($L){echo"<table>\n","<thead><tr><th>".'Name'."<td>".'Schedule'."<td>".'Start'."<td>".'End'."<td></thead>\n";foreach($L
as$K)echo"<tr>","<th>".h($K["Name"]),"<td>".($K["Execute at"]?'At given time'."<td>".$K["Execute at"]:'Every'." ".$K["Interval value"]." ".$K["Interval field"]."<td>$K[Starts]"),"<td>$K[Ends]",'<td><a href="'.h(ME).'event='.urlencode($K["Name"]).'">'.'Alter'.'</a>';echo"</table>\n";$Kc=get_val("SELECT @@event_scheduler");if($Kc&&$Kc!="ON")echo"<p class='error'><code class='jush-sqlset'>event_scheduler</code>: ".h($Kc)."\n";}echo'<p class="links"><a href="'.h(ME).'event=">'.'Create event'."</a>\n";}}}}page_footer();