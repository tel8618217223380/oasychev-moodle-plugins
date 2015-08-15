# Introduction #

После того как пользователь выделит текст на странице, он будет отправлен на сервер с помощью javascript, используя http протокол, где будет обрабатываться с помощью PHP. Затем обработанные данные будут отправлены обратно. всё это будет происходить без обновления полностью всей страницы.

файл index.html
```

<html>

<head>

<title>отправка выделенного текста на сервер

Unknown end tag for &lt;/title&gt;



<link rel="stylesheet" href="mystyles.css">

<script type="text/javascript" src = "myscript.js" >

Unknown end tag for &lt;/script&gt;




Unknown end tag for &lt;/head&gt;



<body bgcolor = "#FCF162">

<div>
<input type="radio" name="switch" value="on" checked="checked">Включить
<input type="radio" name="switch" value="off">Выключить


Unknown end tag for &lt;/div&gt;



<div class="divst">

<p><h2>Пример с текстовым полем:

Unknown end tag for &lt;/h2&gt;



Unknown end tag for &lt;/p&gt;


<center><input name="textbox" type="text" onselect="SendReq('textbox', getSelection()) ">

Unknown end tag for &lt;/center&gt;





Unknown end tag for &lt;/div&gt;



<div class="divst" >

<p><h2>Пример с обычным текстом: 

Unknown end tag for &lt;/h2&gt;



Unknown end tag for &lt;/p&gt;



<p onmouseup="SendReq('P', getSelection())" > fn hglskdn -glks ;jdnfgljs  nfdgljsnd  ^fgljk nss kdfh *ngl gf 435 345 = 5435

Unknown end tag for &lt;/p&gt;





Unknown end tag for &lt;/div&gt;



<div class="divst" id="answer">

<p><h2>Результат: 

Unknown end tag for &lt;/h2&gt;



Unknown end tag for &lt;/p&gt;





Unknown end tag for &lt;/div&gt;





Unknown end tag for &lt;/body&gt;





Unknown end tag for &lt;/html&gt;


```

файл mystyles.css
```
.divst
{
	background: #16A17F ;
	border: 4px solid black;
	margin: 5px;
	width: 400px ; 
}
```

файл myscript.js
```

var req = Create();  
//функция для создания объекта XMLHttpRequest
function Create() 
{  
	if(navigator.appName == "Microsoft Internet Explorer")
	{  
		req = new ActiveXObject("Microsoft.XMLHTTP");  
    	}
	else
	{  
        		req = new XMLHttpRequest();  
    	}  
    	return req;  
}
/////////////////////функция отправки запроса////////////////////////////
function Request(query) 
{   
        //capture.php - файл который будет обрабатывать запрос
        //true - задает асинхронные запросы
        //post - тип запроса
	req.open('post', 'capture.php' , true ); 
        
        //назначение функции, которая будет срабатывать при смене состояний
	req.onreadystatechange = Refresh; 
	req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8"); 
	req.send(query);       
}  
//////////функция, которая запускает весь процесс. Вешается на события onclick или onmouseup///////////////
function SendReq(n, t) //здесь n - имя элемента, в котором выделен текст, t - сам текст
{  
	var radios  = document.getElementsByName("switch");
	if(radios[0].checked)
	{ 
                //encodeURIComponent нужно на случай, если пользователь выделит
                //символы вроде &%'/ ...
		var name = encodeURIComponent(n); 
		var txt = encodeURIComponent(t); 
		var query = 'name='+name+'&txt='+txt; 
		Request(query) ;
	}
}
//////////////////////////////////////////////////////////////////////////////////
function Refresh() 
{ 
        //пока запрос не придёт, в последнем DIV'е будет отображаться анимация загрузки loading.gif 
	var a = req.readyState;    
	if( a == 4 ) 
	{  
		var b = req.responseText;  
		document.getElementById('answer').innerHTML ='<p><h2>Результат: </h2></p> '+ b;  
	} 
	else 
	{ 
		document.getElementById('answer').innerHTML = '<p><h2>Результат: </h2></p><img src="loading.gif" />'; 
	} 
} 
```

файл capture.php
```
<?php 
$txt = (isset($_POST['name']) && isset($_POST['txt']))? (' в элементе ' . $_POST['name'] . ' выделен текст: ' . $_POST['txt'] ) : NULL; 
sleep(4);  
echo $txt; 
?>
```