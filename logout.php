<?php
require_once __DIR__ . '/includes/init.php';

Auth::logout();
Session::setFlash('success', 'Đăng xuất thành công!');
redirect('/');
