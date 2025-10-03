<?php
/**
 * Peleforo Hotspot Manager
 * RouterOS API Class - Connexion simplifiée à Mikrotik
 * Version améliorée avec gestion d'erreurs et ping
 */

class RouterOsAPI {
    private $socket;
    private $connected = false;
    private $debug = false;
    private $lastError = '';
    
    /**
     * Test de ping avant connexion
     * @param string $ip Adresse IP du Mikrotik
     * @return array ['success' => bool, 'message' => string, 'time' => float]
     */
    public function ping($ip) {
        $result = [
            'success' => false,
            'message' => '',
            'time' => 0
        ];
        
        // Valider l'IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $result['message'] = "Adresse IP invalide : $ip";
            return $result;
        }
        
        $startTime = microtime(true);
        
        // Méthode 1: Utiliser la commande ping du système (recommandé)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            $command = "ping -n 1 -w 1000 " . escapeshellarg($ip) . " 2>&1";
        } else {
            // Linux/Mac
            $command = "ping -c 1 -W 1 " . escapeshellarg($ip) . " 2>&1";
        }
        
        exec($command, $output, $returnCode);
        $endTime = microtime(true);
        $result['time'] = round(($endTime - $startTime) * 1000, 2); // en ms
        
        if ($returnCode === 0) {
            $result['success'] = true;
            $result['message'] = "Mikrotik accessible ({$result['time']} ms)";
        } else {
            // Méthode 2: Tenter une connexion socket simple (fallback)
            $result = $this->socketPing($ip);
        }
        
        return $result;
    }
    
    /**
     * Ping via socket (fallback si commande ping non disponible)
     */
    private function socketPing($ip, $port = 8728) {
        $result = [
            'success' => false,
            'message' => '',
            'time' => 0
        ];
        
        $startTime = microtime(true);
        
        $socket = @fsockopen($ip, $port, $errno, $errstr, 2);
        
        $endTime = microtime(true);
        $result['time'] = round(($endTime - $startTime) * 1000, 2);
        
        if ($socket) {
            fclose($socket);
            $result['success'] = true;
            $result['message'] = "Mikrotik accessible sur le port $port ({$result['time']} ms)";
        } else {
            $result['message'] = "Impossible de joindre $ip:$port - $errstr (Code: $errno)";
        }
        
        return $result;
    }
    
    /**
     * Test complet de connexion (ping + API)
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    public function testFullConnection($ip, $port, $username, $password) {
        $result = [
            'success' => false,
            'message' => '',
            'details' => []
        ];
        
        // Étape 1: Test de ping
        $pingResult = $this->ping($ip);
        $result['details']['ping'] = $pingResult;
        
        if (!$pingResult['success']) {
            $result['message'] = "❌ Échec du ping : " . $pingResult['message'];
            return $result;
        }
        
        // Étape 2: Test du port API
        $portTest = $this->socketPing($ip, $port);
        $result['details']['port'] = $portTest;
        
        if (!$portTest['success']) {
            $result['message'] = "❌ Port API inaccessible : " . $portTest['message'];
            $result['message'] .= "\n💡 Activez l'API : /ip service enable api";
            return $result;
        }
        
        // Étape 3: Test de connexion et authentification
        $connectResult = $this->connect($ip, $port, $username, $password);
        $result['details']['auth'] = [
            'success' => $connectResult,
            'message' => $connectResult ? 'Authentification réussie' : $this->lastError
        ];
        
        if (!$connectResult) {
            $result['message'] = "❌ Échec de connexion : " . $this->lastError;
            return $result;
        }
        
        // Étape 4: Test de commande
        $identity = $this->getIdentity();
        $result['details']['identity'] = $identity;
        
        if ($identity) {
            $result['success'] = true;
            $result['message'] = "✅ Connexion réussie au Mikrotik '$identity'";
        } else {
            $result['message'] = "⚠️ Connecté mais impossible de récupérer l'identité";
        }
        
        $this->disconnect();
        
        return $result;
    }
    
    /**
     * Connexion au Mikrotik avec gestion d'erreurs améliorée
     */
    public function connect($ip, $port, $username, $password) {
        $this->lastError = '';
        
        try {
            // Validation des paramètres
            if (empty($ip)) {
                throw new Exception("Adresse IP manquante");
            }
            
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                throw new Exception("Adresse IP invalide : $ip");
            }
            
            if (empty($username)) {
                throw new Exception("Nom d'utilisateur manquant");
            }
            
            if (empty($password)) {
                throw new Exception("Mot de passe manquant");
            }
            
            // Tentative de connexion socket
            $this->socket = @fsockopen($ip, $port, $errno, $errstr, 5);
            
            if (!$this->socket) {
                $errorMsg = "Impossible de se connecter à $ip:$port";
                
                // Messages d'erreur plus explicites selon le code
                switch ($errno) {
                    case 110: // Connection timed out
                        $errorMsg .= " - Le routeur ne répond pas (timeout)";
                        break;
                    case 111: // Connection refused
                        $errorMsg .= " - Connexion refusée. L'API est-elle activée ?";
                        break;
                    case 113: // No route to host
                        $errorMsg .= " - Aucune route vers l'hôte. Vérifiez l'IP et le réseau";
                        break;
                    default:
                        $errorMsg .= " - $errstr (Code: $errno)";
                }
                
                throw new Exception($errorMsg);
            }
            
            // Configuration du socket
            stream_set_timeout($this->socket, 5);
            stream_set_blocking($this->socket, true);
            
            // Lecture de la bannière de connexion
            $response = $this->read();
            
            if (empty($response)) {
                throw new Exception("Pas de réponse du serveur API. Vérifiez que l'API est activée");
            }
            
            // Authentification
            $this->write('/login');
            $this->write('=name=' . $username);
            $this->write('=password=' . $password);
            $this->write('');
            
            $response = $this->read();
            
            if (isset($response[0]) && $response[0] === '!done') {
                $this->connected = true;
                $this->lastError = '';
                return true;
            } elseif (isset($response[0]) && $response[0] === '!trap') {
                // Erreur d'authentification
                $errorMsg = "Authentification échouée : ";
                
                // Chercher le message d'erreur dans la réponse
                foreach ($response as $line) {
                    if (strpos($line, '=message=') === 0) {
                        $errorMsg .= substr($line, 9);
                        break;
                    }
                }
                
                if ($errorMsg === "Authentification échouée : ") {
                    $errorMsg .= "Identifiants incorrects";
                }
                
                throw new Exception($errorMsg);
            } else {
                throw new Exception("Réponse inattendue du serveur");
            }
            
        } catch (Exception $e) {
            $this->connected = false;
            $this->lastError = $e->getMessage();
            
            if ($this->socket) {
                @fclose($this->socket);
                $this->socket = null;
            }
            
            return false;
        }
    }
    
    /**
     * Obtenir le dernier message d'erreur
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Déconnexion
     */
    public function disconnect() {
        if ($this->socket) {
            @fclose($this->socket);
            $this->connected = false;
            $this->socket = null;
        }
    }
    
    /**
     * Créer un utilisateur Hotspot
     */
    public function addHotspotUser($username, $password, $profile, $comment = '') {
        if (!$this->connected) {
            $this->lastError = "Pas de connexion active au Mikrotik";
            return false;
        }
        
        try {
            $this->write('/ip/hotspot/user/add');
            $this->write('=name=' . $username);
            $this->write('=password=' . $password);
            $this->write('=profile=' . $profile);
            if ($comment) {
                $this->write('=comment=' . $comment);
            }
            $this->write('');
            
            $response = $this->read();
            
            if (isset($response[0]) && $response[0] === '!trap') {
                $this->lastError = "Erreur lors de la création de l'utilisateur";
                foreach ($response as $line) {
                    if (strpos($line, '=message=') === 0) {
                        $this->lastError = substr($line, 9);
                        break;
                    }
                }
                return false;
            }
            
            return isset($response[0]) && $response[0] === '!done';
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Lister les utilisateurs Hotspot
     */
    public function getHotspotUsers() {
        if (!$this->connected) {
            $this->lastError = "Pas de connexion active au Mikrotik";
            return [];
        }
        
        try {
            $this->write('/ip/hotspot/user/print');
            $this->write('');
            
            $response = $this->read();
            return $this->parseResponse($response);
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }
    
    /**
     * Supprimer un utilisateur Hotspot
     */
    public function removeHotspotUser($username) {
        if (!$this->connected) {
            $this->lastError = "Pas de connexion active au Mikrotik";
            return false;
        }
        
        try {
            $users = $this->getHotspotUsers();
            $userId = null;
            
            foreach ($users as $user) {
                if (isset($user['name']) && $user['name'] === $username) {
                    $userId = $user['.id'];
                    break;
                }
            }
            
            if (!$userId) {
                $this->lastError = "Utilisateur '$username' introuvable";
                return false;
            }
            
            $this->write('/ip/hotspot/user/remove');
            $this->write('=.id=' . $userId);
            $this->write('');
            
            $response = $this->read();
            return isset($response[0]) && $response[0] === '!done';
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Lister les profils Hotspot
     */
    public function getHotspotProfiles() {
        if (!$this->connected) {
            $this->lastError = "Pas de connexion active au Mikrotik";
            return [];
        }
        
        try {
            $this->write('/ip/hotspot/user/profile/print');
            $this->write('');
            
            $response = $this->read();
            return $this->parseResponse($response);
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }
    
    /**
     * Obtenir les utilisateurs actifs
     */
    public function getActiveUsers() {
        if (!$this->connected) {
            $this->lastError = "Pas de connexion active au Mikrotik";
            return [];
        }
        
        try {
            $this->write('/ip/hotspot/active/print');
            $this->write('');
            
            $response = $this->read();
            return $this->parseResponse($response);
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }
    
    /**
     * Créer un profil Hotspot
     */
    public function addHotspotProfile($name, $rateLimit = '', $sessionTimeout = '', $sharedUsers = 1) {
        if (!$this->connected) {
            $this->lastError = "Pas de connexion active au Mikrotik";
            return false;
        }
        
        try {
            $this->write('/ip/hotspot/user/profile/add');
            $this->write('=name=' . $name);
            if ($rateLimit) {
                $this->write('=rate-limit=' . $rateLimit);
            }
            if ($sessionTimeout) {
                $this->write('=session-timeout=' . $sessionTimeout);
            }
            $this->write('=shared-users=' . $sharedUsers);
            $this->write('');
            
            $response = $this->read();
            return isset($response[0]) && $response[0] === '!done';
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Tester la connexion
     */
    public function testConnection() {
        if (!$this->connected) {
            $this->lastError = "Pas de connexion active au Mikrotik";
            return false;
        }
        
        try {
            $this->write('/system/identity/print');
            $this->write('');
            
            $response = $this->read();
            return isset($response[0]) && $response[0] === '!done';
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Obtenir l'identité du routeur
     */
    public function getIdentity() {
        if (!$this->connected) {
            $this->lastError = "Pas de connexion active au Mikrotik";
            return null;
        }
        
        try {
            $this->write('/system/identity/print');
            $this->write('');
            
            $response = $this->read();
            $data = $this->parseResponse($response);
            
            return isset($data[0]['name']) ? $data[0]['name'] : null;
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }
    
    /**
     * Écrire dans le socket
     */
    private function write($command) {
        $length = strlen($command);
        
        if ($length < 0x80) {
            $length = chr($length);
        } elseif ($length < 0x4000) {
            $length = chr(0x80 | ($length >> 8)) . chr($length & 0xFF);
        } elseif ($length < 0x200000) {
            $length = chr(0xC0 | ($length >> 16)) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x10000000) {
            $length = chr(0xE0 | ($length >> 24)) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } else {
            $length = chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
        
        fwrite($this->socket, $length . $command);
    }
    
    /**
     * Lire depuis le socket
     */
    private function read() {
        $response = [];
        
        while (true) {
            $length = $this->readLength();
            
            if ($length === false || $length === 0) {
                break;
            }
            
            $data = fread($this->socket, $length);
            $response[] = $data;
            
            if ($data === '!done' || $data === '!trap') {
                break;
            }
        }
        
        return $response;
    }
    
    /**
     * Lire la longueur du message
     */
    private function readLength() {
        $byte = ord(fread($this->socket, 1));
        
        if ($byte === 0) {
            return 0;
        }
        
        if (($byte & 0x80) === 0x00) {
            return $byte;
        }
        
        if (($byte & 0xC0) === 0x80) {
            return (($byte & 0x3F) << 8) + ord(fread($this->socket, 1));
        }
        
        if (($byte & 0xE0) === 0xC0) {
            return (($byte & 0x1F) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        }
        
        if (($byte & 0xF0) === 0xE0) {
            return (($byte & 0x0F) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        }
        
        return ord(fread($this->socket, 1)) << 24 + ord(fread($this->socket, 1)) << 16 + ord(fread($this->socket, 1)) << 8 + ord(fread($this->socket, 1));
    }
    
    /**
     * Parser la réponse
     */
    private function parseResponse($response) {
        $parsed = [];
        $current = [];
        
        foreach ($response as $line) {
            if ($line === '!done' || $line === '!trap') {
                if (!empty($current)) {
                    $parsed[] = $current;
                    $current = [];
                }
            } elseif (strpos($line, '=') === 0) {
                $parts = explode('=', substr($line, 1), 2);
                if (count($parts) === 2) {
                    $current[$parts[0]] = $parts[1];
                }
            }
        }
        
        if (!empty($current)) {
            $parsed[] = $current;
        }
        
        return $parsed;
    }
    
    /**
     * Vérifier si connecté
     */
    public function isConnected() {
        return $this->connected;
    }
}
?>