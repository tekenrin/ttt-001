001
<?php
002
//引入共同檔
003
require_once "header.php";
004
 
005
//變數初始化
006
$op=isset($_REQUEST['op'])?$_REQUEST['op']:"";
007
$sn= isset($_REQUEST['sn'])? intval($_REQUEST['sn']) : "";
008
 
009
//流程控制
010
switch($op){
011
 
012
  case "admin":
013
  $main= ($_SESSION['isLeader']) ? list_article($_SESSION['isLeader']) : login_form();
014
  break;
015
 
016
  case "login":
017
  leader_login($_POST['class_sn'], $_POST['pass']);
018
  header("location:index.php?op=admin");
019
  break;
020
 
021
  case "logout":
022
  $_SESSION['isLeader']=null;
023
  header("location:index.php");
024
  break;
025
 
026
  case "insert":
027
  insert_article();
028
  header("location:{$_SERVER['PHP_SELF']}");
029
  break;
030
   
031
  case "edit":
032
  $main=article_form($sn);
033
  break;
034
   
035
 
036
  case "update":
037
  update_article($sn);
038
  header("location:{$_SERVER['PHP_SELF']}");
039
  break;
040
 
041
  case "delete":
042
  delete_article($sn);
043
  header("location:{$_SERVER['PHP_SELF']}");
044
  break;
045
   
046
  case "search":
047
  $main=list_article();
048
  break;
049
 
050
  default:
051
  $main=empty($sn)?list_article():show_article($sn);
052
  break;
053
}
054
 
055
 
056
//套用樣板
057
theme("theme.html");
058
 
059
/*************** 功能函數區 **************/
060
//登入表單
061
function login_form(){
062
  $now_seme=get_seme();
063
  $sql="select class_sn,class_name from tncomu_class where access='1' and seme='{$now_seme}' ";
064
  $result=mysql_query($sql) or die($sql);
065
  $opt="";
066
  while(list($class_sn , $class_name) = mysql_fetch_row($result)){
067
    $opt.="<option value='$class_sn'>$class_name</option>";
068
  }
069
   
070
  $main="
071
  <form action='{$_SERVER['PHP_SELF']}' method='post'>
072
  <select name='class_sn'>
073
    $opt
074
  </select>
075
           
076
  密碼：<input type='password' name='pass'>
077
  <input type='hidden' name='op' value='login'>
078
  <input type='submit' value='登入'>
079
  </form>";
080
  return $main;
081
}
082
 
083
//進行認證
084
function leader_login($class_sn='',$pass=''){
085
  if(empty($class_sn) or empty($pass))return;
086
 
087
   //設定SQL語法
088
    $sql="select passwd from `tncomu_class` where class_sn='{$class_sn}'";
089
    $result=mysql_query($sql) or die("無法執行：".mysql_error());
090
    list($passwd)=mysql_fetch_row($result);
091
 
092
  if($passwd==$pass){
093
    $_SESSION['isLeader']=$class_sn;
094
  }
095
}
096
 
097
 
098
//秀出某一篇文章
099
function show_article($sn=null){
100
  $now_seme=get_seme();
101
 
102
  $sql="update `tncomu_article` set `counter`=`counter`+1 where sn='$sn'";
103
  mysql_query($sql) or die("無法執行：".mysql_error());
104
 
105
  //設定SQL語法
106
  $sql="select a.* , b.class_name
107
  from `tncomu_article` as a left join `tncomu_class` as b on a.class_sn=b.class_sn
108
  where a.enable='1' and a.sn='$sn'";
109
 
110
  //執行SQL語法
111
  $result = mysql_query($sql) or die("無法執行：".mysql_error());
112
 
113
  $data=mysql_fetch_assoc($result);
114
   
115
  $data['content']=($data['mode']=="圖片")?"<img src='pic/{$data['content']}'>":$data['content'];
116
   
117
  $main="
118
  <h1>「{$data['stud_name']}」的學習收藏</h1>
119
  <div style='text-align:right;margin:10px 0px;'>{$data['class_name']}</div>
120
  <div>{$data['content']}</div>
121
  <div style='text-align:right;margin:10px 0px;'>{$data['post_time']}</div>
122
  ";
123
   
124
  return $main;
125
}
126
 
127
 
128
//列出所有文章
129
function list_article($class_sn=null){
130
  require_once "pagebar.php";
131
  $now_seme=get_seme();
132
   
133
  $and_class_sn=empty($class_sn)?"":"and a.class_sn='$class_sn'";
134
   
135
  $and_key=empty($_GET['key'])?"":"and (a.stud_name like '%{$_GET['key']}%' or a.content like '%{$_GET['key']}%')";
136
   
137
   
138
  //設定SQL語法
139
  $sql="select a.* , b.class_name
140
  from `tncomu_article` as a left join `tncomu_class` as b on a.class_sn=b.class_sn
141
  where a.enable='1' and b.seme='$now_seme' $and_class_sn $and_key
142
  order by a.post_time desc";
143
 
144
 
145
  //PageBar(資料數, 每頁顯示幾筆資料, 最多顯示幾個頁數選項);
146
  mysql_query($sql);
147
  $total=mysql_affected_rows();
148
  $navbar = new PageBar($total, 10, 10);
149
  $mybar = $navbar->makeBar();
150
  $bar= "<p align='center'>{$mybar['left']}{$mybar['center']}{$mybar['right']}</p>";
151
  $sql.=$mybar['sql'];
152
 
153
 
154
  //執行SQL語法
155
  $result = mysql_query($sql) or die("無法執行：".mysql_error());
156
 
157
  $js="";
158
  if($_SESSION['isLeader']){
159
    $js="
160
      <script>
161
      function delete_func(sn){
162
       var sure = window.confirm('確定要刪除此資料？');
163
       if (!sure)   return;
164
       location.href='{$_SERVER['PHP_SELF']}?op=delete&sn=' + sn;
165
      }
166
      </script>";
167
  }
168
 
169
 
170
  $main="
171
  $js
172
  $bar
173
  <table>
174
  <tr>
175
    <th>所屬班級</th>
176
    <th>學員姓名</th>
177
    <th>發布日期</th>
178
    <th>人氣</th>
179
    <th>相關功能</th>
180
  </tr>";
181
 
182
  $i=2;
183
  while($data=mysql_fetch_assoc($result)){
184
   
185
    $color=($i % 2)?"white":"#D0D0D0";
186
    $i++;
187
     
188
    $tool=($_SESSION['isLeader']==$data['class_sn'] and !empty($_SESSION['isLeader']))?"| <a href='javascript:delete_func({$data['sn']})'>刪除</a>":"";
189
     
190
    $main.="
191
    <tr style='background-color:$color;'>
192
    <td>{$data['class_name']}</td>
193
    <td><a href='{$_SERVER['PHP_SELF']}?sn={$data['sn']}'>{$data['stud_name']}</a></td>
194
    <td>{$data['post_time']}</td>
195
    <td>{$data['counter']}</td>
196
    <td><a href = '{$_SERVER['PHP_SELF']}?sn={$data['sn']}&op=edit' >編輯</a>{$tool}</td>
197
    </tr>";
198
  }
199
 
200
  $main.="</table>
201
  $bar";
202
 
203
  return $main;
204
}
205
 
206
//輸入學習收藏的表單
207
function article_form($sn=''){
208
 
209
  $next_op="insert";
210
 
211
  //初始值設定
212
  $data['stud_name'] = $data['class_sn'] = $data['content'] = $data['enable'] = $radio1 = $radio0 = "";
213
 
214
  if($sn){
215
   //設定SQL語法
216
    $sql="select * from `tncomu_article` where sn='{$sn}'";
217
 
218
    //執行SQL語法
219
    $result=mysql_query($sql) or die("無法執行：".mysql_error());
220
 
221
    //擷取資料回來存到 $data
222
    $data=mysql_fetch_assoc($result);
223
 
224
    //還原下拉選單預設值
225
 
226
    $radio1=($data['enable']=="1")?"checked":"";
227
    $radio0=($data['enable']=="0")?"checked":"";
228
    $next_op="update";
229
  }
230
 
231
 
232
  $now_seme=get_seme();
233
 
234
  $sql="select class_sn,class_name from tncomu_class where access='1' and seme='{$now_seme}' ";
235
  $result=mysql_query($sql) or die($sql);
236
  $opt="";
237
  while(list($class_sn , $class_name) = mysql_fetch_row($result)){
238
    $selected = ($class_sn == $data['class_sn'])?"selected":"";
239
    $opt.="<option value='$class_sn' $selected>$class_name</option>";
240
  }
241
 
242
 
243
  $main="<h3 style='color:#0066CC'>輸入學習收藏</h3>
244
  <script type='text/javascript' src='ckeditor/ckeditor.js'></script>
245
  <form action='{$_SERVER['PHP_SELF']}' method='post' enctype='multipart/form-data'>
246
    <table>
247
      <tr>
248
        <th>您的姓名：</th>
249
        <td><input type='text' name='stud_name' size='10' value='{$data['stud_name']}'></td>
250
        <th>{$now_seme}班級：</th>
251
        <td>
252
          <select name='class_sn'>
253
            <option value=''>請選擇{$now_seme}班級</option>
254
            $opt
255
          </select>
256
        </td>
257
      </tr>
258
      <tr>
259
        <td colspan=4>
260
          <textarea name='content' id='editor' cols=50 rows=8>{$data['content']}</textarea>
261
          <script type='text/javascript'>
262
            CKEDITOR.replace('editor' , { skin : 'v2' , toolbar : 'MyToolbar' } );
263
          </script>
264
        </td>
265
      </tr>
266
 
267
      <tr>
268
        <th>上傳圖檔：</th>
269
        <td colspan=3><input type='file' name='pic' accept='image/*'></td>
270
      </tr>
271
       
272
      <tr>
273
        <th>設定密碼：</th>
274
        <td><input type='text' name='text_passwd' size='10'></td>
275
        <th>是否發布？</th>
276
        <td>
277
          <input type='radio' name='enable' value='1' id='enable' $radio1><label for='enable'>發布</label>
278
          <input type='radio' name='enable' value='0' id='unable' $radio0><label for='unable'>暫不發布</label>
279
          <input type='hidden' name='sn' value='$sn'>
280
          <input type='hidden' name='op' value='$next_op'>
281
          <input type='submit' value='儲存'>
282
        </td>
283
      </tr>
284
    </table>
285
  </form>
286
  ";
287
  return $main;
288
}
289
 
290
 
291
//執行儲存動作
292
function insert_article(){
293
  //過濾姓名
294
  $stud_name=trim($_POST['stud_name']);
295
  $stud_name=strip_tags($stud_name);
296
  $stud_name = (! get_magic_quotes_gpc()) ? addslashes($stud_name) : $stud_name;
297
  $stud_name=htmlspecialchars($stud_name);
298
  //過濾內容
299
  $_POST['content'] = (! get_magic_quotes_gpc()) ? addslashes($_POST['content']) : $_POST['content'];
300
  $_POST['content']=htmlspecialchars($_POST['content']);
301
  //過濾密碼
302
  $_POST['text_passwd'] = (! get_magic_quotes_gpc()) ? addslashes($_POST['text_passwd']) : $_POST['text_passwd'];
303
   
304
  $class_sn=intval($_POST['class_sn']);
305
 
306
  $sql="INSERT INTO `tncomu_article`(`stud_name`, `content`, `post_time`, `enable`, `class_sn`, `mode`, `text_passwd`) VALUES ('{$stud_name}' , '{$_POST['content']}' , now(), '{$_POST['enable']}', '{$class_sn}', '文字', '{$_POST['text_passwd']}')";
307
  mysql_query($sql) or die(mysql_error().$sql);
308
   
309
  $sn=mysql_insert_id();
310
   
311
  if($_FILES['pic']['name']){
312
    $ext=strtolower(strrchr($_FILES['pic']['name'],"."));
313
    move_uploaded_file($_FILES['pic']['tmp_name'],"pic/{$sn}{$ext}");
314
     
315
    $sql="update `tncomu_article` set `mode`='圖片',content='{$sn}{$ext}' where `sn`='{$sn}'";
316
    mysql_query($sql) or die(mysql_error().$sql);
317
  }
318
  return "儲存完畢";
319
}
320
 
321
//執行更新動作
322
function update_article($sn=''){
323
 
324
  if($sn){
325
   //設定SQL語法
326
    $sql="select text_passwd from `tncomu_article` where sn='{$sn}'";
327
    //執行SQL語法
328
    $result=mysql_query($sql) or die("無法執行：".mysql_error());
329
    //擷取資料回來存到 $data
330
    list($text_passwd)=mysql_fetch_row($result);
331
 
332
    if($text_passwd!=$_POST['text_passwd'] or empty($_POST['text_passwd'])){
333
        return;
334
    }
335
  }
336
 
337
  //過濾姓名
338
  $stud_name=trim($_POST['stud_name']);
339
  $stud_name=strip_tags($stud_name);
340
  $stud_name = (! get_magic_quotes_gpc()) ? addslashes($stud_name) : $stud_name;
341
  $stud_name=htmlspecialchars($stud_name);
342
  //過濾內容
343
  $_POST['content'] = (! get_magic_quotes_gpc()) ? addslashes($_POST['content']) : $_POST['content'];
344
  $_POST['content']=htmlspecialchars($_POST['content']);
345
  //過濾密碼
346
  $_POST['text_passwd'] = (! get_magic_quotes_gpc()) ? addslashes($_POST['text_passwd']) : $_POST['text_passwd'];
347
 
348
  $class_sn=intval($_POST['class_sn']);
349
 
350
  $sql="update `tncomu_article` set `stud_name`='{$stud_name}', `content`='{$_POST['content']}', `post_time`=now(), `enable`='{$_POST['enable']}', `class_sn`='{$_POST['class_sn']}' where `sn`='{$sn}'";
351
  mysql_query($sql) or die(mysql_error().$sql);
352
  return "儲存完畢";
353
}
354
 
355
//刪除文章資料
356
function delete_article($sn=null){
357
 
358
  //設定SQL語法
359
  $sql="delete from `tncomu_article` where sn='{$sn}'";
360
 
361
  //執行SQL語法
362
  mysql_query($sql) or die("無法執行：".mysql_error());
363
 
364
  //執行完轉向
365
  header("location: {$_SERVER['PHP_SELF']}");
366
}
367
?>
