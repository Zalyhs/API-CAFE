<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

// Fonction pour vérifier le ping d'une adresse IP
function ping($host, $timeout = 1) {
    $output = null;
    $resultCode = null;

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        exec("ping -n 1 -w " . ($timeout * 1000) . " " . escapeshellarg($host), $output, $resultCode);
    } else {
        exec("ping -c 1 -W $timeout " . escapeshellarg($host), $output, $resultCode);
    }

    return $resultCode === 0 ? 'success' : 'failed';
}

// Fonction pour récupérer les dispositifs depuis l'API
function getDevicesFromApi() {
    $ch = curl_init();
    $url = "https://80.248.64.38/nms/api/v2.1/devices";

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-auth-token: 9afef525-16ac-4afb-b019-4bca325ebe36', // Remplacer par ton token
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);

    if ($response === false) {
        die('Erreur cURL : ' . curl_error($ch));
    }

    curl_close($ch);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Erreur lors de la lecture du fichier JSON: " . json_last_error_msg());
    }

    return json_decode($response, true);
}

// Fonction pour extraire le numéro de téléphone sans le préfixe "228" et nettoyer les caractères non numériques
function formatPhoneNumber($phoneNumber) {
    // Supprimer le préfixe "228" s'il est présent
    $phoneNumber = preg_replace('/^228/', '', $phoneNumber);
    // Nettoyer les caractères non numériques
    $phoneNumber = preg_replace('/\D/', '', $phoneNumber);
    return $phoneNumber;
}


// Route API pour le diagnostic initial
Route::post('/fetch-data', function (Request $request) {
    // URL d'authentification pour obtenir le token
    $authUrl = 'http://robusta.tg:8085/api/2.0/admin/auth/tokens';

    // Paramètres d'authentification
    $authParams = [
        'login' => 'bernice',
        'password' => 'stagiaire24',
        'auth_type' => 'admin'
    ];

    // Envoi de la requête pour obtenir le token
    $authResponse = Http::asForm()->post($authUrl, $authParams);

    if ($authResponse->failed()) {
        return response()->json(['error' => 'Échec de l\'authentification'], $authResponse->status());
    }

    // Extraction du token d'accès
    $accessToken = $authResponse->json()['access_token'];

    // URL de l'API pour récupérer les données
    $apiUrl = 'http://robusta.tg:8085/api/2.0/admin/customers/customer';

    // Paramètres pour l'API (y compris main_attributes)
    $params = [
        'main_attributes[login]' => $request->input('login'),
        'main_attributes[name]' => $request->input('name')
    ];

    // En-tête d'autorisation pour l'API
    $authorizationHeader = "Splynx-EA (access_token=$accessToken)";

    // Envoi de la requête GET pour récupérer les données
    $response = Http::withHeaders([
        'Authorization' => $authorizationHeader
    ])->get($apiUrl, $params);

    if ($response->successful()) {
        $data = $response->json();
        
        // Récupérer les dispositifs depuis l'API UISP
        $devices = getDevicesFromApi();

        // Extraction des informations spécifiques
        $result = [];
        foreach ($data as $item) {
            $comment = $item['additional_attributes']['comment'] ?? '';
            $serviceIp = $item['additional_attributes']['service_ip'] ?? '';
            $status = $item['status'] ?? '';
            $name = $item['name'] ?? '';
            $id = $item['id'] ?? '';
            $email = $item['email'] ?? '';
            $phone = $item['phone'] ?? '';

            // Formatage du numéro de téléphone
            $phone = formatPhoneNumber($phone);

            
            // Extraction de l'adresse IP depuis comment
            $pattern = '/(\d{1,3}\.){3}\d{1,3}/';
            $extractedIp = '';
            if (preg_match($pattern, $comment, $matches)) {
                $extractedIp = $matches[0];
            }

            // Vérifier le ping pour les adresses IP
            $serviceIpPing = $serviceIp ? ping($serviceIp) : 'failed';
            $extractedIpPing = $extractedIp ? ping($extractedIp) : 'failed';

            // Chercher les informations supplémentaires pour l'extractedIp
            $searchResult = null;
            if ($extractedIp) {
                foreach ($devices as $device) {
                    if (isset($device['ipAddress'])) {
                        $deviceIp = explode('/', $device['ipAddress'])[0];
                        if ($deviceIp === $extractedIp) {
                            $searchResult = $device;
                            break;
                        }
                    }
                }
            }

            $result[] = [
                'Name' => $name,
                'email' => $email,
                'phone' => $phone,
                'service_ip' => $serviceIp,
                'extracted_ip' => $extractedIp,
                'status' => $status,
                'service_ip_ping' => $serviceIpPing,
                'extracted_ip_ping' => $extractedIpPing,
                'device_info' => $searchResult ? [
                    'downlinkCapacity' => $searchResult['overview']['downlinkCapacity'] ?? 'N/A',
                    'status' => $searchResult['identification']['site']['status'] ?? 'N/A',
                    'totalCapacity' => $searchResult['overview']['totalCapacity'] ?? 'N/A',
                    'downlinkUtilization' => $searchResult['overview']['downlinkUtilization'] ?? 'N/A',
                    'uplinkCapacity' => $searchResult['overview']['uplinkCapacity'] ?? 'N/A',
                    'uplinkUtilization' => $searchResult['overview']['uplinkUtilization'] ?? 'N/A',
                    'signal' => $searchResult['overview']['signal'] ?? 'N/A',
                    'linkScore' => $searchResult['overview']['linkScore']['score'] ?? 'N/A',
                    'interfaceId' => $searchResult['overview']['mainInterfaceSpeed']['interfaceId'] ?? 'N/A',
                    'availableSpeed' => $searchResult['overview']['mainInterfaceSpeed']['availableSpeed'] ?? 'N/A',
                    'model' => $searchResult['identification']['model'] ?? 'N/A',
                    'modelName' => $searchResult['identification']['modelName'] ?? 'N/A',
                    'displayName' => $searchResult['identification']['displayName'] ?? 'N/A',
                    'ipAddress' => explode('/', $searchResult['ipAddress'])[0] ?? 'N/A',
                ] : null
            ];
        }

        return response()->json($result);
    }

    return response()->json(['error' => 'Erreur: ' . $response->status()], $response->status());
});


//infos sur son compte
Route::post('/customer-info', function (Request $request) {
    // URL d'authentification pour obtenir le token
    $authUrl = 'http://robusta.tg:8085/api/2.0/admin/auth/tokens';

    // Paramètres d'authentification
    $authParams = [
        'login' => 'bernice',
        'password' => 'stagiaire24',
        'auth_type' => 'admin'
    ];

    // Envoi de la requête pour obtenir le token
    $authResponse = Http::asForm()->post($authUrl, $authParams);

    if ($authResponse->failed()) {
        return response()->json(['error' => 'Échec de l\'authentification', 'details' => $authResponse->json()], $authResponse->status());
    }

    // Extraction du token d'accès
    $accessToken = $authResponse->json()['access_token'] ?? '';

    if (!$accessToken) {
        return response()->json(['error' => 'Token d\'accès non trouvé.'], 401);
    }

    // URL de l'API pour récupérer les données
    $apiUrl = 'http://robusta.tg:8085/api/2.0/admin/customers/customer';

    // Paramètres pour l'API
    $params = [
        'main_attributes[login]' => $request->input('login')
    ];

    // En-tête d'autorisation pour l'API avec le format spécifique
    $response = Http::withHeaders([
        'Authorization' => "Splynx-EA (access_token=$accessToken)"
    ])->get($apiUrl, $params);

    if ($response->failed()) {
        return response()->json(['error' => 'Erreur lors de la récupération des données: ' . $response->status(), 'details' => $response->json()], $response->status());
    }

    // Retourne toutes les données reçues
    return response()->json($response->json());
});



//savoir s'il a déjà un ticket en cours ou pas
Route::post('/customer-ticket-info', function (Request $request) {
    // URL d'authentification pour obtenir le token
    $authUrl = 'http://robusta.tg:8085/api/2.0/admin/auth/tokens';

    // Paramètres d'authentification
    $authParams = [
        'login' => 'bernice',
        'password' => 'stagiaire24',
        'auth_type' => 'admin'
    ];

    // Envoi de la requête pour obtenir le token
    $authResponse = Http::asForm()->post($authUrl, $authParams);

    if ($authResponse->failed()) {
        return response()->json(['error' => 'Échec de l\'authentification', 'details' => $authResponse->json()], $authResponse->status());
    }

    // Extraction du token d'accès
    $accessToken = $authResponse->json()['access_token'] ?? '';

    if (!$accessToken) {
        return response()->json(['error' => 'Token d\'accès non trouvé.'], 401);
    }

    // URL de l'API pour récupérer les données du client
    $apiUrl = 'http://robusta.tg:8085/api/2.0/admin/customers/customer';

    // Paramètres pour l'API
    $params = [
        'main_attributes[login]' => $request->input('login')
    ];

    // En-tête d'autorisation pour l'API avec le format spécifique
    $response = Http::withHeaders([
        'Authorization' => "Splynx-EA (access_token=$accessToken)"
    ])->get($apiUrl, $params);

    if ($response->failed()) {
        return response()->json(['error' => 'Erreur lors de la récupération des données: ' . $response->status(), 'details' => $response->json()], $response->status());
    }

    // Extraction des informations du client
    $customerData = $response->json();

    // Récupération de l'ID du client
    $customerId = $customerData[0]['id'] ?? null;

    if (!$customerId) {
        return response()->json(['error' => 'ID du client non trouvé.'], 404);
    }

    // URL de l'API pour récupérer les tickets
    $ticketsUrl = 'http://robusta.tg:8085/api/2.0/admin/support/tickets';

    // Récupération des tickets associés au client
    $ticketsResponse = Http::withHeaders([
        'Authorization' => "Splynx-EA (access_token=$accessToken)"
    ])->get($ticketsUrl);

    if ($ticketsResponse->failed()) {
        return response()->json(['error' => 'Erreur lors de la récupération des tickets: ' . $ticketsResponse->status(), 'details' => $ticketsResponse->json()], $ticketsResponse->status());
    }

    // Filtrer les tickets pour ceux qui appartiennent au client spécifique
    $allTickets = $ticketsResponse->json();
    $customerTickets = array_filter($allTickets, function($ticket) use ($customerId) {
        return $ticket['customer_id'] === $customerId;
    });

    // Retourne les tickets filtrés
    return response()->json($customerTickets);
});



