<?php
//创建WebSocket Server对象，监听0.0.0.0:9502端口
$ws = new Swoole\WebSocket\Server('0.0.0.0', 9502);
$table = new Swoole\Table(1024);
$table->column('num', Swoole\Table::TYPE);
$table->create();
//监听WebSocket连接打开事件
$ws->on('Open', function ($ws, $request) use (&$num) {
    $num++;
    file_put_contents("php://output", "客户端{$num}\n");
    $ws->push($request->fd, "hello, welcome\n");
});

//监听WebSocket消息事件
$ws->on('Message', function ($ws, $frame) {
    echo "Message: {$frame->data}\n";
    $ws->push($frame->fd, "server: {$frame->data}");
});

//监听WebSocket连接关闭事件
$ws->on('Close', function ($ws, $fd) use (&$num) {
    $num--;
    echo "client-{$fd} is closed\n";
});

$ws->start();
