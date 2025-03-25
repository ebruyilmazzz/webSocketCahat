<?php
require 'vendor/autoload.php'; // Composer ile yüklenen bağımlılıkları dahil eder, Ratchet gibi.
use Ratchet\MessageComponentInterface; // Ratchet kütüphanesinden MessageComponentInterface sınıfını kullanır.
use Ratchet\ConnectionInterface; // Ratchet kütüphanesinden ConnectionInterface sınıfını kullanır.

class ChatServer implements MessageComponentInterface { // WebSocket sunucusunu oluşturmak için MessageComponentInterface sınıfını implement ederiz.
    protected $clients = []; // Bağlantıları ve kullanıcı bilgilerini tutmak için boş bir dizi başlatırız.

    public function onOpen(ConnectionInterface $conn) { // Yeni bir bağlantı açıldığında çalışacak metodu tanımlar.
        $this->clients[$conn->resourceId] = ['conn' => $conn, 'username' => null]; // Bağlantıyı ve kullanıcı bilgisini diziye ekler.
        echo "Yeni bağlantı: ({$conn->resourceId})\n"; // Yeni bağlantının açıldığını konsola yazdırır.
    }

    public function onMessage(ConnectionInterface $from, $msg) { // Mesaj alındığında çalışacak metodu tanımlar.
        $data = json_decode($msg, true); // Gönderilen JSON mesajını diziye dönüştürür.

        if (isset($data['type']) && $data['type'] === 'set_username') { // Eğer mesaj türü 'set_username' ise kullanıcı adı ayarını yapar.
            $this->clients[$from->resourceId]['username'] = $data['username']; // Kullanıcı adı kaydedilir.
            echo "Kullanıcı adı atandı: {$data['username']}\n"; // Kullanıcı adına atama yapıldığını konsola yazdırır.
            return; // Mesajın sonlanması için fonksiyonu sonlandırır.
        }

        if (!isset($data['to']) || !isset($data['message'])) { // Eğer alıcı ya da mesaj eksikse, işlemi sonlandırır.
            return;
        }

        $fromUsername = $this->clients[$from->resourceId]['username']; // Gönderen kullanıcının adını alır.
        $toUsername = $data['to']; // Mesajın gönderileceği alıcının adını alır.
        $message = $data['message']; // Mesajı alır.

    foreach ($this->clients as $client) { // Tüm bağlantılar arasında döngüye girer.
            if ($client['username'] === $toUsername) { // Eğer alıcı adı eşleşirse, mesajı gönderir.
                $client['conn']->send(json_encode([ // Mesajı JSON formatında alıcıya gönderir.
                    'from' => $fromUsername, // Gönderenin ismi
                    'message' => $message // Mesajın içeriği
                ]));
                break; // Mesaj gönderildikten sonra döngüden çıkar.
            }
        }
    }

    public function onClose(ConnectionInterface $conn) { // Bağlantı kapandığında çalışacak metodu tanımlar.
        unset($this->clients[$conn->resourceId]); // Bağlantıyı client listesinden siler.
        echo "Bağlantı kapandı: ({$conn->resourceId})\n"; // Bağlantının kapandığını konsola yazdırır.
    }

    public function onError(ConnectionInterface $conn, \Exception $e) { // Hata alındığında çalışacak metodu tanımlar.
        echo "Hata: {$e->getMessage()}\n"; // Hata mesajını konsola yazdırır.
        $conn->close(); // Bağlantıyı kapatır.
    }
}
use Ratchet\Server\IoServer; // WebSocket sunucusunun çalışabilmesi için IoServer sınıfını kullanır.
use Ratchet\Http\HttpServer; // HTTP sunucusunu kullanarak WebSocket sunucusu üzerinden veri akışını yönetir.
use Ratchet\WebSocket\WsServer; // WebSocket protokolü üzerinden iletişim sağlamak için WsServer sınıfını kullanır.

$server = IoServer::factory( // IoServer fabrikasıyla WebSocket sunucusunu oluşturur.
    new HttpServer(new WsServer(new ChatServer())), // HTTP sunucusunu WebSocket sunucusuna bağlar.
    8080 // WebSocket sunucusunun çalışacağı portu belirtir.
);

echo "WebSocket sunucusu çalışıyor...\n"; // Sunucunun çalıştığını belirten mesajı konsola yazdırır.
$server->run(); // WebSocket sunucusunu başlatır ve çalışmasını sağlar.