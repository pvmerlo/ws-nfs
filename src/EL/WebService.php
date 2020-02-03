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

class WebService
{
    /*
     * Constantes com os nomes das tags setadas em diversas propriedades
     * para em caso de mudança, não ser necessário alterar por todo o documento.
     */
    const PRESTADOR = "Prestador";
    const TOMADOR = "Tomador";
    
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
    
    public function __construct($version = '1.0', $charset = 'utf-8')
    {
        $this->nfs = new DOMDocument($version, $charset);
        $this->nfs->preserveWhiteSpace = false;
        $this->nfs->formatOutput = false;
    }
    
    public function setUrlWsdl($url)
    {
        $this->urlWsdl = $url;
    }
    
    private function makeID()
    {
        return hash('fnv164', rand(1111111111111, 9999999999999));
    }
    
    private function addChild(
        DOMElement &$parent,
        $name,
        $content,
        $required = true
    )
    {
        if (\is_object($content)) {
            // dd( $content );
            $parent->appendChild($content);
        } else {
            if (!$required && ($content == '' || $content == NULL || $content == 0)) {
                return;
            }
            
            $element = $this->nfs->createElement($name, $content);
            $parent->appendChild($element);
        }
    }
    
    private function somenteNumeros($string)
    {
        return preg_replace('/[^0-9]/', '', $string);
    }
    
    private function obterIdentificacao(stdClass $std, string $tipoDoAtor)
    {
        $identificacao = $this->nfs->createElement('Identificacao' . $tipoDoAtor);
        
        /*
         * Limpar o CPF/CNPJ antes da utilização, pois traços e pontos contam como caractere, e apenas contá-los
         * é frágil. Por exemplo: "000.000.000", tem 11 caracteres, porém não é um CPF válido.
         * Além disso, busco por outras tags possíveis para este campo: cpf e cnpj.
         */
        $std->cpfCnpj = $this->somenteNumeros(($std->cpfCnpj ?? ($std->cpf ?? ($std->cnpj ?? ""))));
        
        if (strlen($std->cpfCnpj) == 11) {
            $this->addChild($identificacao, 'IndicacaoCpfCnpj', 1);
        } else {
            if (strlen($std->cpfCnpj) == 14) {
                $this->addChild($identificacao, 'IndicacaoCpfCnpj', 2);
            } else {
                if (strlen($std->cpfCnpj) == 0) {
                    $this->addChild($identificacao, 'IndicacaoCpfCnpj', 0);
                } else {
                    throw new \Exception('CPF / CNPJ Invalido');
                }
            }
        }
        
        $this->addChild($identificacao, 'CpfCnpj', $std->cpfCnpj);
        $this->addChild($identificacao, 'InscricaoMunicipal', $std->inscricaoMunicipal);
        
        return $identificacao;
    }
    
    private function obterDadosDoAtor(stdClass $std, string $tipoDoAtor)
    {
        $dadosDoAtor = $this->nfs->createElement('Dados' . $tipoDoAtor);
        $this->addChild($dadosDoAtor, 'RazaoSocial', $std->razaoSocial);
        
        if (isset($std->nomeFantasia)) {
            $this->addChild($dadosDoAtor, 'NomeFantasia', $std->nomeFantasia);
        }
        
        if ($tipoDoAtor === WebService::PRESTADOR) {
            $this->addChild($dadosDoAtor, 'IncentivadorCultural', $std->incentivadorCultural);
            $this->addChild($dadosDoAtor, 'OptanteSimplesNacional', $std->optanteSimplesNacional);
            $this->addChild($dadosDoAtor, 'NaturezaOperacao', $std->naturezaOperacao);
            $this->addChild($dadosDoAtor, 'RegimeEspecialTributacao', $std->regimeEspecialTributacao);
        }
        
        if (isset($std->{"Identificacao" . $tipoDoAtor})) {
            $this->{"identificacao" . $tipoDoAtor} = $this->obterIdentificacao($std->{"Identificacao" . $tipoDoAtor}, $tipoDoAtor);
        }
        
        if (isset($std->Contato)) {
            $this->{"contato" . $tipoDoAtor} = $this->obterContato($std->Contato);
        }
        
        if (isset($std->Endereco)) {
            $this->{"endereco" . $tipoDoAtor} = $this->obterEndereco($std->Endereco);
        }
        
        return $dadosDoAtor;
    }
    
    private function obterEndereco(stdClass $std)
    {
        $endereco = $this->nfs->createElement('Endereco');
        
        $this->addChild($endereco, 'CodigoMunicipio', $std->codigoMunicipio);
        $this->addChild($endereco, 'Municipio', $std->municipio);
        $this->addChild($endereco, 'Uf', $std->uf);
        
        if (isset($std->logradouroTipo)) {
            $this->addChild($endereco, 'LogradouroTipo', $std->logradouroTipo);
        }
        if (isset($std->logradouro)) {
            $this->addChild($endereco, 'Logradouro', $std->logradouro);
        }
        if (isset($std->logradouroNumero)) {
            $this->addChild($endereco, 'LogradouroNumero', $std->logradouroNumero);
        }
        if (isset($std->logradouroComplemento)) {
            $this->addChild($endereco, 'LogradouroComplemento', $std->logradouroComplemento);
        }
        if (isset($std->bairro)) {
            $this->addChild($endereco, 'Bairro', $std->bairro);
        }
        if (isset($std->cep)) {
            $this->addChild($endereco, 'Cep', $std->cep);
        }
        
        return $endereco;
    }
    
    private
    function obterContato(stdClass $std)
    {
        $contato = $this->nfs->createElement('Contato');
        $this->addChild($contato, 'Email', $std->email);
        
        if (isset($std->telefone)) {
            $this->addChild($contato, 'Telefone', $std->telefone);
        }
        return $contato;
    }
    
    public function LoteRps(stdClass $std)
    {
        $loteRps = $this->nfs->createElement('LoteRps');
        
        $loteRps->setAttribute("xmlns", "http://www.el.com.br/nfse/xsd/el-nfse.xsd");
        $loteRps->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $loteRps->setAttribute("xmlns:xsd", "http://www.w3.org/2001/XMLSchema");
        $loteRps->setAttribute("xsi:schemaLocation", "http://www.el.com.br/nfse/xsd/el-nfse.xsd el-nfse.xsd ");
        
        $this->addChild($loteRps, 'Id', $this->makeID());
        $this->addChild($loteRps, 'NumeroLote', $std->numeroLote);
        $this->addChild($loteRps, 'QuantidadeRps', $std->quantidadeRps);
        $this->loteRps = $loteRps;
        return $loteRps;
    }
    
    public function Rps(stdClass $std)
    {
        $Rps = $this->nfs->createElement('Rps');
        $this->addChild($Rps, 'Id', $this->makeID());
        $this->addChild($Rps, 'LocalPrestacao', $std->localPrestacao);
        $this->addChild($Rps, 'IssRetido', $std->issRetido);
        $this->addChild($Rps, 'DataEmissao', $std->dataEmissao);
        
        $this->nfsStatus = $std->status;
        $this->nfsObs = $std->observacao;
        
        $this->rps = $Rps;
        return $Rps;
    }
    
    
    public function ListaRps()
    {
        $listaRps = $this->nfs->createElement('listaRps');
        $this->addChild($listaRps, 'Rps', $this->rps);
        $this->listaRps = $listaRps;
        return $listaRps;
    }
    
    public function IdentificacaoRps(stdClass $std)
    {
        $identificacaoRps = $this->nfs->createElement('IdentificacaoRps');
        $this->addChild($identificacaoRps, 'Numero', $std->numero);
        $this->addChild($identificacaoRps, 'Serie', $std->serie);
        $this->addChild($identificacaoRps, 'Tipo', $std->tipo);
        $this->identificacaoRps = $identificacaoRps;
        return $identificacaoRps;
    }
    
    /*
     * tcIDadosPrestador
     */
    public function DadosPrestador(stdClass $std)
    {
        $this->dadosPrestador = $this->obterDadosDoAtor($std, WebService::PRESTADOR);
        return $this->dadosPrestador;
    }
    
    /*
     * tcIdentificacaoPrestador
     */
    public function IdentificacaoPrestador(stdClass $std)
    {
        $this->identificacaoPrestador = $this->obterIdentificacao($std, WebService::PRESTADOR);
        return $this->identificacaoPrestador;
    }
    
    /*
     * tcEndereco
     */
    public function EnderecoPrestador(stdClass $std)
    {
        $this->enderecoPrestador = $this->obterEndereco($std);
        return $this->enderecoPrestador;
    }
    
    /*
     * tcContato
     */
    public
    function ContatoPrestador(stdClass $std)
    {
        $this->contatoPrestador = $this->obterContato($std);
        return $this->contatoPrestador;
    }
    
    /*
     * tcDadosTomador
     */
    public
    function DadosTomador(stdClass $std)
    {
        $this->dadosTomador = $this->obterDadosDoAtor($std, WebService::TOMADOR);
        return $this->dadosTomador;
    }
    
    /*
     * tcIdentificacaoPrestador
     */
    public function IdentificacaoTomador(stdClass $std)
    {
        $this->identificacaoTomador = $this->obterIdentificacao($std, WebService::TOMADOR);
        return $this->identificacaoTomador;
    }
    
    /*
     * tcEndereco
     */
    public function EnderecoTomador(stdClass $std)
    {
        $this->enderecoTomador = $this->obterEndereco($std);
        return $this->enderecoTomador;
    }
    
    /*
     * tcContato
     */
    public function ContatoTomador(stdClass $std)
    {
        $this->contatoPrestador = $this->obterContato($std);
        return $this->contatoPrestador;
    }
    
    /*
     * tcServico
     */
    public function adicionarServico(stdClass $std)
    {
        $servico = $this->nfs->createElement('Servico');
        
        if (isset($std->codigoCnae)) {
            $this->addChild($servico, 'CodigoCnae', $std->codigoCnae);
        }
        
        $this->addChild($servico, 'CodigoServico116', $std->codigoServico116);
        $this->addChild($servico, 'CodigoServicoMunicipal', $std->codigoServicoMunicipal);
        $this->addChild($servico, 'Quantidade', $std->quantidade);
        $this->addChild($servico, 'Unidade', $std->unidade);
        $this->addChild($servico, 'Descricao', $std->descricao);
        $this->addChild($servico, 'Aliquota', $std->aliquota);
        $this->addChild($servico, 'ValorServico', $std->valorServico);
        $this->addChild($servico, 'ValorIssqn', $std->valorIssqn);
        
        if (isset($std->valorDesconto)) {
            $this->addChild($servico, 'ValorDesconto', $std->valorDesconto);
        }
        
        if (isset($std->numeroAlvara)) {
            $this->addChild($servico, 'NumeroAlvara', $std->numeroAlvara);
        }
        array_push($this->servicos, $servico);
        return $servico;
    }
    
    public function removerServico($index)
    {
        unset($this->servicos[$index]);
    }
    
    public function Valores(stdClass $std)
    {
        $valores = $this->nfs->createElement('Valores');
        $this->addChild($valores, 'ValorServicos', $std->valorServicos);
        if (isset($std->valorDeducoes)) {
            $this->addChild($valores, 'ValorDeducoes', $std->valorDeducoes);
        }
        if (isset($std->valorPis)) {
            $this->addChild($valores, 'ValorPis', $std->valorPis);
        }
        if (isset($std->valorCofins)) {
            $this->addChild($valores, 'ValorCofins', $std->valorCofins);
        }
        if (isset($std->valorInss)) {
            $this->addChild($valores, 'ValorInss', $std->valorInss);
        }
        if (isset($std->valorIr)) {
            $this->addChild($valores, 'ValorIr', $std->valorIr);
        }
        if (isset($std->valorCsll)) {
            $this->addChild($valores, 'ValorCsll', $std->valorCsll);
        }
        if (isset($std->valorIss)) {
            $this->addChild($valores, 'ValorIss', $std->valorIss);
        }
        if (isset($std->valorOutrasRetencoes)) {
            $this->addChild($valores, 'ValorOutrasRetencoes', $std->valorOutrasRetencoes);
        }
        if (isset($std->valorLiquidoNfse)) {
            $this->addChild($valores, 'ValorLiquidoNfse', $std->valorLiquidoNfse);
        }
        if (isset($std->valorIssRetido)) {
            $this->addChild($valores, 'ValorIssRetido', $std->valorIssRetido);
        }
        if (isset($std->outrosDescontos)) {
            $this->addChild($valores, 'OutrosDescontos', $std->outrosDescontos);
        }
        
        $this->valores = $valores;
        return $valores;
    }
    
    public function montar()
    {
        $servicos = $this->nfs->createElement('Servicos');
        foreach ($this->servicos as $servico) {
            $this->addChild($servicos, 'Servicos', $servico);
        }
        
        $idPrestador = $this->identificacaoPrestador->cloneNode(true);
        
        $rsPrestador = $this->dadosPrestador->getElementsByTagName('RazaoSocial');
        $this->dadosPrestador->insertBefore($idPrestador, $rsPrestador[0]);
        $this->dadosPrestador->appendChild($this->enderecoPrestador);
        $this->dadosPrestador->appendChild($this->contatoPrestador);
        
        
        $rsTomador = $this->dadosTomador->getElementsByTagName('RazaoSocial');
        $this->dadosTomador->insertBefore($this->identificacaoTomador, $rsTomador[0]);
        $this->dadosTomador->appendChild($this->enderecoTomador);
        $this->dadosTomador->appendChild($this->contatoTomador);
        
        $this->listaRps = $this->nfs->createElement('ListaRps');
        $this->rps->appendChild($this->identificacaoRps);
        $this->rps->appendChild($this->dadosPrestador);
        $this->rps->appendChild($this->dadosTomador);
        $this->rps->appendChild($servicos);
        $this->rps->appendChild($this->valores);
        
        if(isset($this->nfsObs)) {
            $this->addChild($this->rps, 'Observacao', $this->nfsObs);
        }
        $this->addChild($this->rps, 'Status', $this->nfsStatus);
        
        $this->listaRps->appendChild($this->rps);
        
        $this->loteRps->appendChild($this->identificacaoPrestador);
        $this->loteRps->appendChild($this->listaRps);
        
        $this->nfs->appendChild($this->loteRps);
        $this->xml = $this->nfs->saveXML();
        
        return $this->nfs;
    }
    
    public function getXML()
    {
        if (!$this->xml) {
            $this->montar();
        }
        
        return $this->xml;
    }
    
    public function getArray($xml = NULL)
    {
        if ($xml == NULL) {
            $xml = $this->xml;
        }
        
        $sxml = simplexml_load_string($xml);
        $array = json_decode(json_encode($sxml), true);
        return $array;
    }
    
    public function consultarUltimoRps($identificacaoPrestador)
    {
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
        } catch (SoapFault $exception) {
            return $exception->getMessage();
        };
    }
    
    public function consultarUltimoLote($identificacaoPrestador)
    {
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
        } catch (SoapFault $exception) {
            return $exception->getMessage();
        }
    }
    
    public function finalizarSessao($hash)
    {
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
        } catch (SoapFault $exception) {
            return $exception->getMessage();
        };
    }
    
    public function cancelarNFse($identificacaoPrestador, $senha, $nfse, $motivoCancelamento)
    {
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
        } catch (SoapFault $exception) {
            return $exception->getMessage();
        };
    }
    
    public function consultarNFse($cnpj, $numeroProtocolo)
    {
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
        } catch (SoapFault $exception) {
            return $exception->getMessage();
        };
    }
    
    public function autenticarContribuinte($cnpj, $senha)
    {
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
        } catch (SoapFault $exception) {
            return $exception->getMessage();
        };
    }
    
    public function enviarNFse($cnpj, $auth_token)
    {
        try {
            if (!$this->xml) {
                $this->montar();
            }
            
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
        } catch (SoapFault $exception) {
            return $exception->getMessage();
        };
    }
    
    public function transmitirNFse($cnpj, $senha)
    {
        $auth_token = $this->autenticarContribuinte($cnpj, $senha);
        return $this->enviarNFse($cnpj, $auth_token);
    }
}
