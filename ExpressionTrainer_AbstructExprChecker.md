# АbstractExprChecker(_В процессе заполнения_, Чуть позже продумаю названия полей , значения по умолчанию ) #

<?php
<br>class abstract_expr_checker {<br>
<blockquote>// Корень дерева текущего ответа студента.<br>
<br>$сurrentresponse;<br>
// Массив верных ответов.<br>
<br>$answers;</blockquote>

<blockquote>// Конструктор класса по стандарту php 5.<br>
// Принимает корень дерево текущего ответа студента, корень дерева верного ответа(Нужен ли ? - наверное нет),<br>
// что-то там с директориями мудла - надо уточнить, ничего не возвращает...<br>
<br>function construct($student_response, $ , $Dir);</blockquote>

<blockquote>// Задает ответ студента, ничего не возвращает.<br>
<br>function set_response($response);</blockquote>

<blockquote>// Возвращает текущей ответ студента, установленный в классе. Ничего не принимает.<br>
<br>function get_response();</blockquote>

<blockquote>// Метод возвращающий степень верности текущего ответа студента/<br>
// Принимает верное решение, возвращает число [0..1] (степень верности).<br>
<br>function proximity_measure($answer);</blockquote>

<blockquote>// Контрпример на текущий ответ студента. Возвращает строку, Принимает верный ответ Answer.<br>
<br>function сounter_example($answer);</blockquote>

<blockquote>// Добавляет массив Answer'ов<br>
<br>function add_array_answerы($answers_array);</blockquote>

<blockquote>// Добавляет очередной Answer<br>
<br>function add_answer($answer);</blockquote>

<blockquote>// Удаляет Answer, если таковой есть, возвращает true если был, иначе false.<br>
<br>function del_answer($answer);</blockquote>

<blockquote>// Возвращает Answer(корень дерева ответа), наиболее близкий к ответу студента.<br>
// Если есть несколько лучших - возвращает первый.<br>
<br>function get_aest_answer();<br>
}