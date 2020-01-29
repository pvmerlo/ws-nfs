# Usando a biblioteca

## Funções

### Funções para informar os dados da NFs

**setUrlWsdl( $url )**

Informa qual a **URL** do Web Service que você quer consumir


**LoteRps( stdClass $std )**

Informações do Lote RPS

```
$std->numeroLote
$std->quantidadeRps
```

> A id é gerada internamente


**Rps( stdClass $std )**

Novo elemento RPS

```
$std->localPrestacao
$std->issRetido
$std->dataEmissao
```

> A id é gerada internamente


**ListaRps()**

Função interna para criar a lista de RPS. *Não usar*


**IdentificacaoRps( stdClass $std )**

Dados da Rps

```
$std->numero
$std->serie
$std->tipo
```


**IdentificacaoPrestador( stdClass $std )**

Identificação do Prestador do serviço

```
$std->cpfCnpj
$std->inscricaoMunicipal
```

> IndicacaoCpfCnpj é decidia pelo tamanno do CpfCnpj
> CpfCnpj Somente números


**DadosPrestador( stdClass $std )**

Dados do prestador do serviço

```
$std->razaoSocial
$std->nomeFantasia *Opcional*
$std->incentivadorCultural
$std->optanteSimplesNacional
$std->naturezaOperacao
$std->regimeEspecialTributacao
```


**EnderecoPrestador( stdClass $std )**

Endereço do prestador do serviço

```
$std->logradouroTipo *Opcional*
$std->logradouro *Opcional*
$std->logradouroNumero *Opcional*
$std->logradouroComplemento *Opcional*
$std->bairro *Opcional*
$std->codigoMunicipio
$std->municipio
$std->uf
$std->cep *Opcional*
```


**ContatoPrestador( stdClass $std )**

Contato do prestador do serviço

```
$std->telefone *Opcional*
$std->email
```


**IdentificacaoTomador( stdClass $std )**

Identificação do tomador do serviço

```
$std->cpfCnpj
$std->inscricaoMunicipal
```

> IndicacaoCpfCnpj é decidia pelo tamanno do CpfCnpj
> CpfCnpj Somente números


**DadosTomador( stdClass $std )**

Dados do tomador do serviço

```
$std->razaoSocial
$std->nomeFantasia *Opcional*
```


**EnderecoTomador( stdClass $std )**

Endereço do tomador do serviço

```
$std->logradouroTipo *Opcional*
$std->logradouro *Opcional*
$std->logradouroNumero *Opcional*
$std->logradouroComplemento *Opcional*
$std->bairro *Opcional*
$std->codigoMunicipio
$std->municipio
$std->uf
$std->cep *Opcional*
```


**ContatoTomador( stdClass $std )**

Contato do tomador do serviço

```
$std->telefone *Opcional*
$std->email
```


**Servico( stdClass $std )**

Dados do Serviço

```
$std->codigoCnae *Opcional*
$std->codigoServico116
$std->codigoServicoMunicipal
$std->quantidade
$std->unidade
$std->descricao
$std->aliquota
$std->valorServico
$std->valorIssqn
$std->valorDesconto *Opcional*
$std->numeroAlvara *Opcional*
```


**Valores( stdClass $std )**

Valores Totais da NFs

```
$std->valorServicos
$std->valorDeducoes *Opcional*
$std->valorPis *Opcional*
$std->valorCofins *Opcional*
$std->valorInss *Opcional*
$std->valorIr *Opcional*
$std->valorCsll *Opcional*
$std->valorIss *Opcional*
$std->valorOutrasRetencoes *Opcional*
$std->valorLiquidoNfse *Opcional*
$std->valorIssRetido *Opcional*
$std->outrosDescontos *Opcional*
```


### Funções para Montar, Enviar e Consultar a NFs

**montar()**

Monta o XML com os dados informados

*return DOMDocument*


**getXML()**

Retorna XML montado

*return String*


**consultarUltimoRps( $identificacaoPrestador )**

Consulta ultimo número Rps enviado

*return Soap call*
*return Exception String*


**consultarUltimoLote( $identificacaoPrestador )**

Consulta ultimo número Lote enviado

*return Soap call*
*return Exception String*


**finalizarSessao( $hash )**

Fecha sessão iniciada com o WS

*return Soap call*
*return Exception String*


**cancelarNFse( $identificacaoPrestador, $senha, $nfse, $motivoCancelamento )**

Cancela NFs

*return Soap call*
*return Exception String*


**consultarNFse( $cnpj, $numeroProtocolo )**

Consulta Status da NFs

*return Soap call*
*return Exception String*


**autenticarContribuinte( $cnpj, $senha )**

Altenticação para consumir Web Service

*return Soap call*
*return Exception String*


**enviarNFse( $cnpj, $auth_token )**

Faz o envio do XML para o Web Service

*return Soap call*
*return Exception String*


**transmitirNFse($cnpj, $senha)**

Autentica o Contribuinde ( autenticarContribuinte ) e envia NFs ( enviarNFse )

*return Soap call - Protocolo de Tranmissão*
*return Exception String*


### Privadas

**makeID()**

Cria as IDs randomicas para NFs segundo manual


**addChild( DOMElement &$parent, $name, $content, $required=TRUE )**

Cria os elementdo do XML
