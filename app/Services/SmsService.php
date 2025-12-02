<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;
use WebSocket\Client;

class SmsService
{
    public function send(string $phone, string $message): bool
    {
        $url = Config::get('services.sms.url');

        if (!$url) {
            Log::error('SMS configuration missing: SMS_API_URL not set');
            return false;
        }

        if (!preg_match('/^wss?:\/\//', $url)) {
            Log::error('SMS URL invalid format', [
                'url' => $url,
                'expected_format' => 'ws://host:port or wss://host:port',
            ]);
            return false;
        }

        $client = null;

        try {
            $options = [
                'timeout' => 30,
            ];

            $apiKey = Config::get('services.sms.key');
            if ($apiKey) {
                $options['headers'] = [
                    'Authorization' => 'Bearer ' . $apiKey,
                ];
            }

            Log::info('Connecting to SMS WebSocket', [
                'url' => $url,
                'phone' => $phone,
            ]);

            $client = new Client($url, $options);

            $payload = json_encode([
                'type' => 'send-sms',
                'data' => [
                    'phone' => $phone,
                    'message' => $message,
                ],
            ]);

            if (!$payload) {
                Log::error('SMS payload encoding failed', [
                    'phone' => $phone,
                    'json_error' => json_last_error_msg(),
                ]);
                return false;
            }

            Log::debug('Sending SMS payload', [
                'phone' => $phone,
                'payload' => $payload,
            ]);

            $client->send($payload);

            $response = $client->receive();

            Log::info('SMS service response received', [
                'phone' => $phone,
                'raw_response' => $response,
            ]);

            $responseData = json_decode($response, true);

            if ($responseData) {
                Log::info('SMS service event data', [
                    'phone' => $phone,
                    'event_data' => $responseData,
                    'event_type' => $responseData['event'] ?? $responseData['type'] ?? 'unknown',
                    'status' => $responseData['status'] ?? null,
                ]);
            }

            if ($responseData && isset($responseData['status']) && $responseData['status'] === 'success') {
                Log::info('SMS sent successfully', [
                    'phone' => $phone,
                    'event' => $responseData,
                ]);
                return true;
            }

            Log::error('SMS failed', [
                'phone' => $phone,
                'response' => $response,
                'parsed_data' => $responseData,
            ]);
            return false;
        } catch (\WebSocket\ConnectionException $exception) {
            Log::error('SMS WebSocket connection error', [
                'phone' => $phone,
                'url' => $url,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]);
            return false;
        } catch (Throwable $exception) {
            Log::error('SMS error', [
                'phone' => $phone,
                'url' => $url,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            return false;
        } finally {
            if ($client !== null) {
                try {
                    $client->close();
                } catch (Throwable $e) {
                    Log::warning('Error closing WebSocket connection', [
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
