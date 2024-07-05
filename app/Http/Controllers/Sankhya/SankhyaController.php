<?php

namespace App\Http\Controllers\Sankhya;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Session;
use Throwable;

class SankhyaController extends Controller
{
    private  $sessionId;
    private  $protocol         = "http://";
    private  $hostname;
    private  $port;
    private  $usename;
    private  $password;
    private  $urlAuth          = "/mge/service.sbr?serviceName=MobileLoginSP.login&outputType=json";
    private  $urlLogout        = "/mge/service.sbr?serviceName=MobileLoginSP.logout&outputType=json";
    private  $urlKillSession   = '/mge/service.sbr?serviceName=SessionManagerSP.killSession&application=IntegradorBV&resourceID=br.com.sankhya.core.cfg.AdministracaoServidor&outputType=json';


    private  $saveRecoard      = "/mge/service.sbr?serviceName=CRUDServiceProvider.saveRecord&outputType=json";
    private  $loadRecoard      = "/mge/service.sbr?serviceName=CRUDServiceProvider.loadRecords&outputType=json";


    public function __construct()
    {
        $this->hostname = env("APP_SANKHYA_HOST");
        $this->port     = env("APP_SANKHYA_PORT");
        $this->usename  = env("APP_SANKHYA_USER");
        $this->password = env("APP_SANKHYA_PASS");
    }


    public function getUrl($method = '')
    {
        return $this->protocol . $this->hostname . ':' . $this->port . $method;
    }


    function httpExecuteJson($method, $url, $data)
    {
        $client = new Client();

        try {
            $headers = [
                'Content-Type' => 'application/json; charset=utf-8',
                'Cookie'       => Session::has('jsessionid') ? 'JSESSIONID=' . Session::get('jsessionid') : '',
            ];

            $options = [
                'timeout'      => 300,
                'headers'      => $headers,
                'body'         => $data,
            ];

            $response = $client->request($method, $url, $options);

            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $message  = 'HTTP error ' . $response->getStatusCode() . ': ' . $response->getReasonPhrase();
            } else {
                $message  = $e->getMessage();
            }
            throw new Exception("Error Processing Request: $message", 1);
        } catch (Throwable $e) {
            throw new Exception("An unexpected error occurred: " . $e->getMessage(), 1);
        }
    }


    public function autenthicate($params)
    {
        $username = $params["user"];
        $password = $params["password"];
        $jnid = null;

        $data = json_encode([
            "serviceName"       => "MobileLoginSP.login",
            "requestBody"       => [
                "NOMUSU"        => ["$" => $username],
                "INTERNO"       => ["$" => $password],
                "KEEPCONNECTED" => ["$" => "S"]
            ]
        ]);

        $url = $this->getUrl($this->urlAuth);

        try {
            $response = $this->httpExecuteJson("POST", $url, $data);
            $result = json_decode($response, true);

            if (!isset($result['responseBody']['jsessionid']['$'])) {
                throw new Exception('Error ao Autenticar: ' . $result['statusMessage']);
            }

            $jnid = $result['responseBody']['jsessionid']['$'];

            Session::put('jsessionid', $jnid);
            Session::put('callID', $result['responseBody']['callID']['$']);
            Session::put('time', Carbon::now());

            $this->sessionId = $jnid;

            return $result;
        } catch (Exception $e) {
            throw new Exception('Erro ao realizar a autenticação: ' . $e->getMessage());
        }
    }


    public function deAutenthicate()
    {
        if (Session::has('jsessionid')) {

            $this->killSession();

            $data = json_encode([
                'serviceName'     => 'MobileLoginSP.logout',
                'status'          => '1',
                'pendingPrinting' => 'false'
            ]);

            try {
                $url    = $this->getUrl($this->urlLogout);
                $response = $this->httpExecuteJson("POST", $url, $data);
                $result = json_decode($response, true);


                if ($result['status'] == 1) {
                    Session::forget(['jsessionid', 'callID', 'time']);

                    return response()->json(['success' => true, 'message' => 'Usuário deslogado com sucesso!']);
                }
            } catch (Exception $e) {
                throw new Exception('Erro ao realizar a requisição: ' . $e->getMessage());
            }
        } else {
            throw new Exception("Sessão inválida: 'jsessionid' não encontrado. Por favor, autentique-se novamente.", 1);
        }
    }


    function killSession()
    {
        if (Session::has('jsessionid')) {

            $data = json_encode([
                'serviceName' => 'SessionManagerSP.killSession',
                'requestBody' => [
                    'SESSION' => [
                        'ID'  => Session::get('jsessionid')
                    ]
                ]
            ]);

            try {

                $url    = $this->getUrl($this->urlKillSession);
                $response = $this->httpExecuteJson("POST", $url, $data);
                $result = json_decode($response, true);

                return $result;
            } catch (Exception $e) {
                throw new Exception('Erro ao realizar a requisição: ' . $e->getMessage());
            }
        } else {
            throw new Exception("Sessão inválida: 'jsessionid' não encontrado. Por favor, autentique-se novamente.", 1);
        }
    }


    public function saveRecord($rootEntity, $localFields, $fieldset = [])
    {
        $localFieldsJson = [];
        $fieldsetJson    = [];


        foreach ($localFields as $fieldName => $fieldValue) {
            $localFieldsJson[$fieldName] = [
                '$' => $fieldValue
            ];
        }


        foreach ($fieldset as $fieldName => $fieldValue) {
            $fieldsetJson[$fieldName] = [
                '$' => $fieldValue
            ];
        }

        $data = json_encode([
            'serviceName' => 'CRUDServiceProvider.saveRecord',
            'requestBody' => [
                'dataSet' => [
                    'rootEntity' => $rootEntity,
                    'includePresentationFields' => 'N',
                    'dataRow' => [
                        'localFields' => $localFieldsJson
                    ],
                    'entity' => [
                        'fieldset' => [
                            $fieldsetJson
                        ]
                    ]
                ]
            ]
        ]);

        try {

            $url        = $this->getUrl($this->saveRecoard);
            $response   = $this->httpExecuteJson("POST", $url, $data);
            $result     = json_decode($response, true);


            if (!isset($result['responseBody']['entities']['entity'])) {
                throw new Exception('Erro ao no retorno do responseBody: ' . $result['statusMessage']);
            }


            return $result;
        } catch (Exception $e) {
            throw new Exception('Erro ao realizar a requisição: ' . $e->getMessage());
        }
    }


    public function loadRecord($rootEntity, $criteriaExpression, $fieldsetList)
    {
        $data = json_encode([
            'serviceName' => 'CRUDServiceProvider.loadRecords',
            'requestBody' => [
                'dataSet' => [
                    'rootEntity' => $rootEntity,
                    'includePresentationFields' => 'S',
                    'offsetPage' => '0',
                    'criteria'   => [
                        'expression' => [
                            '$' => $criteriaExpression
                        ]
                    ],
                    'entity' => [
                        'fieldset' => [
                            'list' => implode(',', $fieldsetList)
                        ]
                    ]
                ]
            ]
        ]);


        try {

            $url        = $this->getUrl($this->loadRecoard);
            $response   = $this->httpExecuteJson("GET", $url, $data);
            $result     = json_decode($response, true);


            if (!isset($result['responseBody']['entities']['entity'])) {
                throw new Exception('Erro ao no retorno do responseBody: ' . $result['statusMessage']);
            }

            return $result;
        } catch (Exception $e) {
            throw new Exception('Erro ao realizar a requisição: ' . $e->getMessage());
        }
    }
}
