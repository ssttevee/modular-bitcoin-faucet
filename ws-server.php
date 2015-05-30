<?php
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require __dir__ . "/autoload.php";

class ModuleCommunicator implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients[$conn] = new StdClass;

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $conn, $msg) {
        parse_str($msg, $msg);

        if($msg == null) {
            echo "Connection {$conn->resourceId} sent a bad message\n";
            $this->respond($conn, "Bad message");
        } else if($msg["op"] == "login") {
            if(!array_key_exists("address", $msg)) {
                echo "Connection {$conn->resourceId} tried to login without specifying a bitcoin address\n";
                $this->respond($conn, "Bitcoin address not specified");
            } else if(\LinusU\Bitcoin\AddressValidator::isValid($msg["address"])) {
                try {
                    $this->clients[$conn]->manager = \AllTheSatoshi\FaucetManager::_($msg["address"]);
                    echo "Connection {$conn->resourceId} successfully logged in\n";
                    $this->respond($conn, "Login successful");
                } catch(Exception $e) {
                    echo $e->getMessage() . "\n";
                    $this->respond($conn, $e->getMessage());
                }
            } else {
                echo "Connection {$conn->resourceId} tried to login with an invalid bitcoin address: {$msg["address"]}\n";
                $this->respond($conn, "Invalid bitcoin address");
            }
        } else if(empty($this->clients[$conn]->manager)) {
            echo "Connection {$conn->resourceId} tried to access without logging in\n";
            $this->respond($conn, "Not logged in");
        } else if(isset($msg["module"])) {
            $module = get_module($msg["module"]);
            if(!isset($module)) {
                echo "Connection {$conn->resourceId} trying to access non-existent module\n";
                $this->respond($conn, "Module does not exist");
            } else if($module->useWebSocket) {
                $faucet = $module->getFaucetInstance($this->clients[$conn]->manager->address);
                if(is_callable([$faucet, $msg["op"]]) && $msg["op"] != "__set") {
                    echo "Connection {$conn->resourceId} called ";
                    $this->respond($conn, call_user_func_array([$faucet, $msg["op"]], $msg["params"]));
                } else {
                    echo "Connection {$conn->resourceId} tried to call a non-existent function: ";
                    $this->respond($conn, "Operation does not exist");
                }
                echo get_class($faucet) . "::" . $msg["op"] . "(". implode(", ", $msg["params"]) .")\n";
            } else {
                echo "Connection {$conn->resourceId} tried to access ws-disabled module\n";
                $this->respond($conn, "WebSocket not enabled");
            }
        } else {
            echo "Connection {$conn->resourceId} tried to do non-existent operation\n";
            $this->respond($conn, "Operation does not exist");
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it
        unset($this->clients[$conn]);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    private function respond($conn, $response, $success = false) {
        if(is_string($response)) $response = ["message" => $response];
        if(!isset($response["success"])) $response["success"] = $success;

        $conn->send(http_build_query($response));
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ModuleCommunicator()
        )
    ),
    8080
);

$server->run();
