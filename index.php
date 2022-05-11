<?php
use Swoole\Http\Response;
use Swoole\Http\Request;
use Swoole\WebSocket\Server;
class ws{
private $server;
private static $msgs='';
public function __construct(){
    $this->server= new Swoole\WebSocket\Server('0.0.0.0', 9500); 
    $this->server->set([
		//  默认多线。多线会造成消息问题
        'worker_num'=>1,
    ]);
    $this->server->on('open', function ($server, $request) {
        // echo "server: handshake success with fd{$request->fd}\n";s
        $server->push($request->fd, "hello, welcome\n {$request->fd}号用户<br>".self::$msgs);
    });
    $this->server->on('message', function ( $server, $frame) {
        echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
        $server->push($frame->fd, "this is server");        
        self::$msgs.= "{$frame->fd}用户说了".$frame->data ;
        foreach($this->server->connections as $fd) { 
            if ($this->server->isEstablished($fd)){      
                $this->server->push($fd, self::$msgs) ;     
            }  
        }
    });
    $this->server->on('close', function ($ser, $fd) {
        echo "client {$fd} closed\n";
    });
    $this->server->on('request', function ($request, $response) {
        $response->header('Content-Type', 'text/html; charset=utf-8');
    $response->end(<<<HTML
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<title>Document</title>
	</head>
	<body>
		<div style="margin: auto; width: 600px; height: 500px">
			<h1 id="user" style="margin: auto">Welcome Swoole Server</h1>
			<div
				class="msg"
				style="width: 100%; height: 80%; border: 1px solid red; overflow: auto"
			></div>
			<input type="text" name="" id="msgCon" />
			<button id="emit">发送</button>
		</div>
		<script>
			var msgCon = document.querySelector('#msgCon')
			var user = document.querySelector('#user')
			var msg = document.querySelector('.msg')
			var con = document.querySelector('#emit')
			var wsServer = 'ws://127.0.0.1:9500'
			var websocket = new WebSocket(wsServer)
			con.onclick = function () {
				if (msgCon.value.length == 0) {
					alert('消息不能空')
					return false
				} else {
					// let msgs= msg.innerHTML.toString() + msgCon.value + '<br>'
					websocket.send('<br>' + msgCon.value + '<br>'+new Date() + '<br>' )
					// websocket.send(  msgCon.value )
					// msg.innerHTML += msgCon.value + '<br>'
					msgCon.value = ''
				}
			}
			websocket.onopen = function (evt) {
				console.log('Connected to WebSocket server.')
				// user.innerHTML
			}
			websocket.onclose = function (evt) {
				console.log('Disconnected')
			}
			websocket.onmessage = function (evt) {
				// console.log('Retrieved data from server: ' + evt.data);
				msg.innerHTML = evt.data
			}
			websocket.onerror = function (evt, e) {
				console.log('Error occured: ' + evt.data)
			}
		</script>
	</body>
</html>
HTML
        );
    });
    $this->server->start();
}
}
new ws();


