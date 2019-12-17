<?php

namespace NFs\EL;

use SoapClient;

use stdClass;

use DOMDocument;
use DOMElement;
use SoapFault;

// CNPJ PRESTADOR TESTE => 11097137000122
// CNPJ TOMADOR TESTE => 53350865000144
// CPF TOMADOR TESTE => 31497508385

class WebService {
    protected $nfs;
    protected $urlWsdl;

    protected $nfsStatus;
    protected $nfsObs;

    protected $loteRps;
    protected $identificacaoPrestador;
    protected $listaRps;
    protected $rps;

    protected $dadosPrestador;
    protected $identificacaoRps;
    protected $enderecoPrestador;
    protected $contatoPrestador;

    protected $dadosTomador;
    protected $identificacaoTomador;
    protected $enderecoTomador;
    protected $contatoTomador;

    protected $servicos = [];

    protected $valores;

    public $xml = NULL;

    public function __construct($version = '1.0', $charset = 'utf-8'){
        $this->nfs = new DOMDocument($version, $charset);
        $this->nfs->preserveWhiteSpace = false;
        $this->nfs->formatOutput = false;
    }

    public function setUrlWsdl( $url ) {
        $this->urlWsdl = $url;
    }

    private function makeID() {
        return hash('fnv164', rand(1111111111111, 9999999999999));
    }

    private function addChild(
        DOMElement &$parent,
        $name,
        $content,
        $required=TRUE
    ) {
        if (\is_object( $content ) ) {
            // dd( $content );
            $parent->appendChild($content);
        } else {
            if ( !$required && ( $content == '' || $content == NULL || $content == 0 ) ) {
                return;
            }

            $element = $this->nfs->createElement($name, $content);
            $parent->appendChild($element);
        }
    }

    public function LoteRps( stdClass $std ){
        $loteRps = $this->nfs->createElement('LoteRps');

        $loteRps->setAttribute("xmlns", "http://www.el.com.br/nfse/xsd/el-nfse.xsd");
        $loteRps->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $loteRps->setAttribute("xmlns:xsd", "http://www.w3.org/2001/XMLSchema");
        $loteRps->setAttribute("xsi:schemaLocation", "http://www.el.com.br/nfse/xsd/el-nfse.xsd el-nfse.xsd ");

        $this->addChild( $loteRps, 'Id', $this->makeID() );
        $this->addChild( $loteRps, 'NumeroLote', $std->numeroLote);
        $this->addChild( $loteRps, 'QuantidadeRps', $std->quantidadeRps);
        $this->loteRps = $loteRps;
        return $loteRps;
    }

    public function Rps( stdClass $std ){
        $Rps = $this->nfs->createElement('Rps');
        $this->addChild( $Rps, 'Id', $this->makeID() );
        $this->addChild( $Rps, 'LocalPrestacao', $std->localPrestacao);
        $this->addChild( $Rps, 'IssRetido', $std->issRetido);
        $this->addChild( $Rps, 'DataEmissao', $std->dataEmissao);

        $this->nfsStatus = $std->status;
        $this->nfsObs = $std->observacao;

        $this->rps = $Rps;
        return $Rps;
    }

    public function IdentificacaoPrestador( stdClass $std ){
        $identificacaoPrestador = $this->nfs->createElement('IdentificacaoPrestador');

        $this->addChild( $identificacaoPrestador, 'CpfCnpj', $std->cpfCnpj);

        if ( strlen( $std->cpfCnpj ) == 11 ) {
            $this->addChild( $identificacaoPrestador, 'IndicacaoCpfCnpj', 1);
        } else if ( strlen( $std->cpfCnpj ) == 14 ) {
            $this->addChild( $identificacaoPrestador, 'IndicacaoCpfCnpj', 2);
        } else if ( strlen( $std->cpfCnpj ) == 0 ) {
            $this->addChild( $identificacaoPrestador,'IndicacaoCpfCnpj', 0);
        } else {
            throw new \Exception( 'CPF / CNPJ Invalido' );
        }

        $this->addChild( $identificacaoPrestador, 'InscricaoMunicipal', $std->inscricaoMunicipal);

        $this->identificacaoPrestador = $identificacaoPrestador;
        return $identificacaoPrestador;
    }

    public function ListaRps(){
        $listaRps = $this->nfs->createElement('listaRps');
        $this->addChild( $listaRps, 'Rps', $this->aRps);
        $this->listaRps = $listaRps;
        return $listaRps;
    }

    public function IdentificacaoRps( stdClass $std ){
        $identificacaoRps = $this->nfs->createElement('IdentificacaoRps');
        $this->addChild( $identificacaoRps, 'Numero', $std->numero);
        $this->addChild( $identificacaoRps, 'Serie', $std->serie);
        $this->addChild( $identificacaoRps, 'Tipo', $std->tipo);
        $this->identificacaoRps = $identificacaoRps;
        return $identificacaoRps;
    }

    public function DadosPrestador( stdClass $std ){
        $dadosPrestador = $this->nfs->createElement('DadosPrestador');
        $this->addChild( $dadosPrestador, 'RazaoSocial', $std->razaoSocial);
        $this->addChild( $dadosPrestador, 'NomeFantasia', $std->nomeFantasia, FALSE);
        $this->addChild( $dadosPrestador, 'IncentivadorCultural', $std->incentivadorCultural);
        $this->addChild( $dadosPrestador, 'OptanteSimplesNacional', $std->optanteSimplesNacional);
        $this->addChild( $dadosPrestador, 'NaturezaOperacao', $std->naturezaOperacao);
        $this->addChild( $dadosPrestador, 'RegimeEspecialTributacao', $std->regimeEspecialTributacao);
        $this->dadosPrestador = $dadosPrestador;
        return $dadosPrestador;
    }

    public function EnderecoPrestador( stdClass $std ){
        $enderecoPrestador = $this->nfs->createElement('Endereco');
        $this->addChild( $enderecoPrestador, 'LogradouroTipo', $std->logradouroTipo, FALSE);
        $this->addChild( $enderecoPrestador, 'Logradouro', $std->logradouro, FALSE);
        $this->addChild( $enderecoPrestador, 'LogradouroNumero', $std->logradouroNumero, FALSE);
        $this->addChild( $enderecoPrestador, 'LogradouroComplemento', $std->logradouroComplemento, FALSE);
        $this->addChild( $enderecoPrestador, 'Bairro', $std->bairro, FALSE);
        $this->addChild( $enderecoPrestador, 'CodigoMunicipio', $std->codigoMunicipio);
        $this->addChild( $enderecoPrestador, 'Municipio', $std->municipio);
        $this->addChild( $enderecoPrestador, 'Uf', $std->uf);
        $this->addChild( $enderecoPrestador, 'Cep', $std->cep, FALSE);
        $this->enderecoPrestador = $enderecoPrestador;
        return $enderecoPrestador;
    }

    public function EnderecoTomador( stdClass $std ){
        $enderecoTomador = $this->nfs->createElement('Endereco');
        $this->addChild( $enderecoTomador, 'LogradouroTipo', $std->logradouroTipo, FALSE);
        $this->addChild( $enderecoTomador, 'Logradouro', $std->logradouro, FALSE);
        $this->addChild( $enderecoTomador, 'LogradouroNumero', $std->logradouroNumero, FALSE);
        $this->addChild( $enderecoTomador, 'LogradouroComplemento', $std->logradouroComplemento, FALSE);
        $this->addChild( $enderecoTomador, 'Bairro', $std->bairro, FALSE);
        $this->addChild( $enderecoTomador, 'CodigoMunicipio', $std->codigoMunicipio);
        $this->addChild( $enderecoTomador, 'Municipio', $std->municipio);
        $this->addChild( $enderecoTomador, 'Uf', $std->uf);
        $this->addChild( $enderecoTomador, 'Cep', $std->cep, FALSE);
        $this->enderecoTomador = $enderecoTomador;
        return $enderecoTomador;
    }

    public function ContatoPrestador( stdClass $std ){
        $contatoPrestador = $this->nfs->createElement('Contato');
        $this->addChild( $contatoPrestador, 'Telefone', $std->telefone, FALSE);
        $this->addChild( $contatoPrestador, 'Email', $std->email);
        $this->contatoPrestador = $contatoPrestador;
        return $contatoPrestador;
    }

    public function ContatoTomador( stdClass $std ){
        $contatoTomador = $this->nfs->createElement('Contato');
        $this->addChild( $contatoTomador, 'Telefone', $std->telefone, FALSE);
        $this->addChild( $contatoTomador, 'Email', $std->email);
        $this->contatoTomador = $contatoTomador;
        return $contatoTomador;
    }

    public function DadosTomador( stdClass $std ){
        $dadosTomador = $this->nfs->createElement('DadosTomador');
        $this->addChild( $dadosTomador, 'RazaoSocial', $std->razaoSocial);
        $this->addChild( $dadosTomador, 'NomeFantasia', $std->nomeFantasia, FALSE);
        $this->dadosTomador = $dadosTomador;
        return $dadosTomador;
    }

    public function IdentificacaoTomador( stdClass $std ){
        $identificacaoTomador = $this->nfs->createElement('IdentificacaoTomador');

        $this->addChild( $identificacaoTomador, 'CpfCnpj', $std->cpfCnpj);

        if ( strlen( $std->cpfCnpj ) == 11 ) {
            $this->addChild( $identificacaoTomador, 'IndicacaoCpfCnpj', 1);
        } else if ( strlen( $std->cpfCnpj ) == 14 ) {
            $this->addChild( $identificacaoTomador, 'IndicacaoCpfCnpj', 2);
        } else if ( strlen( $std->cpfCnpj ) == 0 ) {
            $this->addChild( $identificacaoTomador,'IndicacaoCpfCnpj', 0);
        } else {
            throw new \Exception( 'CPF / CNPJ Invalido' );
        }

        $this->addChild( $identificacaoTomador, 'InscricaoMunicipal', $std->inscricaoMunicipal, FALSE);

        $this->identificacaoTomador = $identificacaoTomador;
        return $identificacaoTomador;
    }

    public function Servico( stdClass $std ){
        $servico = $this->nfs->createElement('Servico');
        $this->addChild( $servico, 'CodigoCnae', $std->codigoCnae, FALSE);
        $this->addChild( $servico, 'CodigoServico116', $std->codigoServico116);
        $this->addChild( $servico, 'CodigoServicoMunicipal', $std->codigoServicoMunicipal);
        $this->addChild( $servico, 'Quantidade', $std->quantidade);
        $this->addChild( $servico, 'Unidade', $std->unidade);
        $this->addChild( $servico, 'Descricao', $std->descricao);
        $this->addChild( $servico, 'Aliquota', $std->aliquota);
        $this->addChild( $servico, 'ValorServico', $std->valorServico);
        $this->addChild( $servico, 'ValorIssqn', $std->valorIssqn);
        $this->addChild( $servico, 'ValorDesconto', $std->valorDesconto, FALSE);
        $this->addChild( $servico, 'NumeroAlvara', $std->numeroAlvara, FALSE);
        array_push( $this->servicos,  $servico);
        return $servico;
    }

    public function Valores( stdClass $std ){
        $valores = $this->nfs->createElement('Valores');
        $this->addChild( $valores, 'ValorServicos', $std->valorServicos);
        $this->addChild( $valores, 'ValorDeducoes', $std->valorDeducoes, FALSE);
        $this->addChild( $valores, 'ValorPis', $std->valorPis, FALSE);
        $this->addChild( $valores, 'ValorCofins', $std->valorCofins, FALSE);
        $this->addChild( $valores, 'ValorInss', $std->valorInss, FALSE);
        $this->addChild( $valores, 'ValorIr', $std->valorIr, FALSE);
        $this->addChild( $valores, 'ValorCsll', $std->valorCsll, FALSE);
        $this->addChild( $valores, 'ValorIss', $std->valorIss, FALSE);
        $this->addChild( $valores, 'ValorOutrasRetencoes', $std->valorOutrasRetencoes, FALSE);
        $this->addChild( $valores, 'ValorLiquidoNfse', $std->valorLiquidoNfse, FALSE);
        $this->addChild( $valores, 'ValorIssRetido', $std->valorIssRetido, FALSE);
        $this->addChild( $valores, 'OutrosDescontos', $std->outrosDescontos, FALSE);
        $this->valores = $valores;
        return $valores;
    }

    public function montar(){
        $servicos = $this->nfs->createElement('Servicos');
        foreach( $this->servicos as $servico ) {
            $this->addChild( $servicos, 'Servicos', $servico);
        }

        $idPrestador = $this->identificacaoPrestador->cloneNode(true);

        $rsPrestador = $this->dadosPrestador->getElementsByTagName('RazaoSocial');
        $this->dadosPrestador->insertBefore( $idPrestador, $rsPrestador[0] );
        $this->dadosPrestador->appendChild( $this->enderecoPrestador );
        $this->dadosPrestador->appendChild( $this->contatoPrestador );


        $rsTomador = $this->dadosTomador->getElementsByTagName('RazaoSocial');
        $this->dadosTomador->insertBefore( $this->identificacaoTomador, $rsTomador[0] );
        $this->dadosTomador->appendChild( $this->enderecoTomador );
        $this->dadosTomador->appendChild( $this->contatoTomador );

        $this->listaRps = $this->nfs->createElement('ListaRps');
        $this->rps->appendChild( $this->identificacaoRps );
        $this->rps->appendChild( $this->dadosPrestador );
        $this->rps->appendChild( $this->dadosTomador );
        $this->rps->appendChild( $servicos );
        $this->rps->appendChild( $this->valores );

        $this->addChild( $this->rps, 'Observacao', $this->nfsObs, FALSE);
        $this->addChild( $this->rps, 'Status', $this->nfsStatus);

        $this->listaRps->appendChild( $this->rps );

        $this->loteRps->appendChild( $this->identificacaoPrestador );
        $this->loteRps->appendChild( $this->listaRps );

        $this->nfs->appendChild( $this->loteRps );
        $this->xml = $this->nfs->saveXML();

        return $this->nfs;
    }

    public function getXML(){
        return $this->xml;
    }

    public function getArray( $xml=NULL ){
        if ( $xml == NULL ) {
            $xml = $this->xml;
        }

        $sxml = simplexml_load_string( $xml );
        $array = json_decode(json_encode($sxml), true);
        return $array;
    }

    public function consultarUltimoRps( $identificacaoPrestador ) {
        try {
            $soapClient = new SoapClient($this->urlWsdl, array(
                'exceptions' => true,
                'trace' => true
            ));

            $last_batch = array(
                "ConsultarUltimaRps" => array(
                    'identificacaoPrestador' => $identificacaoPrestador,
                )
            );
            $request_auth_token = $soapClient->__call('consultarUltimaRps', $last_batch);

            return $request_auth_token->return;
        } catch ( SoapFault $exception ) {
            return $exception->getMessage();
        };
    }

    public function consultarUltimoLote( $identificacaoPrestador ) {
        try {
            $soapClient = new SoapClient($this->urlWsdl, array(
                'exceptions' => true,
                'trace' => true
            ));

            $last_batch = array(
                "ConsultarUltimoLote" => array(
                    'identificacaoPrestador' => $identificacaoPrestador,
                )
            );
            $request_auth_token = $soapClient->__call('consultarUltimoLote', $last_batch);

            return $request_auth_token->return;
        } catch ( SoapFault $exception ) {
            return $exception->getMessage();
        };
    }

    public function finalizarSessao( $hash ) {
        try {
            $soapClient = new SoapClient($this->urlWsdl, array(
                'exceptions' => true,
                'trace' => true
            ));

            $param = array(
                "finalizarSessao" => array(
                    'hashIdentificador' => $hash,
                )
            );
            $request_auth_token = $soapClient->__call('finalizarSessao', $param);
            return $request_auth_token->return;
        } catch ( SoapFault $exception ) {
            return $exception->getMessage();
        };
    }

    public function cancelarNFse( $identificacaoPrestador, $senha, $nfse, $motivoCancelamento ) {
        try {
            $soapClient = new SoapClient($this->urlWsdl, array(
                'exceptions' => true,
                'trace' => true
            ));

            $param = array(
                "CancelarNfseMotivoEnvio" => array(
                    'identificacaoPrestador' => $identificacaoPrestador,
                    'senha' => $senha,
                    'numeroNfse' => $nfse,
                    'motivoCancelamento' => $motivoCancelamento
                )
            );
            $request_auth_token = $soapClient->__call('cancelarNfseMotivoEnvio', $param);
            return $request_auth_token->return;
        } catch ( SoapFault $exception ) {
            return $exception->getMessage();
        };
    }

    public function consultarNFse( $cnpj, $numeroProtocolo ) {
        try {
            $soapClient = new SoapClient($this->urlWsdl, array(
                'exceptions' => true,
                'trace' => true
            ));

            $batch_serach = array(
                'ConsultarLoteRpsEnvio' => array(
                    'identificacaoPrestador' => $cnpj,
                    'numeroProtocolo' => $numeroProtocolo,
                )
            );

            $request_batch_serach = $soapClient->__call('consultarLoteRpsEnvio', $batch_serach);
            return $request_batch_serach->return;
        } catch ( SoapFault $exception ) {
            return $exception->getMessage();
        };
    }

    public function autenticarContribuinte( $cnpj, $senha ) {
        try {
            $soapClient = new SoapClient($this->urlWsdl, array(
                'exceptions' => true,
                'trace' => true
            ));

            $param = array(
                "autenticarContribuinte" => array(
                    'identificacaoPrestador' => $cnpj,
                    'senha' => $senha
                )
            );

            $request_auth_token = $soapClient->__call('autenticarContribuinte', $param);
            return $request_auth_token->return;
        } catch ( SoapFault $exception ) {
            return $exception->getMessage();
        };
    }

    public function enviarNFse( $cnpj, $auth_token ) {
        try {
            $soapClient = new SoapClient($this->urlWsdl, array(
                'exceptions' => true,
                'trace' => true
            ));

            $batch_nfs = array(
                'EnviarLoteRpsEnvio' => array(
                    'identificacaoPrestador' => $cnpj,
                    'hashIdentificador' => $auth_token,
                    'arquivo' => $this->xml,
                )
            );

            $request_batch_nfs_info = $soapClient->__call('enviarLoteRpsEnvio', $batch_nfs);
            return $request_batch_nfs_info->return;
        } catch ( SoapFault $exception ) {
            return $exception->getMessage();
        };
    }

    public function transmitirNFse($cnpj, $senha) {
        $auth_token = $this->autenticarContribuinte($cnpj, $senha);
        $protocol = $this->enviarNFse($cnpj, $auth_token);
        return $protocol;
    }
}
