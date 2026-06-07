<?php
namespace mod_doc2interact;

defined('MOODLE_INTERNAL') || die();

class api_client {

    private $apiurl;
    private $apikey;

    public function __construct() {
        $this->apiurl = get_config('mod_doc2interact', 'apiurl') ?: 'https://doc2interact.com';
        $this->apikey = get_config('mod_doc2interact', 'apikey') ?: 'demo1937';
    }

    public function generar(string $texto, string $titulo, string $tipo, string $instrucciones = ''): array {

        $payload = json_encode([
            'accessKey'     => $this->apikey,
            'textoCompleto' => $texto,
            'titulo'        => $titulo,
            'tipoContenido' => $tipo,
            'promptExtra'   => $instrucciones,
            'nombreBase'    => preg_replace('/[^a-z0-9_]/i', '_', $titulo),
            'logo'          => '',
            'colores'       => '',
        ]);

        $ch = curl_init($this->apiurl . '/generar');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlerr  = curl_error($ch);
        curl_close($ch);

        if ($curlerr || $httpcode !== 200) {
            throw new \moodle_exception('error_api', 'mod_doc2interact', '', $curlerr ?: "HTTP $httpcode");
        }

        $data = json_decode($response, true);
        if (!$data || isset($data['error'])) {
            $msg = $data['error'] ?? 'Respuesta invalida de la API';
            throw new \moodle_exception('error_api', 'mod_doc2interact', '', $msg);
        }

        return $data;
    }
}
