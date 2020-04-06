<?php
class Server
{
    private $serv;

    public function __construct() {
        $this->serv = new swoole_http_server("0.0.0.0", 9501);
        $this->serv->set([//用于设置运行时的各项参数。服务器启动后通过$serv->setting来访问Server->set方法设置的参数数组。
            'enable_coroutine' => true, //enable_coroutine 选项相当于在回调中关闭以前版本的SW_COROUTINE宏开关, 关闭时在回调事件中不再创建协程，但是保留用户创建协程的能力。
            'task_enable_coroutine' => true,//用于保存任务上下文，并返回结果。
            ]);
     /*   $this->serv->set(array(
            'worker_num'=> 2, //开启2个worker进程
            'max_request'     => 4, //每个worker进程 max_request设置为4次
            'document_root'   => '',
            'enable_static_handler' => true,
            'daemonize'       => false, //守护进程(true/false)
        ));
*/
        $this->serv->set(array(
            'worker_num' => 2,//设置启动的Worker进程数
            'daemonize' => false,//守护进程化。如果不启用守护进程，当ssh终端退出后，程序将被终止运行。
        ));
        /*erv->on注册Server的事件回调函数。第1个参数是回调的名称，第2个函数是回调的PHP函数*/
        $this->serv->on('Start', [$this, 'onStart']);//启动后在主进程（master）的主线程回调此函数
        $this->serv->on('WorkerStart', [$this, 'onWorkStart']);//
        $this->serv->on('ManagerStart', [$this, 'onManagerStart']);
        $this->serv->on("Request", [$this, 'onRequest']);

        $this->serv->start();//启动服务器，监听所有TCP/UDP端口
    }

    public function onStart($serv) {//启动后在主进程（master）的主线程回调此函数
        echo "#### onStart ####".PHP_EOL;
        swoole_set_process_name('swoole_process_server_master');

        echo "SWOOLE ".SWOOLE_VERSION . " 服务已启动".PHP_EOL;
        echo "master_pid: {$serv->master_pid}".PHP_EOL;
        echo "manager_pid: {$serv->manager_pid}".PHP_EOL;
        echo "########".PHP_EOL.PHP_EOL;
    }

    public function onManagerStart($serv) {//当管理进程启动时调用它
        echo "#### onManagerStart ####".PHP_EOL.PHP_EOL;
        swoole_set_process_name('swoole_process_server_manager');
    }

    public function onWorkStart($serv, $worker_id) {//此事件在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用
        echo "#### onWorkStart ####".PHP_EOL.PHP_EOL;
        swoole_set_process_name('swoole_process_server_worker');

        spl_autoload_register(function ($className) {
            $classPath = __DIR__ . "/controller/" . $className . ".php";
            if (is_file($classPath)) {
                require "{$classPath}";
                return;
            }
        });

       // date_default_timezone_set(timezone_identifier:'Asia/Shanghai');
    }

    public function onRequest($request, $response) {//在收到一个完整的Http请求后，会回调此函数
        $response->header("Server", "SwooleServer");//设置HTTP响应的Header信息。第一个参数开头需要大写
        $response->header("Content-Type", "text/html; charset=utf-8");
        $server = $request->server;//Http请求相关的服务器信息，相当于PHP的$_SERVER数组。包含了Http请求的方法，URL路径，客户端IP等信息。
        $path_info    = $server['path_info'];
        $request_uri  = $server['request_uri'];
        //响应404请求
        if ($path_info == '/favicon.ico' || $request_uri == '/favicon.ico') {
            return $response->end();
        }

        $controller = 'index';

        $error_return = array(
            "status"=>"fail",
            "errno"=>"url error",
        );

        if ($path_info != '/') {
            $path_info = explode('/',$path_info);

            $count_path_info = count($path_info);
            if ($count_path_info !=2) {//参数不是两个返回路径错误提示
                return $response->end(json_encode($error_return));

            }
            $controller = (isset($path_info[1]) && !empty($path_info[1])) ? $path_info[1] : $controller;
        }else{
            return $response->end(json_encode($error_return));
        }

        $method="run";
        $result=json_encode(($error_return));//json_encode() 用于对变量进行 JSON 编码，该函数如果执行成功返回 JSON 数据，否则返回 FALSE
	    var_dump($request);//var_dump() 函数用于输出变量的相关信息。

        if (class_exists($controller)) {//检查类是否被定义
            $class = new $controller();
            if (method_exists($controller, $method)) {//检查类的方法是否在指定的$controller中
                $result = $class->$method($request);
            }
        }

        return $response->end($result);
    }
}

$server = new Server();
/*
$http = new swoole_http_server("127.0.0.1", 9501);
$http->on('request', function ($request, $response) {
    var_dump($request->server["request_uri"]);
    $response->end("hello");
});
$http->start();
*/
//mysql
/*
$http = new swoole_http_server("127.0.0.1", 9501);
$http->on('request', function ($request, $response) {
    $servername='127.0.0.1';
    $username='root';
    $password='root';
    $dbname = "testdb";
    $es=" ";
    $conn =new mysqli($servername,$username,$password,$dbname);
    if($conn->connect_error){
        $response->end("<h1> connn error ". $conn->connect_error."</h1>");
    }else {
            $sql = "select * from testTable";
        $request=$conn->query($sql);
        while($row=$request->fetch_assoc()){
            $e=$row["test"];
            $es=$es." ".$e;
        }
        $response->end($es);
    }
});
$http->start();
*/



/*
class Server
{
    private $serv;

    public function __construct() {
        $this->serv = new swoole_server("0.0.0.0", 9501);
        $this->serv->set(array(
            'worker_num' => 8,
            'daemonize' => false,
        ));

        $this->serv->on('Start', array($this, 'onStart'));
        $this->serv->on('Connect', array($this, 'onConnect'));
        $this->serv->on('Receive', array($this, 'onReceive'));
        $this->serv->on('Close', array($this, 'onClose'));

        $this->serv->start();
    }

    public function onStart( $serv ) {
        echo "Start\n";
    }

    public function onConnect( $serv, $fd, $from_id ) {
        $serv->send( $fd, "Hello {$fd}!" );
    }

    public function onReceive( swoole_server $serv, $fd, $from_id, $data ) {
        echo "Get Message From Client {$fd}:{$data}\n";
        $serv->send($fd, $data);
    }

    public function onClose( $serv, $fd, $from_id ) {
        echo "Client {$fd} close connection\n";
    }
}
// 启动服务器 Start the server
$server = new Server();*/
