<?php

namespace App\Services;

use App\Models\MonitoredServer;
use App\Models\ServerStat;
use App\Models\ServerMotdHistory;
use App\Models\ServerCache;
use Carbon\Carbon;
use Exception;

class MinecraftServerService
{
    public function pingServer($ipOrServer, $port = null): array
    {
        $startTime = microtime(true);
        
        try {
            // Handle both MonitoredServer object and IP/port parameters
            if ($ipOrServer instanceof MonitoredServer) {
                $ip = $ipOrServer->ip_address;
                $port = $ipOrServer->port;
            } else {
                $ip = $ipOrServer;
                $port = $port ?? 25565; // Default Minecraft port
            }
            
            $socket = @fsockopen($ip, $port, $errno, $errstr, 5);
            
            if (!$socket) {
                return [
                    'success' => false,
                    'online' => false,
                    'ping' => null,
                    'players' => ['online' => 0, 'max' => 0],
                    'description' => ['text' => ''],
                    'version' => ['name' => null],
                    'error' => "Connection failed: $errstr ($errno)"
                ];
            }
            
            // Send handshake packet
            $handshake = $this->createHandshakePacket($ip, $port);
            fwrite($socket, $handshake);
            
            // Send status request
            $statusRequest = "\x01\x00";
            fwrite($socket, $statusRequest);
            
            // Read response with better error handling
            $packetLength = $this->readVarInt($socket); // Packet length
            $packetId = $this->readVarInt($socket); // Packet ID
            $jsonLength = $this->readVarInt($socket); // JSON length
            
            // Read JSON data in chunks to ensure we get all data
            $jsonData = '';
            $bytesRead = 0;
            while ($bytesRead < $jsonLength) {
                $chunk = fread($socket, min(1024, $jsonLength - $bytesRead));
                if ($chunk === false || $chunk === '') {
                    break; // Connection closed or error
                }
                $jsonData .= $chunk;
                $bytesRead += strlen($chunk);
            }
            
            fclose($socket);
            
            $ping = round((microtime(true) - $startTime) * 1000);
            
            // Better JSON error handling
            if (empty($jsonData)) {
                throw new Exception('Empty JSON response from server');
            }
            
            // Check if we received the expected amount of data
            if (strlen($jsonData) < $jsonLength) {
                throw new Exception(sprintf(
                    'Incomplete JSON response: expected %d bytes, got %d bytes. Data: %s',
                    $jsonLength,
                    strlen($jsonData),
                    substr($jsonData, 0, 200)
                ));
            }
            
            // Clean JSON data to handle Minecraft MOTD formatting issues
            // First, handle common Minecraft formatting codes that might break JSON
            $cleanJsonData = $jsonData;
            
            // Remove or escape problematic control characters while preserving JSON structure
            // Keep newlines (\n) and tabs (\t) as they're valid in JSON strings
            $cleanJsonData = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cleanJsonData);
            
            // Handle Minecraft section signs (§) that might cause issues
            // Convert section signs to unicode escape sequences if they're causing problems
            $cleanJsonData = str_replace('§', '\u00A7', $cleanJsonData);
            
            // Ensure proper UTF-8 encoding
            $cleanJsonData = mb_convert_encoding($cleanJsonData, 'UTF-8', 'UTF-8');
            
            // Additional cleanup for malformed JSON strings in MOTD
            // Fix common issues with unescaped quotes in text fields
            $cleanJsonData = preg_replace('/"text"\s*:\s*"([^"]*?)"([^,}\]]*?)"/', '"text":"$1\\"$2"', $cleanJsonData);
            
            $data = json_decode($cleanJsonData, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $jsonError = json_last_error_msg();
                $preview = substr($cleanJsonData, 0, 200);
                $originalPreview = substr($jsonData, 0, 200);
                
                // Find the exact position where JSON parsing fails
                $errorPosition = $this->findJsonErrorPosition($cleanJsonData);
                
                // Log the issue for debugging with more detailed information
                \Log::error('JSON parsing failed for Minecraft server ping', [
                    'error' => $jsonError,
                    'server_ip' => $ip,
                    'server_port' => $port,
                    'original_preview' => $originalPreview,
                    'cleaned_preview' => $preview,
                    'original_length' => strlen($jsonData),
                    'cleaned_length' => strlen($cleanJsonData),
                    'has_section_signs' => strpos($jsonData, '§') !== false,
                    'control_chars_found' => preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $jsonData),
                    'error_position' => $errorPosition,
                    'context_around_error' => $errorPosition ? substr($cleanJsonData, max(0, $errorPosition - 50), 100) : null
                ]);
                
                // Try to extract basic information even if JSON is malformed
                $fallbackData = $this->extractFallbackServerInfo($cleanJsonData);
                if ($fallbackData) {
                    \Log::info('Using fallback server data extraction', ['server_ip' => $ip, 'fallback_data' => $fallbackData]);
                    $data = $fallbackData;
                } else {
                    throw new Exception("Invalid JSON response: {$jsonError}. Cleaned data preview: {$preview}");
                }
            }
            
            if (!$data) {
                throw new Exception('JSON decoded to null or false');
            }
            
            // Extract additional data if available
            $enhancedData = $this->extractEnhancedData($data);
            
            return [
                'success' => true,
                'online' => true,
                'ping' => $ping,
                'players' => ['online' => $data['players']['online'] ?? 0, 'max' => $data['players']['max'] ?? 0],
                'description' => ['text' => $this->extractMotd($data['description'] ?? '')],
                'version' => ['name' => $data['version']['name'] ?? null],
                // Only include data that can actually be extracted from ping
                'tps' => $enhancedData['tps'], // Only if found in MOTD
                'memory_used' => null, // Not available from ping
                'memory_max' => null, // Not available from ping
                'response_time_ms' => $ping, // Calculated from ping time
                'plugins' => $enhancedData['plugins'], // Only from Forge data
                'cpu_usage' => null, // Not available from ping
                'disk_usage' => null, // Not available from ping
                'world_name' => $enhancedData['world_name'], // Only if in MOTD
                'entities_count' => null, // Not available from ping
                'chunks_loaded' => null, // Not available from ping
                'error' => null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'online' => false,
                'ping' => null,
                'players' => ['online' => 0, 'max' => 0],
                'description' => ['text' => null],
                'version' => ['name' => null],
                'tps' => null,
                'memory_used' => null,
                'memory_max' => null,
                'response_time_ms' => null,
                'plugins' => null,
                'cpu_usage' => null,
                'disk_usage' => null,
                'world_name' => null,
                'entities_count' => null,
                'chunks_loaded' => null,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function updateServerData(MonitoredServer $server): void
    {
        $pingResult = $this->pingServer($server);
        $now = Carbon::now();
        
        // Update server record
        $server->update([
            'last_ping_at' => $now,
            'is_online' => $pingResult['online'],
            'current_players' => $pingResult['players']['online'] ?? 0,
            'max_players' => $pingResult['players']['max'] ?? 0,
            'current_motd' => $pingResult['description']['text'] ?? '',
            'version' => $pingResult['version']['name'] ?? null,
        ]);
        
        // Create stat record with available monitoring data
        ServerStat::create([
            'server_id' => $server->id,
            'player_count' => $pingResult['players']['online'] ?? 0,
            'max_players' => $pingResult['players']['max'] ?? 0,
            'is_online' => $pingResult['online'],
            'ping_ms' => $pingResult['ping'],
            'version' => $pingResult['version']['name'] ?? null,
            // Only include fields that can be extracted from standard ping
            'tps' => $pingResult['tps'], // Only if found in MOTD
            'response_time_ms' => $pingResult['response_time_ms'], // Calculated ping time
            'plugins' => $pingResult['plugins'], // Only from Forge data
            'world_name' => $pingResult['world_name'], // Only if in MOTD
            'recorded_at' => $now,
        ]);
        
        // Update MOTD history if MOTD changed
        $motd = $pingResult['description']['text'] ?? '';
        if ($motd && $pingResult['online']) {
            $this->updateMotdHistory($server, $motd, $now);
        }
        
        // Cache the ping result if server is online
        if ($pingResult['online']) {
            $this->updateServerCache($server, $pingResult);
        }
    }
    
    private function updateMotdHistory(MonitoredServer $server, string $motd, Carbon $timestamp): void
    {
        $cleanMotd = preg_replace('/§./', '', $motd);
        
        $existingMotd = ServerMotdHistory::where('server_id', $server->id)
            ->where('motd', $motd)
            ->first();
            
        if ($existingMotd) {
            $existingMotd->update(['last_seen_at' => $timestamp]);
        } else {
            ServerMotdHistory::create([
                'server_id' => $server->id,
                'motd' => $motd,
                'motd_clean' => $cleanMotd,
                'first_seen_at' => $timestamp,
                'last_seen_at' => $timestamp,
            ]);
        }
    }
    
    private function createHandshakePacket(string $host, int $port): string
    {
        $data = "\x00"; // Packet ID
        $data .= $this->writeVarInt(47); // Protocol version
        $data .= $this->writeVarInt(strlen($host)) . $host; // Server address
        $data .= pack('n', $port); // Server port
        $data .= $this->writeVarInt(1); // Next state (status)
        
        return $this->writeVarInt(strlen($data)) . $data;
    }
    
    private function writeVarInt(int $value): string
    {
        $bytes = '';
        while (($value & 0x80) !== 0) {
            $bytes .= chr($value & 0x7F | 0x80);
            $value >>= 7;
        }
        $bytes .= chr($value & 0x7F);
        return $bytes;
    }
    
    private function readVarInt($socket): int
    {
        $value = 0;
        $position = 0;
        
        do {
            $byte = ord(fread($socket, 1));
            $value |= ($byte & 0x7F) << $position;
            $position += 7;
        } while (($byte & 0x80) !== 0);
        
        return $value;
    }
    
    private function extractMotd($description): string
    {
        if (is_string($description)) {
            return $description;
        }
        
        if (is_array($description)) {
            return $this->parseMotdComponent($description);
        }
        
        return (string) $description;
    }
    
    /**
     * Recursively parse MOTD JSON components to extract plain text
     * Handles nested 'extra' arrays and complex formatting structures
     */
    private function parseMotdComponent($component): string
    {
        if (is_string($component)) {
            return $component;
        }
        
        if (!is_array($component)) {
            return (string) $component;
        }
        
        $text = '';
        
        // Add the main text if present
        if (isset($component['text'])) {
            $text .= $component['text'];
        }
        
        // Recursively process 'extra' array
        if (isset($component['extra']) && is_array($component['extra'])) {
            foreach ($component['extra'] as $extraComponent) {
                $text .= $this->parseMotdComponent($extraComponent);
            }
        }
        
        // Handle 'with' array (for translate components)
        if (isset($component['with']) && is_array($component['with'])) {
            foreach ($component['with'] as $withComponent) {
                $text .= $this->parseMotdComponent($withComponent);
            }
        }
        
        return $text;
    }
    
    /**
     * Extract basic server information from malformed JSON as fallback
     * Uses regex patterns to extract key information when JSON parsing fails
     */
    private function extractFallbackServerInfo(string $jsonData): ?array
    {
        $fallback = [
            'version' => ['name' => null],
            'players' => ['online' => 0, 'max' => 0],
            'description' => ['text' => '']
        ];
        
        // Extract version information
        if (preg_match('/"version"\s*:\s*{[^}]*"name"\s*:\s*"([^"]+)"/', $jsonData, $matches)) {
            $fallback['version']['name'] = $matches[1];
        }
        
        // Extract player count
        if (preg_match('/"players"\s*:\s*{[^}]*"online"\s*:\s*(\d+)/', $jsonData, $matches)) {
            $fallback['players']['online'] = (int) $matches[1];
        }
        
        if (preg_match('/"players"\s*:\s*{[^}]*"max"\s*:\s*(\d+)/', $jsonData, $matches)) {
            $fallback['players']['max'] = (int) $matches[1];
        }
        
        // Extract simple description text
        if (preg_match('/"description"\s*:\s*{[^}]*"text"\s*:\s*"([^"]+)"/', $jsonData, $matches)) {
            $fallback['description']['text'] = $matches[1];
        } elseif (preg_match('/"description"\s*:\s*"([^"]+)"/', $jsonData, $matches)) {
            $fallback['description']['text'] = $matches[1];
        }
        
        // Only return fallback data if we extracted at least version or player info
        if ($fallback['version']['name'] || $fallback['players']['online'] > 0 || $fallback['players']['max'] > 0) {
            return $fallback;
        }
        
        return null;
    }
    
    /**
     * Find the approximate position where JSON parsing fails
     * by parsing character by character until an error occurs
     */
    private function findJsonErrorPosition(string $jsonData): ?int
    {
        $length = strlen($jsonData);
        $step = max(1, intval($length / 10)); // Start with 10% chunks
        
        // Binary search approach to find error position
        $validLength = 0;
        
        for ($i = $step; $i <= $length; $i += $step) {
            $chunk = substr($jsonData, 0, $i);
            
            // Try to find a complete JSON structure
            $lastBrace = strrpos($chunk, '}');
            if ($lastBrace !== false) {
                $testChunk = substr($chunk, 0, $lastBrace + 1);
                json_decode($testChunk);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    $validLength = $lastBrace + 1;
                } else {
                    // Found the problematic area, narrow it down
                    return $this->narrowDownErrorPosition($jsonData, $validLength, $i);
                }
            }
        }
        
        return $validLength < $length ? $validLength : null;
    }
    
    /**
     * Narrow down the exact error position within a smaller range
     */
    private function narrowDownErrorPosition(string $jsonData, int $start, int $end): int
    {
        for ($i = $start; $i < $end && $i < strlen($jsonData); $i++) {
            $char = $jsonData[$i];
            $ord = ord($char);
            
            // Check for problematic characters
            if ($ord < 32 && !in_array($ord, [9, 10, 13])) { // Control chars except tab, LF, CR
                return $i;
            }
        }
        
        return $start;
    }
    
    /**
     * Extract enhanced monitoring data from server response
     * Note: Standard Minecraft ping protocol only provides limited data.
     * For comprehensive monitoring, use server query protocol or plugins like ServerListPlus.
     */
    private function extractEnhancedData(array $data): array
    {
        // Initialize with null values - only populate what's actually available
        $enhanced = [
            'tps' => null,
            'memory_used' => null,
            'memory_max' => null,
            'plugins' => null,
            'cpu_usage' => null,
            'disk_usage' => null,
            'world_name' => null,
            'entities_count' => null,
            'chunks_loaded' => null,
        ];
        
        // Extract mod/plugin data from Forge servers
        if (isset($data['forgeData']['mods']) && is_array($data['forgeData']['mods'])) {
            $enhanced['plugins'] = json_encode(array_column($data['forgeData']['mods'], 'modId'));
        }
        
        // Try to extract server info from MOTD if servers include it
        $description = $data['description'] ?? '';
        if (is_string($description)) {
            // Some servers include TPS in their MOTD
            if (preg_match('/TPS[:\s]*(\d+\.?\d*)/', $description, $matches)) {
                $enhanced['tps'] = (float) $matches[1];
            }
            
            // Some servers include world name in MOTD
            if (preg_match('/World[:\s]*([\w\-_]+)/', $description, $matches)) {
                $enhanced['world_name'] = $matches[1];
            }
        }
        
        return $enhanced;
    }
    
    /**
     * Get cached server data if available and not expired
     */
    public function getCachedServerData(MonitoredServer $server): ?array
    {
        $cache = ServerCache::where('server_id', $server->id)->first();
        
        if (!$cache || $cache->isExpired()) {
            return null;
        }
        
        return $cache->cached_data;
    }
    
    /**
     * Update cache with fresh server data
     */
    public function updateServerCache(MonitoredServer $server, array $data): void
    {
        ServerCache::updateOrCreate(
            ['server_id' => $server->id],
            [
                'cached_data' => $data,
                'last_updated' => now(),
            ]
        );
    }
    
    /**
     * Get server data with caching - returns cached data if available, otherwise pings server
     */
    public function getServerDataWithCache(MonitoredServer $server): array
    {
        // Try to get cached data first
        $cachedData = $this->getCachedServerData($server);
        
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Cache miss or expired - ping server and cache result
        $freshData = $this->pingServer($server);
        
        // Only cache successful pings
        if ($freshData['online']) {
            $this->updateServerCache($server, $freshData);
        }
        
        return $freshData;
    }
}