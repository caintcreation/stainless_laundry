<?php
declare(strict_types=1);
function csrf_token(): string { if(empty($_SESSION['_csrf'])) $_SESSION['_csrf']=bin2hex(random_bytes(32)); return $_SESSION['_csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">'; }
function verify_csrf(): void { if($_SERVER['REQUEST_METHOD']==='POST'&&!hash_equals($_SESSION['_csrf']??'',$_POST['csrf_token']??'')){http_response_code(419);exit('The form expired. Please go back and try again.');} }