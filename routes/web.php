<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

// Route::get('/', function () {
//     return view('welcome');
// });





// Route::post('/fetch-data', function (Request $request) {
//     // URL d'authentification pour obtenir le token
//     $authUrl = 'http://robusta.tg:8085/api/2.0/admin/auth/tokens';

//     // Paramètres d'authentification
//     $authParams = [
//         'login' => 'bernice',
//         'password' => 'stagiaire24',
//         'auth_type' => 'admin'
//     ];

//     // Envoi de la requête pour obtenir le token
//     $authResponse = Http::asForm()->post($authUrl, $authParams);

//     if ($authResponse->failed()) {
//         return response()->json(['error' => 'Échec de l\'authentification'], $authResponse->status());
//     }

//     // Extraction du token d'accès
//     $accessToken = $authResponse->json()['access_token'] ?? '';

//     if (!$accessToken) {
//         return response()->json(['error' => 'Token d\'accès non trouvé.'], 401);
//     }

//     // URL de l'API pour récupérer les données
//     $apiUrl = 'http://robusta.tg:8085/api/2.0/admin/customers/customer';

//     // Paramètres pour l'API (y compris main_attributes)
//     $params = [
//         'main_attributes[login]' => $request->input('login')
//     ];

//     // En-tête d'autorisation pour l'API
//     $response = Http::withToken($accessToken)->get($apiUrl, $params);

//     if ($response->failed()) {
//         return response()->json(['error' => 'Erreur lors de la récupération des données: ' . $response->status()], $response->status());
//     }

//     $data = $response->json();

//     // Extraction des informations spécifiques
//     $result = [];
//     foreach ($data['data'] as $item) {
//         $comment = $item['additional_attributes']['comment'] ?? '';
//         $serviceIp = $item['additional_attributes']['service_ip'] ?? '';
//         $status = $item['status'] ?? '';

//         // Extraction de l'adresse IP depuis comment
//         $pattern = '/(\d{1,3}\.){3}\d{1,3}/';
//         $extractedIp = '';
//         if (preg_match($pattern, $comment, $matches)) {
//             $extractedIp = $matches[0];
//         }

//         $result[] = [
//             'service_ip' => $serviceIp,
//             'extracted_ip' => $extractedIp,
//             'status' => $status
//         ];
//     }

//     return response()->json($result);
// });


// Route::post('/fetch-data', function (Request $request) {
//     // URL d'authentification pour obtenir le token
//     $authUrl = 'http://robusta.tg:8085/api/2.0/admin/auth/tokens';

//     // Paramètres d'authentification
//     $authParams = [
//         'login' => 'bernice',
//         'password' => 'stagiaire24',
//         'auth_type' => 'admin'
//     ];

//     // Envoi de la requête pour obtenir le token
//     $authResponse = Http::asForm()->post($authUrl, $authParams);

//     if ($authResponse->failed()) {
//         return response()->json(['error' => 'Échec de l\'authentification'], $authResponse->status());
//     }

//     // Extraction du token d'accès
//     $accessToken = $authResponse->json()['access_token'];

//     // URL de l'API pour récupérer les données
//     $apiUrl = 'http://robusta.tg:8085/api/2.0/admin/customers/customer';

//     // Paramètres pour l'API (y compris main_attributes)
//     $params = [
//         'main_attributes[login]' => $request->input('login')
//     ];

//     // En-tête d'autorisation pour l'API
//     $authorizationHeader = "Splynx-EA (access_token=$accessToken)";

//     // Envoi de la requête GET pour récupérer les données
//     $response = Http::withHeaders([
//         'Authorization' => $authorizationHeader
//     ])->get($apiUrl, $params);

//     if ($response->successful()) {
//         $data = $response->json();

//         // Fonction pour vérifier le ping d'une adresse IP
//         function ping($host, $timeout = 1) {
//             // Utiliser la commande ping pour tester la connectivité
//             $output = null;
//             $resultCode = null;

//             // La commande pour les systèmes Unix/Linux
//             if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
//                 // Pour les systèmes Windows
//                 exec("ping -n 1 -w " . ($timeout * 1000) . " " . escapeshellarg($host), $output, $resultCode);
//             } else {
//                 // Pour les systèmes Unix/Linux
//                 exec("ping -c 1 -W $timeout " . escapeshellarg($host), $output, $resultCode);
//             }

//             // Vérifiez le code de résultat pour déterminer si le ping a réussi
//             return $resultCode === 0 ? 'success' : 'failed';
//         }

//         // Extraction des informations spécifiques
//         $result = [];
//         foreach ($data as $item) {
//             $comment = $item['additional_attributes']['comment'] ?? '';
//             $serviceIp = $item['additional_attributes']['service_ip'] ?? '';
//             $status = $item['status'] ?? '';
            
//             // Extraction de l'adresse IP depuis comment
//             $pattern = '/(\d{1,3}\.){3}\d{1,3}/';
//             $extractedIp = '';
//             if (preg_match($pattern, $comment, $matches)) {
//                 $extractedIp = $matches[0];
//             }

//             // Vérifier le ping pour les adresses IP
//             $serviceIpPing = $serviceIp ? ping($serviceIp) : 'failed';
//             $extractedIpPing = $extractedIp ? ping($extractedIp) : 'failed';

//             $result[] = [
//                 'service_ip' => $serviceIp,
//                 'extracted_ip' => $extractedIp,
//                 'status' => $status,
//                 'service_ip_ping' => $serviceIpPing,
//                 'extracted_ip_ping' => $extractedIpPing
//             ];
//         }

//         return response()->json($result);
//     }

//     return response()->json(['error' => 'Erreur: ' . $response->status()], $response->status());
// });
